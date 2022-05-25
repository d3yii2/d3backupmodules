<?php

namespace d3yii2\d3backupmodules\components;

use d3system\helpers\D3FileHelper;
use vendor\d3yii2\d3backupmodules\model\D3BackupModule;
use yii\base\Component;

/**
 * extend for module backup compnents
 */
class BackupBase extends Component
{

    public $tempDirectory;
    public $backupDirectory;

    public $attachments = [];
    public $folder;

    public function compile(D3BackupModule  $module)
    {

    }

    /**
     * @param string $sys_company_name
     * @param string $list_name
     * @param string $date_from
     * @param string $date_to
     * @return string
     */
    public function createFileName(string $sys_company_name, string $list_name, string $date_from, string $date_to):string
    {
        return $sys_company_name
            . '-' . $list_name
            . '-' . $date_from
            . '-' . $date_to
            . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
    }

    /**
     * @param string $folder
     * @param string $html
     * @return string
     * @throws \yii\base\Exception
     */
    public function createFile(string $folder, string $html):string
    {
        return D3FileHelper::filePutContentInRuntime($this->tempDirectory . '/' . $folder, 'index.html', $html);
    }



}