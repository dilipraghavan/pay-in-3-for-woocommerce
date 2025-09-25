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

	/**
	 * Provides installments table name.
	 *
	 * @return string The full installments table name with prefix.
	 */
	private function get_installments_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'pay_in_3_installments';
	}

	/**
	 * Provides subscription table name.
	 *
	 * @return string The full subscriptions table name with prefix.
	 */
	private function get_subscriptions_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'pay_in_3_subscriptions';
	}

	/**
	 * Retrieves all installments that are due for payment today or are overdue.
	 *
	 * @return array Array of database rows (installments), or an empty array.
	 */
	public function get_due_installments() {
		global $wpdb;
		$table_name   = $this->get_installments_table_name();
		$current_date = current_time( 'mysql' );

		$query = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table_name} 
             WHERE due_date <= %s 
             AND status NOT IN ('paid', 'processing', 'cancelled', 'expired')
             ORDER BY due_date ASC",
			$current_date
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query, ARRAY_A );
		return is_array( $results ) ? $results : array();
	}

	/**
	 * Updates the status and/or transaction ID for a single installment.
	 *
	 * @param int   $installment_id The ID of the installment to update.
	 * @param array $data An associative array of column_name => value to update.
	 * @return bool True on success, false otherwise.
	 */
	public function update_installment( $installment_id, $data ) {
		global $wpdb;
		$table_name = $this->get_installments_table_name();

		// phpcs:ignore
		return $wpdb->update(
			$table_name,
			$data,
			array( 'id' => $installment_id ),
			array_fill( 0, count( $data ), '%s' )
		) !== false;
	}

	/**
	 * Checks if all installments for a subscription are paid.
	 *
	 * @param int $subscription_id The ID of the subscription record.
	 * @return bool True if all installments are 'paid', false otherwise.
	 */
	public function are_all_installments_paid( $subscription_id ) {
		global $wpdb;
		$table_name = $this->get_installments_table_name();

		// phpcs:ignore
		$count = $wpdb->get_var(
			$wpdb->prepare(
				//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(id) FROM {$table_name} WHERE subscription_id = %d AND status != 'paid'",
				$subscription_id
			)
		);

		return 0 === (int) $count;
	}

	/**
	 * Updates the status of the main subscription record.
	 *
	 * @param int    $subscription_id The ID of the subscription.
	 * @param string $status The new status (e.g., 'complete', 'failed').
	 * @return bool True on success, false otherwise.
	 */
	public function update_subscription_status( $subscription_id, $status ) {
		global $wpdb;
		$table_name = $this->get_subscriptions_table_name();

		// phpcs:ignore
		return $wpdb->update(
			$table_name,
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'id' => $subscription_id,
			),
			array( '%s', '%s' ),
			array( '%d' ),
		) !== false;
	}

	/**
	 * Gets a subscription record by its WooCommerce Order ID.
	 *
	 * @param int $order_id The ID of the order.
	 * @return array|null The subscription row as an array, or null if not found.
	 */
	public function get_subscription_by_order_id( $order_id ) {
		global $wpdb;
		$table_name = $this->get_subscriptions_table_name();

		// phpcs:ignore
		return $wpdb->get_row(
			$wpdb->prepare(
				//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table_name} WHERE order_id = %d",
				$order_id
			),
			ARRAY_A
		);
	}
}
