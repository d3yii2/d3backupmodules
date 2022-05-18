<?php

namespace vendor\d3yii2\d3backupmodules\model;

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
}
