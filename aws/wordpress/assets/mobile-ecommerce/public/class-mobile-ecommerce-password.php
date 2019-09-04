<?php

/**
 * The password functionality of the plugin.
 *
 * @link       https://opuslabs.in
 * @since      1.0.0
 */

/**
 * The password related functionality of the plugin.
 *
 *
 * @author     Ujjwal Wahi <w.ujjwal@gmail.com>
 */
class Mobile_Ecommerce_Password
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
        $this->namespace = $this->plugin_name.'/v'.intval($this->version).'/password';
    }

    /**
     * Add the endpoints to the API
     */
    public function add_api_routes()
    {
        register_rest_route($this->namespace, 'forgot', array(
            'methods' => 'POST',
            'callback' => array($this, 'forgot_password')
        ));
    }

    public function forgot_password($request)
    {
        return $this->retrieve_password($request);
    }

    /**
    * Handles sending password retrieval email to user.
    *
    * @return bool|WP_Error True: when finish. WP_Error on error
    */
    private function retrieve_password($request) {
        $errors = new WP_Error();

        if ( empty( $request['user_login'] ) || ! is_string( $request['user_login'] ) ) {
            $errors->add('empty_username', 'Enter a username or email address.');
        } elseif ( strpos( $request['user_login'], '@' ) ) {
            $user_data = get_user_by( 'email', trim( wp_unslash( $request['user_login'] ) ) );
            if ( empty( $user_data ) )
                $errors->add('invalid_email', 'There is no user registered with that email address.');
        } else {
            $login = trim($request['user_login']);
            $user_data = get_user_by('login', $login);
        }

        /**
         * Fires before errors are returned from a password reset request.
         *
         * @since 2.1.0
         * @since 4.4.0 Added the `$errors` parameter.
         *
         * @param WP_Error $errors A WP_Error object containing any errors generated
         *                         by using invalid credentials.
         */
        do_action( 'lostpassword_post', $errors );

        if ( $errors->get_error_code() )
            return $errors;

        if ( !$user_data ) {
            $errors->add('invalidcombo', 'Invalid username or email.');
            return $errors;
        }

        // Redefining user_login ensures we return the right case in the email.
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $key = get_password_reset_key( $user_data );

        if ( is_wp_error( $key ) ) {
            return $key;
        }

        if ( is_multisite() ) {
            $site_name = get_network()->site_name;
        } else {
            /*
             * The blogname option is escaped with esc_html on the way into the database
             * in sanitize_option we want to reverse this for the plain text arena of emails.
             */
            $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        }

        $message = __( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
        /* translators: %s: site name */
        $message .= sprintf( __( 'Site Name: %s'), $site_name ) . "\r\n\r\n";
        /* translators: %s: user login */
        $message .= sprintf( __( 'Username: %s'), $user_login ) . "\r\n\r\n";
        $message .= __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "\r\n\r\n";
        $message .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";
        $message .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . ">\r\n";

        /* translators: Password reset email subject. %s: Site name */
        $title = sprintf( __( '[%s] Password Reset' ), $site_name );

        /**
         * Filters the subject of the password reset email.
         *
         * @since 2.8.0
         * @since 4.4.0 Added the `$user_login` and `$user_data` parameters.
         *
         * @param string  $title      Default email title.
         * @param string  $user_login The username for the user.
         * @param WP_User $user_data  WP_User object.
         */
        $title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );

        /**
         * Filters the message body of the password reset mail.
         *
         * If the filtered message is empty, the password reset email will not be sent.
         *
         * @since 2.8.0
         * @since 4.1.0 Added `$user_login` and `$user_data` parameters.
         *
         * @param string  $message    Default mail message.
         * @param string  $key        The activation key.
         * @param string  $user_login The username for the user.
         * @param WP_User $user_data  WP_User object.
         */
        $message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

        if ( $message && !wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) ) {
            return new WP_Error(
                 'email_not_sent',
                 'Unabel to send email',
                 array(
                     'status' => 400,
                 )
             );
        }

        return ["success" => true];
    }
}
