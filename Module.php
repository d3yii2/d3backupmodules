<?php

namespace d3yii2\d3backupmodules;


use d3system\yii2\base\D3Module;

class Module extends D3Module
{
    /**
     * @var string path to email template view
     */
    public $emailTemplateView = '';

    /**
     * @var array access roles
     */
    public $backupDownloadRoles = ['user'];

    /**
     *  @var string directory path where backups are saved
     */
    public $backupDirectory = '';


    public $controllerNamespace = 'd3yii2\d3backupmodules\controllers';

}
