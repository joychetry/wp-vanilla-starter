<?php
/**
 * Theme supports, menus, editor styles, pattern category.
 *
 * Block themes already get title-tag, post-thumbnails, responsive-embeds,
 * editor-styles, html5 and feed links from core, so they are not repeated here.
 *
 * @package VanillaStarter
 */

namespace VanillaStarter;

add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );
add_action( 'init', __NAMESPACE__ . '\\register_pattern_category' );

/**
 * Registers menus and editor styles, drops supports the theme does not use.
 */
function setup(): void {
	register_nav_menus(
		array(
			'primary' => __( 'Primary', 'vanilla-starter' ),
			'footer'  => __( 'Footer', 'vanilla-starter' ),
		)
	);

	// The editor gets the same files the front end inlines.
	$files = array( 'assets/css/global.css' );
	foreach ( block_stylesheets() as $file ) {
		$files[] = 'assets/css/blocks/' . $file;
	}
	add_editor_style( $files );

	// The theme ships its own patterns; core's set only adds noise to the inserter.
	remove_theme_support( 'core-block-patterns' );
}

/**
 * Registers the theme's pattern category.
 */
function register_pattern_category(): void {
	register_block_pattern_category(
		'vanilla-starter',
		array( 'label' => __( 'Vanilla Starter', 'vanilla-starter' ) )
	);
}
