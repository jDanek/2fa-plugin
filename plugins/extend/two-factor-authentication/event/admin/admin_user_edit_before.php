<?php

return function (array $args) {
    if(isset($_POST['remove_tfa'])) {
        $args['changeset']['tfa_token'] = null;
    }
};
