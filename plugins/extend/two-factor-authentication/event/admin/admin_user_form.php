<?php

use Sunlight\Util\Form;

return function (array $args) {
    $args['output'] .= _buffer(function () use ($args) { ?>
        <tr>
            <th><?= _lang('two-factor-authentication.settings.tfa') ?></th>
            <td>
                <label><?= Form::input('checkbox', 'remove_tfa', '1', ['disabled' => ($args['user']['tfa_token'] === null)]) . ' ' . _lang('global.delete') ?></label>
            </td>
        </tr>
    <?php });
};
