<?php

namespace d3yii2\d3backupmodules\components;

use d3system\helpers\D3FileHelper;
use d3yii2\d3files\components\FileHandler;
use d3yii2\d3files\models\D3files;
use vendor\d3yii2\d3backupmodules\model\D3BackupModule;
use Yii;
use yii\base\Component;
use yii\helpers\VarDumper;

/**
 * extend for module backup compnents
 */
class BackupBase extends Component
{

    public $tempDirectory;
    public $backupDirectory;

    public $attachments = [];
    public $folder;

    public $emailBody;
    public $emailSubject;

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

    /**
     * @param string $className
     * @param int $recordId
     * @return string[]
     * @throws \ReflectionException|\yii\db\Exception
     */
    public static function getRecordFiles(string $className, int $recordId): array
    {
        $files = [];
        $i = 1;
        foreach (D3files::fileListForWidget($className, $recordId, true) as $file) {
            $fileHandler = new FileHandler([
                'model_name' => $file['className'],
                'model_id' => $file['id'],
                'file_name' => $file['file_name']
            ]);
            $filePath = $fileHandler->getFilePath();
            if (file_exists($filePath)) {
                $files[$filePath] = $recordId . '_' . $i . '_' . $file['file_name'];
                $i ++;
            } else {
                Yii::error(
                    'Neatrada failu: ' . VarDumper::dumpAsString($file) . PHP_EOL .
                    ';  Path: ' . $filePath
                );
            }
        }
        return $files;
    }

}