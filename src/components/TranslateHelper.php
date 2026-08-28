<?php

namespace ripaym1970\autocrud\components;

use Exception;
use Google\Cloud\Translate\V2\TranslateClient;
use Yii;
use yii\helpers\Json;

class TranslateHelper
{
    private static $_languages = null;

    public static function translate($from, $to, $text)
    {
        $key = Yii::$app->params['google_api_key'];
        if (empty($key)) {
            throw new Exception('API key is empty. Please pass a valid API key. in site config');
        }

        $translate = new TranslateClient(['key' => $key]);
        try {
            if (empty(self::$_languages)) {
                self::$_languages = $translate->languages();
            }
        } catch (Exception $e) {
            try {
                $message = Json::decode($e->getMessage());
                if (isset($message['error']['message'])) {
                    $message = $message['error']['message'];
                } else {
                    $message = $e->getMessage();
                }
            } catch (Exception $ee) {
                $message = $e->getMessage();
            }
            throw new Exception($message);
        }

        $from = substr($from, 0, 2);
        $to = substr($to, 0, 2);
        if (!in_array($from, self::$_languages)) {
            throw new Exception('Warning from language ' . $from . ' not available for google translate');
        }

        if (!in_array($to, self::$_languages)) {
            throw new Exception('Warning from language ' . $to . ' not available for google translate');
        }

        if ($from == $to) {
            return $text;
        }

        try {
            $result = $translate->translate($text, [
                'source' => $from,
                'target' => $to,
            ]);
        } catch (Exception $e) {
            try {
                $message = Json::decode($e->getMessage() . 'sdf');
                if (isset($message['error']['message'])) {
                    $message = $message['error']['message'];
                } else {
                    $message = $e->getMessage();
                }
            } catch (Exception $ee) {
                $message = $e->getMessage();
            }
            throw new Exception($message);
        }

        return !empty($result['text']) ? str_replace('&#39;', '’‎', $result['text']) : '';
    }

}
