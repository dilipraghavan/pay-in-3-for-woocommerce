<?php
/**
 * Webhook handler for processing incoming payment provider events.
 *
 * @package WpShiftStudio\PayIn3ForWC\Webhook
 */

namespace WpShiftStudio\PayIn3ForWC\Webhook;

use WP_REST_Request;

/**
 * Handles all incoming webhooks from the payment provider.
 *
 * @since 1.0.0
 */
class Handler {

	/**
	 * The hardcoded shared secret will be pulled from
	 * plugin settings eventually.
	 *
	 * @var string
	 */
	const WEBHOOK_SECRET = 'whsec_payin3_testkey12345';

	/**
	 * The time in seconds for replay protection.
	 *
	 * @var int
	 */
	const REPLAY_PROTECTION_WINDOW = 300;

	/**
	 * The namespace for the custom REST API route.
	 *
	 * @var string
	 */
	protected $namespace = 'pay-in-3/v1';

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
		$this->logger = wc_get_logger();
	}

	/**
	 * Registers the REST API routes for webhooks.
	 *
	 * @return void
	 */
	public function register_routes() {
		\register_rest_route(
			$this->namespace,
			'/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true', // Public facing.
			)
		);
	}

	/**
	 * Handler function for the incoming API request.
	 *
	 * @param WP_REST_Request $request The incoming API REST request.
	 * @return WP_REST_Response The REST response.
	 */
	public function handle_webhook( $request ) {

		$body = $request->get_body();
		$this->logger->info( 'Webhook received: ' . $body, 'pay-in-3-webhook' );

		if ( ! $this->verify_signature( $request ) ) {
			return new \WP_REST_Response(
				array(
					'message' => 'Signature verification failed.',
				),
				401
			);
		}

		if ( $this->is_duplicate_event( $request ) ) {
			return new \WP_REST_Response(
				array(
					'message' => 'Event already processed.',
				),
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'message' => 'Webhook recieved successfully.',
			),
			200
		);
	}

	/**
	 * Verifies HMAC signature
	 *
	 * @param WP_REST_Request $request The API request.
	 * @return bool True if signature is valid , false otherwise.
	 */
	private function verify_signature( $request ) {
		$signature_header = $request->get_header( 'x_payin3_signature' );
		$body             = $request->get_body();

		if ( empty( $signature_header[0] ) ) {
			$this->logger->error( ' Webhook: Missing signature header.', 'pay-in-3-webhook' );
			return false;
		}

		$signature = $signature_header[0];

		$expected_signature = hash_hmac( 'sha256', $body, self::WEBHOOK_SECRET );

		return hash_equals( $expected_signature, $signature );
	}


	/**
	 * Verifies duplciate event.
	 *
	 * @param WP_REST_Request $request The REST API request.
	 * @return boolean true if duplicate event, false otherwise.
	 */
	private function is_duplicate_event( $request ) {
		$idempotency_header = $request->get_header( 'x_payin3_idempotency' );

		if ( empty( $idempotency_header[0] ) ) {
			$this->logger->warning( 'Webhook: Missing idempotency header. Processing, but risk of replay present.', 'pay-in-3-webhook' );
			return false;
		}

		$idempotency_key = sanitize_key( $idempotency_header[0] );

		if ( get_transient( $idempotency_key ) ) {
			$this->logger->warning( 'Webhook: Duplicate event blocked. Key: ' . $idempotency_key, 'pay-in-3-webhook' );
			return true;
		}

		set_transient( $idempotency_key, 1, self::REPLAY_PROTECTION_WINDOW );
		return false;
	}
}
