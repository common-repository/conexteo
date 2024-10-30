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

$adresses = conexteoDBExchange::getAddresses();
// var_dump($adresses);

$success = conexteo_syncAddresses($connector, $contactList, $adresses);

if(!$success) {
    conexteo_displayAjax('error', 'Error while syncing customers addresses');
}
else {
    conexteo_displayAjax('success', 'Syncing customers addresses done');
}

function conexteo_syncAddresses($connector, $contactList, $adresses) {
    // global $connector, $contactList;

    $success = true;
    $added = 0;

    // array map data addresses
    $adresses = array_map(function($address) {
        return conexteoDBExchange::getDataAddress($address);
    }, $adresses);

    foreach($adresses as $address) {
        if(!conexteoDBExchange::isAddressInTable($address, $contactList)) {
            conexteoDBExchange::addAddress($address, $contactList);

            $successQuery = $connector->createContact($address, $contactList);

            if(!$successQuery) {
                $success = false;
                conexteoDBExchange::removeAddress($address, $contactList);
            }
            else {
                $added++;
            }
        }
        if(!$success) {
            // quit the loop
            break;
        }
    }
    conexteo_syncAddressesWithConexteoIds($connector, $contactList);
    conexteo_deleteRemovedAddresses($connector, $contactList, $adresses);

    return $success;
}

function conexteo_syncAddressesWithConexteoIds($connector, $contactList) {
    // global $connector, $contactList;
    $success = true;

    $addresses = $connector->getContactsInList($contactList);
    foreach($addresses as $address) {
        conexteoDBExchange::setConexteoIdForAddress($address, $contactList);
    }

    return $success;
}

function conexteo_deleteRemovedAddresses($connector, $contactList, $adresses) {
    // global $connector, $contactList;
    $deleted = 0;

    // get all addresses in api
    $addresses = $connector->getContactsInList($contactList);

    foreach($addresses as $address) {
        $address = conexteoDBExchange::getDataAddress($address);

        // if address is not db
        if(!conexteoDBExchange::isAddressInTable($address, $contactList)) {
            $conexteo_id = conexteoDBExchange::getConexteoIdForAddress($address, $contactList);
            if($conexteo_id) {
                $conexteo_id = $conexteo_id['conexteo_id'];
                // delete address in api
                $success = $connector->deleteContact($conexteo_id);

                if($success) {
                    conexteoDBExchange::removeAddress($address, $contactList);
                    $deleted++;
                }
            }
        }
    }

    return $deleted;
}