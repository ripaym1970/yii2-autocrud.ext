<?php

namespace ripaym1970\autocrud\components\HtmlActions;

use ripaym1970\autocrud\components\Yiit;
use Yii;
use yii\helpers\ArrayHelper;

class Icon extends \yii\base\BaseObject
{
    public const RELOAD_MODE_NO_RELOAD = 0;
    public const RELOAD_MODE_RELOAD_PARENT = 1;
    public const RELOAD_MODE_RELOAD_DOCUMENT = 2;

    /** @var string */
    public $icon = '';

    /** @var int */
    public $reloadMode = self::RELOAD_MODE_NO_RELOAD;

    /** @var bool $destroy */
    public $destroy = false;

    /** @var bool */
    public $enabled = true;

    /** @var bool */
    public $command = false;

    /** @var string|array */
    public $url = '#';

    /** @var array */
    public $attributes = [];

    /** @var boolean */
    public $dialogButtonOk = false;

    /** @var boolean */
    public $dialogButtonSave = false;

    /** @var boolean */
    public $dialogButtonRemove = false;

    /** @var boolean */
    public $dialogButtonClose = false;

    /** @var boolean */
    public $requiresConfirmation = false;

    /** @var string */
    public $dialogTitle = '';

    /** @var string */
    public $confirmationMessage = '';

    /** @var string */
    public $title = '';

    /** @var array */
    protected $classes = [
        'inline-action'
    ];

    protected const GRID_HEADER_ATTRIBUTES = [
        'class' => [
            'action-inline-create',
            'k-button',
            'btn',
            'btn-sm',
            'my-2'
        ],
    ];

    public function __toString()
    {
        if (!$this->enabled) {
            $this->classes = array_merge(
                $this->classes,
                ['js-disabled', 'disabled']
            );
        }
        if ($this->dialogButtonOk) {
            $this->classes = array_merge(
                $this->classes,
                ['js-dialog', 'js-dialog-ok']
            );
        }
        if ($this->dialogButtonSave) {
            $this->classes = array_merge(
                $this->classes,
                ['js-dialog', 'js-dialog-save']
            );
        }
        if ($this->dialogButtonRemove) {
            $this->classes = array_merge(
                $this->classes,
                ['js-dialog', 'js-dialog-remove']
            );
        }
        if ($this->dialogButtonClose) {
            $this->classes = array_merge(
                $this->classes,
                ['js-dialog', 'js-dialog-close']
            );
        }
        if ($this->destroy) {
            $this->classes[] = 'js-destroy';
        }
        if ($this->command) {
            $this->classes[] = 'js-command';
        }

        if ($this->reloadMode == self::RELOAD_MODE_RELOAD_PARENT) {
            $this->classes[] = 'js-reload-parent';
        } elseif ($this->reloadMode == self::RELOAD_MODE_RELOAD_DOCUMENT) {
            $this->classes[] = 'js-reload-document';
        }

        $dataParams = [];

        if ($this->dialogTitle) {
            $dataParams['title'] = $this->dialogTitle;
        }
        if ($this->confirmationMessage) {
            $dataParams['message'] = $this->confirmationMessage;
        }
        if ($this->requiresConfirmation) {
            $dataParams['requires-confirmation'] = $this->requiresConfirmation;
        }

        $attributes = ArrayHelper::merge(
            [
                'class' => $this->classes,
                'title' => $this->title,
            ],
            $this->attributes
        );

        if ($dataParams) {
            $attributes['data-params'] = $dataParams;
        }

        return \yii\helpers\Html::a(
            $this->icon,
            $this->url,
            $attributes
        );
    }

    public static function gridDialogEdit($url)
    {
        return new self([
            'url' => $url,
            'dialogButtonSave' => true,
            'reloadMode' => self::RELOAD_MODE_RELOAD_PARENT,
            'title' => Yii::t('app', "Edit"),
            'icon' => \rmrevin\yii\fontawesome\FAS::icon(
                \rmrevin\yii\fontawesome\FAS::_EDIT
            ),
        ]);
    }

    public static function edit($url)
    {
        $icon = self::gridDialogEdit($url);
        $icon->dialogButtonSave = false;
        $icon->reloadMode = self::RELOAD_MODE_NO_RELOAD;
        return $icon;
    }

