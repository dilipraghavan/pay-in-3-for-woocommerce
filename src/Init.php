<?php
/**
 * Initialization class for the plugin
 *
 * @package WpShiftStudio\PayIn3ForWC
 */

namespace WpShiftStudio\PayIn3ForWC;

use WpShiftStudio\PayIn3ForWC\Database\Manager as DbManager;
use WpShiftStudio\PayIn3ForWC\Gateway\PayIn3Gateway;
use WpShiftStudio\PayIn3ForWC\Webhook\Handler;
use WpShiftStudio\PayIn3ForWC\Cron\Scheduler;

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

		(new Scheduler())->schedule_daily_event();
	}

	/**
	 * Register all hooks used by the plugin.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'register_gateway' ) );
		add_action('rest_api_init', [__CLASS__, 'register_webhook_routes']);
		
		$scheduler = new Scheduler();
		add_action(Scheduler::CRON_HOOK, [$scheduler, 'handle_due_installments']);
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

	/**
	 * Register the webhook REST API routes.
	 *
	 * @return void
	 */
	public static function register_webhook_routes(){
		$handler = new Handler();
		$handler->register_routes();
	} 
	
}
