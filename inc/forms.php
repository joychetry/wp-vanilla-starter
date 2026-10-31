<?php
/**
 * Forms: registry, field helpers, submission handler, webhook and emails.
 *
 * Every form posts to admin-post.php?action=vs_form. No form plugin. Add a form
 * with one entry in forms() and one pattern. See docs/FORMS.md.
 *
 * @package VanillaStarter
 */

namespace VanillaStarter;

add_action( 'init', __NAMESPACE__ . '\\register_submissions' );
add_action( 'admin_post_nopriv_vs_form', __NAMESPACE__ . '\\handle_form' );
add_action( 'admin_post_vs_form', __NAMESPACE__ . '\\handle_form' );
add_filter( 'render_block_core/html', __NAMESPACE__ . '\\form_block_render' );

/**
 * The form registry. The key is the form's name everywhere: patterns, the
 * Submissions menu, Form settings and the vs_form term.
 *
 * Field: name => [ label, type, message when missing (empty = optional), choices for 'choice' ].
 * Types: text, email, url, textarea, choice.
 *
 * @return array<string, array>
 */
function forms(): array {
	static $forms = null;
	return $forms ??= array(
		'contact'    => array(
			'label'  => __( 'Contact', 'vanilla-starter' ),
			'sent'   => __( 'Thanks, your message is in. A confirmation is on its way to your inbox.', 'vanilla-starter' ),
			'fields' => array(
				'name'    => array( __( 'Name', 'vanilla-starter' ), 'text', __( 'Enter your name.', 'vanilla-starter' ) ),
				'email'   => array( __( 'Email', 'vanilla-starter' ), 'email', __( 'Enter your email address.', 'vanilla-starter' ) ),
				'topic'   => array( __( 'Topic', 'vanilla-starter' ), 'choice', '', array( 'Question', 'Project', 'Other' ) ),
				'site'    => array( __( 'Site URL', 'vanilla-starter' ), 'url', '' ),
				'message' => array( __( 'Message', 'vanilla-starter' ), 'textarea', __( 'Write a message.', 'vanilla-starter' ) ),
			),
		),
		'newsletter' => array(
			'label'  => __( 'Newsletter', 'vanilla-starter' ),
			'sent'   => __( 'You are subscribed. A confirmation is on its way to your inbox.', 'vanilla-starter' ),
			'once'   => true,
			'fields' => array(
				'first_name' => array( __( 'First name', 'vanilla-starter' ), 'text', '' ),
				'email'      => array( __( 'Email', 'vanilla-starter' ), 'email', __( 'Enter your email address to subscribe.', 'vanilla-starter' ) ),
			),
		),
	);
}

/**
 * Per-form settings from Submissions > Form settings.
 *
 * @param string $key Form key.
 * @return array{notify: string, endpoint: string, data: string, turnstile: bool}
 */
function form_settings( string $key ): array {
	$all = (array) get_option( 'vs_forms', array() );
	return wp_parse_args(
		$all[ $key ] ?? array(),
		array(
			'notify'    => '',
			'endpoint'  => '',
			'data'      => '',
			'turnstile' => false,
		)
	);
}

/**
 * Cloudflare Turnstile keys, shared by every form.
 *
 * @return array{site: string, secret: string}
 */
function turnstile_keys(): array {
	$keys = (array) get_option( 'vs_turnstile', array() );
	return array(
		'site'   => (string) ( $keys['site'] ?? '' ),
		'secret' => (string) ( $keys['secret'] ?? '' ),
	);
}

/**
 * True when a form has Turnstile ticked and both keys are set.
 *
 * @param string $key Form key.
 */
function turnstile_on( string $key ): bool {
	$keys = turnstile_keys();
	return form_settings( $key )['turnstile'] && '' !== $keys['site'] && '' !== $keys['secret'];
}

/**
 * Asks Cloudflare whether a Turnstile token is valid. Fails closed.
 *
 * @param string $token Posted cf-turnstile-response.
 * @param string $ip    Visitor IP.
 */
function turnstile_verify( string $token, string $ip ): bool {
	if ( '' === $token ) {
		return false;
	}
	$response = wp_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		array(
			'timeout' => 10,
			'body'    => array(
				'secret'   => turnstile_keys()['secret'],
				'response' => $token,
				'remoteip' => $ip,
			),
		)
	);
	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	return ! is_wp_error( $response ) && ! empty( $body['success'] );
}

/**
 * Adds the Turnstile widget, before the error slot, to each form that has it on,
 * and loads Cloudflare's script only then.
 *
 * @param string $html Rendered HTML holding one or more forms.
 * @return string
 */
