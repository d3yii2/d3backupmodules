<?php
namespace d3yii2\d3backupmodules;

use Yii;

class LeftMenu {

    public function list()
    {
        $user = Yii::$app->user;
        return [
            [
                'label' => Yii::t('', '????'),
                'type' => 'submenu',
                //'icon' => 'truck',
                'url' => ['/d3backupmodules/????/index'],
            ],
        ];
    }
}
