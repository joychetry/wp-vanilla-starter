<?php
/**
 * Video Facade block. Static thumbnail link; view.js swaps in the iframe on click.
 * Without JavaScript the link opens the video on YouTube.
 *
 * @package VanillaStarter
 *
 * @var array $attributes Block attributes.
 */

$vs_video = trim( (string) ( $attributes['video'] ?? '' ) );
$vs_id    = preg_match( '/^[\w-]{11}$/', $vs_video ) ? $vs_video : '';

if ( '' === $vs_id && preg_match( '~(?:youtu\.be/|[?&]v=|/embed/|/shorts/|/live/)([\w-]{11})~', $vs_video, $vs_match ) ) {
	$vs_id = $vs_match[1];
}

if ( '' === $vs_id ) {
	if ( wp_is_serving_rest_request() ) {
		echo '<p>' . esc_html__( 'Add a YouTube URL or video ID in the block settings.', 'vanilla-starter' ) . '</p>';
	}
	return;
}

$vs_title = trim( (string) ( $attributes['title'] ?? '' ) );
if ( '' === $vs_title ) {
	$vs_title = __( 'YouTube video', 'vanilla-starter' );
}

printf(
	'<figure %1$s><a class="vs-video__link" href="%2$s" data-vs-video="%3$s" data-vs-title="%4$s"><img src="%5$s" alt="" width="480" height="360" loading="lazy" decoding="async"><span class="vs-video__play" aria-hidden="true"></span><span class="screen-reader-text">%6$s</span></a></figure>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_url( 'https://www.youtube.com/watch?v=' . $vs_id ),
	esc_attr( $vs_id ),
	esc_attr( $vs_title ),
	esc_url( 'https://i.ytimg.com/vi/' . $vs_id . '/hqdefault.jpg' ),
	/* translators: %s: video title. */
	esc_html( sprintf( __( 'Play video: %s', 'vanilla-starter' ), $vs_title ) )
);
