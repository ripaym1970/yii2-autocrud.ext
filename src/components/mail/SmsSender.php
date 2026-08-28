<?php
/**
 * Класс для реализации SMS рассылки
 */

namespace ripaym1970\autocrud\components\mail;

class SmsSender
{
    public const SERVICE_HOST = 'http://sms.ls1.ru'; // sms-сервер
    public const ACCESS_KEY   = ''; // ключ к серверу

    /**
     * Отправляет SMS сообщение на указанный номер или номера телефонов
     *
     * @param string|array $phones перечень номеров телефонов в виде массива или в виде строки через запятую
     * @param string $text текст сообщения
     *
     * @return bool
     */
    public static function send($phones, $text)
    {
        if (!is_array($phones)) {
            $phones = explode(',', $phones);
        }

        array_walk($phones, function (&$phone) {
            $phone = preg_replace('/[^0-9]+/', '', $phone);
        });

        $params['phones'] = implode(',', $phones);
        $params['key'] = self::ACCESS_KEY;
        $params['text'] = $text;

        // Вернет false если ошибка
        $result = file_get_contents(self::SERVICE_HOST . '?' . http_build_query($params));

        return preg_match('/[0-9]{7,}/', $result);
    }
}
