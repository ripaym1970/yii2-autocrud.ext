<?php

namespace ripaym1970\autocrud\components;

use ripaym1970\autocrud\models\crud\Message_translationModel;
use ripaym1970\autocrud\models\crud\MessageModel;
use Yii;

// use ripaym1970\autocrud\models\interfaces\basic\Message_translationModelInterface;
// use ripaym1970\autocrud\models\interfaces\basic\MessageModelInterface;

/**
 * @property string $languageMessage
 */
class Yiit
{
    public static function t($translate, $category = 'app')
    {
        // TODO: Если не переводится, но перевод есть - очистите кеш!
        //Yii::$app->cache->flush();

        if (!$translate) {
            return '';
        }

        // TODO: Если переводить надо на язык сообщений, то не переводим
        $languageMessage = Yii::$app->params['languageSource'];
        $yiiLanguage = Yii::$app->language;
        if ($languageMessage == $yiiLanguage) {
            return $translate;
        }

        $tranlatedKey = $yiiLanguage . md5($category . $translate);
        $cacheGet = Yii::$app->cache->get($tranlatedKey);
        if ($cacheGet) {
            return $cacheGet;
        }

        /** @var MessageModelInterface $check */
        $check = MessageModel::findOne([
            'category' => $category,
            'message'  => $translate,
        ]);
        if ($check) {
            $checkTranslation = $check->translation ?? null;
            if ($checkTranslation) {
                Yii::$app->cache->set($tranlatedKey, $checkTranslation, 86400); // 60 * 60 * 24
                return $checkTranslation;
            }
        } else {
            /** @var MessageModelInterface $modelMessage */
            $check = new MessageModel([
                'category' => $category,
                'message'  => $translate,
            ]);
            if (!$check->save()) {
                dd($check->errors, $check);
            }
        }

        /** @var Message_translationModelInterface $modelMessageT */
        $modelMessageT = new Message_translationModel([
            'message_id'  => $check->id,
            'language_id' => $yiiLanguage,
            'translation' => $translate,
        ]);
        if (!$modelMessageT->save()) {
            dd($modelMessageT->errors, $modelMessageT);
        }

        Yii::$app->cache->set($tranlatedKey, $translate, 86400); // 60 * 60 * 24

        return $translate;
    }
}
