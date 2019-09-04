<?php

/**
 * The file that defines the core plugin class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://opuslabs.in
 * @since      1.0.0
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 *
 * @author     Ujjwal Wahi <w.ujjwal@gmail.com>
 */
class MobileEcommerce
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     *
     * @var Mobile_Ecommerce_Loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     *
     * @var string The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     *
     * @var string The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->plugin_name = 'mobile-ecommerce';
        $this->version = '1.0.0';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Mobile_Ecommerce_Loader. Orchestrates the hooks of the plugin.
     * - Mobile_Ecommerce_Jwt. Defines all hooks for the jwt authentication.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     */
    private function load_dependencies()
    {

        /**
         * Load dependecies managed by composer.
         */
        require_once plugin_dir_path(dirname(__FILE__)).'includes/vendor/autoload.php';

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)).'includes/class-mobile-ecommerce-loader.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)).'public/class-mobile-ecommerce-jwt.php';

        require_once plugin_dir_path(dirname(__FILE__)).'public/class-mobile-ecommerce-basic-authetication.php';

        require_once plugin_dir_path(dirname(__FILE__)).'public/class-mobile-ecommerce-coupon.php';

        require_once plugin_dir_path(dirname(__FILE__)).'public/class-mobile-ecommerce-banner.php';

        require_once plugin_dir_path(dirname(__FILE__)).'public/class-mobile-ecommerce-password.php';

        require_once plugin_dir_path(dirname(__FILE__)).'public/class-mobile-ecommerce-notification.php';

        require_once plugin_dir_path(dirname(__FILE__)).'public/class-mobile-ecommerce-stripe.php';

        // admin related
        require_once plugin_dir_path(dirname(__FILE__)).'admin/class-mobile-ecommerce-admin.php';

        $this->loader = new Mobile_Ecommerce_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Mobile_Ecommerce_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'mobile_ecommerce_menu');
        $this->loader->add_action('wp_ajax_me_save_banner_data', $plugin_admin, 'me_save_banner_data');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        // jwt authentication related
        
        $plugin_jwt = new Mobile_Ecommerce_Jwt($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('rest_api_init', $plugin_jwt, 'add_api_routes');
        $this->loader->add_filter('rest_api_init', $plugin_jwt, 'add_cors_support');
        if(!is_ssl()) {
            $this->loader->add_filter('determine_current_user', $plugin_jwt, 'determine_current_user', 10);
        }
        $this->loader->add_filter( 'rest_pre_dispatch', $plugin_jwt, 'rest_pre_dispatch', 10, 2 );

        // BASIC AUTH for wp/ endpoints
        if(strpos($_SERVER['REQUEST_URI'], "wp/v2/users") !== false && is_ssl()) {
            $plugin_basic_authentication = new Mobile_Ecommerce_Basic_Authentication($this->get_plugin_name(), $this->get_version());
            $this->loader->add_filter('determine_current_user', $plugin_basic_authentication, 'json_basic_auth_handler');
            $this->loader->add_filter('rest_authentication_errors', $plugin_basic_authentication, 'json_basic_auth_error');
        }

        // coupon related
        $plugin_coupon = new Mobile_Ecommerce_Coupon($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('rest_api_init', $plugin_coupon, 'add_api_routes');

        // banner related
        $plugin_banner = new Mobile_Ecommerce_Banner($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('rest_api_init', $plugin_banner, 'add_api_routes');

        // password related
        $plugin_password = new Mobile_Ecommerce_Password($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('rest_api_init', $plugin_password, 'add_api_routes');

        // notification related
        $plugin_notification = new Mobile_Ecommerce_Notification($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('rest_api_init', $plugin_notification, 'add_api_routes');
        $this->loader->add_action('woocommerce_order_status_changed', $plugin_notification, 'on_order_update', 99, 3);

        // stripe related
        $plugin_stripe = new Mobile_Ecommerce_Stripe($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('rest_api_init', $plugin_stripe, 'add_api_routes');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     *
     * @return string The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     *
     * @return Jwt_Auth_Loader Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     *
     * @return string The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}
