<?php

class ConexteoDBExchange {
    public static function getAddresses()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        // get woocommerce addresses
        $sql = 'SELECT umeta_id, user_id, meta_key, meta_value
                FROM ' . $prefix . 'usermeta
                WHERE meta_key LIKE "billing_%"';

        $addresses = $wpdb->get_results($sql, ARRAY_A);

        $return = [];

        foreach($addresses as $key => $address) {
            if($address['meta_key'] === 'billing_address_1') {
                $return[$address['user_id']]['id_address'] = $address['umeta_id'];
            }
            $return[$address['user_id']][$address['meta_key']] = $address['meta_value'];
        }

        return $return;
    }

    public static function getDataAddress($address, $key = null)
    {
        return [
            'id_address' => $address['id_address'] ?? '',
            'champ_adresse' => $address['billing_address_1'] ?? '',
            'champ_cp' => $address['billing_postcode'] ?? '',
            'champ_date' => $address['birthday'] ?? '',
            'champ_mail' => $address['billing_email'] ?? '',
            'champ_nom' => $address['billing_last_name'] ?? '',
            'champ_prenom' => $address['billing_first_name'] ?? '',
            'champ_ville' => $address['billing_city'] ?? '',
            'tel' => $address['billing_phone'] ?? '',
        ];
    }

    public static function isAddressInTable($address, $contact_list_id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $address_sum = conexteoDBExchange::getAddressSum($address);
        $sql = 'SELECT * FROM '. $prefix .'conexteo_contacts WHERE email = "'.esc_sql($address['champ_mail']).'" AND contactlist_id = '.(int)$contact_list_id.' AND address_sum = "'.esc_sql($address_sum).'"';
        
        $result = $wpdb->get_row($sql, ARRAY_A);

        return $result;
    }

    public static function addAddress($address, $contact_list_id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $address_sum = conexteoDBExchange::getAddressSum($address);
        $sql = 'INSERT INTO '.$prefix.'conexteo_contacts (contactlist_id, id_address, email, address_sum) VALUES ('.(int)$contact_list_id.', '.(int)$address['id_address'].', "'.esc_sql($address['champ_mail']).'", "'.esc_sql($address_sum).'")';
        
        $wpdb->query($sql);

        return $wpdb->insert_id;
    }

    public static function getConexteoIdForAddress($address, $contact_list_id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $address_sum = conexteoDBExchange::getAddressSum($address);
        $sql = 'SELECT conexteo_id FROM '.$prefix.'conexteo_contacts WHERE contactlist_id = '.(int)$contact_list_id.' AND address_sum = "'.esc_sql($address_sum).'"';
        
        $result = $wpdb->get_row($sql, ARRAY_A);

        return $result;
    }

    public static function setConexteoIdForAddress($address, $contact_list_id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $address_sum = conexteoDBExchange::getAddressSum($address);
        $sql = 'UPDATE '.$prefix.'conexteo_contacts SET conexteo_id = '.(int)$address['id'].' WHERE email = "'.esc_sql($address['champ_mail']).'" AND address_sum = "'.esc_sql($address_sum).'" AND contactlist_id = '.(int)$contact_list_id.'';
        
        return $wpdb->query($sql);
    }

    public static function removeAddress($address, $contact_list_id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $address_sum = conexteoDBExchange::getAddressSum($address);
        $sql = 'DELETE FROM '.$prefix.'conexteo_contacts WHERE contactlist_id = '.(int)$contact_list_id.' AND address_sum = "'.esc_sql($address_sum).'"';
        
        return $wpdb->query($sql);
    }

    public static function getAddressSum($address)
    {
        $address_sum = md5($address['champ_adresse'].$address['champ_cp'].$address['champ_nom'].$address['champ_prenom'].$address['champ_ville']);

        return $address_sum;
    }

    public static function getAbandonnedCartsSinceNMinutes($minuts)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $carts = [];

        // get woocommerce abandoned carts since 3 hours
        $sql = 'SELECT * FROM '.$prefix.'woocommerce_sessions WHERE session_expiry > '.(time() - $minuts * 60).' AND session_value LIKE "%cart%";';
        $results = $wpdb->get_results($sql, ARRAY_A);

        foreach($results as $result) {
            $cart = unserialize($result['session_value']);

            $session_value = unserialize($result['session_value']);
            if(!isset($session_value['customer'])) {
                continue;
            }

            $customer = unserialize($session_value['customer']);

            $cart_return = [];
            $cart_return['id_cart'] = $result['session_key'];
            $cart_return['id_customer'] = $customer['id'];
            $cart_return['phone'] = $customer['phone'];

            $carts[] = $cart_return;
        }

        return $carts;
    }

    public static function getModelsOfType($type)
    {
        global $wpdb;
		$prefix = $wpdb->prefix;

        $sql = 'SELECT * FROM '.$prefix.'conexteo_models WHERE event_type = "'.esc_sql($type).'"';
        $models = $wpdb->get_results($sql, ARRAY_A);

        return $models;
    }

    public static function insertCartSms($id_cart)
    {
        global $wpdb;
		$prefix = $wpdb->prefix;

        $sql = 'INSERT INTO '.$prefix.'conexteo_cart_sms (id_cart) VALUES ('.(int)$id_cart.')';
        $wpdb->query($sql);
    }

    public static function isCartAlreadyInTable($id_cart)
    {
        global $wpdb;
		$prefix = $wpdb->prefix;

        $sql = 'SELECT * FROM '.$prefix.'conexteo_cart_sms WHERE id_cart = '.(int)$id_cart;
        $result = $wpdb->get_row($sql);

        return $result;
    }

    public static function getModels()
    {
        global $wpdb;
		$prefix = $wpdb->prefix;

        $sql = 'SELECT * FROM '. $prefix .'conexteo_models';
        $results = $wpdb->get_results($sql);

        return $results;
    }

    public static function getModel($id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $sql = 'SELECT * FROM '. $prefix .'conexteo_models WHERE id_model = '.(int)$id;
        $result = $wpdb->get_row($sql);

        return $result;
    }
}