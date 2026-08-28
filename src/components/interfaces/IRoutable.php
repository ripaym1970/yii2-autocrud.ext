<?php

namespace ripaym1970\autocrud\components\interfaces;

interface IRoutable
{
    public static function route($includeDelimiter = true): string;
}
