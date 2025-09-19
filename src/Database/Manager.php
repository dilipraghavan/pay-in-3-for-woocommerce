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
}
