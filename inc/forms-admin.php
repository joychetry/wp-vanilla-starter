<?php
/**
 * Forms admin: Submissions menu per form, Form settings, list columns, Quick
 * view, webhook resend, privacy export and erase.
 *
 * @package VanillaStarter
 */

namespace VanillaStarter;

add_action( 'admin_menu', __NAMESPACE__ . '\\forms_menu' );
add_filter( 'submenu_file', __NAMESPACE__ . '\\forms_submenu_file' );
add_action( 'admin_init', __NAMESPACE__ . '\\register_form_settings' );
add_filter( 'manage_vs_submission_posts_columns', __NAMESPACE__ . '\\submission_columns' );
add_action( 'manage_vs_submission_posts_custom_column', __NAMESPACE__ . '\\submission_column', 10, 2 );
add_filter( 'post_row_actions', __NAMESPACE__ . '\\submission_row_actions', 10, 2 );
add_action( 'add_meta_boxes_vs_submission', __NAMESPACE__ . '\\submission_meta_box' );
add_action( 'admin_post_vs_form_resend', __NAMESPACE__ . '\\resend_webhook' );
add_filter( 'wp_privacy_personal_data_exporters', __NAMESPACE__ . '\\register_privacy_exporter' );
add_filter( 'wp_privacy_personal_data_erasers', __NAMESPACE__ . '\\register_privacy_eraser' );

/**
 * Adds one Submissions entry per form, plus Form settings.
 */
function forms_menu(): void {
	$parent = 'edit.php?post_type=vs_submission';
	foreach ( forms() as $key => $form ) {
		add_submenu_page( $parent, $form['label'], $form['label'], 'edit_posts', $parent . '&vs_form=' . $key );
	}
	add_submenu_page( $parent, __( 'Form settings', 'vanilla-starter' ), __( 'Form settings', 'vanilla-starter' ), 'manage_options', 'vs-form-settings', __NAMESPACE__ . '\\render_form_settings' );
}

/**
 * Highlights the form's own submenu entry on its filtered list.
 *
 * @param string|null $file Current submenu file.
 * @return string|null
 */
function forms_submenu_file( ?string $file ): ?string {
	$key = sanitize_key( $_GET['vs_form'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
	return isset( forms()[ $key ] ) ? 'edit.php?post_type=vs_submission&vs_form=' . $key : $file;
}

/**
 * Registers the vs_forms option: [ form key => [ notify, endpoint, data ] ].
 */
function register_form_settings(): void {
	register_setting(
		'vs_forms',
		'vs_forms',
		array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_form_settings',
		)
	);
	// Same group, so one Save on Form settings stores both.
	register_setting(
		'vs_forms',
		'vs_turnstile',
		array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_turnstile',
		)
	);
}

/**
 * Cleans the Turnstile keys. An empty secret keeps the saved one, so the
 * secret is never printed back into the page.
 *
 * @param mixed $input Posted keys.
 * @return array{site: string, secret: string}
 */
function sanitize_turnstile( mixed $input ): array {
	$input  = (array) $input;
	$site   = sanitize_text_field( (string) ( $input['site'] ?? '' ) );
	$secret = sanitize_text_field( (string) ( $input['secret'] ?? '' ) );
	return array(
		'site'   => $site,
		// Clearing the site key clears the secret too, which turns Turnstile off.
		'secret' => '' === $site ? '' : ( '' === $secret ? turnstile_keys()['secret'] : $secret ),
	);
}

/**
 * Keeps the other tabs when one tab saves, and cleans each value.
 *
 * @param mixed $input Posted settings.
 * @return array
 */
