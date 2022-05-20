<?php

namespace d3yii2\d3backupmodules\components;

use d3system\compnents\D3CommandComponent;
use d3system\controllers\D3ComponentCommandController;
use d3system\helpers\D3FileHelper;
use d3system\models\SysModels;
use d3yii2\d3pop3\components\D3Mail;
use vendor\d3yii2\d3backupmodules\model\D3BackupModule;
use yii\console\ExitCode;
use yii\db\Exception;
use yii\helpers\FileHelper;
use ZipArchive;
use yii\helpers\Url;


/**
 * Class ModuleBackupBase
 * @package d3yii2\d3backupmodules\components
 */
class ModuleBackupBase extends D3CommandComponent {

    /**
     *  call with
     *  yii d3system/d3-component-command d3backupmodules
     *
     */

    /**
     * @var int specify how long backup will exist until deleted
     */
    public $backupExpireDays = 5;
    /**
     *  @var string directory path where backups are saved
     */
    public $backupDirectory;

    /**
     * @var string directory path for temporary files
     */
    public $tempDirectory;

    /**
     * @var string path to email template view
     */
    public $emailTemplateView = '@vendor/d3yii2/d3backupmodules/views/emails/index';

    /**
     * @var array associative array where keys are sys_model_id and value
     * is that module custom component path
     * example : [2 => 'd3modules\d3invoices\components\InvInvoiceBackup']
     */
    public $modelComponents = [];

    /**
     * ModuleBackupBase constructor.
     * @param array $config
     * @throws \yii\base\Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->backupDirectory = D3FileHelper::getRuntimeDirectoryPath('backups/');
        $this->tempDirectory = D3FileHelper::getRuntimeDirectoryPath('tmp/');
    }

    /**
     * @param D3ComponentCommandController $controller
     * @return bool
     */
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
                            ->where(['sys_comapny_id' => $model->sys_company_id, 'id' => $model->getDataValue('id')])
                            ->all();

                        $component = $this->modelComponents[$model->sys_model_id];

                        $component = new $component();
                        try {
                            if(is_callable($component->compile($backup_data, $model))) {
                                $component->compile($backup_data, $model);
                            }
                        } catch (\Exception $e) {
                            $model->writeError($e);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            die(print_r($e->getMessage(), true));
        }
        return ExitCode::OK;
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
        return $sys_company_name . '-' . $list_name . '-' . $date_from . '-' . $date_to . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
    }

    /**
     * @param string $fileName
     * @param string $html
     * @return string
     */
    public function createFile(string $fileName, string $html):string
    {
        mkdir($this->tempDirectory.$fileName);
        $indexFile = fopen($this->tempDirectory.$fileName.'/'."index.html", "w") or die("Unable to open file!");
        fwrite($indexFile, $html);
        fclose($indexFile);
        return $this->tempDirectory.$fileName;
    }

    /**
     * @param string $backUpFolder
     * @param string $fileNameFullPath
     * @param string $fileName
     * @param array $addFiles
     * @param D3BackupModule $model
     * @throws \yii\base\ErrorException
     */
    public function createZip(string $backUpFolder, string $fileNameFullPath, string $fileName, array $addFiles = [], D3BackupModule $model)
    {
            $zip = new ZipArchive();
            $files = array_diff(scandir($backUpFolder), array('.', '..'));

            if ($zip->open($fileNameFullPath.'.zip', ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create a zip file');
            }

            foreach($files as $file){
                $zip->addFile($backUpFolder.'/'.$file, $file);
            }

            foreach ($addFiles as $file) {
                $zip->addFile($file, $file);
            }

            $zip->close();

            FileHelper::removeDirectory($backUpFolder);

            $link = Url::toRoute(['/d3backupmodules/default/download-backup', 'md5' => md5($fileName.'.zip')]);
            $this->sendMail($model,$link, $this->emailTemplateView);
    }


    private function removeOldFiles()
    {
        $directory = $this->backupDirectory;
        $expireDays = $this->backupExpireDays;

        if(!file_exists($directory)) {
            mkdir($directory);
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if(strtotime('+ '.$expireDays.' days', filemtime($directory.$file)) < strtotime('now')) {
                unlink($directory. $file);
            }
        }
    }

    /**
     * @param D3BackupModule $model
     * @param string $link
     * @param string $view
     * @throws \ReflectionException
     * @throws \d3system\exceptions\D3ActiveRecordException
     * @throws \yii\base\Exception
     * @throws \yii\web\ForbiddenHttpException
     */
    private function sendMail(D3BackupModule $model, string $link, string $view)
    {
            $plainBody = \Yii::$app->view->render(
                $view,
                [
                    'link'      => $link,
                ]
            );

            $d3mail = new D3Mail();
            $d3mail->setEmailId(['SYS', $model->sys_company_id, 'INV', $model->id, date('YmdHis')])
            ->setSubject('Backup ready.')
            ->setBodyPlain($plainBody)
            ->setFromName('System Irēķini')
            ->addSendReceiveToInCompany($model->sys_company_id)
            ->setEmailModel($model)
            ->save();

            $model->setStatusDone();
            $model->save();
    }
}
