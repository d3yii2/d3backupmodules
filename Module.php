<?php

namespace d3yii2\d3backupmodules;


use d3system\yii2\base\D3Module;

class Module extends D3Module
{
    /**
     *  @var string directory path where backups are saved
     */
    public $backupDirectory = '';

    /**
     * @var string directory path for temporary files
     */
    public $tempDirectory = '';

    /**
     * @var string specify how long backup will exist until deleted
     */
    public $backupExpireDays = '+ 5 days';

    /**
     * @var string path to email template view
     */
    public $emailTemplateView = '';

    /**
     * @var array access roles
     */
    public $backupDownloadRoles = ['user'];

    public $modelComponents = [2 => 'd3models\d3invoices\components\InvInvoiceBackup'];

    public $controllerNamespace = 'd3yii2\d3backupmodules\controllers';

}