    public static function gridDialogCreate($url)
    {
        return new self([
            'url' => $url,
            'dialogButtonSave' => true,
            'reloadMode' => self::RELOAD_MODE_RELOAD_PARENT,
            'title' => Yii::t('app', "Create"),
            'attributes' => static::GRID_HEADER_ATTRIBUTES,
            'icon' => \rmrevin\yii\fontawesome\FAS::icon(
                \rmrevin\yii\fontawesome\FAS::_PLUS_SQUARE
            )->size(\rmrevin\yii\fontawesome\FAS::SIZE_LARGE),
        ]);
    }

    public static function create($url)
    {
        $icon = self::gridDialogCreate($url);
        $icon->dialogButtonSave = false;
        $icon->reloadMode = self::RELOAD_MODE_NO_RELOAD;
        return $icon;
    }

    public static function gridCreateChildren($url)
    {
        return new self([
            'url' => $url,
            'dialogButtonSave' => true,
            'reloadMode' => self::RELOAD_MODE_RELOAD_PARENT,
            'title' => Yii::t('app', "Create child record"),
            'icon' => \rmrevin\yii\fontawesome\FAS::icon(
                \rmrevin\yii\fontawesome\FAS::_PLUS_SQUARE
            ),
        ]);
    }

    public static function gridDestroy($url)
    {
        return new self([
            'url' => $url,
            'destroy' => true,
            'reloadMode' => self::RELOAD_MODE_RELOAD_PARENT,
            'title' => Yii::t('app', "Remove"),
            'icon' => \rmrevin\yii\fontawesome\FAS::icon(
                \rmrevin\yii\fontawesome\FAS::_TIMES
            ),
        ]);
    }

    public static function gridUnlink($url){
        $icon = self::gridDestroy($url);
        $icon->icon = \rmrevin\yii\fontawesome\FAS::icon(
            \rmrevin\yii\fontawesome\FAS::_UNLINK
        );
        $icon->title = Yii::t('app', "Unlink");
        $icon->confirmationMessage = Yii::t(
            "app",
            "Are you sure you want to unlink this record ?"
        );
        return $icon;
    }

    public static function import($url)
    {
        $create = self::gridDialogCreate($url);
        $create->title = Yii::t('app', "Import");
        $create->icon = \rmrevin\yii\fontawesome\FAS::icon(
            \rmrevin\yii\fontawesome\FAS::_DOWNLOAD
        )->size(\rmrevin\yii\fontawesome\FAS::SIZE_LARGE);
        return $create;
    }

    public static function export($url)
    {
        $create = self::gridDialogCreate($url);
        $create->title = Yii::t('app', "Export");
        $create->icon = \rmrevin\yii\fontawesome\FAS::icon(
            \rmrevin\yii\fontawesome\FAS::_UPLOAD
        )->size(\rmrevin\yii\fontawesome\FAS::SIZE_LARGE);
        $create->dialogButtonSave = false;
        return $create;
    }

    public static function dialogView($url)
    {
        return new self([
            'url' => $url,
            'dialogButtonClose' => true,
            'title' => Yii::t('app', "View"),
            'dialogTitle' => Yii::t('app', "View"),
            'icon' => \rmrevin\yii\fontawesome\FAS::icon(
                \rmrevin\yii\fontawesome\FAS::_EYE
            ),
        ]);
    }

    public static function view($url)
    {
        $icon = self::dialogView($url);
        $icon->dialogButtonClose = false;
        return $icon;
    }

    public static function pdfView($url)
    {
        $icon = self::view($url);
        $icon->title = Yii::t('app', "PDF");
        $icon->icon = \rmrevin\yii\fontawesome\FAS::icon(
            \rmrevin\yii\fontawesome\FAS::_FILE_PDF
        );
        return $icon;
    }

    public static function copy($url)
    {
        return new self([
            'url' => $url,
            'title' => Yiit::t("Clone"),
            'confirmationMessage' => Yiit::t(
                "Are you sure you want to clone record ?"
            ),
            'requiresConfirmation' => true,
            'reloadMode' => self::RELOAD_MODE_RELOAD_PARENT,
            'command' => true,
            'icon' => \rmrevin\yii\fontawesome\FAS::icon(
                \rmrevin\yii\fontawesome\FAS::_COPY
            ),
        ]);
    }

    public static function beginIconsGroup()
    {
        return \yii\helpers\Html::beginTag(
            'div',
            [
                'class' => 'border border-primary rounded d-inline-block ml-1 pl-1'
            ]
        );
    }

    public static function endIconsGroup()
    {
        return \yii\helpers\Html::endTag('div');
    }
}
