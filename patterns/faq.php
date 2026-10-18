<?php
/**
 * Title: FAQ
 * Slug: vanilla-starter/faq
 * Categories: text, vanilla-starter
 * Description: Questions and answers using the core Details block. No JavaScript.
 *
 * @package VanillaStarter
 */

?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"},"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:heading {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} -->
<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--50)"><?php esc_html_e( 'Frequently asked questions', 'vanilla-starter' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:details -->
<details class="wp-block-details"><summary><?php esc_html_e( 'How long does it take?', 'vanilla-starter' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'Most projects launch in four to six weeks.', 'vanilla-starter' ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary><?php esc_html_e( 'Can I edit the site myself?', 'vanilla-starter' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'Yes. Every page is built from blocks you can change in the editor.', 'vanilla-starter' ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary><?php esc_html_e( 'Do you offer support?', 'vanilla-starter' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'Yes. Ask about a care plan when you get in touch.', 'vanilla-starter' ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->
</div>
<!-- /wp:group -->
