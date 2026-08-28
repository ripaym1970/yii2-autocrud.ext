<?php

namespace ripaym1970\autocrud\components;

use yii\base\Component;

class Currency extends Component
{
    public $sign;

    public $signBefore = false;

    public $code;

    public $integerOnly = false;

    /**
     * {@inheritdoc}
     *
     * @throws \yii\base\Exception
     */
    public function init()
    {
        parent::init();

        if (empty($this->sign)) {
            throw new \yii\base\Exception('Currency sign required.');
        }

        if (empty($this->code)) {
            throw new \yii\base\Exception('Currency code required.');
        }
    }

    /**
     * @param float $value
     *
     * @return string
     */
    public function format($value)
    {
        $rounded = $this->round($value);
        $result = $this->signBefore ? $this->sign . $rounded : $rounded . $this->sign;

        return $result;
    }

    /**
     * @param float  $value
     * @param string $space
     *
     * @return string
     */
    public function formatWithCode($value, $space = ' ')
    {
        $rounded = $this->round($value);

        return $rounded . $space . $this->code;
    }

    public function round($value)
    {
        return $this->integerOnly ? round($value) : round($value * 100) / 100;
    }
}
