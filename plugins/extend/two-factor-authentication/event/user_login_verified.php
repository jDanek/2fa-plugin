<?php

use Sunlight\Database\Database as DB;

return function (array $args) {

    $query = DB::queryRow('SELECT tfa_token FROM ' . DB::table('user') . ' WHERE id=' . DB::val($args['user']['id']));

    if ($query['tfa_token'] === null) {
        // tfa is not enabled
        return;
    }

    $_SESSION['sl_tfa'] = [
        'u_id' => $args['user']['id'],
        'u_email' => $args['user']['email'],
        'u_pass' => $args['user']['password'],
        'changeset' => $args['changeset'],
        'persistent' => $args['persistent'],
        'u_tfa' => $query['tfa_token'],
    ];

    $args['result'] = $this::STATUS_NEEDS_TFA;
};
