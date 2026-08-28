<?php
/**
 * Использование
 *
 *
 * $provider = new UnitellerProvider();
 * $paymentUrl = $provider->getPaymentUrl($model->id, $model->client_must_pay, $model->client_email, $model->client_phone);
 *
 * Пример сгенерированной ссылки
 * $paymentUrl = 'https://wpay.uniteller.ru/pay?Shop_IDP=00009502&Order_IDP=37035&Subtotal_P=317&Lifetime=1200&Signature=E906567EBF2A82DF5BF02F3D08EC14DC&URL_RETURN_OK=http%3A%2F%2Fcarsrent.ru%2Fbron%3Fa%3Dstatus%26code%3Dsuccess&URL_RETURN_NO=http%3A%2F%2Fcarsrent.ru%2Fbron%3Fa%3Dstatus&Email=gendlog%40mail.ru&Phone=9037222432&Language=ru';
 * }
 */

namespace ripaym1970\autocrud\components\mail\components\provider;

use Yii;

class UnitellerProvider
{
    protected $_url        = 'https://wpay.uniteller.ru/pay';
    protected $_shopIdp    = '00009502'; // действующая точка продажи
    //protected $_shopIdp  = '00009513'; // тестовая точка продажи
    /*
    Для точки продажи 00009513 используется стандартный шаблон оплаты,
    который открывается в новой вкладке или окне (это настройки в ЛК Unitellera, меню "Точки продажи", "Гаечный ключ").
    Данная точка заведена для виртуального эквайера п. 3.3.1. документа "Технический порядок Интернет-эквайринга",
    т.е. для проведения платежей по ней необходимо использовать специальные тестовые карты.
    Для неё следует использовать только данные следующих тестовых платёжных карт:
    4000000000002487 успешная оплата
    4000000000002479 НЕ успешная оплата

    Срок действия — 01/20
    Имя держателя карты — UNITELLER TEST
    CVV — 123
    Cумма тестового платежа должна составлять от 10,00 до 100,00 руб включительно.
    Сумма на счет не зачисляется, а только возвращается успешность оплаты путем перехода по ссылке URL_RETURN_OK.

    http://prntscr.com/ifrxuy - старый CR
    */
    protected $_login      = '2028';
    protected $_lifetime   = 1200;
    protected $_password   = 'ByvAt1Ib6gstKOvs7iJZ8XlcezQWUiqoygwH6NKSS4tBBMypEDPTfIEuiRF6abynGVdGaGXdbnq6QEtK';

    public $success_url    = '/order/payment?status=completed&order=';
    public $fail_url       = '/order/payment?status=failure&order=';
    public $supportsiframe = true;

    /** +
     * Формирует подпись, гарантирующую неизменность критичных данных оплаты
     *
     * @param $params
     * @return string Сигнатура безопасности
     */
    protected function getRequestSignature($params)
    {
        //dd($params);
        return strtoupper(
            md5(
                md5($params['Shop_IDP']) . '&' .    // Shop_IDP. Идентификатор точки продажи в системе Uniteller
                md5($params['Order_IDP']) . '&' .   // Order_IDP. Номер заказа в системе расчётов интернет-магазина
                md5($params['Subtotal_P']) . '&' .  // Subtotal_P. Сумма покупки в валюте, оговоренной в договоре с банком-эквайером
                md5($params['MeanType']) . '&' .    // 0 — любая платёжная система кредитной карты
                md5($params['EMoneyType']) . '&' .  // 0 - любая система электронных платежей
                md5($params['Lifetime']) . '&' .    // Lifetime. время жизни формы оплаты в секундах, если не используется, то указать пустую строку
                md5('') . '&' . // Customer_IDP. идентификатор покупателя, если не используется, то указать пустую строку
                md5('') . '&' . // Card_IDP. идентификатор зарегистрированной карты, если не используется, то указать пустую строку
                md5('') . '&' . // IData. «длинная запись», если не используется, то указать пустую строку
                md5('') . '&' . // PT_Code. тип платежа, если не используется, то указать пустую строку
                md5($this->_password) // password. Пароль, указанный в личном кабинете
            )
        );
    }

    /** +
     * Генерирует ссылку для оплаты в Uniteller
     *
     * @param $orderId
     * @param $amountToPay
     * @param string $email
     * @param string $phone
     * @param string $lang
     * @return string
     * @throws \Exception
     */
    public function getPaymentUrl($orderId, $amountToPay, $email = '', $phone = '', $lang = 'ru'/*, $paymentMethod=''*/)
    {
        if (!$orderId) {
            throw new \Exception('Не задан ID заказа');
        }
        if (!$amountToPay) {
            throw new \Exception('Не задана сумма к оплате для заказа ID='.$orderId);
        }

        $params['Shop_IDP']   = $this->_shopIdp;
        $params['Order_IDP']  = $orderId;
        $params['Subtotal_P'] = str_replace(',', '.', $amountToPay);
        $params['MeanType']   = 0;
        $params['Lifetime']   = $this->_lifetime; // 1200;
        $params['EMoneyType'] = 0; // ;

        $params['Signature']  = $this->getRequestSignature($params);

        $params['URL_RETURN_OK'] = Yii::$app->params['releaseSiteHostFull'] . $this->success_url . $orderId . '&pay=' . $amountToPay;
        $params['URL_RETURN_NO'] = Yii::$app->params['releaseSiteHostFull'] . $this->fail_url . $orderId . '&pay=' . $amountToPay;

        $params['Email']      = $email; //'ripa@lightsoft.ru';
        $params['Phone']      = $phone;
        $params['Language']   = $lang;

        return $this->_url . '?' . http_build_query($params);
    }

    /** +
     * Проверка подлинности данных полученных от Uniteller
     *
     * @param $orderID
     * @param $status
     * @param $signature
     *
     * @return bool
     */
    public static function checkSignature($orderID, $status, $signature)
    {
        return strtoupper(md5($orderID . $status . 'ByvAt1Ib6gstKOvs7iJZ8XlcezQWUiqoygwH6NKSS4tBBMypEDPTfIEuiRF6abynGVdGaGXdbnq6QEtK')) == strtoupper($signature);
    }

    /** +
     * Создание signature для имитации оплаты от Uniteller
     *
     * @param $orderID
     * @param $status
     *
     * @return string
     */
    public static function getSignature($orderID, $status)
    {
        return strtoupper(md5($orderID . $status . 'ByvAt1Ib6gstKOvs7iJZ8XlcezQWUiqoygwH6NKSS4tBBMypEDPTfIEuiRF6abynGVdGaGXdbnq6QEtK'));
    }
}

/*
$provider = new UnitellerProvider();
$paymentUrl = $provider->getPaymentUrl($model->id, $model->client_must_pay, $model->client_email, $model->client_phone);

// Пример сгенерированной ссылки
// $paymentUrl = 'https://wpay.uniteller.ru/pay?Shop_IDP=00009502&Order_IDP=37035&Subtotal_P=317&Lifetime=1200&Signature=E906567EBF2A82DF5BF02F3D08EC14DC&URL_RETURN_OK=http%3A%2F%2Fcarsrent.ru%2Fbron%3Fa%3Dstatus%26code%3Dsuccess&URL_RETURN_NO=http%3A%2F%2Fcarsrent.ru%2Fbron%3Fa%3Dstatus&Email=gendlog%40mail.ru&Phone=9037222432&Language=ru';
}
 */
