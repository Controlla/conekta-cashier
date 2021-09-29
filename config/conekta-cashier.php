<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conekta Keys
    |--------------------------------------------------------------------------
    |
    | The Conekta publishable key and secret key give you access to Conekta's
    | API. The "publishable" key is typically used when interacting with
    | Conekta.js while the "secret" key accesses private API endpoints.
    |
    */

    'key' => env('CONEKTA_KEY'),

    'secret' => env('CONEKTA_SECRET'),



    /*
    |--------------------------------------------------------------
    | Billable table
    |--------------------------------------------------------------
    |
    |
    */
    'table' => 'users',


    /*
    |--------------------------------------------------------------------------
    | Cashier Path
    |--------------------------------------------------------------------------
    |
    | This is the base URI path where Cashier's views, such as the payment
    | verification screen, will be available from. You're free to tweak
    | this path according to your preferences and application design.
    |
    */

    'path' => env('CASHIER_PATH', 'conekta'),

    /*
    |--------------------------------------------------------------------------
    | Conekta Webhooks
    |--------------------------------------------------------------------------
    |
    | Your Conekta webhook secret is used to prevent unauthorized requests to
    | your Conekta webhook handling controllers. The tolerance setting will
    | check the drift between the current time and the signed request's.
    |
    */

    'webhook' => [
        'secret' => env('CONEKTA_WEBHOOK_SECRET'),
        'tolerance' => env('CONEKTA_WEBHOOK_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency that will be used when generating charges
    | from your application. Of course, you are welcome to use any of the
    | various world currencies that are currently supported via Conekta.
    |
    */

    'currency' => env('CASHIER_CURRENCY', 'mxn'),

    /*
    |--------------------------------------------------------------------------
    | Currency Locale
    |--------------------------------------------------------------------------
    |
    | This is the default locale in which your money values are formatted in
    | for display. To utilize other locales besides the default en locale
    | verify you have the "intl" PHP extension installed on the system.
    |
    */

    'currency_locale' => env('CASHIER_CURRENCY_LOCALE', 'es'),

    /*
    |--------------------------------------------------------------------------
    | Payment Confirmation Notification
    |--------------------------------------------------------------------------
    |
    | If this setting is enabled, Cashier will automatically notify customers
    | whose payments require additional verification. You should listen to
    | Conekta's webhooks in order for this feature to function correctly.
    |
    */

    'payment_notification' => env('CASHIER_PAYMENT_NOTIFICATION'),

    /*
    |--------------------------------------------------------------------------
    | Invoice Paper Size
    |--------------------------------------------------------------------------
    |
    | This option is the default paper size for all invoices generated using
    | Cashier. You are free to customize this settings based on the usual
    | paper size used by the customers using your Laravel applications.
    |
    | Supported sizes: 'letter', 'legal', 'A4'
    |
    */

    'paper' => env('CASHIER_PAPER', 'letter'),

    /*
    |--------------------------------------------------------------------------
    | Conekta Logger
    |--------------------------------------------------------------------------
    |
    | This setting defines which logging channel will be used by the Conekta
    | library to write log messages. You are free to specify any of your
    | logging channels listed inside the "logging" configuration file.
    |
    */

    'logger' => env('CASHIER_LOGGER'),

];