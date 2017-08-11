<?php
/**
 * Plugin Name: Goodvidio Extension
 * Plugin URI: http://goodvid.io
 * Description: Goodvidio helps you increase sales on your ecommerce store using
 *  product videos from social media. If you are selling consumer electronics,
 *  games, health & beauty products, sporting goods, tools & hardware,
 *  accessories, baby products, or fast-moving consumer goods, Goodvidio is an
 *  excellent fit for your store!
 * Version: 1.1
 * Author: Goodvidio
 * Author URI: http://goodvid.io/
 * Developer: Goodvidio
 * Developer URI: http://goodvid.io/
 * Text Domain: goodvidio-extension
 * Domain Path: /languages.
 *
 * License: GNU General Public License, version 2
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * Copyright (C) Goodvidio
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * United Kingdom, The Enterprise Zone, 210 Portobello, Sheffield, S1 4AE
 */
$goodvidioSettingsGroupName              = 'goodvidio_settings';
$goodvidioIntegrationStatusOptionName    = 'goodvidio_enabled';
$goodvidioIntegrationDomainOptionName    = 'goodvidio_integration_domain';
$goodvidioEcommerceTrackingOptionName    = 'goodvidio_ecommerce_tracking';

add_action('admin_menu', 'goodvidio_admin_menu');

add_action('woocommerce_thankyou', 'goodvidio_send_order');

add_action('wp_footer', 'goodvidio_inject_script');

register_activation_hook(__FILE__, array('Goodvidio_InstallCheck', 'install') );
register_deactivation_hook(__FILE__, 'goodvidio_deactivate');

class Goodvidio_InstallCheck {
    static function install() {
        /*
         * Check if WooCommerce & Cubepoints are active
         **/
        if ( !is_plugin_active('woocommerce/woocommerce.php')) {
            /* Deactivate the plugin */
            deactivate_plugins(__FILE__);

            /* Throw an error in the wordpress admin console */
            $error_message = __('This plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!', 'woocommerce');
            die($error_message);
        }
    }
}

/**
 * A function that's activated when the plugin is deactivated.
 */
function goodvidio_deactivate() {
    global
        $goodvidioSettingsGroupName,
        $goodvidioIntegrationStatusOptionName,
        $goodvidioEcommerceTrackingOptionName,
        $goodvidioIntegrationDomainOptionName;

    unregister_setting($goodvidioSettingsGroupName, $goodvidioIntegrationStatusOptionName);
    unregister_setting($goodvidioSettingsGroupName, $goodvidioIntegrationDomainOptionName);
    unregister_setting($goodvidioSettingsGroupName, $goodvidioEcommerceTrackingOptionName);
}

/**
 * Includes the files required in order to store information from the form to
 * the database. Called inside the goodvidio_options page
 */
function goodvidio_include_files() {
    include_once ABSPATH . 'wp-includes/pluggable.php';
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    include_once ABSPATH . 'wp-admin/includes/template.php';
}

/**
 * The function is called from the position hooks in the product page. The
 * functions checks if the hook that called it, is the one that the
 * administrator of the ecommerce decided chose. If it is, it shows the videos
 * for the product in that specific hook position, if not then nothing.
 */
function goodvidio_inject_script()
{
    global $wp_current_filter;
    global $goodvidioIntegrationStatusOptionName;
    global $goodvidioIntegrationDomainOptionName;
    global $product;

    $enabled = filter_var(get_option($goodvidioIntegrationStatusOptionName), FILTER_VALIDATE_BOOLEAN);
    $domain = get_option($goodvidioIntegrationDomainOptionName);
    $domainScriptOutput = get_option($goodvidioIntegrationDomainOptionName) ? "g.setAttribute('data-domain', '" . $domain . "');" : "";

    if (!$enabled) {
        return;
    }

    echo "<script>(function(d,t,id) {
       var s = d.getElementsByTagName(t)[0],g,att;
       if(!d.getElementById(id)){
           g = d.createElement(t);
           g.src = ('https:' == document.location.protocol ? 'https://' : 'http://')+'cdn.goodvid.io/install.js';
           g.type='text/javascript';
           g.async=true;" .
           $domainScriptOutput .
           "g.id=id;
           s.parentNode.insertBefore(g,s);
       }
   }(document, 'script','goodvidio-init'));</script>";
}

function goodvidio_admin_assets() {
    wp_register_style('goodvidio_admin_assets', plugins_url('css/style.min.css',__FILE__ ));
    wp_enqueue_style('goodvidio_admin_assets');
    wp_register_style( 'fontawesome', plugins_url('fonts/font-awesome/css/font-awesome.min.css',__FILE__ ));
    wp_enqueue_style( 'fontawesome' );
}

/**
 * The function creates a Goodvidio Settings button that is shown in the admininstrators page
 */
