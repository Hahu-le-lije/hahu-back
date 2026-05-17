<?php

/*
 * This file is part of the Chapa Laravel package.
 *
 * 
 */
return [


    /**
     * Secret Key: Your Chapa secretKey. Sign up on https://dashboard.chapa.co/ to get one from your settings page
     *
     */
    'chapaSecretKey' => env('CHAPA_SECRET_KEY'),


    /**
     * Webhook Secret: Your Chapa webhook secret for validating incoming events.
     */
    'chapaPublicKey' => env('CHAPA_PUBLIC_KEY'),


];