<?php
/**
 * Title: Footer, columns
 * Slug: vanilla-starter/footer-columns
 * Categories: footer, vanilla-starter
 * Block Types: core/template-part/footer
 * Description: Three columns: about, menu, contact.
 *
 * @package VanillaStarter
 */

?>
<!-- wp:group {"backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"align":"wide","layout":{"type":"grid","minimumColumnWidth":"14rem"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":0} /-->

<!-- wp:site-tagline {"textColor":"muted"} /--></div>
<!-- /wp:group -->

<!-- wp:vanilla-starter/nav {"location":"footer","collapse":false} /-->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:vanilla-starter/icon {"name":"mail"} /-->

<!-- wp:paragraph -->
<p><a href="mailto:hello@example.com">hello@example.com</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