function add_turnstile( string $html ): string {
	return (string) preg_replace_callback(
		'#<form\b[^>]*data-vs-form.*?</form>#s',
		function ( array $m ): string {
			if ( ! preg_match( '/name="vs_form" value="([a-z0-9_-]+)"/', $m[0], $key ) || ! turnstile_on( $key[1] ) ) {
				return $m[0];
			}
			// Cloudflare requires loading api.js from its own host. Logged in docs/EXCEPTIONS.md.
			wp_enqueue_script( 'vanilla-starter-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, array( 'strategy' => 'async', 'in_footer' => true ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- versioned by Cloudflare.
			$widget = '<div class="cf-turnstile" data-sitekey="' . esc_attr( turnstile_keys()['site'] ) . '"></div>';
			$at     = strpos( $m[0], '<p class="vs-form-err"' );
			return false === $at ? str_replace( '</form>', $widget . '</form>', $m[0] ) : substr_replace( $m[0], $widget, $at, 0 );
		},
		$html
	);
}

/**
 * Registers the private Submission post type and the form taxonomy.
 */
function register_submissions(): void {
	register_post_type(
		'vs_submission',
		array(
			'labels'       => array(
				'name'          => __( 'Submissions', 'vanilla-starter' ),
				'singular_name' => __( 'Submission', 'vanilla-starter' ),
				'all_items'     => __( 'All submissions', 'vanilla-starter' ),
				'edit_item'     => __( 'Submission', 'vanilla-starter' ),
				'not_found'     => __( 'No submissions yet.', 'vanilla-starter' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-email-alt',
			'supports'     => array( 'title' ),
			'map_meta_cap' => true,
			'capabilities' => array( 'create_posts' => 'do_not_allow' ),
		)
	);
	register_taxonomy(
		'vs_form',
		'vs_submission',
		array(
			'public'            => false,
			'show_ui'           => false,
			'show_admin_column' => true,
			'query_var'         => 'vs_form',
			'label'             => __( 'Form', 'vanilla-starter' ),
		)
	);
}

/**
 * Where every form posts.
 */
function form_action(): string {
	return admin_url( 'admin-post.php?action=vs_form' );
}

/**
 * Prints the hidden inputs a form needs: form key, render time and honeypot.
 * No nonce: pages are cached and a stale nonce would fail real visitors.
 *
 * @param string $key Form key from forms().
 */
function form_fields( string $key ): void {
	printf(
		'<input type="hidden" name="vs_form" value="%s"><input type="hidden" name="vs_ts" value="%d"><label class="vs-hp" aria-hidden="true">%s <input type="text" name="vs_hp" tabindex="-1" autocomplete="off"></label>',
		esc_attr( $key ),
		time(),
		esc_html__( 'Leave this empty', 'vanilla-starter' )
	);
}

/**
 * Prints the error slot forms.js fills.
 */
function form_error(): void {
	echo '<p class="vs-form-err" role="alert" data-error></p>';
}

/**
 * On any HTML block that holds a form: refreshes the render time (a pattern
 * copied into a page freezes it), adds Turnstile where it is on, and loads the
 * form script and styles, so pages without a form load none of it.
 *
 * @param string $html Rendered block.
 * @return string
 */
function form_block_render( string $html ): string {
	if ( ! str_contains( $html, 'data-vs-form' ) ) {
		return $html;
	}
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_script_module( 'vanilla-starter-forms', get_theme_file_uri( 'assets/js/forms.js' ), array(), $version );
	wp_enqueue_style( 'vanilla-starter-forms', get_theme_file_uri( 'assets/css/forms.css' ), array(), $version );
	wp_style_add_data( 'vanilla-starter-forms', 'path', get_theme_file_path( 'assets/css/forms.css' ) );
	return add_turnstile( (string) preg_replace( '/name="vs_ts" value="\d+"/', 'name="vs_ts" value="' . time() . '"', $html ) );
}

/**
 * Validates one value against its field definition.
 *
 * @param mixed $raw   Posted value.
 * @param array $field [ label, type, missing message, choices ].
 * @return array{0: string, 1: string} [ clean value, error message or '' ].
 */
function validate_field( mixed $raw, array $field ): array {
	[ , $type, $missing ] = $field;
	$value                = is_string( $raw ) ? trim( wp_unslash( $raw ) ) : '';

	$value = match ( $type ) {
		'textarea' => mb_substr( sanitize_textarea_field( $value ), 0, 5000 ),
		'choice'   => in_array( $value, $field[3] ?? array(), true ) ? $value : '',
		default    => mb_substr( sanitize_text_field( $value ), 0, 200 ),
	};

	if ( '' === $value ) {
		return array( '', (string) $missing );
	}
	if ( 'email' === $type && ! is_email( $value ) ) {
		return array( '', __( 'Enter an email address like name@company.com.', 'vanilla-starter' ) );
	}
	if ( 'url' === $type && ! preg_match( '#^https?://[^\s/]+\.[^\s]+$#i', $value ) ) {
		return array( '', __( 'Enter a web address starting with https://', 'vanilla-starter' ) );
	}
	return array( $value, '' );
}

/**
 * Handles every form post: spam checks, validation, save, reply, then webhook and emails.
 */
function handle_form(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- public cached forms, see form_fields().
	$key  = sanitize_key( $_POST['vs_form'] ?? '' );
	$form = forms()[ $key ] ?? null;
	if ( ! $form ) {
		form_reply( false, __( 'This form is not available.', 'vanilla-starter' ), 400 );
	}

	// Bots that fill the honeypot get a fake success.
	if ( ! empty( $_POST['vs_hp'] ) ) {
		form_reply( true, $form['sent'] );
	}
	if ( time() - absint( $_POST['vs_ts'] ?? 0 ) < 3 ) {
		form_reply( false, __( 'That was quick. Please wait a moment and send again.', 'vanilla-starter' ), 429 );
	}

	$ip    = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$limit = 'vs_rl_' . md5( $key . '|' . $ip );
	$count = (int) get_transient( $limit );
	if ( $count >= 5 ) {
		form_reply( false, __( 'Too many sends. Please try again in a few minutes.', 'vanilla-starter' ), 429 );
	}

	$values = array();
	foreach ( $form['fields'] as $name => $field ) {
		[ $value, $error ] = validate_field( $_POST[ $name ] ?? '', $field );
		if ( '' !== $error ) {
			form_reply( false, $error, 422 );
		}
		$values[ $name ] = $value;
	}

	// Last, so only otherwise valid sends cost a remote request.
	if ( turnstile_on( $key ) && ! turnstile_verify( sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ?? '' ) ), $ip ) ) {
		form_reply( false, __( 'Please complete the security check and send again.', 'vanilla-starter' ), 403 );
	}
	// phpcs:enable

	set_transient( $limit, $count + 1, 10 * MINUTE_IN_SECONDS );
	$email = $values['email'] ?? '';
	$name  = $values['name'] ?? $values['first_name'] ?? '';

	// Signup lists keep one row per email; a repeat just says yes.
	if ( ! empty( $form['once'] ) && submission_exists( $key, $email ) ) {
		form_reply( true, $form['sent'] );
	}

	$id = wp_insert_post(
		array(
			'post_type'   => 'vs_submission',
			'post_status' => 'private',
			'post_title'  => '' === $name ? $email : $name . ' (' . $email . ')',
			'meta_input'  => array(
				'_vs_fields' => $values,
				'_vs_email'  => $email,
				'_vs_page'   => esc_url_raw( (string) wp_get_referer() ),
			),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		form_reply( false, __( 'Something went wrong. Please try again.', 'vanilla-starter' ), 500 );
	}
	wp_set_object_terms( $id, $key, 'vs_form' );

	form_reply( true, $form['sent'], 200, false );

	// The visitor already has the reply; slow work runs after it.
	send_webhook( $id );
	send_emails( $id );
	exit;
}

/**
 * True when this form already has a submission from this email.
 *
 * @param string $key   Form key.
 * @param string $email Email address.
 */
function submission_exists( string $key, string $email ): bool {
	return (bool) get_posts(
		array(
			'post_type'      => 'vs_submission',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_key'       => '_vs_email', // phpcs:ignore WordPress.DB.SlowDBQuery -- only on a signup send.
			'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery
			'tax_query'      => array( array( 'taxonomy' => 'vs_form', 'field' => 'slug', 'terms' => $key ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
}

/**
 * Replies with JSON for forms.js, or a plain page without JavaScript.
 *
 * @param bool   $ok      Success.
 * @param string $message Text to show.
 * @param int    $status  HTTP status.
 * @param bool   $stop    Exit after replying. False flushes the reply and keeps running.
 */
function form_reply( bool $ok, string $message, int $status = 200, bool $stop = true ): void {
	$json = str_contains( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ?? '' ) ), 'application/json' );

	if ( ! $json && $stop ) {
		wp_die( esc_html( $message ), esc_html( get_bloginfo( 'name' ) ), array( 'response' => $status, 'back_link' => true ) );
	}

	$body = $json
		? (string) wp_json_encode( array( 'ok' => $ok, 'message' => $message ) )
		: '<!doctype html><meta charset="utf-8"><title>' . esc_html( get_bloginfo( 'name' ) ) . '</title><p>' . esc_html( $message ) . '</p><p><a href="' . esc_url( (string) wp_get_referer() ) . '">' . esc_html__( 'Back', 'vanilla-starter' ) . '</a></p>';

	status_header( $status );
	nocache_headers();
	header( 'Content-Type: ' . ( $json ? 'application/json' : 'text/html' ) . '; charset=utf-8' );
	// Length and close let the browser finish while webhook and emails run.
	header( 'Content-Length: ' . strlen( $body ) );
	header( 'Connection: close' );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.

	if ( $stop ) {
		exit;
	}
	ignore_user_abort( true );
	while ( ob_get_level() ) {
		ob_end_flush();
	}
	flush();
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	}
}

/**
 * Builds the webhook JSON body: the Data template with {{field}} filled in, or every field.
 *
 * @param string $template Data template from Form settings.
 * @param array  $values   Field values.
 */
function webhook_body( string $template, array $values ): string {
	if ( '' === trim( $template ) ) {
		return (string) wp_json_encode( $values );
	}
	// Each value is JSON-escaped without its quotes, so it is safe inside "{{field}}".
	return (string) preg_replace_callback(
		'/\{\{\s*([a-z0-9_]+)\s*\}\}/i',
		fn( $m ) => substr( (string) wp_json_encode( (string) ( $values[ $m[1] ] ?? '' ) ), 1, -1 ),
		$template
	);
}

/**
 * Posts a submission to its form's endpoint and stores the result in _vs_hook.
 *
 * @param int $id Submission ID.
 */
function send_webhook( int $id ): void {
	$key      = submission_form( $id );
	$settings = form_settings( $key );
	if ( '' === $settings['endpoint'] ) {
		return;
	}
	$response = wp_safe_remote_post(
		$settings['endpoint'],
		array(
			'timeout' => 10,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => webhook_body( $settings['data'], submission_fields( $id ) ),
		)
	);
	$result   = is_wp_error( $response ) ? $response->get_error_message() : (string) wp_remote_retrieve_response_code( $response );
	update_post_meta( $id, '_vs_hook', $result );
}

/**
 * Sends the team notification and the visitor confirmation as HTML emails.
 *
 * @param int $id Submission ID.
 */
function send_emails( int $id ): void {
	$key    = submission_form( $id );
	$form   = forms()[ $key ] ?? null;
	$values = submission_fields( $id );
	$email  = $values['email'] ?? '';
	if ( ! $form ) {
		return;
	}
	$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$table   = fields_table( $key, $values );
	$notify  = form_settings( $key )['notify'];
	$to      = '' === $notify ? get_option( 'admin_email' ) : array_map( 'trim', explode( ',', $notify ) );

	wp_mail(
		$to,
		/* translators: 1: form label, 2: site name. */
		sprintf( __( 'New %1$s submission on %2$s', 'vanilla-starter' ), $form['label'], $site ),
		$table . '<p><a href="' . esc_url( admin_url( 'post.php?action=edit&post=' . $id ) ) . '">' . esc_html__( 'View in admin', 'vanilla-starter' ) . '</a></p>',
		array_merge( $headers, is_email( $email ) ? array( 'Reply-To: ' . $email ) : array() )
	);

	if ( is_email( $email ) ) {
		wp_mail(
			$email,
			/* translators: %s: site name. */
			sprintf( __( 'We got it: %s', 'vanilla-starter' ), $site ),
			'<p>' . esc_html( $form['sent'] ) . '</p>' . $table,
			$headers
		);
	}
}

/**
 * Field values as an HTML table, labelled from the form definition.
 *
 * @param string $key    Form key.
 * @param array  $values Field values.
 */
function fields_table( string $key, array $values ): string {
	$fields = forms()[ $key ]['fields'] ?? array();
	$rows   = '';
	foreach ( $values as $name => $value ) {
		$rows .= '<tr><th align="left" valign="top">' . esc_html( $fields[ $name ][0] ?? $name ) . '</th><td>' . nl2br( esc_html( (string) $value ) ) . '</td></tr>';
	}
	return '<table cellpadding="6">' . $rows . '</table>';
}

/**
 * The form key a submission belongs to.
 *
 * @param int $id Submission ID.
 */
function submission_form( int $id ): string {
	$terms = get_the_terms( $id, 'vs_form' );
	return is_array( $terms ) ? $terms[0]->slug : '';
}

/**
 * A submission's field values.
 *
 * @param int $id Submission ID.
 * @return array<string, string>
 */
function submission_fields( int $id ): array {
	return (array) get_post_meta( $id, '_vs_fields', true );
}
