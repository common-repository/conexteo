<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://conexteo.com
 * @since             1.0.0
 * @package           Conexteo - SMS for Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Conexteo - SMS for Woocommerce
 * Plugin URI:        https://conexteo.com
 * Description:       Notre module vous permet d'envoyer facilement des SMS à vos clients pour les informer de leur commande, d'un changement de status ou de tout autre message important. Synchronisation des contacts intégrée.
 * Version:           1.0.0
 * Author:            Conexteo
 * Author URI:        https://conexteo.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       conexteo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CONEXTEO_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-conexteo-activator.php
 */
function activate_conexteo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conexteo-activator.php';
	Conexteo_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-conexteo-deactivator.php
 */
function deactivate_conexteo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-conexteo-deactivator.php';
	Conexteo_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_conexteo' );
register_deactivation_hook( __FILE__, 'deactivate_conexteo' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-conexteo.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_conexteo() {

	$plugin = new Conexteo();
	$plugin->run();

}
run_conexteo();

require_once plugin_dir_path( __FILE__ ) . 'includes/class-conexteo-model.php';

add_action( 'woocommerce_order_status_pending', 'conexteo_order_status_change');
add_action( 'woocommerce_order_status_failed', 'conexteo_order_status_change');
add_action( 'woocommerce_order_status_on-hold', 'conexteo_order_status_change');
add_action( 'woocommerce_order_status_processing', 'conexteo_order_status_change');
add_action( 'woocommerce_order_status_completed', 'conexteo_order_status_change');
add_action( 'woocommerce_order_status_refunded', 'conexteo_order_status_change');
add_action( 'woocommerce_order_status_cancelled', 'conexteo_order_status_change');

function conexteo_order_status_change($order_id) {
	$conexteo_appid_setting = get_option('conexteo_appid_setting');
	$conexteo_keyid_setting = get_option('conexteo_keyid_setting');
	$conexteo_synccustomers_setting = get_option('conexteo_synccustomers_setting');
	$conexteo_cronkey_setting = get_option('conexteo_cronkey_setting');

	$connector = new ConexteoConnector($conexteo_appid_setting, $conexteo_keyid_setting, $conexteo_cronkey_setting);
	$connection_ok = $connector->checkConnection();

	if(!$connection_ok) {
		return false;
	}

	$order = wc_get_order($order_id);

	// get new order status
	$new_status = 'wc-' . $order->get_status();

	// check if there is model for this status
	$model = new ConexteoModel();
	$model = $model->getByStatusAndType($new_status, 'order');

	if($model) {
		$address = $order->data['billing'];

		$phone = $address['phone'];

		if($phone) {
			$sender = $model->sender;
			$message = $model->message;

			if($model->delay == 0) {
				$connector->sendSMS([$phone], $sender, $message);
			}
			else{
				$datetime = date('Y-m-d H:i:s', strtotime('+'.($model->delay * $model->delay_multiplier).' minute'));
				$date = date('Y-m-d', strtotime($datetime));
				$time = date('H:i:s', strtotime($datetime));

				$connector->scheduleSMS([$phone], $sender, $message, $date, $time);
			}
		}
	}
}

// add a div before order details
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'add_conexteo_message_form' );

function add_conexteo_message_form($order_id) {
	$order = new WC_Order( $order_id );
	$phone = $order->get_billing_phone();

	if($phone) {
	?>
		<div class="conexteo-order-details">
			<h3 class="card-header-title">
				Envoyer un SMS avec Conexteo
			</h3>
			<div id="conexteo_message"></div>
			<br />
			<input type="text" class="form-control" name="sender" placeholder="SENDER (11 caractères maximum)" id="conexteo_order-order-sender" maxlength="11" size="40" />
			<br /><br />
			<textarea class="form-control" name="message" placeholder="Votre message ..." rows="3" cols="40" id="conexteo_order-order-message"></textarea>
			<br />
			<span class="input-group-text conexteo_order-count-sms">0 carac.</span>
			<br /><br />
			<input class="form-check-input" type="checkbox" value="0" id="conexteo_order-order-stop">
			<label class="form-check-label" for="conexteo_order-order-stop">
				Inclure un STOP dans le message
			</label>
			<br /><br />
			<input type="button" class="button button-primary" value="Envoyer" id="conexteo_order-send" data-url="<?php echo get_site_url() . '/?conexteocron=send_message&key=' . esc_attr(get_option('conexteo_cronkey_setting')); ?>&recipient=<?php echo esc_html($phone); ?>" />
		</div>
	<?php
	}
}

add_action( 'init', 'conexteocron_init_internal' );
function conexteocron_init_internal()
{
    add_rewrite_rule( 'conexteocron.php$', 'index.php?conexteocron=', 'top' );
}

add_filter( 'query_vars', 'conexteocron_query_vars' );
function conexteocron_query_vars( $query_vars )
{
    $query_vars[] = 'conexteocron';
    return $query_vars;
}

add_action( 'parse_request', 'conexteocron_parse_request' );
function conexteocron_parse_request( &$wp )
{
    if ( array_key_exists( 'conexteocron', $wp->query_vars ) ) {
		if($wp->query_vars['conexteocron'] == 'carts') {
			include 'cron_carts.php';
		}
		elseif($wp->query_vars['conexteocron'] == 'sync') {
			include 'cron_sync.php';
		}
		elseif($wp->query_vars['conexteocron'] == 'send_message') {
			include 'send_message.php';
		}
        exit();
    }
    return;
}