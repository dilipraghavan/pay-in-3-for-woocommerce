<?php
/**
 * Initialization class for the plugin
 *
 * @package WpShiftStudio\PayIn3ForWC
 */

namespace WpShiftStudio\PayIn3ForWC;

use WpShiftStudio\PayIn3ForWC\Database\Manager as DbManager;

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
}
