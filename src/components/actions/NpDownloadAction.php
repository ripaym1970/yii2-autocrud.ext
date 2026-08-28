<?php

namespace ripaym1970\autocrud\components\actions;


use ripaym1970\autocrud\models\crud\Np_areaModel;
use ripaym1970\autocrud\models\crud\Np_cityModel;
use ripaym1970\autocrud\models\crud\Np_districtModel;
use ripaym1970\autocrud\models\crud\Np_settlementModel;
use ripaym1970\autocrud\models\crud\Np_warehouseModel;
use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii2tech\admin\actions\Action;

class NpDownloadAction extends Action
{
    public $newModel;
    /**
     * Creates new model instance.
     * @return ActiveRecordInterface|Model new model instance.
     * @throws InvalidConfigException on invalid configuration.
     */
    public function newModel()
    {
        if ($this->newModel !== null) {
            return call_user_func($this->newModel, $this);
        } elseif ($this->controller->hasMethod('newModel')) {
            return call_user_func([$this->controller, 'newModel'], $this);
        }
        throw new InvalidConfigException('Either "' . get_class($this) . '::$newModel" must be set or controller must declare method "newModel()".');
    }

    /**
     * @return Response
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function run()
    {
        $model = $this->newModel();
        $modelClass = get_class($model);
        $tableName = $modelClass::tableName();
        //dd($tableName, $model);

        $np = new \ripaym1970\autocrud\components\NovaPoshtaApi2\NovaPoshtaApi2('46ded1c464c80f9bdeb7eedc2bdb24dd');

        if ($tableName == 'np_counterparty') {
            $data = $np->getCounterparties([
                // Вид контрагента Sender/Recipient/ThirdPerson
                //'CounterpartyProperty' => 'Sender',        // Відправник
                'CounterpartyProperty' => 'Recipient',   // Одержувач
                //'CounterpartyProperty' => 'ThirdPerson', // Третя особа
                'Page'                 => '1', // Треба, бо нема відповіді
            ]);
            if (!$data) {
                //dd($data);
                $this->setFlash(['error' => Yiit::t('Errors: Не вдалося отримати дані з Нової пошти.')]);
                return $this->controller->redirect($this->createReturnUrl('index'));
            }
            dd($data);

            foreach ($data as $item) {
                /** @var ActiveRecord $model */
                $model = new Np_counterpartyModel([
                    'ref'                        => $item['Ref'],                       // Ref (ID)
                    'description'                => $item['Description'],               // Назва
                    'counterparty_type'          => $item['CounterpartyType'],          // Тип контрагенту

                    'city'                       => $item['City'],                      // Місто контрагента
                    'city_description'           => $item['CityDescription'],           // Назва міста контрагента
                    'counterparty'               => $item['Counterparty'],              // Ref Контрагента ?
                    'last_name'                  => $item['LastName'],                  // Прізвище
                    'first_name'                 => $item['FirstName'],                 // Ім'я
                    'middle_name'                => $item['MiddleName'],                // По-батькові
                    'ownership_form_ref'         => $item['OwnershipFormRef'],          // Ідентифікатор форми власності
                    'ownership_form_description' => $item['OwnershipFormDescription'],  // Опис форми власності
                    'EDRPOU'                     => $item['EDRPOU'],                    // Код ЄДРПОУ

                    'active'     => true,
                    'created_at' => time(),
                ]);

