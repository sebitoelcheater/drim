<?php

/**
 * The coupon functionality of the plugin.
 *
 * @link       https://opuslabs.in
 * @since      1.0.0
 */

/**
 * The coupon functionality of the plugin.
 *
 *
 * @author     Ujjwal Wahi <w.ujjwal@gmail.com>
 */
class Mobile_Ecommerce_Stripe
{

	/**
     * The ID of this plugin.
     *
     * @since    1.0.0
     *
     * @var string The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     *
     * @var string The current version of this plugin.
     */
    private $version;

    /**
     * The namespace to add to the api calls.
     *
     * @var string The namespace to add to the api call
     */
    private $namespace;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->namespace = $this->plugin_name.'/v'.intval($this->version).'/stripe';
    }	
	

	/**
     * Add the endpoints to the API
     */
    public function add_api_routes()
    {
        register_rest_route($this->namespace, 'payment', array(
            'methods' => 'POST',
            'callback' => array($this, 'payment')
        ));
    }	

	public function payment( $request = null ) {

		$response       = array();
		$parameters 	= $request->get_params();
		$payment_method = sanitize_text_field( $parameters['payment_method'] );
		$order_id       = sanitize_text_field( $parameters['order_id'] );
		$payment_token  = sanitize_text_field( $parameters['payment_token'] );
		$error          = new WP_Error();

		if ( empty( $payment_method ) ) {
			$error->add( 400, __( "Payment Method 'payment_method' is required.", 'wc-rest-payment' ), array( 'status' => 400 ) );
			return $error;
		}
		if ( empty( $order_id ) ) {
			$error->add( 401, __( "Order ID 'order_id' is required.", 'wc-rest-payment' ), array( 'status' => 400 ) );
			return $error;
		} else if ( wc_get_order($order_id) == false ) {
			$error->add( 402, __( "Order ID 'order_id' is invalid. Order does not exist.", 'wc-rest-payment' ), array( 'status' => 400 ) );
			return $error;
		} else if ( wc_get_order($order_id)->get_status() !== 'pending' ) {
			$error->add( 403, __( "Order status is NOT 'pending', meaning order had already received payment. Multiple payment to the same order is not allowed. ", 'wc-rest-payment' ), array( 'status' => 400 ) );
			return $error;
		}
		if ( empty( $payment_token ) ) {
			$error->add( 404, __( "Payment Token 'payment_token' is required.", 'wc-rest-payment' ), array( 'status' => 400 ) );
			return $error;
		}
		
		if ( $payment_method === "stripe" ) {
			$wc_gateway_stripe                = new WC_Gateway_Stripe();
			$_POST['stripe_token']            = $payment_token;
			$payment_result                   = $wc_gateway_stripe->process_payment( $order_id );
			if ( $payment_result['result'] === "success" ) {
				$response['code']    = 200;
				$response['message'] = __( "Your Payment was Successful", "wc-rest-payment" );

				$order = wc_get_order( $order_id );

				// set order to completed
				if( $order->get_status() == 'processing' ) {
					$order->update_status( 'completed' );
				}

			} else {
				return new WP_REST_Response( array("c"), 123 );
				$response['code']    = 401;
				$response['message'] = __( "Please enter valid card details", "wc-rest-payment" );
			}
		}  else {
			$response['code'] = 405;
			$response['message'] = __( "Please select an available payment method. Supported payment method can be found at https://wordpress.org/plugins/wc-rest-payment/#description", "wc-rest-payment" );
		}

		return new WP_REST_Response( $response, 123 );
	}

}
	