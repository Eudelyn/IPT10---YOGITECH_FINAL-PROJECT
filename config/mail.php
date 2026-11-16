<?php
return [
    // log   = don't send, save each email to storage/mail/ (default, works offline)
    // brevo = Brevo Transactional Email REST API (free: 300 emails/day)
    // smtp  = any SMTP server, e.g. Gmail with an App Password
    // gmail = Gmail API (OAuth): real emails from your Gmail account to real inboxes
    // mailtrap = Mailtrap Email Sandbox API: every email lands in a fake online inbox,
    //            nothing reaches real people (best for demos and testing)
    'driver' => env('MAIL_DRIVER', 'log'),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@yogitech.test'),
        'name'    => env('MAIL_FROM_NAME', 'Yogitech'),
    ],

    'brevo' => [
        'url'     => env('BREVO_API_URL', 'https://api.brevo.com/v3/smtp/email'),
        'api_key' => env('BREVO_API_KEY', ''),
    ],

    // Gmail API: sends from your own Gmail account, so emails land in the inbox, not Spam.
    // One-time setup: see README "Gmail API", then run: php yogi gmail:auth
    'gmail' => [
        'client_id'     => env('GMAIL_CLIENT_ID', ''),
        'client_secret' => env('GMAIL_CLIENT_SECRET', ''),
        'refresh_token' => env('GMAIL_REFRESH_TOKEN', ''),
        'token_url'     => env('GMAIL_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'send_url'      => env('GMAIL_SEND_URL', 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send'),
        'auth_url'      => 'https://accounts.google.com/o/oauth2/v2/auth',
        'scope'         => 'https://www.googleapis.com/auth/gmail.send',
    ],

    'mailtrap' => [
        'url'      => env('MAILTRAP_API_URL', 'https://sandbox.api.mailtrap.io/api/send'),
        'token'    => env('MAILTRAP_API_TOKEN', ''),
        'inbox_id' => (string) env('MAILTRAP_INBOX_ID', ''),
    ],

    'smtp' => [
        'host'       => env('SMTP_HOST', 'smtp.gmail.com'),
        'port'       => (int) env('SMTP_PORT', 465),
        'encryption' => env('SMTP_ENCRYPTION', 'ssl'), // ssl (465), tls (587) or none
        'username'   => env('SMTP_USERNAME', ''),
        'password'   => (string) env('SMTP_PASSWORD', ''),
    ],
];
