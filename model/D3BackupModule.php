<?php

namespace vendor\d3yii2\d3backupmodules\model;

use d3system\dictionaries\SysModelsDictionary;
use d3system\exceptions\D3ActiveRecordException;
use d3yii2\d3backupmodules\Module;
use Throwable;
use Yii;
use yii\helpers\Json;
use vendor\d3yii2\d3backupmodules\model\base\D3BackupModule as BaseD3BackupModule;
use yii\web\User;

/**
 * This is the model class for table "d3backup_module".
 *
 * Column data (JSON):
 *  - id         - array of record ids to archive
 *  - date_from  - period start
 *  - date_to    - period end
 *  - error      - error text
 *  - user_id    - id of the user who created the archive request
 *  - username   - user name of the user who created the archive request
 *  - dataBackup - true, if the request was created by the data backup user
 *                 (Module::$dataBackupUsername) - add SQL data backup,
 *                 do not send e-mail
 */
class D3BackupModule extends BaseD3BackupModule
{
    public static function canRun():bool
    {
        return empty(D3BackupModule::findOne(['status' => D3BackupModule::STATUS_PROCESSING]));
    }

    public function writeError($msg)
    {
        $data = Json::decode($this->data, true);
        $data['errors'] = $msg;
        $this->data = Json::encode($data);
        $this->setStatusFailed();
        $this->save();
    }

    public function getDataValue(string $key)
    {
        $ids = Json::decode($this->data, true);
        return isset($ids[$key]) ? $ids[$key] : '';
    }

    /**
     * Is SQL data backup required for this archive request
     * (request created by the data backup user)
     */
    public function isDataBackup(): bool
    {
        return (bool)$this->getDataValue('dataBackup');
    }

    public static function createEntry(string $sys_model_class, int $sys_company_id, array $ids, string $date_from, string $date_to):bool
    {
        $model = new D3BackupModule();
        $model->sys_model_id = SysModelsDictionary::getIdByClassName($sys_model_class);
        $model->sys_company_id = $sys_company_id;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;
        $data['error'] = '';
        $data['id'] = array_values($ids);

        /** who created the request; decides about the SQL data backup */
        [$userId, $username] = self::getCurrentUser();
        $data['user_id'] = $userId;
        $data['username'] = $username;
        $data['dataBackup'] = self::isDataBackupUsername($username);

        $model->data = Json::encode($data);
        if(!$model->save()) {
            throw new D3ActiveRecordException($model);
        }

        return true;
    }

    /**
     * Is the user name equal to Module::$dataBackupUsername.
     * Module d3backupmodules must be configured in the web application (main.php).
     */
    public static function isDataBackupUsername(?string $username): bool
    {
        if (!$username || !Yii::$app->hasModule('d3backupmodules')) {
            return false;
        }
        $module = Yii::$app->getModule('d3backupmodules');
        if (!$module instanceof Module || !$module->dataBackupUsername) {
            return false;
        }
        return $username === $module->dataBackupUsername;
    }

    /**
     * Logged in user [id, username]. In console or for guest returns [null, null].
     * @return array
     */
    private static function getCurrentUser(): array
    {
        try {
            if (!Yii::$app->has('user')) {
                return [null, null];
            }
            $user = Yii::$app->get('user');
            if (!$user instanceof User || $user->isGuest) {
                return [null, null];
            }
            $identity = $user->identity;
            return [$user->id, $identity->username ?? null];
        } catch (Throwable $e) {
            Yii::error($e);
            return [null, null];
        }
    }
}
