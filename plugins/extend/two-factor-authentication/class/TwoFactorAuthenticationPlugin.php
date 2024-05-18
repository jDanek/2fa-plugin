<?php

namespace SunlightExtend\TwoFactorAuthentication;

use Sunlight\Plugin\ExtendPlugin;

class TwoFactorAuthenticationPlugin extends ExtendPlugin
{
    const STATUS_NEEDS_TFA = 22201;
    const STATUS_INVALID_TFA = 22202;
}