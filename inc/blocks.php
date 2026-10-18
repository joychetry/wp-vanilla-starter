<?php
/**
 * Theme blocks and the shared inline SVG icon helper.
 *
 * @package VanillaStarter
 */

namespace VanillaStarter;

add_action( 'init', __NAMESPACE__ . '\\register_blocks' );
add_filter( 'block_type_metadata', __NAMESPACE__ . '\\icon_block_enum' );

/**
 * Registers blocks/<name>/block.json. A known list, so no directory scan.
 */
function register_blocks(): void {
	foreach ( array( 'icon', 'nav', 'video-facade' ) as $block ) {
		register_block_type( get_template_directory() . '/blocks/' . $block );
	}
}

/**
 * Icons from assets/icons/icons.php, the single list of icon names.
 *
 * @return array<string, string> Icon name => SVG markup.
 */
function icons(): array {
	static $icons = null;
	return $icons ??= require get_template_directory() . '/assets/icons/icons.php';
}

/**
 * Fills the Icon block's `name` enum from icons(), so block.json never lists names.
 *
 * @param array $metadata Block metadata from block.json.
 * @return array
 */
function icon_block_enum( array $metadata ): array {
	if ( 'vanilla-starter/icon' === ( $metadata['name'] ?? '' ) ) {
		$metadata['attributes']['name']['enum'] = array_keys( icons() );
	}
	return $metadata;
}

/**
 * Returns an inline SVG icon.
 *
 * @param string $name  Icon name, a key of icons().
 * @param string $label Accessible label. Empty means decorative (aria-hidden).
 * @return string SVG markup, or '' for unknown names.
 */
function icon( string $name, string $label = '' ): string {
	$svg = icons()[ $name ] ?? '';
	if ( '' === $svg ) {
		return '';
	}

	$attrs = '' === $label
		? 'aria-hidden="true" focusable="false"'
		: 'role="img" aria-label="' . esc_attr( $label ) . '"';

	return preg_replace( '/^<svg\b/', '<svg class="vs-icon" ' . $attrs, $svg, 1 );
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
