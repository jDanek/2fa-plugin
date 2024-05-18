<?php

use PragmaRX\Google2FA\Google2FA;
use Sunlight\Database\Database as DB;
use Sunlight\IpLog;
use Sunlight\Logger;
use Sunlight\Router;
use Sunlight\User;
use Sunlight\Util\Request;
use Sunlight\Util\Response;

return function (array $args) {

    if (empty($_SESSION['sl_tfa'])) {
        return;
    }

    $query = DB::queryRow('SELECT id, username, email, password, tfa_token FROM ' . DB::table('user') . ' WHERE id=' . DB::val($_SESSION['sl_tfa']['u_id']));

    $continue = false;

    if (isset($_POST['confirm'])) {
        $g2fa = new Google2FA();

        if (
            !$g2fa->verifyKey($query['tfa_token'], Request::post('otp', ''))
            || $query['email'] !== $_SESSION['sl_tfa']['u_email']
            || $query['password'] !== $_SESSION['sl_tfa']['u_pass']
        ) {
            IpLog::update(IpLog::FAILED_LOGIN_ATTEMPT);
            // log
            Logger::notice('2fa', sprintf('Failed two-factor login attempt for user "%s"', $query['username']), ['user_id' => $query['id']]);
            // redirect
            Response::redirect(Router::module('login', ['absolute' => true, 'query' => ['login_form_result' => $this::STATUS_INVALID_TFA]]));
            exit;
        }
        $continue = true;
    }

    if (!$continue) {
        return;
    }

    User::login(
        $_SESSION['sl_tfa']['u_id'],
        $_SESSION['changeset']['password'] ?? $_SESSION['sl_tfa']['u_pass'],
        $_SESSION['sl_tfa']['u_email'],
        $_SESSION['sl_tfa']['persistent']
    );

    unset($_SESSION['sl_tfa']);
    Response::redirect(Router::module('login', ['absolute' => true, 'query' => ['login_form_result' => User::LOGIN_SUCCESS]]));
    exit;
};