                if (!$model->save()) {
                    dd('Errors: ' . implode("<br/>", $model->getErrorSummary(true)));
                    $this->setFlash(['error' => 'Errors: ' . implode("<br/>", $model->getErrorSummary(true))]);
                }
            }
            $this->setFlash(['success' => Yiit::t('Дані оновлено з Нової пошти.')]);
        }
        elseif ($tableName == 'np_area') {
            $data = $np->getSettlementAreas();
            if (!$data) {
                $this->setFlash(['error' => Yiit::t('Errors: Не вдалося отримати дані з Нової пошти.')]);
                return $this->redirect(['/' . $this->_table]);
            }
            //dd($data);

            foreach ($data as $item) {
                /** @var ActiveRecord $model */
                $model = new Np_areaModel([
                    'ref'          => $item['Ref'],
                    'description'  => $item['Description'],
                    'areas_center' => $item['AreasCenter'],
                    'region_type'  => (isset($item['Description']) && $item['Description'] == 'АРК'
                        ? ''
                        : $item['RegionType']),
                    'active'       => (isset($item['Description']) && $item['Description'] == 'АРК'
                        ? false
                        : true),
                    'created_at'   => time(),
                ]);

                if (!$model->save()) {
                    dd('Errors: ' . implode("<br/>", $model->getErrorSummary(true)));
                    $this->setFlash(['error' => 'Errors: ' . implode("<br/>", $model->getErrorSummary(true))]);
                }
            }
            $this->setFlash(['success' => Yiit::t('Дані оновлено з Нової пошти.')]);
        }
        elseif ($tableName == 'np_district') {
            $areas = Np_areaModel::find()
                ->select([
                    'ref',
                ])
                ->andWhere([
                    'active' => true,
                ])
                ->column();
            foreach ($areas as $areaRef) {
                $data = $np->getSettlementDistricts([
                    'AreaRef' => $areaRef,
                ]);
                //dd($data);

                foreach ($data as $item) {
                    /** @var ActiveRecord $model */
                    $model = new Np_districtModel([
                        'ref'          => $item['Ref'],
                        'description'  => $item['Description'],
                        'areas_center' => $item['AreasCenter'],
                        'region_type'  => $item['RegionType'],
                        'active'       => true,
                        'created_at'   => time(),
                    ]);

                    if (!$model->save()) {
                        dd('Errors: ' . implode("<br/>", $model->getErrorSummary(true)));
                        $this->setFlash(['error' => 'Errors: ' . implode("<br/>", $model->getErrorSummary(true))]);
                    }
                }
            }
            //dd('');
            $this->setFlash(['success' => Yiit::t('Дані оновлено з Нової пошти.')]);
        }
        elseif ($tableName == 'np_settlement') {
            $districtRefs = Np_districtModel::find()
                ->select([
                    'ref',
                ])
                ->andWhere([
                    'active' => true,
                ])
                ->column();
            //$districtRefs = ['e4b03847-4b33-11e4-ab6d-005056801329']; // В-Д район
            foreach ($districtRefs as $districtRef) {
                $data = $np->getSettlements([
                    //'FindByString' => 'Київ',
                    //'Ref'          => '00000000-0000-0000-0000-000000000000',
                    'RegionRef'      => $districtRef,
                    //'AreaRef'      => $districtRef,
                    'Warehouse'      => '1',
                    //'Page'         => '1',
                    //'Limit'        => '20',
                ]);
                //dd($data);

                foreach ($data as $item) {
                    /** @var ActiveRecord $model */
                    $model = new Np_settlementModel([
                        'ref'          => $item['Ref'],
                        'description'  => $item['Description'],

                        'settlement_type'             => $item['SettlementType'],
                        'settlement_type_description' => $item['SettlementTypeDescription'],

                        'region'              => $item['Region'],
                        'regions_description' => $item['RegionsDescription'],

                        'area'             => $item['Area'],
                        'area_description' => $item['AreaDescription'],

                        'latitude'  => $item['Latitude'],
                        'longitude' => $item['Longitude'],

                        'index1' => $item['Index1'],
                        'index2' => $item['Index2'],

                        'warehouse' => $item['Warehouse'],

                        'active'       => true,
                        'created_at'   => time(),
                    ]);

                    if (!$model->save()) {
                        dd('Errors: ' . implode("<br/>", $model->getErrorSummary(true)));
                        $this->setFlash(['error' => 'Errors: ' . implode("<br/>", $model->getErrorSummary(true))]);
                    }
                }
            }
            //dd('');
            $this->setFlash(['success' => Yiit::t('Дані оновлено з Нової пошти.')]);
        }
        //elseif ($tableName == 'np_city') {
        //    $data = $np->getCities();
        //}
        elseif ($tableName == 'np_street') {
            $cities = Np_cityModel::find()
                //$cities = Np_SettlementModel::find()
                //    ->andWhere([
                //        'ref' => 'e718a680-4b33-11e4-ab6d-005056801329', // Київ
                //    ])
                ->limit(3)
                ->all();
            //dd($cities);
            foreach ($cities as $city) {
                //dd($city);
                $data = $np->getStreet($city->ref);
                //dd($data);
                echo '<pre>';var_dump('$data=', $data);echo '</pre>';exit();
                if ($data['data']) {
                    foreach ($data['data'] as $item) {
                        //dd($item);
                        echo '<pre>';var_dump('$item=', $item);echo '</pre>';exit();
                        //$model = $this->modelClass::findOne(['ref' => $item['Ref']])
                        //    ?? new $this->modelClass(['ref' => $item['Ref'],]);
                        //
                        //$model->description = $item['Description'];
                        //$model->settlement_street_ref = $item['StreetsTypeRef'];
                        //$model->streets_type = $item['StreetsType'];
                        //
                        //if (!$model->save()) {
                        //    Yii::$app->session->setFlash(
                        //        'error',
                        //        'Errors: ' . implode("<br/>", $model->getErrorSummary(true))
                        //    );
                        //}
                    }
                }
            }
        }
        elseif ($tableName == 'np_warehouse') {
            $cities = Np_cityModel::find()
                ->limit(3)
                ->all();
            //dd($cities);

            // Для каждого города получим отделения НП
            foreach ($cities as $city) {
                //dd($city);
                $data = $np->getWarehouses($city->ref);
                //d('totalCount='.$data['info']['totalCount']);
                if ($data['data']) {
                    foreach ($data['data'] as $item) {
                        //dd($item);
                        $model = $this->modelClass::findOne(['ref' => $item['Ref']])
                            ?? new $this->modelClass(['ref' => $item['Ref'],]);
                        $model->site_key = $item['SiteKey'];
                        $model->description = $item['Description'];
                        $model->description_ru = $item['DescriptionRu'];
                        $model->short_address = $item['ShortAddress'];
                        $model->short_address_ru = $item['ShortAddressRu'];
                        $model->phone = !empty($item['Phone']) ? $item['Phone'] : null;
                        $model->type_of_warehouse = $item['TypeOfWarehouse'];
                        $model->number = $item['Number'];
                        $model->longitude = $item['Longitude'];
                        $model->latitude = $item['Latitude'];
                        $model->warehouse_status = $item['WarehouseStatus'];
                        $model->category_of_warehouse = $item['CategoryOfWarehouse'];
                        $model->city_ref = $item['CityRef'];
                        $model->city_description = $item['CityDescription'];
                        $model->city_description_ru = $item['CityDescriptionRu'];
                        $model->np_settlements_id = $item['SettlementRef'];

                        if (!$model->save()) {
                            Yii::$app->session->setFlash(
                                'error',
                                'Errors: ' . implode("<br/>", $model->getErrorSummary(true))
                            );
                        }
                    }
                }
            }
        }

        return $this->controller->redirect($this->createReturnUrl('index'));
    }
}
