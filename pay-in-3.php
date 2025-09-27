<?php
/**
 * Plugin Name:       Pay-in-3 for WooCommerce
 * Plugin URI:        https://github.com/dilipraghavan/pay-in-3-for-woocommerce.git
 * Description:       Allows customers to pay for their order in three installments.
 * Version:           1.0.0
 * Author:            Dilip Raghavan
 * Author URI:        http://www.wpshiftstudio.com
 * Text Domain:       pay-in-3
 * Domain Path:       /languages
 *
 * @package WpShiftStudio\PayIn3ForWC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/vendor/autoload.php';
use WpShiftStudio\PayIn3ForWC\Init;

register_activation_hook( __FILE__, array( Init::class, 'activate' ) );
register_uninstall_hook( __FILE__, array( Init::class, 'uninstall' ) );


add_action(
	'plugins_loaded',
	function () {

		if ( ! class_exists( 'Woocommerce' ) ) {
			return;
		}

		Init::register_hooks();
	}
);
