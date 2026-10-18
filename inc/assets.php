<?php
/**
 * Front-end styles. Every stylesheet sets `path` so core can inline it.
 *
 * @package VanillaStarter
 */

namespace VanillaStarter;

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_global_style' );
add_action( 'init', __NAMESPACE__ . '\\enqueue_block_styles' );

/**
 * Enqueues the global stylesheet: reset plus layout primitives.
 */
function enqueue_global_style(): void {
	wp_enqueue_style(
		'vanilla-starter-global',
		get_theme_file_uri( 'assets/css/global.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
	wp_style_add_data( 'vanilla-starter-global', 'path', get_theme_file_path( 'assets/css/global.css' ) );
}

/**
 * Attaches assets/css/blocks/core-<block>.css to its core block.
 * Core loads each file only on pages where that block renders.
 */
function enqueue_block_styles(): void {
	$version = wp_get_theme()->get( 'Version' );
	foreach ( block_stylesheets() as $file ) {
		$block = substr( $file, 5, -4 ); // core-details.css => details.
		wp_enqueue_block_style(
			'core/' . $block,
			array(
				'handle' => 'vanilla-starter-core-' . $block,
				'src'    => get_theme_file_uri( 'assets/css/blocks/' . $file ),
				'path'   => get_theme_file_path( 'assets/css/blocks/' . $file ),
				'ver'    => $version,
			)
		);
	}
}

/**
 * Lists assets/css/blocks/core-*.css file names.
 *
 * Cached in a non-expiring transient keyed by theme version. Non-expiring
 * transients are autoloaded, so a warm cache costs no query and no disk scan.
 * Local and development environments skip the cache so new files show up at once.
 *
 * @return string[] File names, e.g. [ 'core-details.css' ].
 */
function block_stylesheets(): array {
	static $files = null;
	if ( null !== $files ) {
		return $files;
	}

	$version = wp_get_theme()->get( 'Version' );
	$dev     = in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	$cached  = $dev ? false : get_transient( 'vs_block_styles' );

	if ( is_array( $cached ) && ( $cached['version'] ?? '' ) === $version ) {
		$files = $cached['files'];
		return $files;
	}

	$found = glob( get_template_directory() . '/assets/css/blocks/core-*.css' );
	$files = false === $found ? array() : array_map( 'basename', $found );
	if ( ! $dev ) {
		set_transient(
			'vs_block_styles',
			array(
				'version' => $version,
				'files'   => $files,
			)
		);
	}
	return $files;
}
