<?php
/**
 * Mock API provider for testing purposes.
 *
 * @package WpShiftStudio\PayIn3ForWC\API
 */

namespace WpShiftStudio\PayIn3ForWC\API;

/**
 * This class simulates payment gateway responses for
 * successful and failed transactions without external calls.
 *
 * @since 1.0.0
 */
class MockProvider {

	/**
	 * Simulates a successful charge against the API
	 *
	 * @param float $amount The amount to be charged.
	 * @return object
	 */
	public function charge( $amount ) {
		return (object) array(
			'status' => 'success',
			'id'     => 'charge_' . uniqid(),
			'amount' => $amount,
		);
	}

	/**
	 * Simulates a payment intent creation.
	 *
	 * @param float $amount The amount to be charged.
	 * @return object
	 */
	public function create_payment_intent( $amount ) {
		return (object) array(
			'status' => 'requires_action',
			'id'     => 'pi_' . uniqid(),
			'amount' => $amount,
		);
	}
}
