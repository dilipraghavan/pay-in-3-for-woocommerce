<?php
/**
 * Pay in 3 for WooCommerce Payment Gateway.
 *
 * @package WpShiftStudio\PayIn3ForWC\Gateway
 */

namespace WpShiftStudio\PayIn3ForWC\Gateway;

use WC_Order;
use WC_Payment_Gateway;
use WpShiftStudio\PayIn3ForWC\API\MockProvider;
use WpShiftStudio\PayIn3ForWC\Database\Manager;


/**
 * Pay in 3 for WooCommerce Payment Gateway.
 *
 * @since 1.0.0
 */
class PayIn3Gateway extends WC_Payment_Gateway {

	/**
	 * The API provider for handling payments.
	 *
	 * @var MockProvider
	 */
	protected $api_provider;

	/**
	 * Handles all database operations.
	 *
	 * @var Manager
	 */
	protected $database_manager;

	/**
	 * Logger instance.
	 *
	 * @var \WC_Logger
	 */
	private $logger;

	/**
	 * Constructor for the Gateway
	 */
	public function __construct() {
		$this->id                 = 'pay-in-3';
		$this->icon               = '';
		$this->has_fields         = false;
		$this->title              = __( 'Pay in 3', 'pay-in-3' );
		$this->method_title       = __( 'Pay in 3', 'pay-in-3' );
		$this->method_description = __( 'Allows customers to pay for their order in three installments.', 'pay-in-3' );
		$this->api_provider       = new MockProvider();
		$this->database_manager   = new Manager();
		$this->logger             = wc_get_logger();

		// Method with all the settings fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		//Ensure settings are saved when admin save changes.
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'        => array(
				'title'   => __( 'Enable/Disable', 'pay-in-3' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Pay in 3 Gateway', 'pay-in-3' ),
				'default' => 'no',
			),
			'title'          => array(
				'title'       => __( 'Title', 'pay-in-3' ),
				'type'        => 'text',
				'description' => __( 'This controls the title that the customer sees during checkout.', 'pay-in-3' ),
				'default'     => __( 'Pay in 3', 'pay-in-3' ),
				'desc_tip'    => true,
			),
			'description'    => array(
				'title'       => __( 'Description', 'pay-in-3' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description that the customer sees during checkout.', 'pay-in-3' ),
				'default'     => __( 'Pay for your order in three easy installments.', 'pay-in-3' ),
				'desc_tip'    => true,
			),
			'testmode'       => array(
				'title'       => __( 'Test Mode', 'pay-in-3' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Test Mode', 'pay-in-3' ),
				'description' => __( 'Place the gateway in test mode using test API keys.', 'pay-in-3' ),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'api_base_url'   => array(
				'title'       => __( 'API Base URL', 'pay-in-3' ),
				'type'        => 'text',
				'description' => __( 'The base URL for the API endpoints.', 'pay-in-3' ),
				'default'     => 'https://api.test.example.com',
				'desc_tip'    => true,
			),
			'api_public_key' => array(
				'title'       => __( 'API Public Key', 'pay-in-3' ),
				'type'        => 'text',
				'description' => __( 'Your public key for API authentication.', 'pay-in-3' ),
				'desc_tip'    => true,
			),
			'api_secret_key' => array(
				'title'       => __( 'API Secret Key', 'pay-in-3' ),
				'type'        => 'password',
				'description' => __( 'Your secret key for API authentication.', 'pay-in-3' ),
				'desc_tip'    => true,
			),
			'min_order'      => array(
				'title'       => __( 'Minimum Order Amount', 'pay-in-3' ),
				'type'        => 'number',
				'description' => __( 'Minimum order amount to show the gateway.', 'pay-in-3' ),
				'default'     => 100,
				'desc_tip'    => true,
			),
			'max_order'      => array(
				'title'       => __( 'Maximum Order Amount', 'pay-in-3' ),
				'type'        => 'number',
				'description' => __( 'Maximum order amount to show the gateway.', 'pay-in-3' ),
				'default'     => 1000,
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id The order ID.
	 * @return array
	 * @throws  \Exception If the payment fails.
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$first_installment_amount = $order->get_total() / 3;

		$amount_text = wc_format_decimal( $first_installment_amount, wc_get_price_decimals() );
		$this->logger->info(
			sprintf('Processing payment for Order# %d. First installment amount: %s', $order_id, $amount_text),
			[ 'source' => 'pay-in-3-gateway' ] 
		);
		try {
			$response = $this->api_provider->charge( $first_installment_amount );
			
			$this->logger->info(
				sprintf('API response for Order# %d: Status: %s, ID: %s', $order_id, $response->status, $response->id ),
				[ 'source' => 'pay-in-3-gateway' ] 
			);
			

			if ( 'success' !== $response->status ) {
				$this->logger->error(
					sprintf('Payment failed for Order# %d. API returned status: %s', $order_id, $response->status ),
					[ 'source' => 'pay-in-3-gateway' ] 
				);
				throw new \Exception( __( 'Payment failed. Please try again or contact support', 'pay-in-3' ) );
			}

			$order->add_order_note(
				sprintf(
					// translators: 1: The amount of the first payment. 2: The charge ID from the API.
					__( 'Pay in 3: First payment of %1$s successfully processed. Charge ID: %2$s. ', 'pay-in-3' ),
					wc_price( $first_installment_amount ),
					$response->id
				)
			);

			$order->set_status( 'processing' );
			$order->save();

			$this->create_installments( $order, $first_installment_amount );

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);

		} catch ( \Exception $e ) {
			$this->logger->error(
				sprintf('Error processing payment for Order# %d: %s', $order_id, $e->getMessage() ),
				[ 'source' => 'pay-in-3-gateway' ] 
			);
			wc_add_notice( $e->getMessage(), 'error' );
			return array(
				'result' => 'fail',
			);
		}
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Get the parent result first (checks if enabled, etc.).
		$is_available = parent::is_available();

		if ( ! $is_available ) {
			return false;
		}

		// Check if the order amount is within the min/max limits.
		$min_order_amount = (float) $this->get_option( 'min_order', 0 );
		$max_order_amount = (float) $this->get_option( 'max_order', PHP_FLOAT_MAX );
		$current_total    = (float) WC()->cart->get_cart_contents_total();

		if ( $current_total < $min_order_amount || $current_total > $max_order_amount ) {
			return false;
		}

		// All checks passed, gateway is available.
		return true;
	}

	/**
	 * Output custom fields on the checkout page.
	 *
	 * @return void
	 */
	public function payment_fields() {
		if ( $this->get_option( 'testmode' ) === 'yes' ) {
			?>
			<div class="woocommerce-info">
				<?php esc_html_e( 'TEST MODE ENABLED. This gateway is in test mode. You will not be charged.', 'pay-in-3' ); ?>
			</div>
			<?php
		}
		if ( $this->get_description() ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wpautop( wp_kses_post( $this->get_description() ) );
		}
	}

	/**
	 * Creates and saves the installments plan.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 * @param float    $first_payment_amount The amount of the first payment.
	 * @return void
	 */
	private function create_installments( $order, $first_payment_amount ) {

		$total_amount          = $order->get_total();
		$remaining_balance     = $total_amount - $first_payment_amount;
		$second_payment_amount = (float) wc_format_decimal( $remaining_balance / 2, wc_get_price_decimals() );
		$third_payment_amount  = (float) wc_format_decimal($remaining_balance - $second_payment_amount, wc_get_price_decimals());

		$now_ts = current_time( 'timestamp' );
		$due_now = current_time( 'mysql' );
		$due_30 = gmdate( 'Y-m-d H:i:s', $now_ts + ( 30 * DAY_IN_SECONDS ) );
		$due_60 = gmdate( 'Y-m-d H:i:s', $now_ts + ( 60 * DAY_IN_SECONDS ) );
		
		
		$installments = array(
			array(
				'amount'   => $first_payment_amount,
				'due_date' => $due_now,
				'status'   => 'paid',
			),
			array(
				'amount'   => $second_payment_amount,
				'due_date' => $due_30,
				'status'   => 'pending',
			),
			array(
				'amount'   => $third_payment_amount,
				'due_date' => $due_60,
				'status'   => 'pending',
			),
		);

		$order_id = $order->get_id();
		$this->database_manager->save_subscription( $order_id, $installments );

		$order->add_order_note(
			__( 'Pay in 3: Installment plan created successfully. Future payments are due in 30 and 60 days', 'pay-in-3' )
		);

		$second_amount_text = wc_format_decimal( $second_payment_amount, wc_get_price_decimals() );
		$this->logger->info(
			sprintf('Installments for Order# %d. Second installment amount: %s on %s', $order_id, $second_amount_text, $installments[1]['due_date'] ),
			[ 'source' => 'pay-in-3-gateway' ] 
		);

		$third_amount_text = wc_format_decimal( $third_payment_amount, wc_get_price_decimals() );
		$this->logger->info(
			sprintf('Installments for Order# %d. Third installment amount: %s on %s', $order_id, $third_amount_text, $installments[2]['due_date'] ),
			[ 'source' => 'pay-in-3-gateway' ] 
		);
	}

	/**
	 * Add content to the order confirmation page.
	 *
	 * @param int $order_id The order ID.
	 */
	public static function add_thank_you_message( $order_id ) {

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( $order->get_payment_method() === 'pay-in-3' ) {

			remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
    		remove_action( 'woocommerce_thankyou', 'woocommerce_view_order', 10 );
    		remove_action( 'woocommerce_thankyou', 'woocommerce_order_again_button', 20 );

			echo '<h2>' . esc_html__( 'Payment Plan Details', 'pay-in-3' ) . '</h2>';
			echo '<p>' . esc_html__( 'Thank you for your order! Your payment has been set up as a 3-part installment plan.', 'pay-in-3' ) . '</p>';
			echo '<p><strong>' . esc_html__( 'First Payment:', 'pay-in-3' ) . '</strong> ' . esc_html__( 'The first third of your payment has been successfully charged.', 'pay-in-3' ) . '</p>';
			echo '<p><strong>' . esc_html__( 'Next Payments:', 'pay-in-3' ) . '</strong> ' . esc_html__( 'The remaining two installments will be automatically billed to your card in 30 and 60 days from now.', 'pay-in-3' ) . '</p>';
        
		}
	}
}
