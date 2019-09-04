<?php

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
class Mobile_Ecommerce_Notification
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
        $this->namespace = $this->plugin_name.'/v'.intval($this->version).'/notification';
    }

    /**
     * Add the endpoints to the API
     */
    public function add_api_routes()
    {
        register_rest_route($this->namespace, 'register/(?P<id>[\d]+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_for_notification'),
            'permission_callback' => array( $this, 'check_register_notification_permission' )
        ));
    }

    public function register_for_notification($request)
    {
      $userId = get_user_by('id', $request->get_param('id'));
      $playerId = $request->get_param('player_id');

      if(!$userId || !$playerId) {
        return new WP_Error(
             'mobile_ecommerce_notification_missing_parameter',
             'Missing or wrong parameter',
             array(
                 'status' => 400,
             )
         );
      }

      $result = update_user_meta( $userId->ID, 'onesignal_player_id', $playerId, true );

      return array('success' => $result);
    }

    public function check_register_notification_permission($request)
    {
        if ( ! current_user_can('administrator') ) 
        {
            return new WP_Error( 'rest_forbidden', 'Sorry, you cannot view this resource.', array( 'status' => 401 ) );
        }

        // This is a black-listing approach. You could alternatively do this via white-listing, by returning false here and changing the permissions check.
        return true;
    }

    public function on_order_update($order_id, $old_status, $new_status)
    {
        $order = wc_get_order( $order_id );
        self::send_notification_on_order_update($order, $old_status, $new_status);
    }

    /**
  * The main function that actually sends a notification to OneSignal.
  */
  public static function send_notification_on_order_update($order, $old_status, $new_status) {
    try {     
      if(!class_exists("OneSignal_Admin")) {
        return;
      }

      if (!function_exists('curl_init')) {
          onesignal_debug('Canceling send_notification_on_wp_post because curl_init() is not a defined function.');
          return;
      }

      //get playerid of user
      $playerId = get_user_meta($order->get_user_id(), 'onesignal_player_id', true);

      if(!$playerId) {
        return;
      }


      $time_to_wait = OneSignal_Admin::get_sending_rate_limit_wait_time();
        if ($time_to_wait > 0) {
            set_transient('onesignal_transient_error', '<div class="error notice onesignal-error-notice">
                    <p><strong>OneSignal Push:</strong><em> Please try again in ' . $time_to_wait . ' seconds. Only one notification can be sent every ' . ONESIGNAL_API_RATE_LIMIT_SECONDS . ' seconds.</em></p>
                </div>', 86400);
            return;
        }

      $onesignal_wp_settings = OneSignal::get_onesignal_settings();

      $do_send_notification = true;

      if ($do_send_notification) {

        $notif_content = "Order #" . $order->get_order_number() . " status updated to " . $new_status;

        $site_title = OneSignalUtils::decode_entities("Order Update ");

        if (function_exists('qtrans_getLanguage')) {
          try {
            $qtransLang    = qtrans_getLanguage();
            $site_title    = qtrans_use($qtransLang, $site_title, false);
            $notif_content = qtrans_use($qtransLang, $notif_content, false);
          } catch (Exception $e) {
            onesignal_debug('Caught qTrans exception:', $e->getMessage());
          }
        }

        $fields = array(
          'app_id'             => $onesignal_wp_settings['app_id'],
          'headings'           => array("en" => $site_title),          
          'isAnyWeb'           => false,
          'contents'           => array("en" => $notif_content),
          'include_player_ids' => [$playerId],
          'data'               => ["type" => 'order']
        );

        $send_to_mobile_platforms = $onesignal_wp_settings['send_to_mobile_platforms'];
        if ($send_to_mobile_platforms == true) {
          $fields['isIos'] = true;
          $fields['isAndroid'] = true;
        }

        $ch = curl_init();

        $onesignal_post_url = "https://onesignal.com/api/v1/notifications";

        if (defined('ONESIGNAL_DEBUG') && defined('ONESIGNAL_LOCAL')) {
          $onesignal_post_url = "https://localhost:3001/api/v1/notifications";
        }

        $onesignal_auth_key = $onesignal_wp_settings['app_rest_api_key'];

        curl_setopt($ch, CURLOPT_URL, $onesignal_post_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Authorization: Basic ' . $onesignal_auth_key
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

          if (defined('ONESIGNAL_DEBUG')) {
              // Turn off host verification for localhost testing since we're using a self-signed certificate
              curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
          }

          if (class_exists('WDS_Log_Post')) {
          curl_setopt($ch, CURLOPT_FAILONERROR, false);
          curl_setopt($ch, CURLOPT_HTTP200ALIASES, array(400));
          curl_setopt($ch, CURLOPT_VERBOSE, true);
          curl_setopt($ch, CURLOPT_STDERR, $out);
        }

        $response = curl_exec($ch);

        $curl_http_code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          onesignal_debug('$curl_http_code:', $curl_http_code);

        if ($curl_http_code != 200) {
          if ($curl_http_code != 0) {
            set_transient( 'onesignal_transient_error', '<div class="error notice onesignal-error-notice">
                    <p><strong>OneSignal Push:</strong><em> There was a ' . $curl_http_code . ' error sending your notification:</em></p>
                    <pre>' . print_r($response, true) . '</pre>
                </div>', 86400 );
          } else {
            // A 0 HTTP status code means the connection couldn't be established
            set_transient( 'onesignal_transient_error', '<div class="error notice onesignal-error-notice">
                    <p><strong>OneSignal Push:</strong><em> There was an error establishing a network connection. Please make sure outgoing network connections from cURL are allowed.</em></p>
                </div>', 86400 );
          }
        } else {
          $parsed_response = json_decode($response, true);
          if (!empty($parsed_response)) {
            onesignal_debug('OneSignal API Raw Response:', $response);
            onesignal_debug('OneSignal API Parsed Response:', $parsed_response);
            // API can send a 200 OK even if the notification failed to send
            $recipient_count = $parsed_response['recipients'];
            $sent_or_scheduled = array_key_exists('send_after', $fields) ? 'scheduled' : 'sent';

            $config_show_notification_send_status_message = $onesignal_wp_settings['show_notification_send_status_message'] == "1";

            if ($config_show_notification_send_status_message) {
              if ($recipient_count != 0) {
                set_transient('onesignal_transient_success', '<div class="updated notice notice-success is-dismissible">
                        <p><strong>OneSignal Push:</strong><em> Successfully ' . $sent_or_scheduled . ' a notification to ' . $parsed_response['recipients'] . ' recipients.</em></p>
                    </div>', 86400);
              } else {
                set_transient('onesignal_transient_success', '<div class="updated notice notice-success is-dismissible">
                        <p><strong>OneSignal Push:</strong><em> A notification was ' . $sent_or_scheduled . ', but there were no recipients.</em></p>
                    </div>', 86400);
              }
            }
          }
        }

          if (defined('ONESIGNAL_DEBUG') || class_exists('WDS_Log_Post')) {
          fclose($out);
          $debug_output = ob_get_clean();

          $curl_effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
          $curl_total_time    = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

          onesignal_debug('OneSignal API POST Data:', $fields);
          onesignal_debug('OneSignal API URL:', $curl_effective_url);
          onesignal_debug('OneSignal API Response Status Code:', $curl_http_code);
            if ($curl_http_code != 200) {
                onesignal_debug('cURL Request Time:', $curl_total_time, 'seconds');
                onesignal_debug('cURL Error Number:', curl_errno($ch));
                onesignal_debug('cURL Error Description:', curl_error($ch));
                onesignal_debug('cURL Response:', print_r($response, true));
                onesignal_debug('cURL Verbose Log:', $debug_output);
            }
        }
          curl_close($ch);

        return $response;
      }
    }
    catch (Exception $e) {
      onesignal_debug('Caught Exception:', $e->getMessage());
    }
  }
}
