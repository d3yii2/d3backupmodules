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
     * @var string set the email from e-mail
     */
    public $emailFrom;

    /**
     * @var string email body text
     */
    public $emailBody;

    /**
     * @var array associative array where keys are sys_model_id and value
     * is that module custom component path
     * example : [2 => 'd3modules\d3invoices\components\InvInvoiceBackup']
     */
    public $modelComponents = [];

    /**
     * @var int set how many archives to generate in one run
     */
    public $archivesPerRun;

    /**
     * ModuleBackupBase constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->backupDirectory = $config['backupDirectory'];
        $this->tempDirectory = $config['tempDirectory'];
        $this->emailFrom = $config['emailFrom'];
        $this->emailBody = $config['emailBody'];
        $this->archivesPerRun = $config['archivesPerRun'];
    }

    /**
     * @param D3ComponentCommandController $controller
     * @return bool
     */
    public function run(D3ComponentCommandController $controller): bool
    {
        parent::run($controller);
        try {
            $this->out('Remove old files');
            $this->removeOldFiles();
            $this->out(' - done');
            if (D3BackupModule::canRun()) {
                foreach(D3BackupModule::find()
                    ->where(['status' => D3BackupModule::STATUS_NEW])
                    ->orderBy(['created' => SORT_DESC])
                    ->limit($this->archivesPerRun)->all() as $model
                ) {
                    Yii::$app->SysCmp->setActiveId($model->sys_company_id);
                    $this->out('id=' . $model->id . ' created: ' . $model->created);
                        if (!$componentClass = SysModelsDictionary::getClassList()[$model->sys_model_id] ?? false) {
                            throw new \yii\base\Exception('Undefined sys_model_id: ' . $model->sys_model_id);
                        }
                    $this->out(' compnent: ' . $componentClass);
                        $component = new $componentClass([
                            'tempDirectory' => $this->tempDirectory,
                            'backupDirectory' => $this->backupDirectory
                        ]);

                        if (!is_callable(array($componentClass, 'compile'))) {
                            throw new \yii\base\Exception('Uncallable : ' . $componentClass . '->compile()');
                        }
                        $component->compile($model);
                        $this->createZip($model, $component);
                }
            }
        } catch (Exception $e) {
            Yii::error($e->getMessage() . PHP_EOL . $e->getTraceAsString());
            $this->out($e->getMessage() . PHP_EOL . $e->getTraceAsString());
            if (isset($model)) {
                $model->writeError($e);
            }
        }
        return true;
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
     * @param BackupBase $component
     * @param string $view
     * @throws \ReflectionException
     * @throws \d3system\exceptions\D3ActiveRecordException
     * @throws \yii\base\Exception
     * @throws \yii\web\ForbiddenHttpException
     */
    private function sendMail(D3BackupModule $model, string $view, BackupBase $component)
    {
        if ($this->emailBody) {
            $link = Url::toRoute([
                '/d3backupmodules/default/download-backup',
                'token' => md5(basename($component->folder . '.zip')),
            ]);
            $plainBody = str_replace('{$link}', $link, $this->emailBody);
        } else {
            $plainBody = Yii::$app->view->render(
                $view,
                [
                    'body' => $component->emailBody,
                ]
            );
        }

        $d3mail = new D3Mail();
        $d3mail->setEmailId(['SYS', $model->sys_company_id, 'INV', $model->id, date('YmdHis')])
            ->setSubject($component->emailSubject)
            ->setBodyPlain($plainBody)
            ->setFromEmail($this->emailFrom)
            ->addSendReceiveToInCompany($model->sys_company_id)
            ->save();

        $model->setStatusDone();
        $model->save();
    }

    /**
     * @param D3BackupModule $model
     * @param BackupBase $component
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception|\ReflectionException
     */
    public function createZip(
        D3BackupModule $model,
        BackupBase $component
    )
    {
        $zip = new ZipArchive();

        if ($zip->open(D3FileHelper::getRuntimeDirectoryPath($this->backupDirectory) . '/' . $component->folder . '.zip', ZipArchive::CREATE) !== TRUE) {
            throw new RuntimeException('Cannot create a zip file');
        }

        foreach(D3FileHelper::getDirectoryFiles($this->tempDirectory . '/' . $component->folder) as $file){
            $zip->addFile($file, basename($file));
        }

        foreach ($component->attachments as $recordAttchments) {
            foreach ($recordAttchments as $fullPath => $name) {
                $zip->addFile($fullPath, 'attachments/' . $name);
            }
        }

        $zip->close();

        FileHelper::removeDirectory(D3FileHelper::getRuntimeDirectoryPath($this->tempDirectory. '/' . $component->folder));

        $this->sendMail($model, $this->emailTemplateView, $component);
    }

}
