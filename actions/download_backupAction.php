<?php

namespace d3yii2\d3backupmodules\actions;

use yii\base\Action;
use d3yii2\d3backupmodules\Module;

/**
* Class download_backupAction 
* @package d3yii2\d3backupmodules\actions 
*/
class download_backupAction extends Action 
{

    private $defaultRoute = '';

    public function init(): void
    {
        $this->defaultRoute = Module::getInstance()->backupDirectory;
    }

    public function run($md5)
    {
        $files = scandir($this->defaultRoute);
        foreach ($files as $file) {
            if (strpos($md5, md5($file)) !== false) {
                return \Yii::$app->response->sendFile($this->defaultRoute . $file);
            }
        }
    }
}
