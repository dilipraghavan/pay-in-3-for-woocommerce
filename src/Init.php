<?php
/**
 * Initialization class for the plugin
 *
 * @package WpShiftStudio\PayIn3ForWC
 */

namespace WpShiftStudio\PayIn3ForWC;

use WpShiftStudio\PayIn3ForWC\Database\Manager as DbManager;
use WpShiftStudio\PayIn3ForWC\Gateway\PayIn3Gateway;

/**
 * Initialization class for the plugin
 *
 * @since 1.0.0
 */
class Init {

	/**
	 * Calls the DbManager run method.
	 *
	 * @return void
	 */
	public static function activate() {
		( new DbManager() )->run();
	}

	/**
	 * Register all hooks used by the plugin.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'register_gateway' ) );
	}

	/**
	 * Register the Pay In 3 gateway with woocommerce.
	 *
	 * @param array $gateways An array of existing gateways.
	 * @return array
	 */
	public static function register_gateway( $gateways ) {
		$gateways[] = PayIn3Gateway::class;
		return $gateways;
	}
}
