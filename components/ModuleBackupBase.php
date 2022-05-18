<?php

namespace d3yii2\d3backupmodules\components;

use d3system\compnents\D3CommandComponent;
use d3system\controllers\D3ComponentCommandController;


class ModuleBackupBase extends D3CommandComponent {

    public function init(): void
    {

    }

    public function run(D3ComponentCommandController $controller): bool
    {
        parent::run($controller);
        return true;
    }


    public function createFileName(string $sys_company_name, string $list_name, string $date_from, string $date_to):string
    {
        return $sys_company_name . '-' . $list_name . '-' . $date_from . '-' . $date_to . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
    }

    public function createFile(string $html)
    {

    }


}
