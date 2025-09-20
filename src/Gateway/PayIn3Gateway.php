<?php
/**
 * Pay in 3 for WooCommerce Payment Gateway
 * 
 * @package WpShiftStudio\PayIn3ForWC\Gateway
 */

namespace WpShiftStudio\PayIn3ForWC\Gateway;
use WC_Payment_Gateway;


/**
 * Pay in 3 for WooCommerce Payment Gateway
 * 
 * @since 1.0.0
 */
class PayIn3Gateway extends WC_Payment_Gateway{

    /**
     * Constructor for the Gateway
     */
    public function __construct(){
        $this->id = 'pay-in-3';
        $this->icon = '';
        $this->has_fields = false;
        $this->title = __('Pay in 3', 'pay-in-3'); 
        $this->method_title = __( 'Pay in 3', 'pay-in-3' );
        $this->method_description = __( 'Allows customers to pay for their order in three installments.', 'pay-in-3' );

        // Method with all the settings fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
    }

    /**
     * Initialize Gateway Settings Form Fields.
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __( 'Enable/Disable', 'pay-in-3' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Pay in 3 Gateway', 'pay-in-3' ),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __( 'Title', 'pay-in-3' ),
                'type'        => 'text',
                'description' => __( 'This controls the title that the customer sees during checkout.', 'pay-in-3' ),
                'default'     => __( 'Pay in 3', 'pay-in-3' ),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __( 'Description', 'pay-in-3' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the description that the customer sees during checkout.', 'pay-in-3' ),
                'default'     => __( 'Pay for your order in three easy installments.', 'pay-in-3' ),
                'desc_tip'    => true,
            ],
            'testmode' => [
                'title'       => __( 'Test Mode', 'pay-in-3' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable Test Mode', 'pay-in-3' ),
                'description' => __( 'Place the gateway in test mode using test API keys.', 'pay-in-3' ),
                'default'     => 'yes',
                'desc_tip'    => true,
            ],
            'api_base_url' => [
                'title'       => __( 'API Base URL', 'pay-in-3' ),
                'type'        => 'text',
                'description' => __( 'The base URL for the API endpoints.', 'pay-in-3' ),
                'default'     => 'https://api.test.example.com',
                'desc_tip'    => true,
            ],
            'api_public_key' => [
                'title'       => __( 'API Public Key', 'pay-in-3' ),
                'type'        => 'text',
                'description' => __( 'Your public key for API authentication.', 'pay-in-3' ),
                'desc_tip'    => true,
            ],
            'api_secret_key' => [
                'title'       => __( 'API Secret Key', 'pay-in-3' ),
                'type'        => 'password',
                'description' => __( 'Your secret key for API authentication.', 'pay-in-3' ),
                'desc_tip'    => true,
            ],
            'min_order' => [
                'title'       => __( 'Minimum Order Amount', 'pay-in-3' ),
                'type'        => 'number',
                'description' => __( 'Minimum order amount to show the gateway.', 'pay-in-3' ),
                'default'     => 100,
                'desc_tip'    => true,
            ],
            'max_order' => [
                'title'       => __( 'Maximum Order Amount', 'pay-in-3' ),
                'type'        => 'number',
                'description' => __( 'Maximum order amount to show the gateway.', 'pay-in-3' ),
                'default'     => 1000,
                'desc_tip'    => true,
            ],
        ];
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id The order ID.
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        // Mark the order as complete.
        $order->payment_complete();

        // Return a successful result and a redirect URL.
        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        ];
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
        $min_order_amount = (float)$this->get_option( 'min_order', 0 );
        $max_order_amount = (float)$this->get_option( 'max_order', PHP_FLOAT_MAX);
        $current_total    = (float)WC()->cart->get_cart_contents_total();

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
        if ( $this->get_option( 'testmode' ) === 'yes') {
        ?>
            <div class="woocommerce-info">
                <?php esc_html_e( 'TEST MODE ENABLED. This gateway is in test mode. You will not be charged.', 'pay-in-3' ); ?>
            </div>
        <?php
        }
        if ( $this->get_description() ) {
            echo wpautop( wp_kses_post( $this->get_description() ) );
        }
    }
}