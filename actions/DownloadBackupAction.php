<?php

namespace d3yii2\d3backupmodules\actions;

use d3system\helpers\D3FileHelper;
use Yii;
use yii\base\Action;


/**
* Class download_backupAction 
* @package d3yii2\d3backupmodules\actions
*/
class DownloadBackupAction extends Action
{

    public function run(string $token)
    {
        $backupDir = $this->controller->module->backupDirectory;
        foreach (D3FileHelper::getDirectoryFiles($backupDir) as $file) {
            if (strpos($token, md5(basename($file))) !== false) {
                return Yii::$app->response->sendFile($file);
            }
        }
        /**
         * @todo jaatgriež paziņojums, ka fails av atrasts ar flashhelperi
         */
        return 'Requested file do not exist!';
    }
}
