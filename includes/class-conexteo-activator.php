<?php

/**
 * Fired during plugin activation
 *
 * @link       https://ecomiz.com
 * @since      1.0.0
 *
 * @package    Conexteo
 * @subpackage Conexteo/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Conexteo
 * @subpackage Conexteo/includes
 * @author     EcomiZ <contact@ecomiz.com>
 */
class Conexteo_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		$sql = array();
		// get wordpress db prefix
		$prefix = $wpdb->prefix;
		// get wp db engine
		$engine = 'MyISAM';

		$sql[] = 'CREATE TABLE IF NOT EXISTS `' . $prefix . 'conexteo_contacts` (
			`id_conexteo` INT NOT NULL AUTO_INCREMENT ,
			`contactlist_id` INT NOT NULL,
			`id_address` INT NOT NULL,
			`email` VARCHAR(255) NOT NULL,
			`address_sum` VARCHAR(255) NOT NULL,
			`conexteo_id` INT(20) NULL,
			PRIMARY KEY (`id_conexteo`))
		ENGINE = ' . $engine . ' DEFAULT CHARSET=utf8;';

		$sql[] = 'CREATE TABLE IF NOT EXISTS `' . $prefix . 'conexteo_models` (
			`id_model` INT NOT NULL AUTO_INCREMENT ,
			`name` VARCHAR(255) NOT NULL ,
			`sender` VARCHAR(255) NOT NULL ,
			`message` VARCHAR(160) NOT NULL ,
			`stop` BOOLEAN NOT NULL ,
			`event_type` VARCHAR(20) NOT NULL ,
			`delay` INT(10) NOT NULL ,
			`delay_multiplier` INT(10) NOT NULL ,
			`status` VARCHAR(100) NOT NULL ,
			PRIMARY KEY (`id_model`))
		ENGINE = ' . $engine . ';';


		$sql[] = 'CREATE TABLE IF NOT EXISTS `' . $prefix . 'conexteo_cart_sms` (
			`id_cron` INT NOT NULL AUTO_INCREMENT ,
			`id_cart` INT(10) NOT NULL ,
			PRIMARY KEY (`id_cron`))
		ENGINE = ' . $engine . ';';

		foreach ($sql as $query) {
			$wpdb->query($query);
		}

		// insert in config a random value md5 for conexteo_cronkey_setting only if it does not exist yet
		if (get_option('conexteo_cronkey_setting') === false) {
			add_option('conexteo_cronkey_setting', md5(rand()));
		}
	}

}
