<?php

use Sunlight\Message;

return function (array $args) {
    if ($args['code'] === $this::STATUS_INVALID_TFA) {
        $args['value'] = Message::warning(_lang('two-factor-authentication.validate.failure'));
    }
};
