yii2-autocrud
==========

[![Latest Stable Version](https://poser.pugx.org/bupy7/yii2-autocrud/v/stable)](https://packagist.org/packages/bupy7/yii2-page)
[![Total Downloads](https://poser.pugx.org/bupy7/yii2-pages/downloads)](https://packagist.org/packages/bupy7/yii2-pages)
[![Latest Unstable Version](https://poser.pugx.org/bupy7/yii2-pages/v/unstable)](https://packagist.org/packages/bupy7/yii2-pages)
[![License](https://poser.pugx.org/bupy7/yii2-pages/license)](https://packagist.org/packages/bupy7/yii2-pages)
[![Build Status](https://travis-ci.org/bupy7/yii2-autocrud.svg?branch=master)](https://travis-ci.org/bupy7/yii2-autocrud)
[![Coverage Status](https://coveralls.io/repos/github/bupy7/yii2-pages/badge.svg?branch=master)](https://coveralls.io/github/bupy7/yii2-autocrud?branch=master)

A static pages module implements CRUD using Imperavi Redactor.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist ripaym1970/yii2-autocrud "*"
```

or add

```
"ripaym1970/yii2-autocrud": "*"
```

to the require section of your `composer.json` file.


Installation
------------

**Add module in your config file:**

```php
'bootstrap' => ['autocrud'],

...

'modules' => [
    ...

    'autocrud' => 'ripaym1970\autocrud\Module',
]
```

> You must add the above config in your console config file to apply migrations.

> Without module in console config file this command will throw an exception.

Usage
-----

In module two controllers: ```default``` and ```manager```.

**manager** need for control the pages out of the control panel. You need 
protect it controller via ```controllerMap``` or override it for add behavior with ```AccessControl```.

Example:

```php
'modules' => [
    ...

    'autocrud' => [
        'class' => 'ripaym1970\autocrud\Module',
        
        ...

        'controllerMap' => [
            'manager' => [
                'class' => 'ripaym1970\autocrud\controllers\ManagerController',
                'as access' => [
                    'class' => AccessControl::className(),
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['admin'],
                        ],
                    ],
                ],
            ],
        ],
    ],
],
```

**default** for display of pages to site. You need add url rules to
file of config for getting content via aliases pages.

Example:

```php
'urlManager' => [
    'rules' => [
        ...
        ''           => 'site/index',
        '/'          => 'site/index',

        'gii'        => 'gii',
        //'db-manager' => 'db-manager',
        'crud/list'  => 'crud/list',

        '<action:(login|logout|signup|request-password-reset|password-reset)>' => 'site/<action>',

        '<table:[\w-]+>/<id:\d+>'                 => 'crud/view',
        '<table:[\w-]+>/index/<page:\d+>'         => 'crud/index',
        '<table:[\w-]+>/<action:[\w-]+>/<id:\d+>' => 'crud/<action>',
        '<table:[\w-]+>/<action:[\w-]+>'          => 'crud/<action>',
        '<table:[\w-]+>'                          => 'crud/index',
        '<table>'                                 => 'crud/index',
    ],
],
```

You can upload and add files/images via Imperavi Redactor, if enable it:

```php
'modules' => [
    ...
    
    'autocrud' => [
        'class' => 'ripaym1970\autocrud\Module',
        
        ...

        'pathToImages' => '@webroot/images',
        'urlToImages' => '@web/images',
        'pathToFiles' => '@webroot/files',
        'urlToFiles' => '@web/files',
        'uploadImage' => true,
        'uploadFile' => true,
        'addImage' => true,
        'addFile' => true,
    ],
],
```

Set up the custom language at Imperavi redactor:

```php
'modules' => [
    ...

    'autocrud' => [
        'class' => 'ripaym1970\autocrud\Module',
        'imperaviLanguage' => 'es',
    ],
]
```

License
-------

yii2-autocrud is released under the BSD 3-Clause License.
