<?php
/**
 * Handles scheduling and execution of recurring insallment charges.
 *
 * @package WpShiftStudio\PayIn3ForWC\Cron
 */

namespace WpShiftStudio\PayIn3ForWC\Cron;

use WpShiftStudio\PayIn3ForWC\Database\Manager;
use WpShiftStudio\PayIn3ForWC\API\MockProvider;
use WC_Order;

/**
 * Handles schedulling and execution of recurring payments.
 *
 * @since 1.0.0
 */
class Scheduler {

	/**
	 * The unique action hook for the cron event.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'pay_in_3_process_due_installments';

	/**
	 * Maximum number of retries for a failed installment.
	 *
	 * @var int
	 */
	const MAX_RETRIES = 3;

	/**
	 * DB Managet instance.
	 *
	 * @var Manager
	 */
	private $db_manager;

	/**
	 * API provider instance.
	 *
	 * @var MockProvider
	 */
	private $api_provider;


	/**
	 * Logger instance.
	 *
	 * @var \WC_Logger
	 */
	private $logger;

	/**
	 * Constrcutor to set up dependencies.
	 */
	public function __construct() {
		$this->db_manager   = new Manager();
		$this->api_provider = new MockProvider();
		$this->logger       = wc_get_logger();
	}

	/**
	 * Schedules the daily cron event if its not scheduled.
	 *
	 * @return void
	 */
	public function schedule_daily_event() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 300, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Clears the scheduled cron event.
	 * This is called during plugin deactivation.
	 *
	 * @return void
	 */
	public function unschedule_event() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * The main cron execution handler.
	 *
	 * @return void
	 */
	public function handle_due_installments() {

		$this->logger->info( 'Cron: Starting daily installment processing.', [ 'source' => 'pay-in-3-cron' ] );
		$due_installments = $this->db_manager->get_due_installments();

		if ( empty( $due_installments ) ) {
			$this->logger->info( 'Cron: No installments currently due.', [ 'source' => 'pay-in-3-cron' ] );
			return;
		}

		$this->logger->info( sprintf( 'Cron: Found %d installments to process.', count( $due_installments ) ), [ 'source' => 'pay-in-3-cron' ] );

		foreach ( $due_installments as $installment ) {
			$this->process_single_payment( $installment );
		}

		$this->logger->info( 'Cron: Finished daily installment processing.', [ 'source' => 'pay-in-3-cron' ] );
	}

	/**
	 * Attempts to process a single payment and retries.
	 *
	 * @param array $installment The installment data row.
	 * @return void
	 */
	private function process_single_payment( $installment ) {

		$installment_id  = absint( $installment['id'] );
		$order_id        = absint( $installment['order_id'] );
		$amount          = (float) $installment['amount'];
		$current_retries = absint( $installment['retries'] );

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			$this->logger->error( sprintf( 'Cron: Order %d installment %d not found...', $order_id, $installment_id ), [ 'source' => 'pay-in-3-cron' ] );
			return;
		}

		if ( $current_retries >= self::MAX_RETRIES ) {
			$this->fail_subscription_permanently( $order, $installment_id );
			return;
		}

		try {
			$response = $this->api_provider->charge( $amount);

			if ( 'success' === $response->status ) {
				$this->handle_successful_charge( $order, $installment, $response->id );
			} else {
				$this->handle_failed_charge( $order, $installment, 'Payment provider declined the charge.' );
			}
		} catch ( \Exception $e ) {
				$this->handle_failed_charge( $order, $installment, 'API Exception: ' . $e->getMessage() );

		}
	}

	/**
	 * Handles a successful payment, updates DB, and adds an order note.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $installment The installment data row.
	 * @param int      $transaction_id The id of the transaction.
	 * @return void
	 */
	private function handle_successful_charge( $order, $installment, $transaction_id ) {
		$this->db_manager->update_installment(
			$installment['id'],
			array(
				'status'         => 'paid',
				'transaction_id' => $transaction_id,
			)
		);

		$order->add_order_note(
			sprintf(
				// translators: 1: Installment ID, 2: Amount, 3: Transaction ID.
				__( 'Pay in 3: Installment #%1$d of %2$s successfully charged by Cron. Txn ID: %3$s', 'pay-in-3' ),
				$installment['id'],
				wc_price( $installment['amount'] ),
				$transaction_id
			)
		);

		// Check if all payments are complete.
		if ( $this->db_manager->are_all_installments_paid( $installment['subscription_id'] ) ) {
			$order->set_status( 'completed' );
			$order->save();
			$order->add_order_note( __( 'Pay in 3: All installments are now paid. Order marked as completed.', 'pay-in-3' ) );
			$this->db_manager->update_subscription_status( $installment['subscription_id'], 'complete' );
		}
	}

	/**
	 * Handles a failed payment, updates DB, increments retries, and adds an order note.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $installment The installment data row.
	 * @param string   $reason Reason for failure.
	 * @return void
	 */
	private function handle_failed_charge( $order, $installment, $reason ) {
		$new_retries = absint( $installment['retries'] ) + 1;

		$this->db_manager->update_installment(
			$installment['id'],
			array(
				'status'    => 'failed',
				'failed_at' => current_time( 'mysql' ),
				'retries'   => $new_retries,
			)
		);

		$order->add_order_note(
			sprintf(
				// translators: 1: Installment ID, 2: Amount, 3: Failure Reason, 4: New retry count.
				__( 'Pay in 3: Installment #%1$d of %2$s failed. Reason: %3$s. Retries: %4$d/%5$d.', 'pay-in-3' ),
				$installment['id'],
				wc_price( $installment['amount'] ),
				$reason,
				$new_retries,
				self::MAX_RETRIES
			)
		);

		if ( $new_retries >= self::MAX_RETRIES ) {
			$this->fail_subscription_permanently( $order, $installment['id'] );
		}
	}

	/**
	 * Fails the subscription and updates the order status.
	 *
	 * @param WC_order $order The order object.
	 * @param array    $installment_id The installment data row.
	 * @return void
	 */
	private function fail_subscription_permanently( $order, $installment_id ) {
		$order->set_status( 'on-hold' );
		$order->save();
		$order->add_order_note(
			sprintf(
				// translators: 1: Installment ID.
				__( 'Pay in 3: Installment #%d failed after max retries. Subscription is now on hold. Requires manual intervention.', 'pay-in-3' ),
				$installment_id
			)
		);

		// Mark the entire subscription as failed/cancelled.
		$subscription = $this->db_manager->get_subscription_by_order_id( $order->get_id() );
		if ( $subscription ) {
			$this->db_manager->update_subscription_status( $subscription['id'], 'failed' );
		}
	}
}
