<?php

namespace ripaym1970\autocrud\components\mail\components\widgets\Dropdown;

class DropDownListBuilder
{

    public static function dropDownElement($id, \yii\db\ActiveRecord $model, $file, $content)
    {
        $element = $id
            ? ['id' => $id, 'selectable' => true]
            : [];

        if ($file)
            $element['icon'] = $file->link;
        $element['label'] = $content;
        return $element;
    }


    protected static function run(array $config, $parentModel = null)
    {
        $itemFilter = \yii\helpers\ArrayHelper::getValue($config, 'itemFilter');
        $itemBuilder = $config['itemBuilder'];
        $selectedId = \yii\helpers\ArrayHelper::getValue($config, 'selectedId');

        $result = [];
        $query = call_user_func($config['query'], $parentModel);
        $models = $query ? $query->all() : [];

        foreach ($models as $model) {
            if (is_callable($itemFilter) && !call_user_func($itemFilter, $model))
                continue;

            $element = call_user_func($itemBuilder, $config, $model);

            if ($selectedId && $model->id == $selectedId) {
                $element['checked'] = true;
                $element['expanded'] = true;
            }

            $childModels = self::run($config, $model);
            $items = \yii\helpers\ArrayHelper::getValue($config, 'items');
            $children = $items
                ? self::run($items, $model)
                : [];

            if ($childModels || $children) {
                $element['items'] = \yii\helpers\ArrayHelper::merge(
                    $childModels,
                    $children
                );
                // parent should be expanded if children selected or expanded
                array_filter(
                    $element['items'],
                    function ($x) {
                        return isset($x['checked']) || isset($x['expanded']);
                    }
                ) && $element['expanded'] = true;
            }

            if (array_key_exists('id', $element) || array_key_exists('items', $element))
                $result [] = $element;
        }

        return $result;
    }

    protected static function productGroupsConfig()
    {
        return [
            'query' => function ($parentModel) {
                return \ripaym1970\autocrud\models\ProductGroup::find()->andWhere([
                    'parent_id' => $parentModel
                        ? $parentModel->id
                        : null,
                ])->orderBy("name");
            },
            'itemBuilder' => function (array $config, \ripaym1970\autocrud\models\ProductGroup $productGroup) {
                return self::dropDownElement(
                    array_key_exists('selectedId', $config)
                        ? $productGroup->id
                        : null,
                    $productGroup,
                    $productGroup->file,
                    $productGroup->name
                );
            },
        ];
    }

    public static function productGroups($selectedProductGroupId)
    {
        $config = \yii\helpers\ArrayHelper::merge(
            self::productGroupsConfig(),
            [
                'selectedId' => $selectedProductGroupId,
            ]
        );
        return self::run($config);
    }

    public static function productGroupWithProvisioningModule(array $moduleIds, $selectedId)
    {
        $products = \ripaym1970\autocrud\models\Product::getActiveProducts($moduleIds);
        $ids = [];

        foreach ($products as $product) {
            $ids = \yii\helpers\ArrayHelper::merge(
                $ids,
                \yii\helpers\ArrayHelper::getColumn($product->productGroup->pathToParent, 'id')
            );
        }

        $ids = array_unique($ids);

        $config = \yii\helpers\ArrayHelper::merge(
            self::productGroupsConfig(),
            [
                'selectedId' => $selectedId,
                'itemFilter' => function (\ripaym1970\autocrud\models\ProductGroup $model) use ($ids) {
                    return in_array($model->id, $ids);
                }
            ]
        );

        return self::run($config);
    }

    // $provisioningModuleIds can be empty to not filter by module
    // $includeOnlyProductIds -- if not empty, only product ids from this parameter will be fetched
    public static function productGroupsWithProducts(
        $selectedProductId,
        array $provisioningModuleIds
    )
    {
        $config = self::productGroupsConfig();
        $config['items'] = [
            'selectedId' => $selectedProductId,
            'itemFilter' => function (\ripaym1970\autocrud\models\Product $product) use ($provisioningModuleIds) {
                return (
                    empty($provisioningModuleIds)
                    || in_array($product->provisioningModuleId, $provisioningModuleIds)
                );
            },

            'query' => function ($parentModel) {
                if (!$parentModel || $parentModel instanceof \ripaym1970\autocrud\models\Product)
                    return false;

                return \ripaym1970\autocrud\models\Product::find()->andWhere([
                    'state' => \ripaym1970\autocrud\models\Product::STATE_ACTIVE,
                    'product_group_id' => $parentModel->id
                ])->orderBy("name");
            },

            'itemBuilder' => function (array $config, \ripaym1970\autocrud\models\Product $product) {
                return self::dropDownElement(
                    $product->id,
                    $product,
                    $product->file,
                    $product->name
                );
            },
        ];

        return self::run($config);
    }

