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

$recipient = sanitize_text_field($_GET['recipient']);
$sender = sanitize_text_field($_POST['sender']);
$message = sanitize_text_field($_POST['message']);

if(empty($sender)) {
    conexteo_displayAjax('error', 'No sender provided');
}

if(empty($message)) {
    conexteo_displayAjax('error', 'No message provided');
}

if(empty($recipient)) {
    $send = $connector->sendMessageToList($contactList, $sender, $message);
}
else {
    $send = $connector->sendSMS([$recipient], $sender, $message);
}

if(!$send) {
    conexteo_displayAjax('error', 'Error while sending message');
}

$send = json_decode($send, true);

conexteo_displayAjax('success', $send['credits_used']);