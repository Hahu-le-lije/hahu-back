<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class Chapa
{
    // Define the base URL as a static property since it's constant
    protected static $baseUrl = 'https://api.chapa.co/v1';

    /**
     * Helper to get the secret key since static classes don't use constructors
     */
    protected static function getSecretKey()
    {
        error_log("Chapa::getSecretKey - Retrieving secret key from config.");
        return config('chapa.chapaSecretKey');
    }

    public static function generateReference(?string $transactionPrefix = NULL)
    {
        error_log("Chapa::generateReference - Method started. Provided prefix: " . ($transactionPrefix ?? 'NONE'));

        if ($transactionPrefix) {
            error_log("Chapa::generateReference - Using custom transaction prefix.");
            return $transactionPrefix . '_' . uniqid(time());
        }

        error_log("Chapa::generateReference - No prefix provided, generating from app name.");
        $appName = preg_replace('/[^A-Za-z0-9_-]+/', '_', config('app.name', 'Laravel'));
        $reference = $appName . '_chapa_' . uniqid(time());
        
        error_log("Chapa::generateReference - Generated reference: " . $reference);
        return $reference;
    }

    public static function initializePayment(array $data): array
    {
        // Use the static helper and static property
        error_log("Chapa::initializePayment - Initializing payment with data: " . json_encode($data));
        
        $response = Http::withToken(self::getSecretKey())->post(
            self::$baseUrl . '/transaction/initialize',
            $data
        );
        
        error_log("Chapa::initializePayment - Chapa API response: " . $response->body());
        
        return $response->json();
    }

    public static function getTransactionIDFromCallback()
    {
        error_log("Chapa::getTransactionIDFromCallback - Method started.");
        
        $transactionID = request()->trx_ref;

        if (!$transactionID) {
            error_log("Chapa::getTransactionIDFromCallback - 'trx_ref' not found in request, attempting to parse from 'resp' JSON.");
            $transactionID = json_decode(request()->resp)->data->id ?? null;
        } else {
            error_log("Chapa::getTransactionIDFromCallback - 'trx_ref' found in request.");
        }

        error_log("Chapa::getTransactionIDFromCallback - Resolved Transaction ID: " . ($transactionID ?? 'NULL'));
        
        return $transactionID;
    }

    public static function verifyTransaction($id)
    {
        error_log("Chapa::verifyTransaction - Method started for ID: " . $id);
        
        $response = Http::withToken(self::getSecretKey())->get(
            self::$baseUrl . "/transaction/verify/" . $id
        );

        error_log("Chapa::verifyTransaction - Chapa API response body: " . $response->body());
        
        return $response->json();
    }

    public static function createTransfer(array $data)
    {
        error_log("Chapa::createTransfer - Method started. Transfer data: " . json_encode($data));
        
        $response = Http::withToken(self::getSecretKey())->post(
            self::$baseUrl . '/transfers',
            $data
        );

        error_log("Chapa::createTransfer - Chapa API response body: " . $response->body());
        
        return $response->json();
    }

    public static function verifyTransfer($id)
    {
        error_log("Chapa::verifyTransfer - Method started for transfer ID: " . $id);
        
        $response = Http::withToken(self::getSecretKey())->get(
            self::$baseUrl . "/transfers/verify/" . $id
        );

        error_log("Chapa::verifyTransfer - Chapa API response body: " . $response->body());
        
        return $response->json();
    }
}