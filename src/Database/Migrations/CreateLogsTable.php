<?php
/**
 * Handles Logs SQL table
 *
 * @package WpShiftStudio\PayIn3ForWC\Database\Migrations
 */

namespace WpShiftStudio\PayIn3ForWC\Database\Migrations;

use WpShiftStudio\PayIn3ForWC\Database\Migration;

/**
 * Handles Logs SQL table
 *
 * @since 1.0.0
 */
class CreateLogsTable implements Migration {

	/**
	 * Creates the SQL table for logs.
	 *
	 * @return void
	 */
	public function up() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'pay_in_3_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) unsigned DEFAULT NULL,
            order_id bigint(20) unsigned DEFAULT NULL,
            context varchar(50) NOT NULL,
            log_level varchar(20) NOT NULL,
            message text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY subscription_id (subscription_id),
            KEY order_id (order_id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
