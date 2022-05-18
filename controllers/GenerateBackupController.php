<?php

namespace d3yii2\d3backupmodules\controllers;

use d3system\commands\D3CommandController;
use d3system\models\SysModels;
use d3yii2\d3backupmodules\Module;
use vendor\d3yii2\d3backupmodules\model\D3BackupModule;
use yii\console\ExitCode;
use yii\db\Exception;
use yii\helpers\Json;


class GenerateBackupController extends D3CommandController
{
    /**
     * default action
     * @return int
     */

    public function actionIndex(): int
    {

        try {
            if(D3BackupModule::canRun()) {
                if($model = D3BackupModule::find()
                    ->where(['status' => D3BackupModule::STATUS_NEW])
                    ->orderBy(['created' => SORT_DESC])
                    ->one()) {

                    if($class = SysModels::findOne($model->sys_model_id)) {
                        $class_model = new $class->class_name();
                        $backup_data = $class_model->find()
                            ->where(['sys_comapny_id' => $model->sys_company_id, 'id' => Json::decode($model->data, false)])
                            ->all();

                        $component = Module::getInstance()->modelComponents[$model->sys_model_id];

                        if(is_callable($component->run())) {
                            $html = $component->run($backup_data);
                        }

                    }
                }

            }
        } catch (Exception $e) {
            die(print_r($e->getMessage(), true));
        }
        return ExitCode::OK;
    }

}