    public static function customerOrderProductsForCustomer($customerId, $selectedId)
    {
        $config = [
            'selectedId' => $selectedId,
            'query' => function ($parentModel) use ($customerId) {
                $query = \ripaym1970\autocrud\models\OrderProduct::find()
                    ->alias('t')
                    ->joinWith(['order o'])
                    ->where(['t.state' => \ripaym1970\autocrud\models\OrderProduct::STATE_ACTIVE]);

                if ($parentModel) {
                    $query->andWhere(['t.parent_id' => $parentModel->id]);
                } else {
                    $query->andWhere(
                        '(o.customer_id = :customer_id or t.shared_customer_id = :customer_id) and t.parent_id is null',
                        [
                            ':customer_id' => $customerId,
                        ]
                    );
                }

                return $query->orderBy(['t.order_id' => SORT_ASC]);
            },
            'itemBuilder' => function (array $config, \ripaym1970\autocrud\models\OrderProduct $model) {
                return self::dropDownElement(
                    $model->id,
                    $model,
                    $model->product->file,
                    $model->product->name
                );
            },
        ];
        return self::run($config);
    }

    public static function customerOrderProductsForAdmin($customerId, $selectedId)
    {
        $config = [
            'query' => function ($parentModel) use ($customerId) {
                if ($parentModel && $parentModel instanceof \ripaym1970\autocrud\models\Order)
                    return false;
                $query = \ripaym1970\autocrud\models\Order::find()->where([
                    'customer_id' => $customerId
                ]);

                $sharedIds = array_unique(
                    \yii\helpers\ArrayHelper::getColumn(
                        \ripaym1970\autocrud\models\OrderProduct::getTopLevelSharedWithCustomer($customerId),
                        'order_id'
                    )
                );
                if ($sharedIds)
                    $query = $query->orWhere([
                        'id' => $sharedIds
                    ]);

                return $query->orderBy("id");
            },

            'itemBuilder' => function (array $config, \ripaym1970\autocrud\models\Order $order) use ($customerId) {

                $modifier = $order->customer_id == $customerId
                    ? ''
                    : \Yii::t('app', " (shared)");
                $content = \Yii::t(
                    'app',
                    "Order #{order_id}{modifier}",
                    [
                        'order_id' => $order->id,
                        'modifier' => $modifier,
                    ]
                );

                return self::dropDownElement(
                    null,
                    $order,
                    null,
                    $content
                );
            },
            'items' => [
                'selectedId' => $selectedId,
                'query' => function ($parentModel) {
                    $query = \ripaym1970\autocrud\models\OrderProduct::find();
                    if ($parentModel instanceof \ripaym1970\autocrud\models\Order) {
                        return $query->andWhere([
                            'order_id' => $parentModel->id,
                            'parent_id' => null,
                        ]);
                    } elseif ($parentModel instanceof \ripaym1970\autocrud\models\OrderProduct) {
                        return $query->andWhere([
                            'parent_id' => $parentModel->id,
                        ])->with("product");
                    }
                    throw new \yii\base\Exception("unknown model");
                },
                'itemBuilder' => function (array $config, \ripaym1970\autocrud\models\OrderProduct $model) {

                    $content = implode('.', [
                        $model->id,
                        $model->product->name
                    ]);

                    return \yii\helpers\ArrayHelper::merge(
                        self::dropDownElement(
                            $model->id,
                            $model,
                            $model->product->file,
                            $content
                        ),
                        [
                            'href' => \yii\helpers\Url::to(
                                ["/order-products/view", 'id' => $model->id],
                                true
                            ),
                        ]
                    );
                },
            ]
        ];
        return self::run($config);
    }

}
