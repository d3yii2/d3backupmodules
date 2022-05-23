<?php

namespace d3yii2\d3backupmodules\actions;

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
        $defaultRoute = $this->controller->module->backupDirectory;
        $files = scandir($defaultRoute);
        foreach ($files as $file) {
            if (strpos($token, md5($file)) !== false) {
                return Yii::$app->response->sendFile($defaultRoute . $file);
            }
        }
        /**
         * @todo jaatgriež paziņojums, ka fails av atrasts
         */
        return 'Requested file do not exist!';
    }
}
