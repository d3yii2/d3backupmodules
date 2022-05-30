<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace vendor\d3yii2\d3backupmodules\model\base;

use Yii;
use d3system\behaviors\D3DateTimeBehavior;
use d3yii2\d3pop3\models\D3cCompany;
use d3system\models\SysModels;
use yii\db\ActiveRecord;

/**
 * This is the base-model class for table "d3backup_module".
 *
 * @property integer $id
 * @property integer $sys_company_id
 * @property string $status
 * @property integer $sys_model_id
 * @property string $created
 * @property string $updated
 * @property string $data
 *
 * @property \vendor\d3yii2\d3backupmodules\model\D3cCompany $sysCompany
 * @property \vendor\d3yii2\d3backupmodules\model\SysModels $sysModel
 * @property string $aliasModel
 */
abstract class D3BackupModule extends ActiveRecord
{



    /**
    * ENUM field values
    */
    public const STATUS_NEW = 'new';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DONE = 'done';
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return 'd3backup_module';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = [
        ];
        $behaviors = array_merge(
            $behaviors,
            D3DateTimeBehavior::getConfig(['created','updated'])
        );
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            'required' => [['sys_company_id', 'sys_model_id'], 'required'],
            'enum-status' => ['status', 'in', 'range' => [
                    self::STATUS_NEW,
                    self::STATUS_PROCESSING,
                    self::STATUS_FAILED,
                    self::STATUS_DONE,
                ]
            ],
            'tinyint Unsigned' => [['sys_model_id'],'integer' ,'min' => 0 ,'max' => 255],
            'smallint Unsigned' => [['id','sys_company_id'],'integer' ,'min' => 0 ,'max' => 65535],
            [['status', 'data'], 'string'],
            [['created', 'updated'], 'safe'],
            [['sys_company_id'], 'exist', 'skipOnError' => true, 'targetClass' => D3cCompany::className(), 'targetAttribute' => ['sys_company_id' => 'id']],
            [['sys_model_id'], 'exist', 'skipOnError' => true, 'targetClass' => SysModels::className(), 'targetAttribute' => ['sys_model_id' => 'id']],
            'D3DateTimeBehavior' => [['created_local','updated_local'],'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sys_company_id' => 'Sys Company ID',
            'status' => 'Status',
            'sys_model_id' => 'Sys Model ID',
            'created' => 'Created',
            'updated' => 'Updated',
            'data' => 'Data',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSysCompany()
    {
        return $this->hasOne(\vendor\d3yii2\d3backupmodules\model\D3cCompany::className(), ['id' => 'sys_company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSysModel()
    {
        return $this->hasOne(\vendor\d3yii2\d3backupmodules\model\SysModels::className(), ['id' => 'sys_model_id']);
    }




    /**
     * get column status enum value label
     * @param string $value
     * @return string
     */
    public static function getStatusValueLabel(string $value): string
    {
        if (!$value) {
            return '';
        }
        $labels = self::optsStatus();
        return $labels[$value] ?? $value;
    }

    /**
     * column status ENUM value labels
     * @return string[]
     */
    public static function optsStatus(): array
    {
        return [
            self::STATUS_NEW => 'new',
            self::STATUS_PROCESSING => 'processing',
            self::STATUS_FAILED => 'failed',
            self::STATUS_DONE => 'done',
        ];
    }
    /**
    * ENUM field values
    */
    /**
     * @return bool
     */
    public function isStatusNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

     /**
     * @return void
     */
    public function setStatusNew(): void
    {
        $this->status = self::STATUS_NEW;
    }
    /**
     * @return bool
     */
    public function isStatusProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

     /**
     * @return void
     */
    public function setStatusProcessing(): void
    {
        $this->status = self::STATUS_PROCESSING;
    }
    /**
     * @return bool
     */
    public function isStatusFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

     /**
     * @return void
     */
    public function setStatusFailed(): void
    {
        $this->status = self::STATUS_FAILED;
    }
    /**
     * @return bool
     */
    public function isStatusDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

     /**
     * @return void
     */
    public function setStatusDone(): void
    {
        $this->status = self::STATUS_DONE;
    }
}
