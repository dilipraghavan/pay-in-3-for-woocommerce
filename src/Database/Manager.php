<?php
/**
 * Manages all database migrations for the plugin.
 *
 * @package WpShiftStudio\PayIn3ForWC\Database
 */

namespace WpShiftStudio\PayIn3ForWC\Database;

use WpShiftStudio\PayIn3ForWC\Database\Migrations\CreateInstallmentsTable;
use WpShiftStudio\PayIn3ForWC\Database\Migrations\CreateLogsTable;
use WpShiftStudio\PayIn3ForWC\Database\Migrations\CreateSubscriptionsTable;

/**
 * Manages all database migrations for the plugin.
 *
 * @since 1.0.0
 */
class Manager {

	/**
	 * Array of migration classes to run
	 *
	 * @var array
	 */
	protected $migrations = array(
		CreateInstallmentsTable::class,
		CreateLogsTable::class,
		CreateSubscriptionsTable::class,
	);

	/**
	 * Runs all the database migrations.
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->migrations as $migration_class ) {
			( new $migration_class() )->up();
		}
	}

	/**
	 * Saves a new subscription and its installments to the database.
	 *
	 * @param int   $order_id The WooCommerce order ID.
	 * @param array $installments An array of installments data.
	 * @return int|false The new subscription id or false on failure.
	 */
	public function save_subscription( $order_id, $installments ) {

		global $wpdb;

		$subscriptions_table = $wpdb->prefix . 'pay_in_3_subscriptions';
		$installments_table  = $wpdb->prefix . 'pay_in_3_installments';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$subscriptions_table,
			array(
				'order_id'   => $order_id,
				'status'     => 'active',
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);

		$subscription_id = $wpdb->insert_id;

		if ( ! $subscription_id ) {
			return false;
		}

		foreach ( $installments as $installment ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$installments_table,
				array(
					'subscription_id' => $subscription_id,
					'amount'          => $installment['amount'],
					'due_date'        => $installment['due_date'],
					'status'          => $installment['status'],
				),
				array( '%d', '%f', '%s', '%s' )
			);
		}

		return $subscription_id;
	}
}
