<?php
namespace App;

class Main {
    public function __construct() { }
    public function Test() 
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox-merchant.revolut.com/api/orders',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS =>     10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "amount": 500,
                "currency": "GBP"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'Revolut-Api-Version: 2023-09-01',
                'Authorization: Bearer '.REVOLUT_API_SECRET_KEY,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        dd(json_decode($response), json_decode($response));
    }
}