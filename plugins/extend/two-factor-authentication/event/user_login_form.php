<?php

use Sunlight\Core;
use Sunlight\Router;
use Sunlight\Util\Form;
use Sunlight\Util\Request;

return function (array $args) {

    if (Request::get('login_form_result') != $this::STATUS_NEEDS_TFA || empty($_SESSION['sl_tfa'])) {
        return;
    }

    // form URL
    $form_url = Core::getCurrentUrl();
    if ($form_url->has('login_form_result')) {
        $form_url->remove('login_form_result');
    }

    $args['output'] = Form::render([
        'name' => 'login_form_tfa',
        'action' => Router::path('system/script/login.php', ['query' => ['_return' => $return_url ?? '']]),
        'form_append' => Form::input('hidden', 'login_form_url', $form_url->buildRelative()) . "\n",
    ],
        [
            [
                'label' => _lang('two-factor-authentication.login.form.otp'),
                'content' => Form::input('text', 'otp', null, [
                    'autocomplete' => 'one-time-code',
                    'autofocus' => true,
                    'class' => 'inputsmall',
                    'placeholder' => 'XXXXXX',
                    'required' => true,
                ]),
            ],
            [
                'label' => null,
                'content' => '<span class="hint">' . _lang('two-factor-authentication.login.form.otp.hint') . '</span>',
            ],
            Form::getSubmitRow([
                'text' => _lang('two-factor-authentication.login.form.submit'),
                'name' => 'confirm'
            ]),
        ]
    );
};
