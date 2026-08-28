<?php

namespace ripaym1970\autocrud\components\widgets\SaveRelations;

trait SaveRelationsTrait
{
    public function load($data, $formName = null)
    {
        $loaded = parent::load($data, $formName);
        if ($loaded && $this->hasMethod('loadRelations')) {
            $this->loadRelations($data);
        }
        return $loaded;
    }
}
