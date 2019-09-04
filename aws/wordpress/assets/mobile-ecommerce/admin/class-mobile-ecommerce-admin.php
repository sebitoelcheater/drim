<?php

include_once "includes/pages/generate_apk.php";

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
class Mobile_Ecommerce_Admin
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
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        if (isset($_GET['page']) && !empty($_GET['page']) && (
                $_GET['page'] == "mobile_ecommerce_banner_setting" || $_GET['page'] == "mobile_ecommerce_generate_apk"
            )) {
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/me-admin.css', array(), $this->version, 'all');
        }

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        if (isset($_GET['page']) && !empty($_GET['page']) && ($_GET['page'] == "mobile_ecommerce_banner_setting")) {
            wp_enqueue_media();
            wp_enqueue_script('jquery');
            wp_enqueue_script('thickbox');
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/me-admin.js', array('jquery'), $this->version, false);
        }

        if (isset($_GET['page']) && !empty($_GET['page']) && ($_GET['page'] == "mobile_ecommerce_generate_apk")) {
            wp_enqueue_script('jquery');
            wp_enqueue_script( 'jquery-validate','https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js');
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/me-generate-apk.js', $this->version, false);
        }

    }

    public function mobile_ecommerce_menu() {
        global $GLOBALS;
        if (empty($GLOBALS['admin_page_hooks']['mobile_ecommerce'])) {
            add_menu_page(
                'Mobile Ecommerce', 'Mobile Ecommerce', 'manage_option', 'mobile_ecommerce', array($this, 'mobile_ecommerce_menu_page'), ME_PLUGIN_URL . 'admin/images/menu-icon.png', 25
            );
        }
        add_submenu_page('mobile_ecommerce', 'Banner Management', 'Banner Management', 'manage_options', 'mobile_ecommerce_banner_setting', array($this, 'my_custom_submenu_page_callback'));
        //add_submenu_page('mobile_ecommerce', 'Generate Apk', 'Generate Apk', 'manage_options', 'mobile_ecommerce_generate_apk', array($this, 'my_custom_submenu_generate_apk_callback'));
    }

    public function mobile_ecommerce_menu_page() {
        echo "";  
    }

    public function my_custom_submenu_page_callback() {
        $banners = get_option('mobile_ecommerce_banners');
        wp_nonce_field( 'category-ajax-nonce', 'category-ajax-nonce_field' );
        $categories = get_terms(['taxonomy'   => "product_cat"]);
        ?>
        <div class="me_banner_container">
            
            <header class="me-header">
                <div class="me-logo-main">
                    <img src="<?php echo ME_PLUGIN_URL . 'admin/images/opus-logo-small.png' ?>" />
                </div>
                <div class="me-header-right">
                    <div class="logo-detail"><strong>Mobile App Home Screen Banners</strong></div>
                </div>
            </header>
            <table class="banner_row">
                <tr>
                    <th scope="row"><label for="banner_url">Banner Image</label></th>
                    <td><a class='me_upload_file_button button' uploader_title='Select File' uploader_button_text='Include File'>Upload File</a>  <a class='me_remove_file button'>Remove File</a></td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td><img class="me_banner_img_admin" style="display:<?php echo isset($banners[0]['img_src']) ? 'block' : 'none'; ?>;height: 200px;" src="<?php echo isset($banners[0]['img_src']) ? $banners[0]['img_src'] : '' ?>" /></td>
                </tr>
                <tr>
                    <th>Select Category</th>
                    <td>
                        <select class="category">
                            <?php
                            foreach ($categories as $category) {
                                $selected = '';
                                if(isset($banners[0]['category']) && $banners[0]['category'] == $category->term_id) {
                                    $selected = 'selected';
                                }
                                echo '<option ' . $selected . ' value="' . $category->term_id .'">' . $category->name . '</option>';                               
                            }
                            ?>
                        </select>
                        <label>(This category's screen will open when you will click on banner image in app)</label>
                    </td>
                </tr>
            </table>
            <hr />
            <table class="banner_row">
                <tr>
                    <th scope="row"><label for="banner_url">Banner Image</label></th>
                    <td><a class='me_upload_file_button button' uploader_title='Select File' uploader_button_text='Include File'>Upload File</a>  <a class='me_remove_file button'>Remove File</a></td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td><img class="me_banner_img_admin" style="display:<?php echo isset($banners[1]['img_src']) ? 'block' : 'none'; ?>;height: 200px;" src="<?php echo isset($banners[1]['img_src']) ? $banners[1]['img_src'] : '' ?>" /></td>
                </tr>
                <tr>
                    <th>Select Category</th>
                    <td>
                        <select class="category">
                            <?php
                            foreach ($categories as $category) {
                                $selected = '';
                                if(isset($banners[1]['category']) && $banners[1]['category'] == $category->term_id) {
                                    $selected = 'selected';
                                }
                                echo '<option ' . $selected . ' value="' . $category->term_id .'">' . $category->name . '</option>';                               
                            }
                            ?>
                        </select>
                        <label>(This category's screen will open when you will click on banner image in app)</label>
                    </td>
                </tr>
            </table>
            <hr />
            <table class="banner_row">
                <tr>
                    <th scope="row"><label for="banner_url">Banner Image</label></th>
                    <td><a class='me_upload_file_button button' uploader_title='Select File' uploader_button_text='Include File'>Upload File</a>  <a class='me_remove_file button'>Remove File</a></td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td><img class="me_banner_img_admin" style="display:<?php echo isset($banners[2]['img_src']) ? 'block' : 'none'; ?>;height: 200px;" src="<?php echo isset($banners[2]['img_src']) ? $banners[2]['img_src'] : '' ?>" /></td>
                </tr>
                <tr>
                    <th>Select Category</th>
                    <td>
                        <select class="category">
                            <?php
                            foreach ($categories as $category) {
                                $selected = '';
                                if(isset($banners[2]['category']) && $banners[2]['category'] == $category->term_id) {
                                    $selected = 'selected';
                                }
                                echo '<option ' . $selected . ' value="' . $category->term_id .'">' . $category->name . '</option>';                               
                            }
                            ?>
                        </select>
                        <label>(This category's screen will open when you will click on banner image in app)</label>
                    </td>
                </tr>
            </table>
            <hr />
            <table class="banner_row">
                <tr>
                    <th scope="row"><label for="banner_url">Banner Image</label></th>
                    <td><a class='me_upload_file_button button' uploader_title='Select File' uploader_button_text='Include File'>Upload File</a>  <a class='me_remove_file button'>Remove File</a></td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td><img class="me_banner_img_admin" style="display:<?php echo isset($banners[3]['img_src']) ? 'block' : 'none'; ?>;height: 200px;" src="<?php echo isset($banners[3]['img_src']) ? $banners[3]['img_src'] : '' ?>" /></td>
                </tr>
                <tr>
                    <th>Select Category</th>
                    <td>
                        <select class="category">
                            <?php
                            foreach ($categories as $category) {
                                $selected = '';
                                if(isset($banners[3]['category']) && $banners[3]['category'] == $category->term_id) {
                                    $selected = 'selected';
                                }
                                echo '<option ' . $selected . ' value="' . $category->term_id .'">' . $category->name . '</option>';                               
                            }
                            ?>
                        </select>
                        <label>(This category's screen will open when you will click on banner image in app)</label>
                    </td>
                </tr>
            </table>
            <hr />
            <table class="banner_row">
                <tr>
                    <th scope="row"><label for="banner_url">Banner Image</label></th>
                    <td><a class='me_upload_file_button button' uploader_title='Select File' uploader_button_text='Include File'>Upload File</a>  <a class='me_remove_file button'>Remove File</a></td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td><img class="me_banner_img_admin" style="display:<?php echo isset($banners[4]['img_src']) ? 'block' : 'none'; ?>;height: 200px;" src="<?php echo isset($banners[4]['img_src']) ? $banners[4]['img_src'] : '' ?>" /></td>
                </tr>
                <tr>
                    <th>Select Category</th>
                    <td>
                        <select class="category">
                            <?php
                            foreach ($categories as $category) {
                                $selected = '';
                                if(isset($banners[4]['category']) && $banners[4]['category'] == $category->term_id) {
                                    $selected = 'selected';
                                }
                                echo '<option ' . $selected . ' value="' . $category->term_id .'">' . $category->name . '</option>';                               
                            }
                            ?>
                        </select>
                        <label>(This category's screen will open when you will click on banner image in app)</label>
                    </td>
                </tr>
            </table>
            <input type="button" name="save_me_banners" id="save_me_banners" class="button button-primary" value="Save Changes">
        </div>
        <?php
    }

    public function my_custom_submenu_generate_apk_callback() {
        GenerateApk::render_settings_page();
    }

    /**
     * Save banners
     *
     */
    public function me_save_banner_data() {
        // verify nonce
        check_ajax_referer( 'category-ajax-nonce', 'security', false );
        $banner_images = [];
        if(!empty($_POST['banner_images'])) {
            foreach($_POST['banner_images'] as $banner) {
                array_push($banner_images, $banner);
            }
        }
        update_option('mobile_ecommerce_banners', $banner_images);
    }
}
