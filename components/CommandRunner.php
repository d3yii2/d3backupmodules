<?php

namespace d3yii2\d3backupmodules\components;

use d3system\compnents\D3CommandComponent;
use d3system\controllers\D3ComponentCommandController;
use d3system\dictionaries\SysModelsDictionary;
use d3system\helpers\D3FileHelper;
use d3yii2\d3pop3\components\D3Mail;
use DateInterval;
use DateTime;
use PhpOffice\PhpWord\Shared\ZipArchive;
use RuntimeException;
use vendor\d3yii2\d3backupmodules\model\D3BackupModule;
use Yii;
use yii\console\ExitCode;
use Exception;
use yii\helpers\FileHelper;
use yii\helpers\Url;


/**
 * Class ModuleBackupBase
 * @package d3yii2\d3backupmodules\components
 */
class CommandRunner extends D3CommandComponent
{

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
     * @var string directory in runtime directory where backups are saved
     */
    public $backupDirectory = 'modules_backups';

    /**
     * @var string directory in runtime directory
     */
    public $tempDirectory = 'modules_backups_temp';

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
        $this->backupDirectory = $this->backupDirectory;
        $this->tempDirectory = $this->tempDirectory;

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
            if (D3BackupModule::canRun()) {
                if ($model = D3BackupModule::find()
                    ->where(['status' => D3BackupModule::STATUS_NEW])
                    ->orderBy(['created' => SORT_DESC])
                    ->one()
                ) {
                        if (!$componentClass = SysModelsDictionary::getClassList()[$model->sys_model_id] ?? false) {
                            throw new \yii\base\Exception('Undefined sys_model_id: ' . $model->sys_model_id);
                        }
                        $componentClass = $this->modelComponents[$model->sys_model_id];
                        $component = new $componentClass([
                            'tempDirectory' => $this->tempDirectory,
                            'backupDirectory' => $this->backupDirectory
                        ]);

                        if (!is_callable(array($componentClass, 'compile'))) {
                            throw new \yii\base\Exception('Uncallable : ' . $componentClass . '->compile()');
                        } else {
                            $component->compile($model);
                            $this->createZip($model, $component->folder, $component->attachments);
                        }
                }
            }
        } catch (Exception $e) {
            $model->writeError($e);
        }
        return ExitCode::OK;
    }


    private function removeOldFiles(): void
    {
        foreach (D3FileHelper::getDirectoryFiles($this->backupDirectory) as $file) {
            $fullPath = $file;
            $time = new DateTime();
            $time->setTimestamp(filemtime($fullPath));
            $time->add(new DateInterval('P' . $this->backupExpireDays . 'D'));
            if ($time < new DateTime()) {
                unlink($fullPath);
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
        $plainBody = Yii::$app->view->render(
            $view,
            [
                'link' => $link,
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

    /**
     * @param D3BackupModule $model
     * @param string $folder
     * @param array $addFiles
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    public function createZip(
        D3BackupModule $model,
        string $folder,
        array $addFiles = []
    )
    {
        $zip = new ZipArchive();

        if ($zip->open(D3FileHelper::getRuntimeDirectoryPath($this->backupDirectory) . '/' . $folder . '.zip', ZipArchive::CREATE) !== TRUE) {
            throw new RuntimeException('Cannot create a zip file');
        }

        foreach(D3FileHelper::getDirectoryFiles($this->tempDirectory . '/' . $folder) as $file){
            $zip->addFile($file, $file);
        }

        foreach ($addFiles as $file) {
            $zip->addFile($file, $file);
        }
        $zip->close();

        FileHelper::removeDirectory(D3FileHelper::getRuntimeDirectoryPath($this->tempDirectory. '/' . $folder));

        $link = Url::toRoute([
            '/d3backupmodules/default/download-backup',
            'token' => md5(basename($folder . '.zip')),
        ]);

        $this->sendMail($model, $link, $this->emailTemplateView);
    }

}
