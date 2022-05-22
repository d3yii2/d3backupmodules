<?php

namespace d3yii2\d3backupmodules\controllers;

use d3yii2\d3backupmodules\actions\DownloadBackupAction;
use eaBlankonThema\yii2\web\LayoutController;
use yii\filters\AccessControl;
use d3yii2\d3backupmodules\Module as BackupModule;

class DefaultController extends LayoutController
{
    /**
    * @var boolean whether to enable CSRF validation for the actions in this controller.
    * CSRF validation is enabled only when both this property and [[Request::enableCsrfValidation]] are true.
    */
    public $enableCsrfValidation = false;

    /**
    * specify route for identifing active menu item
    */
    public $menuRoute = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                [
                    'allow' => true,
                    'actions' => [
                        'download-backup',
                    ],
                    'roles' => BackupModule::getInstance()->backupDownloadRoles
                    ,
                ],
              ],
            ],
        ];
    }

    public function actions(): array
    {
        return [
            'download-backup' =>
            [
                'class' => DownloadBackupAction::class,
            ]
        ];
    }


}
