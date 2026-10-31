<?php
/**
 * Title: Newsletter form
 * Slug: vanilla-starter/newsletter-form
 * Categories: vanilla-starter
 * Keywords: form, newsletter, subscribe, signup
 * Description: Signup form. One row per email under Submissions > Newsletter. See docs/FORMS.md.
 *
 * @package VanillaStarter
 */

?>
<!-- wp:html -->
<form class="vs-form" method="post" action="<?php echo esc_url( \VanillaStarter\form_action() ); ?>" data-vs-form aria-label="<?php esc_attr_e( 'Newsletter signup', 'vanilla-starter' ); ?>">
	<?php \VanillaStarter\form_fields( 'newsletter' ); ?>

	<label><?php esc_html_e( 'First name (optional)', 'vanilla-starter' ); ?>
		<input type="text" name="first_name" autocomplete="given-name"></label>

	<label><?php esc_html_e( 'Email', 'vanilla-starter' ); ?>
		<input type="email" name="email" autocomplete="email" spellcheck="false" required></label>

	<?php \VanillaStarter\form_error(); ?>
	<button class="wp-element-button" type="submit" data-busy="<?php esc_attr_e( 'Subscribing...', 'vanilla-starter' ); ?>"><?php esc_html_e( 'Subscribe', 'vanilla-starter' ); ?></button>
	<p role="status" aria-live="polite" data-sent="<?php esc_attr_e( 'You are subscribed. A confirmation is on its way to your inbox.', 'vanilla-starter' ); ?>"></p>
</form>
<!-- /wp:html -->
