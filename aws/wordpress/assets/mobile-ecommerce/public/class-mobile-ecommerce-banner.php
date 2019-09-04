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
class Mobile_Ecommerce_Banner
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
        $this->namespace = $this->plugin_name.'/v'.intval($this->version).'/banners';
    }

    /**
     * Add the endpoints to the API
     */
    public function add_api_routes()
    {
        register_rest_route($this->namespace, 'list', array(
            'methods' => 'GET',
            'callback' => array($this, 'list_banners')
        ));
    }

    public function list_banners($request)
    {
        $banners = get_option('mobile_ecommerce_banners');

        return $banners ? $banners : [];
    }
}
