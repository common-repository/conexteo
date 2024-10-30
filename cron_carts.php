<?php
// load WP
require_once plugin_dir_path(__FILE__) . 'includes/class-conexteo-model.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-db-exchange.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-conexteo-connector.php';

header('Content-Type: application/json');

function conexteo_displayAjax($status, $message) {
    if($status == 'error') {
        // header
        header('HTTP/1.1 400 Bad Request');
    }
    else {
        // header
        header('HTTP/1.1 200 OK');
    }

    echo json_encode(array(
        'status' => $status,
        'message' => $message
    ));
    exit;
}

$key = '';
if(isset($_GET['key'])) {
    // sanitize it
    $key = sanitize_text_field($_GET['key']);
}

if(empty($key)) {
    conexteo_displayAjax('error', 'No key provided');
}

$appId = get_option('conexteo_appid_setting');
$keyId = get_option('conexteo_keyid_setting');
$cronKey = get_option('conexteo_cronkey_setting');
$contactList = get_option('conexteo_contactlist_setting');

$connector = new ConexteoConnector($appId, $keyId, $cronKey);

// check if key is ok
if(!$connector->checkCronKey($key)) {
    conexteo_displayAjax('error', 'No valid key provided');
}

$success = conexteo_processModelsOfTypeCart($connector, $contactList);

if(!$success) {
    conexteo_displayAjax('error', 'Error while sending carts sms');
}
else {
    conexteo_displayAjax('success', 'Sending carts sms done');
}

function conexteo_processModelsOfTypeCart($connector, $contactList) {
    $models = conexteoDBExchange::getModelsOfType('cart');
    $success = true;

    foreach($models as $model) {
        if(!conexteo_conexteo_processCarts($connector, $contactList, $model)) {
            $success = false;
        }
    }

    return $success;
}

function conexteo_conexteo_processCarts($connector, $contactList, $model) {
    $minuts = $model['delay'] * $model['delay_multiplier'];
    $carts = conexteoDBExchange::getAbandonnedCartsSinceNMinutes($minuts);
    $success = true;

    foreach($carts as $cart) {
        if(!conexteo_processCart($connector, $contactList, $cart, $model)) {
            $success = false;
        }
    }

    return $success;
}


function conexteo_processCart($connector, $contactList, $cart, $model) {
    // global $connector;

    if(!$cart['phone']) {
        return true;
    }

    // check if cart is already in conexteo_cart_sms
    if(conexteoDBExchange::isCartAlreadyInTable($cart['id_cart'])) {
        return true;
    }

    // insert this cart into conexteo_cart_sms
    conexteoDBExchange::insertCartSms($cart['id_cart']);

    $phone = $cart['phone'];

    // send sms
    if(!$connector->sendSms([$phone], $model['sender'], $model['message'])) {
        return false;
    }
    else {
        return true;
    }
}