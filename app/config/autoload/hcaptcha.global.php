<?php

declare(strict_types=1);

return [
    'hcaptcha' => [
        'site_key' => getenv('HCAPTCHA_SITE_KEY') ?: '517aa85e-9192-431e-b220-1ed487067446',
        'secret' => getenv('HCAPTCHA_SECRET') ?: 'ES_10818f73639b4508b5ac6f989bd2d991',
    ],
];
