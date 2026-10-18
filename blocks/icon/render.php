<?php
/**
 * Icon block.
 *
 * @package VanillaStarter
 *
 * @var array $attributes Block attributes.
 */

$svg = \VanillaStarter\icon( (string) ( $attributes['name'] ?? '' ), trim( (string) ( $attributes['label'] ?? '' ) ) );

if ( '' !== $svg ) {
	printf( '<span %s>%s</span>', get_block_wrapper_attributes(), $svg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG file, label escaped in icon().
}
