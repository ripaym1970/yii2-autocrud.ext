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

namespace ripaym1970\autocrud\components\provider;

class AdvcashProvider
{
    protected $_url        = 'https://wallet.advcash.com/sci/';
    protected $_shopIdp    = ''; // действующая точка продажи

    protected $_login      = '';
    protected $_password   = '';
    protected $_lifetime   = 1200; // Время жизни формы

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
        $params = [];
        //$params = [
        //    'ac_account_email'      => $accountEmail,
        //    'ac_sci_name'           => $merchantName,
        //    'ac_amount'             => $amount,
        //    'ac_currency'           => $currency,
        //    'ac_order_id'           => $invoiceId,
        //    'ac_sign'               => $sign,
        //    'ac_ps'                 => $defaultPaymentSystem,
        //    'ac_comments'           => $description,
        //    'ac_success_url'        => $successUrl,
        //    'ac_success_url_method' => $successUrlMethod,
        //    'ac_fail_url'           => $failureUrl,
        //    'ac_fail_url_method'    => $failureUrl,
        //    'ac_status_url'         => $resultUrl,
        //    'ac_status_url_method'  => $resultUrlMethod,
        //];
/*
        'formId'               => $this->formId,
        'formAction'           => $this->formAction,
        'formMethod'           => $this->formMethod,
        'redirectMessage'      => $this->redirectMessage,
        'api'                  => $this->api,
        'accountEmail'         => $this->api->accountEmail,
        'merchantName'         => $this->api->merchantName,
        'amount'               => Merchant::normalizeAmount($this->amount),
        'currency'             => $this->api->sciCurrency,
        'invoiceId'            => $this->invoiceId,
        'sign'                 => $this->api->sciCheckSign ? $this->api->createSciSign($this->amount, $this->invoiceId) : null,
        'defaultPaymentSystem' => $this->api->sciDefaultPs,
        'description'          => $this->description,
        'successUrl'           => $this->api->successUrl,
        'successUrlMethod'     => $this->api->successUrlMethod,
        'failureUrl'           => $this->api->failureUrl,
        'failureUrlMethod'     => $this->api->failureUrlMethod,
        'resultUrl'            => $this->api->resultUrl,
        'resultUrlMethod'      => $this->api->resultUrlMethod,
*/
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
