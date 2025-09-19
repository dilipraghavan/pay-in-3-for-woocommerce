<?php
/**
 * Handles Subscriptions SQL table
 *
 * @package WpShiftStudio\PayIn3ForWC\Database\Migrations
 */

namespace WpShiftStudio\PayIn3ForWC\Database\Migrations;

use WpShiftStudio\PayIn3ForWC\Database\Migration;

/**
 * Handles Subscriptions SQL table
 *
 * @since 1.0.0
 */
class CreateSubscriptionsTable implements Migration {

	/**
	 * Creates the SQL table for subscriptions.
	 *
	 * @return void
	 */
	public function up() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'pay_in_3_subscriptions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            customer_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            payment_gateway_id varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY customer_id (customer_id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