function goodvidio_admin_menu() {
    $page_title = 'Overview - Goodvidio';
    $menu_title = 'Goodvidio';
    $capability = 'manage_options';
    $menu_slug = 'goodvidio';
    $function = 'goodvidio_overview';
    $icon = plugin_dir_url( __FILE__ ) . '/images/favicon.png';

    add_menu_page($page_title,
                     $menu_title,
                     $capability,
                     $menu_slug,
                     $function,
                     $icon
                     );

    add_submenu_page('goodvidio',
                     'Overview - Goodvidio',
                     'Overview',
                     'manage_options',
                     'goodvidio',
                     'goodvidio_overview'
                     );

    add_submenu_page('goodvidio',
                     'Settings - Goodvidio',
                     'Settings',
                     'manage_options',
                     'goodvidio_settings',
                     'goodvidio_options'
                     );

    add_action('admin_init', 'goodvidio_update_settings');
    add_action('admin_init', 'goodvidio_admin_assets');
}

/**
 * The function creates the overview page of Goodvidio plugin
 */
function goodvidio_overview()
{

?>
<div id="goodvidio-plugin">
    <div class="step-section">
        <div class="step-section-content align-center">
            <img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/goodvidio-logo-black-250px.png" alt="Goodvidio Logo" />
            <br>
            <br>
            <p class="ph3">Welcome to Goodvidio Integration for WooCommerce. Learn more at <a href="https://goodvid.io/?utm_source=woocommerce&utm_medium=app&utm_campaign=woocommerce-integration&utm_content=learn-more-text" target="_blank">goodvid.io</a>.</p>
        </div>
    </div>
    <div class="step-section">
        <div class="step-section-content align-center">
            <h2 class="i-want-to-header extrabold">I would like to</h2>
            <br>
            <div class="row responsive four-column">
                <div class="column">
                    <a href="https://signup.goodvid.io/?utm_source=woocommerce&utm_medium=app&utm_campaign=woocommerce-integration&utm_content=start-trial-cta" class="link-button" target="_blank">
                        <i class="fa fa-home" aria-hidden="true"></i>
                        <h2>
                            Start free trial
                        </h2>
                    </a>
                </div>
                <div class="column">
                    <a href="https://dashboard.goodvid.io" class="link-button" target="_blank">
                        <i class="fa fa-sign-in" aria-hidden="true"></i>
                        <h2>
                            Sign in to Goodvidio App
                        </h2>
                    </a>
                </div>
                <div class="column">
                    <a href="<?php echo admin_url('admin.php?page=goodvidio_settings'); ?>" class="link-button">
                        <i class="fa fa-cogs" aria-hidden="true"></i>
                        <h2>
                            Configure the integration
                        </h2>
                    </a>
                </div>
                <div class="column">
                    <a href="https://goodvid.io/contact" class="link-button" target="_blank">
                        <i class="fa fa-comments-o" aria-hidden="true"></i>
                        <h2>
                            Get help
                        </h2>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="step-section">
        <div class="step-section-content align-center">
            <p class="ph3">Connect with us</p>
            <div class="row four-column footer-row">
                <div class="column">
                    <a href="https://goodvid.io/blog" class="footer-button" target="_blank">
                        <i class="fa fa-rss-square" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="column">
                    <a href="http://www.facebook.com/goodvid.io" class="footer-button" target="_blank">
                        <i class="fa fa-facebook-square" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="column">
                    <a href="http://www.twitter.com/goodvidio" class="footer-button" target="_blank">
                        <i class="fa fa-twitter-square" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="column">
                    <a href="https://plus.google.com/103754965916979719767/posts" class="footer-button" target="_blank">
                        <i class="fa fa-google-plus-square" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="column">
                    <a href="http://www.linkedin.com/company/goodvid-io" class="footer-button" target="_blank">
                        <i class="fa fa-linkedin-square" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}


/**
 * The function creates the form that will be shown in the configure page of
 * Goodvidio. This form offers options where the goodvidio
 */
