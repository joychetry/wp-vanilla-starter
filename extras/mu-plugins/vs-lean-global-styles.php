<?php
/**
 * Plugin Name: VS Lean Global Styles
 * Description: Drops core's default presets (palette, gradients, duotone, font sizes, spacing, shadows, aspect ratios) from global styles when the active theme turns them off in theme.json.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * Plugin-layer code, not theme code. Copy into wp-content/mu-plugins/.
 * Core keeps printing default presets even when theme.json sets
 * `defaultPalette: false` etc., so old content that uses them keeps its look.
 * Removing them means content with classes like `has-vivid-red-color` or
 * `has-medium-font-size` loses that style. Check content first (see docs/REUSE.md).
 *
 * Turn off with: add_filter( 'vs_lean_global_styles', '__return_false' );
 *
 * @package VanillaStarter
 */

namespace VanillaStarter\LeanGlobalStyles;

defined( 'ABSPATH' ) || exit;

add_filter( 'wp_theme_json_data_default', __NAMESPACE__ . '\\drop_unused_defaults' );

/**
 * Empties each default preset group the theme opted out of.
 *
 * Runs once per request: core caches the resolved default data.
 *
 * @param \WP_Theme_JSON_Data $theme_json Core default data.
 * @return \WP_Theme_JSON_Data
 */
function drop_unused_defaults( $theme_json ) {
	/**
	 * Filters whether to drop default presets the theme turned off.
	 *
	 * @param bool $enabled Default true.
	 */
	if ( ! apply_filters( 'vs_lean_global_styles', true ) ) {
		return $theme_json;
	}

	// Theme origin only (parent and child). Classic themes without theme.json keep core defaults.
	$theme = \WP_Theme_JSON_Resolver::get_theme_data( array(), array( 'with_supports' => false ) )->get_settings();

	// Theme setting => default data path to empty.
	$map = array(
		'color.defaultPalette'           => array( 'color', 'palette' ),
		'color.defaultGradients'         => array( 'color', 'gradients' ),
		'color.defaultDuotone'           => array( 'color', 'duotone' ),
		'typography.defaultFontSizes'    => array( 'typography', 'fontSizes' ),
		'shadow.defaultPresets'          => array( 'shadow', 'presets' ),
		'dimensions.defaultAspectRatios' => array( 'dimensions', 'aspectRatios' ),
	);

	$settings = array();
	foreach ( $map as $flag => $path ) {
		if ( false === _wp_array_get( $theme, explode( '.', $flag ), true ) ) {
			_wp_array_set( $settings, $path, array() );
		}
	}

	// Default spacing sizes are generated from spacingScale, so zero its steps too.
	if ( false === _wp_array_get( $theme, array( 'spacing', 'defaultSpacingSizes' ), true ) ) {
		$settings['spacing'] = array(
			'spacingScale' => array( 'steps' => 0 ),
			'spacingSizes' => array(),
		);
	}

	if ( array() === $settings ) {
		return $theme_json;
	}

	return $theme_json->update_with(
		array(
			'version'  => \WP_Theme_JSON::LATEST_SCHEMA,
			'settings' => $settings,
		)
	);
}
