<?php

namespace d3yii2\d3backupmodules\components;

use d3system\compnents\D3CommandComponent;
use d3system\controllers\D3ComponentCommandController;
use d3system\models\SysModels;
use vendor\d3yii2\d3backupmodules\model\D3BackupModule;
use yii\console\ExitCode;
use yii\db\Exception;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use ZipArchive;


class ModuleBackupBase extends D3CommandComponent {



    /**
     *
     *  yii d3system/d3-component-command d3backupmodules
     *
     */

    /**
     * @var string specify how long backup will exist until deleted
     */
    public $backupExpireDays = '+ 5 days';
    /**
     *  @var string directory path where backups are saved
     */
    public $backupDirectory;
    /**
     * @var string directory path for temporary files
     */
    public $tempDirectory;

    public $modelComponents = [];

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->backupDirectory = \Yii::getAlias('@runtime').'/backups/';
        $this->tempDirectory = \Yii::getAlias('@runtime').'/tmp/';
    }

    public function run(D3ComponentCommandController $controller): bool
    {
        parent::run($controller);
        try {
            $this->removeOldFiles();
            if(D3BackupModule::canRun()) {
                if($model = D3BackupModule::find()
                    ->where(['status' => D3BackupModule::STATUS_NEW])
                    ->orderBy(['created' => SORT_DESC])
                    ->one()) {

                    if($class = SysModels::findOne($model->sys_model_id)) {
                        $class_model = new $class->class_name();
                        $backup_data = $class_model->find()
                            ->where(['sys_comapny_id' => $model->sys_company_id, 'id' => Json::decode($model->data, false)])
                            ->all();

                        $component = $this->modelComponents[$model->sys_model_id];

                        $component = new $component();
                        if(is_callable($component->compile($backup_data))) {
                            $component->compile($backup_data);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            die(print_r($e->getMessage(), true));
        }
        return ExitCode::OK;
    }


    public function createFileName(string $sys_company_name, string $list_name, string $date_from, string $date_to):string
    {
        return $sys_company_name . '-' . $list_name . '-' . $date_from . '-' . $date_to . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
    }

    public function createFile(string $fileName, string $html):string
    {
        try {
            mkdir($this->tempDirectory.$fileName);
            $indexFile = fopen($this->tempDirectory.$fileName.'/'."index.html", "w") or die("Unable to open file!");
            fwrite($indexFile, $html);
            fclose($indexFile);
        } catch (\Exception $e) {
            die(print_r($e->getMessage(), true));
        }
        return $this->tempDirectory.$fileName;
    }

    public function createZip(string $backUpFolder, string $fileName)
    {
        try {
            $zip = new ZipArchive();
            $files = array_diff(scandir($backUpFolder), array('.', '..'));

            if ($zip->open($fileName.'.zip', ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create a zip file');
            }

            foreach($files as $file){
                $zip->addFile($backUpFolder.'/'.$file, $file);
            }

            $zip->close();
            $this->sendMail();

            FileHelper::removeDirectory($backUpFolder);

        } catch (\Exception $e) {
            die(print_r($e->getMessage(), true));
        }
    }


    public function removeOldFiles()
    {
        $directory = $this->backupDirectory;
        $expireDays = $this->backupExpireDays;

        if(!file_exists($directory)) {
            mkdir($directory);
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if(strtotime($expireDays, filemtime($directory.$file)) < strtotime('now')) {
                unlink($directory. $file);
            }
        }
    }

    public function sendMail()
    {

    }
}
