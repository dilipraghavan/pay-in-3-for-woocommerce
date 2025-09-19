<?php
/**
 * Handles Installments SQL table
 *
 * @package WpShiftStudio\PayIn3ForWC\Database\Migrations
 */

namespace WpShiftStudio\PayIn3ForWC\Database\Migrations;

use WpShiftStudio\PayIn3ForWC\Database\Migration;

/**
 * Handles Installments SQL table
 *
 * @since 1.0.0
 */
class CreateInstallmentsTable implements Migration {

	/**
	 * Creates the SQL table for installments.
	 *
	 * @return void
	 */
	public function up() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'pay_in_3_installments';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned NOT NULL,
            amount decimal(10,2) NOT NULL,
            due_date datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            transaction_id varchar(255) DEFAULT NULL,
            failed_at datetime DEFAULT NULL,
            retries int(11) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY subscription_id (subscription_id),
            KEY order_id (order_id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
