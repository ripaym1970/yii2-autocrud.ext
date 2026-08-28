<?php

/**
 * Функция для работы с асинхронной возможностью пагинатора 'Показать еще'.
 * Чтобы не писать в каждом контроллере, отдельный метод, повторяющий получение данных для списка.
 */

namespace ripaym1970\autocrud\components\widgets\Pager\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\ExitException;
use yii\base\InvalidArgumentException;
use yii\web\Response;

class LoadMore extends Behavior
{
    /**
     * @param array input - массив параметров:
     *      если параметр 'data' является массивом (и не задан параметр itemView), тогда на выход пойдет JSON этого массива
     *      если параметр 'data' является строкой, тогда на выход пойдет эта строка как html
     *      если параметр 'data' является массивом и также заданы параметры itemView и itemName,
     *      тогда каждый элемент массива 'data' будет отрендерен по шаблону 'itemView'
     *      (причем в шаблон будет передан опциональный массив параметров 'viewParams',
     *      а сам элемент будет передан в шаблон с именем заданнным в параметре 'itemName')
     *
     * @throws ExitException
     */
    public function loadMoreReady(array $input)
    {
        if (Yii::$app->getRequest()->isAjax && !empty($_GET['_loadMore'])) {
            if (!isset($input['data'])) {
                throw new InvalidArgumentException('Должен быть определен ключ data');
            }

            $response = Yii::$app->getResponse();

            if (is_callable($input['data'])) {
                $input['data'] = call_user_func_array($input['data'], [!empty($input['viewParams']) ? $input['viewParams'] : []]);
            }

            if (is_string($input['data'])) {
                // html
                $response->data = $input['data'];
            } elseif (is_array($input['data']) && !empty($input['itemView'])) {
                // template
                if (empty($input['itemName'])) {
                    throw new InvalidArgumentException('Должен быть определен ключ itemName');
                }
                $response->data = $this->_renderItems($input);
            } else {
                // json или указанный формат
                Yii::$app->response->format = isset($input['format']) ? $input['format'] : Response::FORMAT_JSON;
                $response->data = $input['data'];
            }

            Yii::$app->end(0, $response);
        }
    }

    private function _renderItems(array &$input)
    {
        $viewParams = !empty($input['viewParams']) ? $input['viewParams'] : [];
        $viewParamsCallback = !empty($input['viewParamsCallback']) ? $input['viewParamsCallback'] : null;
        $result = '';

        array_walk($input['data'], function ($item, $index) use (&$result, $input, $viewParams, $viewParamsCallback) {
            $params = array_merge($viewParams, [$input['itemName'] => $item]);

            if ($viewParamsCallback) {
                $params = array_merge($params, call_user_func_array($viewParamsCallback, [$item, $index, $input['data'], $params]));
            }

            $result .= Yii::$app->controller->renderPartial($input['itemView'], $params);
        });

        return $result;
    }
}
