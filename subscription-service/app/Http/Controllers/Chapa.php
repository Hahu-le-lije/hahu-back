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
        return config('chapa.chapaSecretKey');
    }

    public static function generateReference(?string $transactionPrefix = NULL)
    {
        if ($transactionPrefix) {
            return $transactionPrefix . '_' . uniqid(time());
        }

        $appName = preg_replace('/[^A-Za-z0-9_-]+/', '_', config('app.name', 'Laravel'));
        return $appName . '_chapa_' . uniqid(time());
    }

    public static function initializePayment(array $data): array
    {
        // Use the static helper and static property
        error_log("Initializing payment with data: " . json_encode($data). "\n");
        $response = Http::withToken(self::getSecretKey())->post(
            self::$baseUrl . '/transaction/initialize',
            $data
        );
        error_log("Chapa API response: " . $response . "\n");
        
        return $response->json();
    }

    public static function getTransactionIDFromCallback()
    {
        $transactionID = request()->trx_ref;

        if (!$transactionID) {
            $transactionID = json_decode(request()->resp)->data->id ?? null;
        }

        return $transactionID;
    }

    public static function verifyTransaction($id)
    {
        return Http::withToken(self::getSecretKey())->get(
            self::$baseUrl . "/transaction/verify/" . $id
        )->json();
    }

    public static function createTransfer(array $data)
    {
        return Http::withToken(self::getSecretKey())->post(
            self::$baseUrl . '/transfers',
            $data
        )->json();
    }

    public static function verifyTransfer($id)
    {
        return Http::withToken(self::getSecretKey())->get(
            self::$baseUrl . "/transfers/verify/" . $id
        )->json();
    }
}