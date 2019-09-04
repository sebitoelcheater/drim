<?php

class GenerateApk
{
    public static function render_settings_page()
    {
        $notice = null;
        if ($_POST) {

            $errors = [];

            /* request validation */
            if(empty($_POST['package_name'])) {
                $errors[] = "Package name is required";
            }

            /* handle file upload */
            if ( ! function_exists( 'wp_handle_upload' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }

            $iconfile = $_FILES['icon'];
            $splashfile = $_FILES['splash'];
            $iconfilepath = null;
            $splashfilepath = null;

            $upload_overrides = array( 'test_form' => false );

            $iconfile = wp_handle_upload( $iconfile, $upload_overrides );
            $splashfile = wp_handle_upload( $splashfile, $upload_overrides );

            if ( $iconfile && ! isset( $iconfile['error'] ) ) {
                $iconfilepath = $iconfile['file'];
            } else {

                $errors[] = $iconfile['error'];
            }

            if ( $splashfile && ! isset( $splashfile['error'] ) ) {
                $splashfilepath = $splashfile['file'];
            } else {
                $errors[] = $splashfile['error'];
            }

            if(empty($errors)) {

                /* send a request to server for generating apk*/
                $curl = curl_init();

                $request_data = [
                    'package_name' => $_POST['package_name'],
                    'app_name' => $_POST['app_name'],
                    'api_base' => $_POST['api_base'],
                    'admin_username' => $_POST['admin_username'],
                    'admin_password' => $_POST['admin_password'],
                    'icon' => new CURLFile($iconfilepath),
                    'splash' => new CURLFile($splashfilepath),
                    'email' => $_POST['email'],
                ];

                //$url = "http://localhost/ionicdemo/public/api/request-apk";
                $url = "http://opuslabs.in:9001/api/request-apk";
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $request_data,
                    CURLOPT_HTTPHEADER => array(
                        "Accept: application/json",
                        "Cache-Control: no-cache"
                    ),
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);
                $curl_info = curl_getinfo($curl);

                curl_close($curl);


                if ($err) {
                    echo $err;
                    $notice = "<div class='error'>cURL Error #:" . $err . "</div>";
                } else {
                    $notice = "<div class='notice notice-success is-dismissible'><p>Your request has been submitted. We will send an emai once process is complete</p></div>";
                }
            }
        }
        ?>
        <div class="me_banner_container">

            <header class="me-header">
                <div class="me-logo-main">
                    <img src="<?php echo ME_PLUGIN_URL . 'admin/images/opus-logo-small.png' ?>"/>
                </div>
                <div class="me-header-right">
                    <div class="logo-detail"><strong>Mobile App Generate APK</strong></div>
                </div>
            </header>

            <?= $notice ?>

            <?php
            if($errors) {
                ?>
                <div class="error">
                    <?php
                    foreach ($errors as $error) {
                        echo "<p>" . $error . "</p>";
                    }
                    ?>
                </div>
                <?php
            }
            ?>

            <div class="error" id="error" style="display: none"></div>
            <form id="generate-apk" method="post" enctype="multipart/form-data">
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="package_name">Package Name</label></th>
                        <td>
                            <input name="package_name" type="text" id="package_name" placeholder="Package Name"
                                   class="regular-text" required>
                            <p class="description" id="tagline-description">Android package name</p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="app_name">App Name</label></th>
                        <td>
                            <input name="app_name" type="text" id="app_name" placeholder="App Name" class="regular-text"
                                   required>
                            <p class="description" id="tagline-description">Name of your mobile app</p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="api_base">Base Url</label></th>
                        <td>
                            <input name="api_base" type="text" id="api_base" placeholder="Base Url" class="regular-text"
                                   required>
                            <p class="description" id="tagline-description">Url of your wordrpess website.</p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="admin_username">Consumer Key</label></th>
                        <td>
                            <input name="admin_username" type="text" id="admin_username" placeholder="Admin Username"
                                   class="regular-text">
                            <p class="description" id="tagline-description">REST API consumer key, required if your
                                website uses https:// instead of http://</p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="admin_password">Consumer Secret</label></th>
                        <td>
                            <input name="admin_password" type="text" id="admin_username" placeholder="Admin Password"
                                   class="regular-text">
                            <p class="description" id="tagline-description">REST API consumer secret, required if your
                                website uses https:// instead of http://</p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="admin_username">Admin Username</label></th>
                        <td>
                            <input name="admin_username" type="text" id="admin_username" placeholder="Admin Username"
                                   class="regular-text">
                            <p class="description" id="tagline-description">Username of admin panel of your website,
                                required if your website uses http:// instead of https://</p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="admin_password">Admin Password</label></th>
                        <td>
                            <input name="admin_password" type="text" id="admin_username" placeholder="Admin Password"
                                   class="regular-text">
                            <p class="description" id="tagline-description">Password of admin panel of your website,
                                required if your website uses http:// instead of https://</p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="email">Email</label></th>
                        <td>
                            <input name="email" type="text" id="email" placeholder="Email" class="regular-text"
                                   required>
                            <p class="description" id="tagline-description">We will send an email to you once your apk
                                is ready</p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="icon">Icon</label></th>
                        <td>
                            <input name="icon" id="icon" type="file" placeholder="Icon (1024x1024)" class="regular-text"
                                   required>
                            <p class="description" id="tagline-description">Icon of your app, required dimension is
                                1024x1024</p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="splash">Splash</label></th>
                        <td>
                            <input name="splash" id="splash" type="file" placeholder="Icon (2732x2732)"
                                   class="regular-text" required>
                            <p class="description" id="tagline-description">Splash of your app, required dimension is
                                2732x2732</p>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary"
                                         value="Save Changes"></p>
            </form>
        </div>
        <?php
    }
}

?>