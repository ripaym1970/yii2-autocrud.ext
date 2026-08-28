<?php
/**
 * Класс для реализации E-mail рассылки
 */

namespace ripaym1970\autocrud\components\mail;

use Exception;
use Yii;

class MailSender
{
    /**
     * Универсальный метод отправки письма
     *
     * @param array $mailParams параметры письма
     * @param string $layoutName отображение
     *
     * @return mixed
     * @throws Exception
     */
    public static function sendMail($mailParams, $layoutName = 'layouts/html')
    {
        if (!isset($mailParams['mailTo'])) {
            throw new Exception("Внимание! Не передан параметр 'mailTo'");
        }
        if (!isset($mailParams['mailTitle'])) {
            throw new Exception("Внимание! Не передан параметр 'mailTitle'");
        }
        if (!isset($mailParams['mailContent'])) {
            throw new Exception("Внимание! Не передан параметр 'mailContent'");
        }

        // От кого письмо
        if (isset($mailParams['mailFrom'])) {
            $mailFrom = $mailParams['mailFrom'];
        } else {
            $mailFrom = 'info@seor.com.ua';
        }

        // Кому. Добавляем info@carsrent.ru
        $a = explode(', ', $mailParams['mailTo']); // Ключи = [e-mail]
        $b = [];                                   // Значения = [Кому]
        $i = 1;
        foreach ($a as $item) {
            $b[] = 'Получатель ' . $i++;
        }
        $a[] = Yii::$app->params['supportEmail'];
        $b[] = 'Поддержка';
        $mailParams['mailTo'] = array_combine($a, $b);

        // Настройка параметров письма почты
        /** @var yii\symfonymailer\Mailer $messageObj */
        $messageObj = Yii::$app
            ->mailer
            ->compose(
                $layoutName,
                [
                    'content' => $mailParams['mailContent'],
                    //'imageFileName' => Yii::getAlias('@webroot') . '/new/images/email_images/head.png',
                ]
            )
            ->setFrom($mailFrom)
            ->setTo($mailParams['mailTo'])
            ->setSubject($mailParams['mailTitle'])
        ;

        // Отправка письма
        return $messageObj->send($mailParams['mailContent']);
    }
}

//$mailParams['mailTo'] = $model->client_email; // Нужно, чтобы список адресов был через ', '
//$mailParams['mailTitle'] = 'Заказ ' . $orderID . ' трансфера "' . $model->address_from . ', ' . $model->placeFrom->name_autocomplit . '->' . $model->address_to . ', ' . $model->placeTo->name_autocomplit . '" на сайте ' . Yii::$app->params['releaseSiteHostFull'];
//$mailParams['mailContent'] = Yii::$app
//    ->getView()
//    ->render('@common/mail/client-new_order', [
//        'model'      => $model,
//        'order_code' => $modelOrderCode->code,
//    ]);
//// TODO: Отправка почты
//if (MailSender::sendMail($mailParams)) {
//    file_put_contents($logFile, 'Письмо клиенту о заказе № ' . $orderID . ' отправлено' . "\n", FILE_APPEND);
//} else {
//    file_put_contents($logFile,'Письмо клиенту о заказе № ' . $orderID . ' не удалось отправить.' . "\n", FILE_APPEND);
//}
