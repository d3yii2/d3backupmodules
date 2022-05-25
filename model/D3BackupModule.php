<?php

namespace vendor\d3yii2\d3backupmodules\model;

use d3system\dictionaries\SysModelsDictionary;
use d3system\exceptions\D3ActiveRecordException;
use yii\helpers\Json;
use vendor\d3yii2\d3backupmodules\model\base\D3BackupModule as BaseD3BackupModule;

/**
 * This is the model class for table "d3backup_module".
 */
class D3BackupModule extends BaseD3BackupModule
{
    public static function canRun():bool
    {
        return empty(D3BackupModule::findOne(['status' => D3BackupModule::STATUS_PROCESSING]));
    }

    public function writeError($msg)
    {
        $data = Json::decode($this->data, true);
        $data['errors'] = $msg;
        $this->data = Json::encode($data);
        $this->setStatusFailed();
        $this->save();
    }

    public function getDataValue(string $key)
    {
        $ids = Json::decode($this->data, true);
        return isset($ids[$key]) ? $ids[$key] : '';
    }

    public static function createEntry(string $sys_model_class, int $sys_company_id, array $ids, string $date_from, string $date_to):bool
    {
        $model = new D3BackupModule();
        $model->sys_model_id = SysModelsDictionary::getIdByClassName($sys_model_class);
        $model->sys_company_id = $sys_company_id;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;
        $data['error'] = '';
        $data['id'] = array_values($ids);
        $model->data = Json::encode($data);
        if(!$model->save()) {
            throw new D3ActiveRecordException($model);
        }

        return true;
    }
}
