<?php

namespace ripaym1970\autocrud\components\mail\components\interfaces;

interface IDataProtectionProvider
{
    /**
     * Get secure fields to protect/mask
     *
     * @return array
     */
    public static function getSecureFields();
}
