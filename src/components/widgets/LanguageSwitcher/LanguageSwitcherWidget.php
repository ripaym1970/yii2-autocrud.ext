<?php

namespace ripaym1970\autocrud\components\widgets\LanguageSwitcher;

use ripaym1970\autocrud\models\crud\LanguageModel;
use Yii;
use yii\base\Widget;
use yii\web\View;

class LanguageSwitcherWidget extends Widget
{
    public $languages = [
        'uk' => 'Українська',
    ];
    ///** @var int время жизни cookie (в днях) */
    //public $cookieExpire = 3;

    public function init()
    {
        // Получим используемые языки
        $languages = LanguageModel::findAll(['active' => true]);
        if ($languages) {
            foreach ($languages as $language) {
                $this->languages[$language->id] = $language->name;
            }
        }
    }

    public function run()
    {
        // Вывод через frontend/web/css/scss/language.scss
        echo '<div class="dropdown switcher-language" data-lang="' . Yii::$app->language . '">
            <a href="#" data-toggle="dropdown" class="dropdown-toggle-language" aria-expanded="true">
                <span class="language-block">' . $this->languages[Yii::$app->language] . '</span>'
            //. '<b class="caret">
            //        <svg height="20" width="14" fill="white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 213.333 213.333" enable-background="new 0 0 213.333 213.333"><path d="M0 53.333l106.667 106.667 106.666-106.667z"></path></svg>
            //    </b>'
            . '</a>
            <ul class="language-list dropdown-menu">';
        foreach ($this->languages as $languageCode => $languageName) {
            if ($languageCode == Yii::$app->language) {
                continue;
            }
            // Надо первым '/', т.к. заменяет все вхождения 'en'. Например: '/EN/paymENt/'
            $url = rtrim(str_replace('/' . Yii::$app->language, '', $_SERVER['REQUEST_URI']), '/');
            $href = ($languageCode == Yii::$app->urlManager->getDefaultLanguage() ? '' : '/' . $languageCode) . $url;
            echo '<li><a class="dropdown-item" href="' . $href . '">'
                . '<span class="language-block">' . $languageName . '</span>'
                . '</a></li>';
        }
        echo '</ul>
        </div>';

        $this->registerJs();
    }

    public function registerJs()
    {
        $js = <<< JS
$('.switcher-language').on('click', function() {
    $('.language-list').toggleClass('show');
});
JS;
        $this->view->registerJs($js, View::POS_END);
    }
}
