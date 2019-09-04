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
class Mobile_Ecommerce_Coupon
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
        $this->namespace = $this->plugin_name.'/v'.intval($this->version).'/coupon';
    }

    /**
     * Add the endpoints to the API
     */
    public function add_api_routes()
    {
        register_rest_route($this->namespace, 'order/(?P<id>[\d]+)/apply-coupon', array(
            'methods' => 'GET',
            'callback' => array($this, 'apply_coupon'),
            'permission_callback' => array( $this, 'check_apply_coupon_permission' )
        ));
    }

    public function apply_coupon($request)
    {
        $code = $request->get_param('code');
        $orderId = $request->get_param('id');

        if(!$orderId || !$code) {
            return new WP_Error(
                 'missing_parameter',
                 'Missing Parameter',
                 array(
                     'status' => 400,
                 )
             );
        }

        $order = wc_get_order($orderId);

        if(!$order) {
            return new WP_Error(
                 'order_not_found',
                 'Order not found',
                 array(
                     'status' => 404,
                 )
             );
        }
        
        if (is_wp_error( $order ) ) {
            return $order;
        }
        
        $couponApplied = $order->apply_coupon($code);

        if (is_wp_error( $couponApplied ) ) {
            return $couponApplied;
        }

        if(!$couponApplied) {
            return new WP_Error(
                 'coupon_not_applied',
                 'Unable to apply coupon',
                 array(
                     'status' => 400,
                 )
             );
        }

        $order = wc_get_order($orderId);

        return $this->prepare_item_for_response($order, $request);
    }

    private function prepare_item_for_response( $post, $request ) {
        $this->request       = $request;
        $this->request['dp'] = is_null( $this->request['dp'] ) ? wc_get_price_decimals() : absint( $this->request['dp'] );
        $statuses            = wc_get_order_statuses();
        $order               = wc_get_order( $post );
        $data                = array_merge( array( 'id' => $order->get_id() ), $order->get_data() );
        $format_decimal      = array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax' );
        $format_date         = array( 'date_created', 'date_modified', 'date_completed', 'date_paid' );
        $format_line_items   = array( 'line_items', 'tax_lines', 'shipping_lines', 'fee_lines', 'coupon_lines' );

        // Format decimal values.
        foreach ( $format_decimal as $key ) {
            $data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
        }

        // Format date values.
        foreach ( $format_date as $key ) {
            $data[ $key ] = $data[ $key ] ? wc_rest_prepare_date_response( get_gmt_from_date( date( 'Y-m-d H:i:s', $data[ $key ] ) ) ) : false;
        }

        // Format the order status.
        $data['status'] = 'wc-' === substr( $data['status'], 0, 3 ) ? substr( $data['status'], 3 ) : $data['status'];

        // Format line items.
        foreach ( $format_line_items as $key ) {
            $data[ $key ] = array_values( array_map( array( $this, 'get_order_item_data' ), $data[ $key ] ) );
        }

        // Refunds.
        $data['refunds'] = array();
        foreach ( $order->get_refunds() as $refund ) {
            $data['refunds'][] = array(
                'id'     => $refund->get_id(),
                'refund' => $refund->get_reason() ? $refund->get_reason() : '',
                'total'  => '-' . wc_format_decimal( $refund->get_amount(), $this->request['dp'] ),
            );
        }

        $context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $response = rest_ensure_response( $data );

        /**
         * Filter the data for a response.
         *
         * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
         * prepared for the response.
         *
         * @param WP_REST_Response   $response   The response object.
         * @param WP_Post            $post       Post object.
         * @param WP_REST_Request    $request    Request object.
         */
        return apply_filters( "woocommerce_rest_prepare_order", $response, $post, $request );
    }

    /**
     * Expands an order item to get its data.
     *
     * @param WC_Order_item $item Order item data.
     * @return array
     */
    private function get_order_item_data( $item ) {
        $data           = $item->get_data();
        $format_decimal = array( 'subtotal', 'subtotal_tax', 'total', 'total_tax', 'tax_total', 'shipping_tax_total' );

        // Format decimal values.
        foreach ( $format_decimal as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
            }
        }

        // Add SKU and PRICE to products.
        if ( is_callable( array( $item, 'get_product' ) ) ) {
            $data['sku']   = $item->get_product() ? $item->get_product()->get_sku() : null;
            $data['price'] = $item->get_quantity() ? $item->get_total() / $item->get_quantity() : 0;
        }

        // Format taxes.
        if ( ! empty( $data['taxes']['total'] ) ) {
            $taxes = array();

            foreach ( $data['taxes']['total'] as $tax_rate_id => $tax ) {
                $taxes[] = array(
                    'id'       => $tax_rate_id,
                    'total'    => $tax,
                    'subtotal' => isset( $data['taxes']['subtotal'][ $tax_rate_id ] ) ? $data['taxes']['subtotal'][ $tax_rate_id ] : '',
                );
            }
            $data['taxes'] = $taxes;
        } elseif ( isset( $data['taxes'] ) ) {
            $data['taxes'] = array();
        }

        // Remove names for coupons, taxes and shipping.
        if ( isset( $data['code'] ) || isset( $data['rate_code'] ) || isset( $data['method_title'] ) ) {
            unset( $data['name'] );
        }

        // Remove props we don't want to expose.
        unset( $data['order_id'] );
        unset( $data['type'] );

        return $data;
    }

    public function check_apply_coupon_permission($request)
    {
        if ( ! wc_rest_check_post_permissions( 'shop_order', 'edit', $request['id'] ) ) 
        {
            return new WP_Error( 'rest_forbidden', 'Sorry, you cannot view this resource.', array( 'status' => 401 ) );
        }

        // This is a black-listing approach. You could alternatively do this via white-listing, by returning false here and changing the permissions check.
        return true;
    }
}
