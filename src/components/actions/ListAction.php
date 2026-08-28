<?php

/**
 * AJAX-cписок записей из таблицы без добавления новой записи
 */

namespace ripaym1970\autocrud\components\actions;

use ripaym1970\autocrud\models\CrudModel;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii2tech\admin\actions\Action;

class ListAction extends Action
{
    public function run()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $requestPost = Yii::$app->request->post();
        $requestGet = Yii::$app->request->get();

        $out = [
            'results'[0] => [
                'id'   => '',
                'text' => '',
            ],
        ];

        $q     = $requestPost['q'] ?? $requestGet['q'] ?? '';               // Строка поиска
        $name  = $requestPost['name'] ?? $requestGet['name'] ?? 'name';     // Поле поиска
        $table = $requestPost['table'] ?? $requestGet['table'] ?? null;     // Таблица поиска
        $condition = $requestPost['where'] ?? $requestGet['where'] ?? '[]'; // Доп условие фильтрации
        $condition = json_validate($condition)
            ? json_decode($condition, true)
            : [];
        //$condition = array_shift($condition);

        $parent = $requestPost['depdrop_all_params'] ?? $requestGet['depdrop_all_params'] ?? [];
        $parentFieldName = '';
        $parentFieldValue = '';
        if ($parent) {
            foreach ($parent as $key => $item) {
                $parentFieldName = $key;
                $parentFieldValue = $item;
                break;
            }
        }

        if (!$name && !$table) {
            return $out;
        }

        $tableModel = ucfirst($table) . 'Model';
        $nameId = 'id';
        $isTranslations = ArrayHelper::getValue(Yii::$app->params, 'tables.' . $table . '.behaviors.translations', false);
        if ($isTranslations) {
            $nameId = $table . '_id';
            $tableModel = ucfirst($table) . '_translationModel';
        }

        $className = '\ripaym1970\autocrud\models\crud\\' . $tableModel;
        /** @var CrudModel $className */
        $query = $className::find()
            ->select([
                'id'   => $nameId,
                'text' => $name,
            ])
            ->andWhere(['NOT', [$name => null]]);

        if ($isTranslations) {
            $query->andWhere([
                'language_id' => Yii::$app->language,
            ]);
        } else {
            $query->andFilterWhere($condition);
        }

        if ($parentFieldName && $parentFieldValue) {
            $query->andWhere([
                $parentFieldName => $parentFieldValue,
            ]);
        }

        if ($q) {
            if (is_numeric($q)) {
                if ((int)$q > 0) {
                    $query->andWhere([
                        $nameId => $q,
                    ]);
                }
            }
            $query->andWhere(['LIKE', 'LOWER(' . $name . ')', $q]);
        }

        $data = $query
            ->limit(20)
            ->asArray()
            ->all()
        ;

        ArrayHelper::multisort($data, 'text');

        $out['results'] = array_values($data);

        return $out;
    }
}
