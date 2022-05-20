<?php

namespace d3yii2\d3backupmodules;


use d3system\yii2\base\D3Module;

class Module extends D3Module
{
    /**
     * @var array access roles for download action
     */
    public $backupDownloadRoles = ['User'];

    /**
     *  @var string directory path where backups are saved
     *  and needed as download link source
     */
    public $backupDirectory;


    public $controllerNamespace = 'd3yii2\d3backupmodules\controllers';

}
