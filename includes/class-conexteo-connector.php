<?php

class ConexteoConnector {
    private $appId;
    private $apiKey;
    private $cronKey;

    public function __construct($appId, $apiKey, $cronKey)
    {
        $this->appId = $appId;
        $this->apiKey = $apiKey;
        $this->cronKey = $cronKey;
    }

    // check api connection
    public function checkConnection()
    {
        $response = $this->callAPI('users/credits', 'GET');

        if($response === false) {
            return false;
        }

        return true;
    }

    public function callAPI($url, $method, $params = [])
    {
        $curl = curl_init();

        // if($method === 'GET') {
        //     curl_setopt_array($curl, array(
        //         CURLOPT_URL => 'https://api.conexteo.com/' . $url,
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_ENCODING => '',
        //         CURLOPT_MAXREDIRS => 10,
        //         CURLOPT_TIMEOUT => 0,
        //         CURLOPT_FOLLOWLOCATION => true,
        //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //         CURLOPT_CUSTOMREQUEST => 'GET',
        //         CURLOPT_HTTPHEADER => array(
        //             'X-APP-ID: ' . $this->appId,
        //             'X-API-KEY: ' . $this->apiKey,
        //         ),
        //     ));
        // }

        // use wp http api
        if($method === 'GET') {
            $response = wp_remote_get('https://api.conexteo.com/' . $url, array(
                'headers' => array(
                    'X-APP-ID' => $this->appId,
                    'X-API-KEY' => $this->apiKey,
                ),
            ));

            if(is_wp_error($response)) {
                return false;
            }

            $httpCode = $response['response']['code'];
            $response = $response['body'];
        }

        // if post then add raw body with params
        // if($method === 'POST') {
        //     $data = http_build_query($params);

        //     curl_setopt_array($curl, array(
        //         CURLOPT_URL => 'https://api.conexteo.com/' . $url,
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_ENCODING => '',
        //         CURLOPT_MAXREDIRS => 10,
        //         CURLOPT_TIMEOUT => 0,
        //         CURLOPT_FOLLOWLOCATION => true,
        //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //         CURLOPT_CUSTOMREQUEST => 'POST',
        //         CURLOPT_POSTFIELDS => $data,
        //         CURLOPT_HTTPHEADER => array(
        //             'X-APP-ID: ' . $this->appId,
        //             'X-API-KEY: ' . $this->apiKey,
        //         ),
        //     ));
        // }

        // use wp http api
        if($method === 'POST') {
            $response = wp_remote_post('https://api.conexteo.com/' . $url, array(
                'headers' => array(
                    'X-APP-ID' => $this->appId,
                    'X-API-KEY' => $this->apiKey,
                ),
                'body' => $params,
            ));

            if(is_wp_error($response)) {
                return false;
            }

            $httpCode = $response['response']['code'];
            $response = $response['body'];
        }

        // if($method == 'DELETE') {
        //     curl_setopt_array($curl, array(
        //         CURLOPT_URL => 'https://api.conexteo.com/' . $url,
        //         CURLOPT_RETURNTRANSFER => true,
        //         CURLOPT_ENCODING => '',
        //         CURLOPT_MAXREDIRS => 10,
        //         CURLOPT_TIMEOUT => 0,
        //         CURLOPT_FOLLOWLOCATION => true,
        //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //         CURLOPT_CUSTOMREQUEST => 'DELETE',
        //         CURLOPT_HTTPHEADER => array(
        //             'X-APP-ID: ' . $this->appId,
        //             'X-API-KEY: ' . $this->apiKey,
        //         ),
        //     ));
        // }

        // use wp http api
        if($method === 'DELETE') {
            $response = wp_remote_request('https://api.conexteo.com/' . $url, array(
                'method' => 'DELETE',
                'headers' => array(
                    'X-APP-ID' => $this->appId,
                    'X-API-KEY' => $this->apiKey,
                ),
            ));

            if(is_wp_error($response)) {
                return false;
            }

            $httpCode = $response['response']['code'];
            $response = $response['body'];
        }

        // $response = curl_exec($curl);
        // $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // curl_close($curl);

        if ($httpCode != 200) {
            return false;
        }

        return $response;
    }

    public function checkCronKey($key)
    {
        if($key === $this->cronKey) {
            return true;
        }

        return false;
    }

    public function getAvailableCredits()
    {
        $response = $this->callAPI('users/credits', 'GET');

        if($response === false) {
            return false;
        }

        return json_decode($response, true);
    }

    public function getContactLists()
    {
        $response = $this->callAPI('contactlists', 'GET');

        if($response === false) {
            return false;
        }

        return json_decode($response, true);
    }

    public function createDefaultList()
    {
        $response = $this->callAPI('contactlists', 'POST', [
            'name' => 'WordPress',
        ]);

        if($response === false) {
            return false;
        }

        return json_decode($response, true);
    }

    public function getContactsInList($listId)
    {
        $response = $this->callAPI('contactlists/' . $listId . '/contacts-extract', 'GET');

        if($response === false) {
            return false;
        }

        return json_decode($response, true);
    }

    public function createContact($address, $listId)
    {
        if(isset($address['id_address'])) {
            unset($address['id_address']);
        }

        $payload = [
            "contactlist_id" => $listId,
            "contacts" => [
                $address
            ]
        ];

        return $this->callAPI('contacts', 'POST', $payload);
    }

    public function deleteContact($contactId)
    {
        return $this->callAPI('contacts/' . $contactId, 'DELETE');
    }

    public function sendMessageToList($listId, $sender, $message)
    {
        $payload = [
            "content" => $message,
            "sender" => $sender,
            "contactlist" => [$listId],
        ];

        return $this->callAPI('messages/contactlist', 'POST', $payload);
    }

    public function sendSMS(Array $recipients, $sender, $message, $scheduledate = null, $scheduletime = null)
    {
        $payload = [
            "content" => $message,
            "sender" => $sender,
            "recipients" => $recipients,
        ];

        if($scheduledate !== null) {
            $payload['scheduledate'] = $scheduledate;
        }

        if($scheduletime !== null) {
            $payload['scheduletime'] = $scheduletime;
        }

        return $this->callAPI('messages', 'POST', $payload);
    }

    public function scheduleSMS(Array $recipients, $sender, $message, $scheduledate, $scheduletime)
    {
        return $this->sendSMS($recipients, $sender, $message, $scheduledate, $scheduletime);
    }
}