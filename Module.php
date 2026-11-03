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

    /**
     * @var string|null user name (user.username) of the data backup user.
     *
     * When this user creates an archive, the archive also gets an SQL data
     * backup (data.sql: company, partners, users and module data), no
     * "archive ready" e-mail is sent and the backup is written to the data
     * backup log (runtime/logs/d3backup_data.log).
     * For all other users archives are created as before.
     * Empty (default) - data backup is switched off.
     */
    public ?string $dataBackupUsername = null;


    public $controllerNamespace = 'd3yii2\d3backupmodules\controllers';

}
