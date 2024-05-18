<?php

use Sunlight\Util\Arr;

return function (array $args) {
    Arr::insertAfter($args['actions'], 'password', [
        'tfa' => [
            'title' => _lang('two-factor-authentication.settings.tfa'),
            'script' => __DIR__ . '/../script/settings-tfa.php'
        ]
    ]);
};
