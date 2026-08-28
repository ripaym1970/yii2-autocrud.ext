<?php

namespace ripaym1970\autocrud\components\interfaces;

/**
 * @inheritdoc
 *
 * @property string $stringRepresentation
 **/
interface IStringRepresentation
{
    public function getStringRepresentation(array $params = []): string;
}
