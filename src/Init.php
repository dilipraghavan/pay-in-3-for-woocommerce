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
use WpShiftStudio\PayIn3ForWC\AdminUI\Logs;

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

		( new Scheduler() )->schedule_daily_event();
	}

	/**
	 * Register all hooks used by the plugin.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'register_gateway' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_webhook_routes' ) );

		$scheduler = new Scheduler();
		add_action( Scheduler::CRON_HOOK, array( $scheduler, 'handle_due_installments' ) );
	
		$admin_logs = new Logs();
		add_action( 'admin_menu', array( $admin_logs, 'register_menu_page'), 99 );
	
		add_action( 'woocommerce_thankyou_pay-in-3', array( PayIn3Gateway::class, 'add_thank_you_message' ) );

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
	public static function register_webhook_routes() {
		$handler = new Handler();
		$handler->register_routes();
	}

	/**
	 * Deletes all plugin data on uninstallation.
	 *
	 * @return void
	 */
	public static function uninstall() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pay_in_3_subscriptions';
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

		$table_name_installments = $wpdb->prefix . 'pay_in_3_installments';
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name_installments}" );

		(new Scheduler())->unschedule_event();

		 //remove plugin log files.
    	$logs = Logs::get_plugin_log_files(); 
        foreach ( $logs as $path ) {
            if ( is_file( $path ) && is_writable( $path ) ) {
                @unlink( $path ); // suppress errors
            }
        }
	}
}