function sanitize_form_settings( mixed $input ): array {
	$all = (array) get_option( 'vs_forms', array() );
	foreach ( (array) $input as $key => $row ) {
		if ( ! isset( forms()[ $key ] ) || ! is_array( $row ) ) {
			continue;
		}
		$emails = array_filter( array_map( 'sanitize_email', explode( ',', (string) ( $row['notify'] ?? '' ) ) ) );
		$data   = trim( (string) ( $row['data'] ?? '' ) );
		if ( '' !== $data && null === json_decode( webhook_body( $data, array() ) ) ) {
			add_settings_error( 'vs_forms', 'vs_forms_data', __( 'Data is not valid JSON, so it was not saved.', 'vanilla-starter' ) );
			$data = $all[ $key ]['data'] ?? '';
		}
		$all[ $key ] = array(
			'notify'    => implode( ', ', $emails ),
			'endpoint'  => esc_url_raw( trim( (string) ( $row['endpoint'] ?? '' ) ), array( 'https', 'http' ) ),
			'data'      => $data,
			'turnstile' => ! empty( $row['turnstile'] ),
		);
	}
	return $all;
}

/**
 * Renders Submissions > Form settings, one tab per form.
 */
function render_form_settings(): void {
	$forms = forms();
	$tab   = sanitize_key( $_GET['tab'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
	$tab   = isset( $forms[ $tab ] ) ? $tab : (string) array_key_first( $forms );
	$s     = form_settings( $tab );
	$name  = 'vs_forms[' . $tab . ']';
	$keys  = turnstile_keys();
	$ready = '' !== $keys['site'] && '' !== $keys['secret'];
	?>
	<div class="wrap vs-form-settings">
		<style>.vs-form-settings :is(input:not([type=checkbox], [type=radio], [type=hidden]), textarea) { min-height: 40px; }</style>
		<h1><?php esc_html_e( 'Form settings', 'vanilla-starter' ); ?></h1>
		<?php settings_errors(); ?>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $forms as $key => $form ) : ?>
				<a class="nav-tab<?php echo $key === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'edit.php?post_type=vs_submission&page=vs-form-settings&tab=' . $key ) ); ?>"><?php echo esc_html( $form['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<form method="post" action="options.php">
			<?php settings_fields( 'vs_forms' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="vs-notify"><?php esc_html_e( 'Notify emails', 'vanilla-starter' ); ?></label></th>
					<td><input class="regular-text" id="vs-notify" name="<?php echo esc_attr( $name ); ?>[notify]" value="<?php echo esc_attr( $s['notify'] ); ?>">
					<p class="description"><?php esc_html_e( 'Comma separated. Empty sends to the site admin email.', 'vanilla-starter' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="vs-endpoint"><?php esc_html_e( 'Endpoint URL', 'vanilla-starter' ); ?></label></th>
					<td><input class="large-text code" type="url" id="vs-endpoint" name="<?php echo esc_attr( $name ); ?>[endpoint]" value="<?php echo esc_attr( $s['endpoint'] ); ?>">
					<p class="description"><?php esc_html_e( 'Each submission is posted here as JSON, for a CRM or mailing list. Put any token in the URL. Empty sends nothing.', 'vanilla-starter' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="vs-data"><?php esc_html_e( 'Data', 'vanilla-starter' ); ?></label></th>
					<td><textarea class="large-text code" rows="5" id="vs-data" name="<?php echo esc_attr( $name ); ?>[data]"><?php echo esc_textarea( $s['data'] ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'JSON shape with {{field}} placeholders. Empty sends every field. Fields:', 'vanilla-starter' ); ?>
						<code><?php echo esc_html( implode( ', ', array_keys( $forms[ $tab ]['fields'] ) ) ); ?></code>
					</p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Turnstile', 'vanilla-starter' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[turnstile]" value="1" <?php checked( $s['turnstile'] ); ?>>
						<?php esc_html_e( 'Ask visitors to pass a Cloudflare Turnstile check on this form', 'vanilla-starter' ); ?></label>
					<?php if ( ! $ready ) : ?>
						<p class="description"><?php esc_html_e( 'Off until both Turnstile keys below are saved.', 'vanilla-starter' ); ?></p>
					<?php endif; ?></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Cloudflare Turnstile', 'vanilla-starter' ); ?></h2>
			<p><?php esc_html_e( 'Shared by all forms. Get both keys from Cloudflare dashboard > Turnstile, then tick Turnstile on each form that should use it.', 'vanilla-starter' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="vs-ts-site"><?php esc_html_e( 'Site key', 'vanilla-starter' ); ?></label></th>
					<td><input class="regular-text code" id="vs-ts-site" name="vs_turnstile[site]" value="<?php echo esc_attr( $keys['site'] ); ?>" autocomplete="off" spellcheck="false">
					<p class="description"><?php esc_html_e( 'Empty turns Turnstile off on every form.', 'vanilla-starter' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="vs-ts-secret"><?php esc_html_e( 'Secret key', 'vanilla-starter' ); ?></label></th>
					<td><input class="regular-text code" type="password" id="vs-ts-secret" name="vs_turnstile[secret]" value="" autocomplete="new-password" spellcheck="false"
						placeholder="<?php echo '' === $keys['secret'] ? '' : esc_attr__( 'Saved. Leave empty to keep it.', 'vanilla-starter' ); ?>"></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * List columns: email, page and webhook result after the title.
 *
 * @param array $columns Columns.
 * @return array
 */
function submission_columns( array $columns ): array {
	$date = $columns['date'];
	unset( $columns['date'] );
	return $columns + array(
		'vs_email' => __( 'Email', 'vanilla-starter' ),
		'vs_page'  => __( 'Page', 'vanilla-starter' ),
		'vs_hook'  => __( 'Webhook', 'vanilla-starter' ),
		'date'     => $date,
	);
}

/**
 * Prints a list cell. The email cell also holds the Quick view dialog.
 *
 * @param string $column Column key.
 * @param int    $id     Submission ID.
 */
function submission_column( string $column, int $id ): void {
	if ( 'vs_email' === $column ) {
		$email = (string) get_post_meta( $id, '_vs_email', true );
		printf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $email ), esc_html( $email ) );
		printf(
			// The list table is a form, so Close uses an invoker command, not a nested form.
			'<dialog id="vs-qv-%1$d" aria-label="%2$s" style="max-width:40rem">%3$s<button type="button" class="button" commandfor="vs-qv-%1$d" command="close">%4$s</button></dialog>',
			(int) $id,
			esc_attr( get_the_title( $id ) ),
			wp_kses_post( fields_table( submission_form( $id ), submission_fields( $id ) ) ),
			esc_html__( 'Close', 'vanilla-starter' )
		);
	} elseif ( 'vs_page' === $column ) {
		$page = (string) get_post_meta( $id, '_vs_page', true );
		echo '' === $page ? '' : '<a href="' . esc_url( $page ) . '">' . esc_html( (string) wp_parse_url( $page, PHP_URL_PATH ) ) . '</a>';
	} elseif ( 'vs_hook' === $column ) {
		echo esc_html( (string) get_post_meta( $id, '_vs_hook', true ) );
	}
}

/**
 * Row actions: View, Quick view, Resend (when a webhook ran), Trash.
 *
 * @param array    $actions Row actions.
 * @param \WP_Post $post    Post.
 * @return array
 */
function submission_row_actions( array $actions, \WP_Post $post ): array {
	if ( 'vs_submission' !== $post->post_type ) {
		return $actions;
	}
	$keep = array_intersect_key( $actions, array_flip( array( 'trash', 'untrash', 'delete' ) ) );
	$new  = array(
		'view'  => '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html__( 'View', 'vanilla-starter' ) . '</a>',
		// Invoker commands open the dialog natively, no admin JavaScript.
		'quick' => '<button type="button" class="button-link" commandfor="vs-qv-' . (int) $post->ID . '" command="show-modal">' . esc_html__( 'Quick view', 'vanilla-starter' ) . '</button>',
	);
	if ( '' !== (string) get_post_meta( $post->ID, '_vs_hook', true ) ) {
		$url           = wp_nonce_url( admin_url( 'admin-post.php?action=vs_form_resend&post=' . $post->ID ), 'vs_form_resend_' . $post->ID );
		$new['resend'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Resend', 'vanilla-starter' ) . '</a>';
	}
	return $new + $keep;
}

/**
 * Shows the submitted fields on the edit screen.
 */
function submission_meta_box(): void {
	add_meta_box(
		'vs-fields',
		__( 'Fields', 'vanilla-starter' ),
		function ( \WP_Post $post ): void {
			echo wp_kses_post( fields_table( submission_form( $post->ID ), submission_fields( $post->ID ) ) );
			$page = (string) get_post_meta( $post->ID, '_vs_page', true );
			$hook = (string) get_post_meta( $post->ID, '_vs_hook', true );
			echo '<p>' . esc_html__( 'Page:', 'vanilla-starter' ) . ' <a href="' . esc_url( $page ) . '">' . esc_html( $page ) . '</a></p>';
			if ( '' !== $hook ) {
				echo '<p>' . esc_html__( 'Webhook:', 'vanilla-starter' ) . ' ' . esc_html( $hook ) . '</p>';
			}
		},
		'vs_submission',
		'normal',
		'high'
	);
}

/**
 * Resends one submission's webhook, then returns to the list.
 */
function resend_webhook(): void {
	$id = absint( $_GET['post'] ?? 0 );
	check_admin_referer( 'vs_form_resend_' . $id );
	if ( ! current_user_can( 'edit_post', $id ) || 'vs_submission' !== get_post_type( $id ) ) {
		wp_die( esc_html__( 'You cannot resend this submission.', 'vanilla-starter' ), 403 );
	}
	send_webhook( $id );
	wp_safe_redirect( wp_get_referer() ?: admin_url( 'edit.php?post_type=vs_submission' ) );
	exit;
}

/**
 * Submission IDs for an email address.
 *
 * @param string $email Email address.
 * @return int[]
 */
function submissions_by_email( string $email ): array {
	return get_posts(
		array(
			'post_type'      => 'vs_submission',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_key'       => '_vs_email', // phpcs:ignore WordPress.DB.SlowDBQuery -- privacy requests only.
			'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
}

/**
 * Adds form submissions to Tools > Export Personal Data.
 *
 * @param array $exporters Exporters.
 * @return array
 */
function register_privacy_exporter( array $exporters ): array {
	$exporters['vanilla-starter-forms'] = array(
		'exporter_friendly_name' => __( 'Form submissions', 'vanilla-starter' ),
		'callback'               => function ( string $email ): array {
			$data = array();
			foreach ( submissions_by_email( $email ) as $id ) {
				$fields = forms()[ submission_form( $id ) ]['fields'] ?? array();
				$items  = array(
					array(
						'name'  => __( 'Date', 'vanilla-starter' ),
						'value' => get_the_date( 'c', $id ),
					),
				);
				foreach ( submission_fields( $id ) as $name => $value ) {
					$items[] = array(
						'name'  => $fields[ $name ][0] ?? $name,
						'value' => $value,
					);
				}
				$data[] = array(
					'group_id'    => 'vs-submissions',
					'group_label' => __( 'Form submissions', 'vanilla-starter' ),
					'item_id'     => 'vs-submission-' . $id,
					'data'        => $items,
				);
			}
			return array(
				'data' => $data,
				'done' => true,
			);
		},
	);
	return $exporters;
}

/**
 * Adds form submissions to Tools > Erase Personal Data.
 *
 * @param array $erasers Erasers.
 * @return array
 */
function register_privacy_eraser( array $erasers ): array {
	$erasers['vanilla-starter-forms'] = array(
		'eraser_friendly_name' => __( 'Form submissions', 'vanilla-starter' ),
		'callback'             => function ( string $email ): array {
			$removed = false;
			foreach ( submissions_by_email( $email ) as $id ) {
				$removed = (bool) wp_delete_post( $id, true ) || $removed;
			}
			return array(
				'items_removed'  => $removed,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		},
	);
	return $erasers;
}
