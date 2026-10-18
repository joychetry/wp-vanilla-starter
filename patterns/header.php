<?php
/**
 * Title: Header
 * Slug: vanilla-starter/header
 * Categories: header, vanilla-starter
 * Block Types: core/template-part/header
 * Description: Site title on the left, navigation on the right.
 *
 * @package VanillaStarter
 */

?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide"><!-- wp:site-title {"level":0} /-->

<!-- wp:vanilla-starter/nav /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
