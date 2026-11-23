<?php
/**
 * Plugin Name: LeanCart Stock Scarcity
 * Plugin URI:  https://tmjspecialistguide.com/
 * Description: When stock falls below a set threshold, shows an “Only 3 left” message on the product page to speed up the buying decision.
 * Version:     1.0.0
 * Author:      LeanCart
 * Author URI:  https://leancart.dev
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: stock-scarcity
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEANCART_STOCK_SCARCITY_DEMO_SITE' ) ) {
	define( 'LEANCART_STOCK_SCARCITY_DEMO_SITE', 'https://tmjspecialistguide.com' );
}

require_once __DIR__ . '/includes/class-leancart-framework.php';

final class LeanCart_Stock_Scarcity {

	const SLUG = 'stock-scarcity';

	public static function init() {
		LeanCart_Framework::boot( array(
			'slug'    => self::SLUG,
			'name'    => 'LeanCart Stock Scarcity',
			'version' => '1.0.0',
			'file'    => __FILE__,
			'site'    => LEANCART_STOCK_SCARCITY_DEMO_SITE,
		) );

		// WooCommerce may load after these plugins; defer the check to runtime.
		add_action( 'admin_notices', array( __CLASS__, 'wc_notice' ) );

		load_plugin_textdomain( 'stock-scarcity', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		add_action( 'leancart_' . self::SLUG . '_settings_fields', array( __CLASS__, 'fields' ) );

		// WooCommerce may load after this plugin; hooks are registered once WC is ready.
		if ( LeanCart_Framework::is_wc() ) {
			self::hooks();
		} else {
			add_action( 'woocommerce_loaded', array( __CLASS__, 'hooks' ) );
		}
	}

	public static function wc_notice() {
		if ( LeanCart_Framework::is_wc() ) { return; }
		echo '<div class="notice notice-warning"><p><strong>' . esc_html( 'LeanCart Stock Scarcity' ) . '</strong>: ';
		esc_html_e( 'This plugin requires WooCommerce to be active.', 'stock-scarcity' );
		echo '</p></div>';
	}

	public static function fields() {
		LeanCart_Framework::field_text( 'threshold', __('Threshold', 'stock-scarcity'), __('Show an alert below this number.', 'stock-scarcity'), 'number' );
		LeanCart_Framework::field_text( 'message', __('Message', 'stock-scarcity'), __('Example: Only %d left!', 'stock-scarcity'), 'text' );
	}


	public static function hooks() {
		add_filter( 'woocommerce_get_stock_html', array( __CLASS__, 'notice' ), 10, 2 );
		add_shortcode( 'lc_stock', array( __CLASS__, 'shortcode' ) );
	}

	public static function notice( $html, $product ) {
		if ( ! is_object( $product ) || ! $product->is_in_stock() ) { return $html; }
		$stock = $product->get_stock_quantity();
		$raw   = LeanCart_Framework::setting( 'threshold', 5 );
		$threshold = ( '' === $raw || ! is_numeric( $raw ) ) ? 5 : max( 1, (int) $raw );
		if ( null === $stock || $stock <= 0 || $stock > $threshold ) { return $html; }
		$msg = LeanCart_Framework::setting( 'message', __( 'Only %d left!', 'stock-scarcity' ) );
		if ( ! is_string( $msg ) || '' === trim( $msg ) ) { $msg = __( 'Only %d left!', 'stock-scarcity' ); }
		$html .= '<p class="lc-scarcity" style="color:#b32d2e;font-weight:600;margin:8px 0">' . esc_html( str_replace( '%d', (string) $stock, $msg ) ) . '</p>';
		return $html;
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'lc_stock' );
		if ( ! function_exists( 'wc_get_product' ) ) { return ''; }
		$product = wc_get_product( (int) $atts['id'] );
		if ( ! $product ) { return ''; }
		$stock = $product->get_stock_quantity();
		if ( null === $stock ) { return ''; }
		return esc_html__( 'Stock:', 'stock-scarcity' ) . ' ' . (int) $stock;
	}


}

LeanCart_Stock_Scarcity::init();

