<?php
/**
 * Theme blocks and the shared inline SVG icon helper.
 *
 * @package VanillaStarter
 */

namespace VanillaStarter;

/**
 * Icons shipped in assets/icons. Also the allowlist for file reads, so no
 * user input ever reaches the filesystem. Keep in sync with blocks/icon/block.json.
 */
const ICONS = array( 'arrow-right', 'check', 'chevron-down', 'close', 'external', 'mail', 'menu', 'search' );

add_action( 'init', __NAMESPACE__ . '\\register_blocks' );

/**
 * Registers blocks/<name>/block.json. A known list, so no directory scan.
 */
function register_blocks(): void {
	foreach ( array( 'icon', 'nav', 'video-facade' ) as $block ) {
		register_block_type( get_template_directory() . '/blocks/' . $block );
	}
}

/**
 * Returns an inline SVG icon.
 *
 * @param string $name  Icon name from ICONS.
 * @param string $label Accessible label. Empty means decorative (aria-hidden).
 * @return string SVG markup, or '' for unknown names.
 */
function icon( string $name, string $label = '' ): string {
	static $svgs = array();

	if ( ! in_array( $name, ICONS, true ) ) {
		return '';
	}

	if ( ! isset( $svgs[ $name ] ) ) {
		$svg = wp_cache_get( $name, 'vs_icons' );
		if ( false === $svg ) {
			$svg = (string) file_get_contents( get_template_directory() . '/assets/icons/' . $name . '.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
			wp_cache_set( $name, $svg, 'vs_icons' );
		}
		$svgs[ $name ] = trim( $svg );
	}

	$attrs = '' === $label
		? 'aria-hidden="true" focusable="false"'
		: 'role="img" aria-label="' . esc_attr( $label ) . '"';

	return preg_replace( '/^<svg\b/', '<svg class="vs-icon" ' . $attrs, $svgs[ $name ], 1 );
}

/**
 * Builds <li> items for the nav block.
 *
 * Order: navigation post (ref), then the classic menu location, then top-level pages.
 *
 * @param int    $ref      wp_navigation post ID, 0 to skip.
 * @param string $location Registered menu location.
 * @return string List items HTML, '' when there is nothing to show.
 */
function nav_items( int $ref, string $location ): string {
	if ( $ref > 0 ) {
		$post = get_post( $ref );
		if ( $post && 'wp_navigation' === $post->post_type && 'publish' === $post->post_status ) {
			return nav_items_from_blocks( parse_blocks( $post->post_content ) );
		}
	}

	if ( has_nav_menu( $location ) ) {
		return (string) wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'items_wrap'     => '%3$s',
				'depth'          => 2,
				'fallback_cb'    => false,
				'echo'           => false,
			)
		);
	}

	return (string) wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 1,
			'echo'     => false,
		)
	);
}

/**
 * Renders navigation-link, navigation-submenu, home-link and page-list blocks
 * as plain list items, without loading core Navigation or its scripts.
 *
 * @param array $blocks Parsed blocks.
 * @return string List items HTML.
 */
function nav_items_from_blocks( array $blocks ): string {
	$html = '';
	foreach ( $blocks as $block ) {
		$name  = $block['blockName'] ?? '';
		$attrs = $block['attrs'] ?? array();

		if ( 'core/page-list' === $name ) {
			$html .= wp_list_pages(
				array(
					'title_li' => '',
					'depth'    => 1,
					'echo'     => false,
				)
			);
			continue;
		}

		if ( ! in_array( $name, array( 'core/navigation-link', 'core/navigation-submenu', 'core/home-link' ), true ) ) {
			continue;
		}

		$is_home = 'core/home-link' === $name;
		$url     = $is_home ? home_url( '/' ) : (string) ( $attrs['url'] ?? '' );
		$label   = (string) ( $attrs['label'] ?? ( $is_home ? __( 'Home', 'vanilla-starter' ) : '' ) );
		if ( '' === $url || '' === $label ) {
			continue;
		}

		$current = ( $is_home && is_front_page() )
			|| ( ! empty( $attrs['id'] ) && 'post-type' === ( $attrs['kind'] ?? '' ) && get_queried_object_id() === (int) $attrs['id'] );
		$sub     = empty( $block['innerBlocks'] ) ? '' : '<ul>' . nav_items_from_blocks( $block['innerBlocks'] ) . '</ul>';

		$html .= sprintf(
			'<li><a href="%s"%s>%s</a>%s</li>',
			esc_url( $url ),
			$current ? ' aria-current="page"' : '',
			wp_kses_post( $label ),
			$sub
		);
	}
	return $html;
}
