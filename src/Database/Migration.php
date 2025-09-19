<?php
/**
 * Interface for Migration tables.
 *
 * @package WpShiftStudio\PayIn3ForWC\Database
 */

namespace WpShiftStudio\PayIn3ForWC\Database;

/**
 * Interface for Migration tables.
 *
 * @since 1.0.0
 */
interface Migration {
	/**
	 * Creates SQL tables
	 *
	 * @return void
	 */
	public function up();
}
