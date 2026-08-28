<?php

/** @var $model \ripaym1970\autocrud\models\ProductInstance
 * @var $this \yii\web\view
 */

$form = \madeit\components\Util::beginTypicalHorizontalForm();

echo ripaym1970\autocrud\components\widgets\Dropdown::widget([
    'isDropDown'  => false,
    'multiSelect' => false,
    'name'        => 'product_id',
    'data'        => \ripaym1970\autocrud\helpers\DropDownListBuilder::productGroupsWithProducts($model->product_id, []),
]);

// кусок формы
// $customerGroupIds = \yii\helpers\ArrayHelper::getValue($_REQUEST, 'customerGroupIds', [])
//     ?: \yii\helpers\ArrayHelper::getColumn($model->customerGroups, 'id');
//
// $traverse = function ($collection) use (&$traverse, $customerGroupIds) {
//     $result = [];
//     foreach ($collection as $item) {
//         /* @var \ripaym1970\autocrud\models\CustomerGroup $item */
//         $result[] = [
//             'id' => $item->id,
//             'expanded' => true,
//             'label' => $item->name,
//             'items' => $traverse($item->children),
//             'checked' => in_array($item->id, $customerGroupIds),
//         ];
//     }
//     return $result;
// };
//
// $customerGroups = $traverse(
//     array_filter(
//         (new \ripaym1970\autocrud\models\CustomerGroup())->modelsForSelect,
//         function (\ripaym1970\autocrud\models\CustomerGroup $x) {
//             return !$x->parent_id;
//         }
//     )
// );
// // кусок формы
// [
//     'label'   => Yii::t('app', 'Groups'),
//     'content' => \madeit\components\Dropdown\Widget::widget(
//         [
//             'data'        => $customerGroups,
//             'name'        => 'customerGroupIds[]',
//             'multiSelect' => true,
//             'isDropDown'  => false,
//         ]
//     ),
// ],


$form::end();
