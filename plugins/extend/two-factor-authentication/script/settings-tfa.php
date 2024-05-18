<?php

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PragmaRX\Google2FA\Google2FA;
use Sunlight\Database\Database as DB;
use Sunlight\Logger;
use Sunlight\Message;
use Sunlight\Router;
use Sunlight\Settings;
use Sunlight\User;
use Sunlight\Util\Form;
use Sunlight\Util\Request;

defined('SL_ROOT') or exit;

function isVerifiedPassword(): bool
{
    return isset($_SESSION['verified_password']) && $_SESSION['verified_password'] > time();
}

$g2fa = new Google2FA();

// verify tfa token with app
if (isset($_POST['savetoken'])) {

    $changeset = [];

    // remove token
    if (isset($_POST['remove_tfa_token'])) {
        $changeset['tfa_token'] = null;
        // log
        Logger::notice('2fa', sprintf('Removed two-factor authentication for user "%s"', User::getUsername()));
    }

    // add token
    if (isset($_SESSION['tmp_2fa_token'])) {
        if ($g2fa->verifyKey($_SESSION['tmp_2fa_token'], Request::post('otp', ''))) {
            $changeset['tfa_token'] = $_SESSION['tmp_2fa_token'];
            // log
            Logger::notice('2fa', sprintf('Added two-factor authentication for user "%s"', User::getUsername()));
        } else {
            $_index->redirect(Router::module('settings', ['query' => ['action' => 'tfa', 'failed' => 1], 'absolute' => true]));
        }
    }

    // update changes
    if (!empty($changeset)) {
        DB::update('user', 'id=' . User::getId(), $changeset);

        // remove token from session
        unset($_SESSION['tmp_2fa_token']);

        $_index->redirect(Router::module('settings', ['query' => ['action' => 'tfa', ($changeset['tfa_token'] === null ? 'deactivated' : 'activated') => 1], 'absolute' => true]));
    }
}

// verify password for set 2fa
if (isset($_POST['verifypass'])) {
    $errors = [];

    if (!User::checkPassword(Request::post('current_password', ''))) {
        $errors[] = _lang('mod.settings.password.error.bad_current');
    }

    if (!empty($errors)) {
        $output .= Message::list($errors);
    } else {
        $_SESSION['verified_password'] = (time() + (60 * 5));
    }
}

// settings for tfa
if (isVerifiedPassword()) {

    if (User::$data['tfa_token'] === null) {
        $userTfaToken = $_SESSION['tmp_2fa_token'] ?? ($_SESSION['tmp_2fa_token'] = $g2fa->generateSecretKey(32));

        $qrOptions = new QROptions();
        //$qrOptions->addQuietzone = false; // border?

        $qrCode = new QRCode($qrOptions);

        $encodedQrData = $qrCode->render($g2fa->getQRCodeUrl(
            Settings::get('title'),
            User::$data['email'],
            $userTfaToken
        ));

        $output .= '<p>' . _lang('two-factor-authentication.settings.info') . '</p>';
    }

    if (isset($_GET['activated'])) {
        $output .= Message::ok(_lang('two-factor-authentication.validate.activated'));
    } elseif (isset($_GET['deactivated'])) {
        $output .= Message::ok(_lang('two-factor-authentication.validate.deactivated'));
    } elseif (isset($_GET['failed'])) {
        $output .= Message::error(_lang('two-factor-authentication.validate.failure'));
    }

    $userHasTfaToken = User::$data['tfa_token'] !== null;

    $output .= Form::render(
        [
            'name' => 'user_settings_tfa_set_token',
            'table_attrs' => ' class="profiletable"',
            'form_prepend' => '<fieldset><legend>' . _lang('two-factor-authentication.settings.tfa') . '</legend>',
            'form_append' => '</fieldset>'
                . Form::input('submit', 'savetoken',
                    _lang('two-factor-authentication.settings.button.' . ($userHasTfaToken ? 'deactivate' : 'activate')),
                    ['class' => 'btn', 'onclick' => $userHasTfaToken ? 'return Sunlight.confirm();' : 'return true;']
                )
        ],
        $userHasTfaToken
            ? [ // reset 2fa
            [
                'label' => _lang('two-factor-authentication.settings.tfa.is_active'),
                'content' => Form::input('hidden', 'remove_tfa_token', '1'),
            ]
        ]
            : [ // set 2fa
            [
                'label' => _lang('two-factor-authentication.settings.qrcode'),
                'content' => '<img src="' . $encodedQrData . '" alt="QR Code">',
                'top' => true,
            ],
            [
                'label' => _lang('two-factor-authentication.settings.qrcode.text'),
                'content' => chunk_split($userTfaToken, 4, ' '),
            ],
            [
                'content' => '<div class="hr"><hr></div>',
            ],
            [
                'label' => _lang('two-factor-authentication.settings.otp'),
                'content' => Form::input('text', 'otp', null, [
                        'autocomplete' => 'one-time-code',
                        'autofocus' => true,
                        'class' => 'inputsmall',
                        'placeholder' => 'XXXXXX',
                        'required' => true,
                    ])
                    . '<span class="hint">(' . _lang('two-factor-authentication.settings.otp.hint') . ')</span>',
            ],
        ]
    );
}

// authentication or invalid password
if (!isVerifiedPassword()) {
    $output .= Form::render(
        [
            'name' => 'user_settings_tfa_verify_pass',
            'table_attrs' => ' class="profiletable"',
            'form_prepend' => '<fieldset><legend>' . _lang('two-factor-authentication.settings.tfa') . '</legend>',
            'form_append' => '</fieldset>'
                . Form::input('submit', 'verifypass', _lang('global.continue'))
        ],
        [
            [
                'content' => '<p>' . _lang('two-factor-authentication.settings.addinfo') . '</p>',
            ],
            [
                'label' => _lang('mod.settings.password.current'),
                'content' => Form::input('password', 'current_password', null, ['class' => 'inputsmall', 'autocomplete' => 'off']),
            ],
        ]
    );
}
