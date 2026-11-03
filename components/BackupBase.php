<?php

namespace d3yii2\d3backupmodules\components;

use d3system\helpers\D3FileHelper;
use CompanyRights\dictionaries\AuthCompanyAssigmentsDictionary;
use d3yii2\d3files\components\FileHandler;
use d3yii2\d3files\models\D3files;
use ReflectionException;
use vendor\d3yii2\d3backupmodules\model\D3BackupModule;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\db\Query;
use yii\helpers\VarDumper;

/**
 * Base class for module backup components.
 *
 * Each module that supports backups (e.g. invoices) extends this class and
 * implements compile(). The backup is processed by CommandRunner
 * (yii d3system/d3-component-command d3backupmodules):
 *  1. CommandRunner creates the component with tempDirectory and backupDirectory;
 *  2. calls compile($module) - the component writes index.html into
 *     {runtime}/{tempDirectory}/{folder} and fills $attachments, $folder,
 *     $emailSubject and optionally $emailBody;
 *  3. CommandRunner::createZip() packs the temp folder and attachments into
 *     {runtime}/{backupDirectory}/{folder}.zip and sends the e-mail.
 *
 * SQL data backup (only for archives created by Module::$dataBackupUsername):
 * compile() calls createSqlDump(), sqlDumpCompanyData(), adds module tables
 * with SqlDump::table() and finishes with finishSqlDump(). data.sql is added
 * to the zip together with index.html.
 */
class BackupBase extends Component
{

    /**
     * @var string directory (relative to runtime) for temporary files of the backup
     */
    public string $tempDirectory = '';

    /**
     * @var string directory (relative to runtime) where finished zip archives are saved
     */
    public string $backupDirectory = '';

    /**
     * @var array record attachments to add to the zip, grouped by record id:
     *      [recordId => [fullFilePath => fileNameInZip, ...], ...]
     *      (see getRecordFiles())
     */
    public array $attachments = [];

    /**
     * @var string name of the backup folder in tempDirectory, also used as
     *      the zip file name (without .zip). Must be set in compile().
     */
    public string $folder = '';

    /**
     * @var string|null e-mail body. Used only when CommandRunner::$emailBody
     *      is not configured. Nullable so CommandRunner can read it even
     *      when compile() does not set it (an uninitialized typed property
     *      would throw an Error).
     */
    public ?string $emailBody = null;

    /**
     * @var string e-mail subject. Should be set in compile().
     */
    public string $emailSubject = '';

    /**
     * @var array SQL data backup - rows count by table [table => count]
     *      (filled by finishSqlDump(), used for the data backup log)
     */
    public array $sqlStats = [];

    /**
     * @var array SQL data backup - errors by table [table => message]
     */
    public array $sqlErrors = [];

    /**
     * Prepares the backup data. Overwrite in the module component.
     *
     * Must set $folder, create the backup file(s) with createFile()
     * and fill $attachments and $emailSubject.
     *
     * @param D3BackupModule $module backup request (data: ids, date_from, date_to)
     */
    public function compile(D3BackupModule  $module): void
    {

    }

    /**
     * Creates a unique backup folder / zip file name:
     * {company}-{list}-{dateFrom}-{dateTo}-{random 8 chars}
     *
     * Characters not allowed in file names (e.g. quotes in 'SIA "Firma"')
     * and whitespace are replaced with '_', so the name works on Windows
     * and Linux.
     *
     * @param string $sys_company_name
     * @param string $list_name
     * @param string $date_from
     * @param string $date_to
     * @return string
     */
    public function createFileName(string $sys_company_name, string $list_name, string $date_from, string $date_to):string
    {
        $name = $sys_company_name
            . '-' . $list_name
            . '-' . $date_from
            . '-' . $date_to
            . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
        return preg_replace('/[\\\\\/:*?"<>|\s]+/u', '_', $name);
    }

    /**
     * Saves the backup HTML as {runtime}/{tempDirectory}/{folder}/index.html
     *
     * @param string $folder backup folder name (see createFileName())
     * @param string $html
     * @return string full path of the created file
     * @throws Exception
     */
    public function createFile(string $folder, string $html):string
    {
        return D3FileHelper::filePutContentInRuntime($this->tempDirectory . '/' . $folder, 'index.html', $html);
    }

