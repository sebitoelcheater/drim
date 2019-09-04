<?php

/** Requires the JWT library. */
use \Firebase\JWT\JWT;

/**
 * The jwt functionality of the plugin.
 *
 * @link       https://opuslabs.in
 * @since      1.0.0
 */

/**
 * The jwt auth functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     Enrique Chavez <noone@tmeister.net>
 */
class Mobile_Ecommerce_Basic_Authentication
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
    }

    public function json_basic_auth_handler( $user ) {
        global $wp_json_basic_auth_error;
        global $wpdb;

        $wp_json_basic_auth_error = null;

        // Don't authenticate twice
        if ( ! empty( $user ) ) {
            return $user;
        }
        // Check that we're trying to authenticate
        if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
            return $user;
        }
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
        /**
         * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
         * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
         * recursion and a stack overflow unless the current function is removed from the determine_current_user
         * filter during authentication.
         */
        //remove_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );
        //$user = wp_authenticate( $username, $password );
        //add_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

        $consumer_key = wc_api_hash( sanitize_text_field( $username ) );
        $user         = $wpdb->get_row(
            $wpdb->prepare(
                "
            SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
            FROM {$wpdb->prefix}woocommerce_api_keys
            WHERE consumer_key = %s
        ", $consumer_key
            )
        );

        if ( empty( $user ) ) {
            $wp_json_basic_auth_error = new WP_Error( 'mobile_ecommerce_authentication_error', 'Consumer key/secret is invalid.', array( 'status' => 401 ) );
            return null;
        }

        // Validate user secret.
        if ( ! hash_equals( $user->consumer_secret, $password ) ) { // @codingStandardsIgnoreLine
            $wp_json_basic_auth_error = new WP_Error( 'mobile_ecommerce_authentication_error', 'Consumer secret is invalid.', 'woocommerce', array( 'status' => 401 ) );

            return null;
        }

        if ( is_wp_error( $user ) ) {
            $wp_json_basic_auth_error = $user;
            return null;
        }
        $wp_json_basic_auth_error = true;

        return $user->user_id;
    }

    public function json_basic_auth_error( $error ) {
        // Passthrough other errors
        if ( ! empty( $error ) ) {
            return $error;
        }
        global $wp_json_basic_auth_error;
        return $wp_json_basic_auth_error;
    }
}
