<?php

namespace components;

use d3system\helpers\D3FileHelper;
use Exception;
use PhpOffice\PhpWord\Shared\ZipArchive;
use RuntimeException;
use vendor\d3yii2\d3backupmodules\model\D3BackupModule;
use yii\base\Component;
use yii\helpers\FileHelper;
use yii\helpers\Url;

/**
 * extend for module backup compnents
 */
class BackupBase extends Component
{

    public $tempDirectory;
    public $backupDirectory;

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
     * @param string $fileName
     * @param string $html
     * @return string
     * @throws \yii\base\Exception
     */
    public function createFile(string $fileName, string $html):string
    {
        return D3FileHelper::filePutContentInRuntime($this->tempDirectory, $fileName, $html);
    }



}