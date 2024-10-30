<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ecomiz.com
 * @since      1.0.0
 *
 * @package    Conexteo
 * @subpackage Conexteo/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Conexteo
 * @subpackage Conexteo/admin
 * @author     EcomiZ <contact@ecomiz.com>
 */

require plugin_dir_path(__FILE__) . '../includes/class-conexteo-connector.php';
require plugin_dir_path(__FILE__) . '../includes/class-db-exchange.php';

class Conexteo_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $connector;
	private $connection_ok;
	private $options_contact_lists;
	private $contact_list_exists;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('admin_menu', array($this, 'addPluginAdminMenu'), 9);
		add_action('admin_init', array($this, 'registerAndBuildFields'));
	}

	public function register_settings()
	{
		register_setting('conexteo_plugin_options', 'conexteo_setting__options', 'conexteo_setting__options_validate');
		add_settings_section('api_settings', 'API Settings', array($this, 'plugin_section_text'), 'conexteo_setting_');

		add_settings_field('plugin_setting_api_key', 'API Key', array($this, 'plugin_setting_api_key'), 'conexteo_setting_', 'api_settings');
	}

	public function plugin_section_text()
	{
		echo '<p>Here you can set all the options for using the API</p>';
	}

	public function plugin_setting_api_key()
	{
		$options = get_option('example_plugin_options');
		echo "<input id='plugin_setting_api_key' name='example_plugin_options[api_key]' type='text' value='" . esc_attr($options['api_key']) . "' />";
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Conexteo_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Conexteo_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/conexteo-admin.css', array(), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Conexteo_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Conexteo_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/conexteo-admin.js', array('jquery'), $this->version, false);

	}

	public function addPluginAdminMenu()
	{

		//add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page($this->plugin_name, 'Conexteo', 'administrator', $this->plugin_name, null, 'dashicons-email', 26);

		// //add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		add_submenu_page($this->plugin_name, 'Configuration de Conexteo', 'Configuration de Conexteo', 'administrator', $this->plugin_name . '-settings', array($this, 'displayConfigPage'));

		add_submenu_page($this->plugin_name, 'Modèles de SMS', 'Modèles de SMS', 'administrator', $this->plugin_name . '-models', array($this, 'displayModelsPage'));

		add_submenu_page($this->plugin_name, 'Nouveau modèle de SMS', 'Nouveau modèle', 'administrator', $this->plugin_name . '-new-model', array($this, 'displayNewModelPage'));

		// remove first menu item
		remove_submenu_page($this->plugin_name, $this->plugin_name);
	}

	public function displayConfigPage()
	{
		// set this var to be used in the settings-display view
		$active_tab = 'general';
		if(isset($_GET['tab'])) {
			// sanitize it
			$active_tab = sanitize_text_field($_GET['tab']);
		}
		if (isset($_GET['error_message'])) {
			add_action('admin_notices', array($this, 'pluginNameSettingsMessages'));
			do_action('admin_notices', sanitize_text_field($_GET['error_message']));
		}

		$connection_ok = $this->connection_ok;
		$sync_customers = get_option('conexteo_synccustomers_setting') && $connection_ok;

		$cron_cart_url = get_site_url() . '/?conexteocron=carts&key=' . esc_attr(get_option('conexteo_cronkey_setting'));
		$cron_sync_url = get_site_url() . '/?conexteocron=sync&key=' . esc_attr(get_option('conexteo_cronkey_setting'));
		$send_message_url = get_site_url() . '/?conexteocron=send_message&key=' . esc_attr(get_option('conexteo_cronkey_setting'));

		if($connection_ok) {
			if(isset($_GET['create-default-list']) && (!$list || $this->contact_list_exists == false)) {
				$list_created = $this->connector->createDefaultList();
				if($list_created) {
					update_option('conexteo_contactlist_setting', $list_created['id']);
					// refresh page and remove get param create-default-list
					$redirect_url = remove_query_arg('create-default-list');
					Header('Location: ' . $redirect_url);
					exit;
				}
			}

			$credits = $this->connector->getAvailableCredits()[0] ?? null;
			$lists = $this->options_contact_lists;
		}

		require_once 'partials/conexteo-admin-settings-display.php';
	}

	public function displayModelsPage()
	{
		global $wpdb;
		if(isset($_GET['action'])) {
			$action = sanitize_text_field($_GET['action']);
			if($action == 'delete') {
				$id = sanitize_text_field($_GET['id']);
				$result = $wpdb->delete($wpdb->prefix . 'conexteo_models', array('id_model' => $id));
				if($result) {
					$message = '<div class="notice notice-success settings-error is-dismissible"> 
						<p><strong>Modèle supprimé avec succès.</strong></p>
						<button type="button" class="notice-dismiss"><span class="screen-reader-text">Ignorer cette notification.</span></button>
					</div>';
				}
				else {
					$message = '<div class="notice notice-error settings-error is-dismissible"> 
						<p><strong>Une erreur est survenue lors de la suppression du modèle.</strong></p>
						<button type="button" class="notice-dismiss"><span class="screen-reader-text">Ignorer cette notification.</span></button>
					</div>';
				}
			}
		}

		$models = conexteoDBExchange::getModels();

		require_once 'partials/conexteo-admin-models-display.php';
	}

	public function displayNewModelPage()
	{
		if(!isset($_GET['model_id'])) {
			require_once 'partials/conexteo-admin-new-model-display.php';
		}
		else{
			$model_id = sanitize_text_field($_GET['model_id']);
			$model = conexteoDBExchange::getModel($model_id);

			if($model) {
				require_once 'partials/conexteo-admin-new-model-display.php';
			}
			else{
				Header('Location: ' . admin_url('admin.php?page=conexteo-models'));
				exit;
			}
		}
	}

	public function pluginNameSettingsMessages($error_message)
	{
		switch ($error_message) {
			case '1':
				$message = __('There was an error adding this setting. Please try again.', 'my-text-domain');
				$err_code = esc_attr('conexteo_appid_setting');
				$setting_field = 'conexteo_appid_setting';
				break;
		}
		$type = 'error';
		add_settings_error(
			$setting_field,
			$err_code,
			$message,
			$type
		);
	}

	public function registerAndBuildFields()
	{
		/**
		 * First, we add_settings_section. This is necessary since all future settings must belong to one.
		 * Second, add_settings_field
		 * Third, register_setting
		 */
		add_settings_section(
			'conexteo_general_section',
			'',
			array($this, 'conexteo_display_general_account'),
			'conexteo_general_settings'
		);

		// get values for field
		$conexteo_appid_setting = get_option('conexteo_appid_setting');
		$conexteo_keyid_setting = get_option('conexteo_keyid_setting');
		$conexteo_synccustomers_setting = get_option('conexteo_synccustomers_setting');
		$conexteo_cronkey_setting = get_option('conexteo_cronkey_setting');

		$settings = array(
			'conexteo_appid_setting',
			'conexteo_keyid_setting',
		);

		unset($args);

		$args = array(
			'appid' => array(
				'type' => 'input',
				'subtype' => 'text',
				'id' => 'conexteo_appid_setting',
				'name' => 'conexteo_appid_setting',
				'required' => 'required',
				'get_options_list' => '',
				'value_type' => 'normal',
				'wp_data' => 'option',
				'label' => 'Votre appID',
			),

			'keyid' => array(
				'type' => 'input',
				'subtype' => 'text',
				'id' => 'conexteo_keyid_setting',
				'name' => 'conexteo_keyid_setting',
				'required' => 'required',
				'get_options_list' => '',
				'value_type' => 'normal',
				'wp_data' => 'option',
				'label' => 'Votre keyID',
			),
		);

		if($conexteo_appid_setting && $conexteo_keyid_setting) {
			$this->connector = new ConexteoConnector($conexteo_appid_setting, $conexteo_keyid_setting, $conexteo_cronkey_setting);
			$this->connection_ok = $this->connector->checkConnection();

			if($this->connection_ok) {
				$args['synccustomers'] = array(
					'type' => 'input',
					'subtype' => 'checkbox',
					'id' => 'conexteo_synccustomers_setting',
					'name' => 'conexteo_synccustomers_setting',
					'required' => '',
					'get_options_list' => '',
					'value_type' => 'normal',
					'wp_data' => 'option',
					'label' => 'Activer la synchro clients',
				);

				$args['cronkey'] = array(
					'type' => 'input',
					'subtype' => 'text',
					'id' => 'conexteo_cronkey_setting',
					'name' => 'conexteo_cronkey_setting',
					'required' => '',
					'get_options_list' => '',
					'value_type' => 'normal',
					'wp_data' => 'option',
					'label' => 'Clé CRON',
				);

				$settings[] = 'conexteo_synccustomers_setting';
				$settings[] = 'conexteo_cronkey_setting';

				if($conexteo_synccustomers_setting == 1) {
					$contact_lists = $this->connector->getContactLists();

					$this->options_contact_lists = [];

					foreach($contact_lists as $list) {
						$this->options_contact_lists[$list['id']] = $list['name'];
					}

					if(is_array($this->options_contact_lists)) {
						$list = get_option('conexteo_contactlist_setting');
						foreach($contact_lists as $contact_list) {
							if($contact_list['id'] == $list) {
								$this->contact_list_exists = true;
							}
						}
					}

					if($this->options_contact_lists) {
						$args['contactlist'] = array(
							'type' => 'select',
							'id' => 'conexteo_contactlist_setting',
							'name' => 'conexteo_contactlist_setting',
							'required' => '',
							'get_options_list' => '',
							'value_type' => 'normal',
							'wp_data' => 'option',
							'label' => 'Liste de contacts',
							'get_options_list' => $this->options_contact_lists,
						);

						$settings[] = 'conexteo_contactlist_setting';
					}
					else{
						add_settings_error(
							'conexteo_contactlist_setting',
							'403',
							'Aucune liste de contacts n\'est disponible. Créez en une via votre compte Conextio ou bien <a href="?page=conexteo-settings&create-default-list=1">en créer une par défaut</a> puis actualisez cette page pour la sélectionner.',
							'error'
						);
					}

					if($this->contact_list_exists !== true) {
						add_settings_error(
							'conexteo_contactlist_setting',
							'403',
							'La liste de contacts précédemment sélectionnée n\'existe plus. Veuillez en sélectionner une autre ou bien <a href="?page=conexteo-settings&create-default-list=1">en créer une par défaut</a> puis actualisez cette page pour la sélectionner.',
							'error'
						);
					}
				}
			}
		}

		foreach ($args as $arg) {
			add_settings_field(
				$arg['id'],
				$arg['label'] . ' :',
				array($this, 'conexteo_render_settings_field'),
				'conexteo_general_settings',
				'conexteo_general_section',
				$arg
			);
		}
		
		foreach($settings as $setting) {
			register_setting(
				'conexteo_general_settings',
				$setting,
				array()
			);
		}
	}

	public function conexteo_display_general_account()
	{
		echo '<p>Configuration de la conexion à l\'API.</p>';
	}

	public function conexteo_render_settings_field($args)
	{
		/* EXAMPLE INPUT
		'type'      => 'input',
		'subtype'   => '',
		'id'    => $this->plugin_name.'_example_setting',
		'name'      => $this->plugin_name.'_example_setting',
		'required' => 'required="required"',
		'get_option_list' => "",
		'value_type' = serialized OR normal,
		'wp_data'=>(option or post_meta),
		'post_id' =>
		*/
		if ($args['wp_data'] == 'option') {
			$wp_data_value = get_option($args['name']);
		} elseif ($args['wp_data'] == 'post_meta') {
			$wp_data_value = get_post_meta($args['post_id'], $args['name'], true);
		}

		switch ($args['type']) {

			case 'input':
				$value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
				if ($args['subtype'] != 'checkbox') {
					$prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">' . esc_html($args['prepend_value']) . '</span>' : '';
					$prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
					$step = (isset($args['step'])) ? 'step="' . esc_html($args['step']) . '"' : '';
					$min = (isset($args['min'])) ? 'min="' . esc_html($args['min']) . '"' : '';
					$max = (isset($args['max'])) ? 'max="' . esc_html($args['max']) . '"' : '';
					if (isset($args['disabled'])) {
						// hide the actual input bc if it was just a disabled input the informaiton saved in the database would be wrong - bc it would pass empty values and wipe the actual information
						echo esc_html($prependStart) . '<input type="' . esc_attr($args['subtype']) . '" id="' . esc_attr($args['id']) . '_disabled" ' . esc_html($step) . ' ' . esc_html($max) . ' ' . esc_html($min) . ' name="' . esc_html($args['name']) . '_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="' . esc_html($args['id']) . '" ' . esc_html($step) . ' ' . esc_html($max) . ' ' . esc_html($min) . ' name="' . esc_attr($args['name']) . '" size="40" value="' . esc_attr($value) . '" />' . esc_html($prependEnd);
					} else {
						echo esc_html($prependStart) . '<input type="' . esc_attr($args['subtype']) . '" id="' . esc_attr($args['id']) . '" ' . esc_html($args['required']) . ' ' . esc_html($step) . ' ' . esc_html($max) . ' ' . esc_html($min) . ' name="' . esc_attr($args['name']) . '" size="40" value="' . esc_attr($value) . '" />' . esc_html($prependEnd);
					}
				} else {
					$checked = ($value) ? 'checked' : '';
					echo '<input type="' . esc_html($args['subtype']) . '" id="' . esc_html($args['id']) . '" ' . esc_html($args['required']) . ' name="' . esc_html($args['name']) . '" size="40" value="1" ' . esc_attr($checked) . ' />';
				}
				break;
			case 'select':
				$selected_value = ($args['value_type'] == 'serialized') ? unserialize($wp_data_value) : $wp_data_value;
				$options = $args['get_options_list'];
				echo '<select id="' . esc_html($args['id']) . '" ' . esc_html($args['required']) . ' name="' . esc_html($args['name']) . '">';
				foreach ($options as $key => $value) {
					$selected = ($selected_value == $key) ? 'selected' : '';
					echo '<option value="' . esc_attr($key) . '" ' . esc_html($selected) . '>' . esc_html($value) . '</option>';
				}
				echo '</select>';
				break;
			default:
				# code...
				break;
		}
	}
}