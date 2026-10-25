<?php
/**
 * [dstudio_button] shortcode: CTA buttons in any content.
 *
 * @package Demfilz_Studio_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a call to action button.
 *
 * Uses the Demfilz Studio theme's button classes and icons when the
 * theme is active; degrades to plain classes otherwise.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function dstudio_button_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'text'  => __( 'Learn More', 'demfilz-studio-core' ),
			'url'   => home_url( '/' ),
			'style' => 'solid',
			'size'  => '',
			'icon'  => '',
		),
		$atts,
		'dstudio_button'
	);

	if ( function_exists( 'dstudio_button_classes' ) ) {
		$class = dstudio_button_classes( $atts['style'], $atts['size'] );
	} else {
		$style = in_array( $atts['style'], array( 'solid', 'ghost', 'dark', 'light' ), true ) ? $atts['style'] : 'solid';
		$class = 'btn btn-' . $style . ( 'large' === $atts['size'] ? ' btn-lg' : '' );
	}

	$svg = '';
	if ( '' !== $atts['icon'] && function_exists( 'dstudio_icon' ) ) {
		$svg = dstudio_icon( $atts['icon'], 18, 'btn-icon' );
	}

	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $atts['url'] ) . '"><span>' . esc_html( $atts['text'] ) . '</span>' . $svg . '</a>';
}
add_shortcode( 'dstudio_button', 'dstudio_button_shortcode' );
