<?php

namespace d3yii2\d3backupmodules\actions;

use d3system\helpers\D3FileHelper;
use d3yii2\d3backupmodules\controllers\DefaultController;
use Yii;
use yii\base\Action;
use yii\base\Exception;


/**
* Class download_backupAction 
* @package d3yii2\d3backupmodules\actions
* @property DefaultController $controller
*/
class DownloadBackupAction extends Action
{

    /**
     * @throws Exception
     */
    public function run(string $token)
    {
        $backupDir = $this->controller->module->backupDirectory;
        foreach (D3FileHelper::getDirectoryFiles($backupDir) as $file) {
            if (str_contains($token, md5(basename($file)))) {
                return Yii::$app->response->sendFile($file);
            }
        }
        /**
         * @todo jaatgriež paziņojums, ka fails av atrasts ar flashhelperi
         */
        return 'Requested file do not exist!';
    }
}
