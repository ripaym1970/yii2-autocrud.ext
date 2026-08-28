# Yii2 Multiple input widget.

Виджет Yii2 для обработки нескольких входных данных для атрибута модели и табличного ввода для пакета моделей.

[![Latest Stable Version](https://poser.pugx.org/unclead/yii2-multiple-input/v/stable)](https://packagist.org/packages/unclead/yii2-multiple-input)

## Последний релиз
Последняя стабильная версия расширения — v2.27.0. Следуйте [инструкции](./UPGRADE.md) для обновления с предыдущих версий.

## Базовое использование
![Single column example](./resources/images/single-column.gif?raw=true)

Например, вы хотите иметь возможность вводить несколько адресов электронной почты пользователя на странице профиля.
В этом случае вы можете использовать виджет yii2-multiple-input, как показано в следующем коде.

```php
use ripaym1970\autocrud\components\widgets\MultipleInput;

...

<?php
echo $form->field($model, 'emails')
    ->widget(MultipleInput::className(), [
        'max'               => 6,
        'min'               => 2, // должно быть не менее 2 строк
        'allowEmptyList'    => false,
        'enableGuessTitle'  => true,
        'addButtonPosition' => MultipleInput::POS_HEADER, // показать кнопку добавления в шапке справа
    ])
    ->label(false);
?>
```

## Документация
Вы можете найти полную версию документации [здесь](https://unclead.github.io/yii2-multiple-input/)

## Изменения для этого проекта
Теперь кнопка добавления строки выводится над колонкой перемещения (выводится всегда)
