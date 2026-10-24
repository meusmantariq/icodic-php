<?php
/**
 * Quantum Nexis configuration. Copy to config.php and edit, or run /install/ in the browser.
 * Never commit config.php with real credentials.
 */
return [
    'app' => [
        'name'      => 'Quantum Nexis',
        'url'       => 'APP_URL_PLACEHOLDER',   // no trailing slash
        'env'       => 'production',                // production | development
        'debug'     => false,
        'timezone'  => 'Asia/Dubai',
        'secret'    => 'APP_KEY_PLACEHOLDER',  // used for cookie/token signing
    ],
    'db' => [
        'host'     => 'DB_HOST_PLACEHOLDER',
        'port'     => 3306,
        'name'     => 'DB_NAME_PLACEHOLDER',
        'user'     => 'DB_USER_PLACEHOLDER',
        'pass'     => 'DB_PASS_PLACEHOLDER',
        'charset'  => 'utf8mb4',
        'socket'   => null,                          // e.g. /var/lib/mysql/mysql.sock (optional)
    ],
    'mail' => [
        'driver'     => 'mail',                      // mail | smtp | log
        'from_email' => 'noreply@example.com',
        'from_name'  => 'Quantum Nexis',
        'admin_email'=> 'ADMIN_EMAIL_PLACEHOLDER',// receives order / contact notifications
        'smtp' => [
            'host'       => 'smtp.example.com',
            'port'       => 587,
            'encryption' => 'tls',                   // tls | ssl | none
            'username'   => '',
            'password'   => '',
        ],
    ],
    'chat' => [
        // Optional. Any OpenAI-compatible endpoint (OpenAI, Groq, OpenRouter, local). Leave api_key empty to use the offline catalog assistant.
        'api_key'  => '',
        'base_url' => 'https://api.openai.com/v1',
        'model'    => 'gpt-4o-mini',
    ],
    'site' => [
        'whatsapp_number' => '971543517933',
        'review_url'      => 'https://g.page/r/CbCrFBjbHlpJEBM/review',
        'linkedin_url'    => 'https://www.linkedin.com/in/usman-tariq-89b612142/',
        'default_region'  => 'UAE',
        'default_lang'    => 'en',
    ],
    'uploads' => [
        'max_bytes' => 5 * 1024 * 1024,
        'allowed'   => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    ],
];