function goodvidio_options()
{
    /**
     * Includes the files required for the information inputted to be
     * stored in the database.
     */
    goodvidio_include_files();
    global
        $goodvidioSettingsGroupName,
        $goodvidioIntegrationStatusOptionName,
        $goodvidioEcommerceTrackingOptionName,
        $goodvidioIntegrationDomainOptionName;
    /**
     * Gets the stored gallery position decided by the user in the database.
     */
    $integrationStatusValue = filter_var(get_option($goodvidioIntegrationStatusOptionName), FILTER_VALIDATE_BOOLEAN);
    $ecommerceTrackingValue = filter_var(get_option($goodvidioEcommerceTrackingOptionName), FILTER_VALIDATE_BOOLEAN);
    $integrationDomainValue = get_option($goodvidioIntegrationDomainOptionName);
?>
<div class="wrap">
    <h1>Goodvidio <span style="opacity:0.5;">></span> Integration settings</h1>

    <form method="post" action="options.php">
        <?php if ( isset($_GET['settings-updated']) ) { ?>
            <div id="message" class="updated notice is-dismissible">
                <p>Your changes has been saved succesfully.</p>
            </div>
        <?php } ?>
    <?php
        settings_fields($goodvidioSettingsGroupName);
        do_settings_sections($goodvidioSettingsGroupName);
    ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <h3>General</h3>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="<?php echo $goodvidioIntegrationStatusOptionName ?>">Enable Goodvidio integration: </label>
                    </th>
                    <td>
                        <select name="<?php echo $goodvidioIntegrationStatusOptionName ?>">
                            <option
                                value="true"
                                <?php echo $integrationStatusValue ? 'selected' : ''; ?>>
                                    Yes
                            </option>
                            <option
                                value="false"
                                <?php echo !$integrationStatusValue ? 'selected' : ''; ?>>
                                    No
                            </option>
                        </select>
                        <p class="description">
                            Select if you would like to enable or disable the Goodvidio integration.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="<?php echo $goodvidioIntegrationDomainOptionName; ?>">Goodvidio account domain: </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="<?php echo $goodvidioIntegrationDomainOptionName; ?>"
                            value="<?php echo $integrationDomainValue ?>">
                        <p class="description">
                            Enter the domain you used for creating your Goodvidio account.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <h3>Ecommerce transaction tracking</h3>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="<?php echo $goodvidioEcommerceTrackingOptionName ?>">Enable tracking: </label>
                    </th>
                    <td>
                        <select name="<?php echo $goodvidioEcommerceTrackingOptionName ?>">
                            <option
                                value="true"
                                <?php echo $ecommerceTrackingValue ? 'selected' : ''; ?>>
                                    Yes
                            </option>
                            <option
                                value="false"
                                <?php echo !$ecommerceTrackingValue ? 'selected' : ''; ?>>
                                    No
                            </option>
                        </select>
                        <p class="description">
                            Select if you would like to enable or disable the
                            ecommerce transaction tracking.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <?php submit_button(); ?>
        </p>
    </form>
</div>
    <?php
}

function goodvidio_update_settings()
{
    global $goodvidioSettingsGroupName;

    global $goodvidioIntegrationStatusOptionName;
    register_setting($goodvidioSettingsGroupName, $goodvidioIntegrationStatusOptionName);

    global $goodvidioIntegrationDomainOptionName;
    register_setting($goodvidioSettingsGroupName, $goodvidioIntegrationDomainOptionName);

    global $goodvidioEcommerceTrackingOptionName;
    register_setting($goodvidioSettingsGroupName, $goodvidioEcommerceTrackingOptionName);
}

/*
* This method is called when the order is placed, though not when the order's
* completed. It takes all products in the order placement, finds the category
* of each and sends the data to Goodvidio.
*/
function goodvidio_send_order($order_id) {

    global $goodvidioIntegrationStatusOptionName;
    global $goodvidioEcommerceTrackingOptionName;

    $integrationEnabled = filter_var(get_option($goodvidioIntegrationStatusOptionName), FILTER_VALIDATE_BOOLEAN);
    $trackingEnabled = filter_var(get_option($goodvidioEcommerceTrackingOptionName), FILTER_VALIDATE_BOOLEAN);

    if (!$integrationEnabled) {
        return;
    }
    if (!$trackingEnabled) {
        return;
    }

    $categories = '';
    $order = new WC_Order($order_id);
    $products = $order->get_items();
    $productsToSend = array();

    foreach ($products as $product) {
        $productInfo = array();

        $p = new WC_Product($product['product_id']);

        $productInfo['name'] = $product['name'];
        $productInfo['sku'] = $p->get_sku();
        $productInfo['price'] = $product['line_subtotal'];
        $productInfo['quantity'] = $product['qty'];
        $productInfo['category'] = '';

        array_push($productsToSend, $productInfo);
    }

    $order_id_to_send = (string) $order_id;

    $orderInformation = array(
        'id' => $order_id_to_send,
        'transactionData' => $productsToSend,
    );

    $orderInformation = json_encode($orderInformation);

    wp_localize_script('purchase', 'order_information', $orderInformation);

    echo "<script>
        if (typeof _gv === 'undefined') {
            var n = '_gv';
            var w = window;
            w.GoodvidioTrackingObject = n;
            if (!(n in w)) {
                w[n] = function() {
                    w[n].q.push(arguments);
                };
                w[n].q = [];
            }
            w[n].l = (new Date()).getTime();
        }

        _gv('send','purchase', " . $orderInformation . ");</script>";
}