    /**
     * Opens the SQL data backup file {runtime}/{tempDirectory}/{folder}/data.sql
     * $folder must be set before.
     *
     * @return SqlDump
     * @throws Exception
     */
    protected function createSqlDump(): SqlDump
    {
        if (!$this->folder) {
            throw new Exception('Backup folder is not set');
        }
        $directory = D3FileHelper::getRuntimeDirectoryPath($this->tempDirectory . '/' . $this->folder);
        return new SqlDump($directory . '/data.sql');
    }

    /**
     * Closes the SQL data backup file and saves statistics for the log
     */
    protected function finishSqlDump(SqlDump $dump): void
    {
        $dump->close();
        $this->sqlStats = $dump->stats;
        $this->sqlErrors = $dump->errors;
    }

    /**
     * Adds the system company data to the SQL data backup:
     *  - own company: d3c_company, branches, addresses, settings,
     *    inv_company, inv_company_settings, inv_bank_account, inv_numbers
     *  - all partners: the same partner list as in the system
     *    (AuthCompanyAssigmentsDictionary::getAllPartnersId()) - d3c_company,
     *    branches, addresses, contact persons
     *  - users: auth_assignment, user (password hash, auth key and
     *    registration IP are not exported), profile, d3p_person, d3p_person_contact
     *
     * Every table is written once, so the SQL can be loaded into an empty database.
     *
     * @param SqlDump $dump
     * @param int $sysCompanyId
     */
    protected function sqlDumpCompanyData(SqlDump $dump, int $sysCompanyId): void
    {
        /** own company + all partners */
        $companyIds = array_values(array_unique(array_merge(
            [$sysCompanyId],
            array_map('intval', AuthCompanyAssigmentsDictionary::getAllPartnersId($sysCompanyId))
        )));

        $dump->table('d3c_company', ['id' => $companyIds]);
        $dump->table('d3c_company_branch', ['company_id' => $companyIds]);
        $dump->table('d3c_address', [
            'id' => (new Query())
                ->select('address_id')
                ->from('d3c_company_branch')
                ->where(['company_id' => $companyIds])
        ]);
        $dump->table('auth_company_assigments', ['sys_company_id' => $sysCompanyId]);
        $dump->table('d3c_company_setting_val', ['sys_company_id' => $sysCompanyId]);

        /** invoice module company settings (inv_company - also partner settings) */
        $dump->table('inv_company', ['sys_company_id' => $sysCompanyId]);
        $dump->table('inv_company_settings', ['sys_company_id' => $sysCompanyId]);
        $dump->table('inv_bank_account', ['sys_company_id' => $sysCompanyId]);
        $dump->table('inv_numbers', ['sys_company_id' => $sysCompanyId]);
        $dump->table('inv_numbers_documents', [
            'number_id' => (new Query())
                ->select('id')
                ->from('inv_numbers')
                ->where(['sys_company_id' => $sysCompanyId])
        ]);

        /** users of the company */
        $userIds = (new Query())
            ->select('user_id')
            ->from('auth_assignment')
            ->where(['sys_company_id' => $sysCompanyId]);
        $dump->table('auth_assignment', ['sys_company_id' => $sysCompanyId]);
        /** password hash and auth key are NOT NULL - exported as empty strings */
        $dump->table('user', ['id' => $userIds], [], [
            'password_hash' => '',
            'auth_key' => '',
            'registration_ip' => null,
        ]);
        $dump->table('profile', ['user_id' => $userIds]);

        /** persons: company contact persons and users' persons */
        $dump->table('d3c_comapny_person', ['company_id' => $companyIds]);
        $personCondition = [
            'or',
            [
                'id' => (new Query())
                    ->select('person_id')
                    ->from('d3c_comapny_person')
                    ->where(['company_id' => $companyIds])
            ],
            ['user_id' => $userIds],
        ];
        $dump->table('d3p_person', $personCondition);
        $dump->table('d3p_person_contact', [
            'person_id' => (new Query())
                ->select('id')
                ->from('d3p_person')
                ->where($personCondition)
        ]);
    }

    /**
     * Returns the attached files (d3files) of one record for adding to the zip.
     * Files missing on disk are skipped and logged.
     *
     * @param string $className model class of the record
     * @param int $recordId
     * @return string[] [fullFilePath => '{recordId}_{nr}_{fileName}', ...]
     * @throws ReflectionException|\yii\db\Exception
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
                /** record id and sequence number in the name keep file names unique in the zip */
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
