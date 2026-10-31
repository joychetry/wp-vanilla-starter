<?php
/**
 * Title: Contact form
 * Slug: vanilla-starter/contact-form
 * Categories: vanilla-starter
 * Keywords: form, contact, email
 * Description: Contact form. Saves to Submissions > Contact and emails the team. See docs/FORMS.md.
 *
 * @package VanillaStarter
 */

?>
<!-- wp:html -->
<form class="vs-form" method="post" action="<?php echo esc_url( \VanillaStarter\form_action() ); ?>" data-vs-form aria-label="<?php esc_attr_e( 'Contact form', 'vanilla-starter' ); ?>">
	<?php \VanillaStarter\form_fields( 'contact' ); ?>

	<label><?php esc_html_e( 'Name', 'vanilla-starter' ); ?>
		<input type="text" name="name" autocomplete="name" required></label>

	<label><?php esc_html_e( 'Email', 'vanilla-starter' ); ?>
		<input type="email" name="email" autocomplete="email" spellcheck="false" required></label>

	<fieldset>
		<legend><?php esc_html_e( 'Topic', 'vanilla-starter' ); ?></legend>
		<label><input type="radio" name="topic" value="Question" checked> <?php esc_html_e( 'Question', 'vanilla-starter' ); ?></label>
		<label><input type="radio" name="topic" value="Project"> <?php esc_html_e( 'Project', 'vanilla-starter' ); ?></label>
		<label><input type="radio" name="topic" value="Other"> <?php esc_html_e( 'Other', 'vanilla-starter' ); ?></label>
	</fieldset>

	<label><?php esc_html_e( 'Site URL (optional)', 'vanilla-starter' ); ?>
		<input type="url" name="site" inputmode="url" spellcheck="false" placeholder="https://"></label>

	<label><?php esc_html_e( 'Message', 'vanilla-starter' ); ?>
		<textarea name="message" rows="5" required></textarea></label>

	<?php \VanillaStarter\form_error(); ?>
	<button class="wp-element-button" type="submit" data-busy="<?php esc_attr_e( 'Sending...', 'vanilla-starter' ); ?>"><?php esc_html_e( 'Send message', 'vanilla-starter' ); ?></button>
	<p role="status" aria-live="polite" data-sent="<?php esc_attr_e( 'Thanks, your message is in. A confirmation is on its way to your inbox.', 'vanilla-starter' ); ?>"></p>
</form>
<!-- /wp:html -->
