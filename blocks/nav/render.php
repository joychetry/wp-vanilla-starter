<?php
/**
 * Lean Navigation block. Native popover for the mobile menu, zero JavaScript.
 *
 * @package VanillaStarter
 *
 * @var array $attributes Block attributes.
 */

$vs_location = (string) ( $attributes['location'] ?? 'primary' );
$vs_items    = \VanillaStarter\nav_items( (int) ( $attributes['ref'] ?? 0 ), $vs_location );

if ( '' === $vs_items ) {
	return;
}

$vs_label = trim( (string) ( $attributes['label'] ?? '' ) );
if ( '' === $vs_label ) {
	$vs_label = 'footer' === $vs_location ? __( 'Footer', 'vanilla-starter' ) : __( 'Primary', 'vanilla-starter' );
}

$vs_list = '<ul class="vs-nav__list">' . $vs_items . '</ul>';

if ( empty( $attributes['collapse'] ) ) {
	printf(
		'<nav %s>%s</nav>',
		get_block_wrapper_attributes( array( 'aria-label' => $vs_label ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$vs_list // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts in nav_items().
	);
	return;
}

$vs_id = wp_unique_id( 'vs-nav-' );

printf(
	'<nav %1$s><button type="button" class="vs-nav__toggle" popovertarget="%2$s">%3$s<span>%4$s</span></button><div class="vs-nav__panel" id="%2$s" popover><button type="button" class="vs-nav__close" popovertarget="%2$s" popovertargetaction="hide">%5$s<span>%6$s</span></button>%7$s</div></nav>',
	get_block_wrapper_attributes( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array(
			'aria-label' => $vs_label,
			'class'      => 'is-collapsible',
		)
	),
	esc_attr( $vs_id ),
	\VanillaStarter\icon( 'menu' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html__( 'Menu', 'vanilla-starter' ),
	\VanillaStarter\icon( 'close' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html__( 'Close', 'vanilla-starter' ),
	$vs_list // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
