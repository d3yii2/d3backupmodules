##Features

- Creates compressed backup of preferred module data with it's attachments. (run from console with cron)
- Sends out an email of process completion with link (depends on view)
- Checks expire parameter to delete old compressed files.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ composer require d3yii2/d3backupmodules "*"
```

or add

```
"d3yii2/d3backupmodules": "*"
```

to the `require` section of your `composer.json` file.


## Configuration
change console.php
```php
    'components' =>
        [
            'd3backupmodules' => [
                'class' => 'd3yii2\d3backupmodules\components\ModuleBackupBase',
                'modelComponents' => [2 => 'd3modules\d3invoices\components\InvInvoiceBackup'],
                'emailFrom' => 'Irēķini sistēma',
                'emailBody' => '<a href="{$link}" target="_blank">Lejuplādēt arhīvu</a>',
            ]
        ]
```
change main.php/web.php
```php
    'modules' => [
        'd3backupmodules' => [
            'class' => 'd3yii2\d3backupmodules\Module',
            'backupDirectory' => $basePath . '/runtime/backups/'
        ]
    ]
```
## SQL data backup

One user can be set as the data backup user (main.php / web.php):
```php
    'modules' => [
        'd3backupmodules' => [
            'class' => 'd3yii2\d3backupmodules\Module',
            'backupDirectory' => 'archives',
            'dataBackupUsername' => 'backup.user',   // user.username; empty - switched off
        ]
    ]
```
When this user creates an archive:
- `D3BackupModule::createEntry()` saves `user_id`, `username` and `dataBackup = true` in the `data` column;
- the component adds `data.sql` to the zip (INSERT statements, no mysqldump needed):
  own company, all partners, users (password hash and auth key are not exported) and module data;
- no "archive ready" e-mail is sent;
- a line is written to the data backup log `@runtime/logs/d3backup_data.log`
  (CommandRunner `dataBackupLogFile`) and Yii log category `d3backupmodules.dataBackup`:
  ```
  2026-11-03 21:40:00 | backup_id=62 | sys_company_id=5 | company=SIA Firma | user=backup.user | component=...InvInvoiceBackup | records=120 | sql_rows=3450 | sql_errors=0 | DONE | zip=.../archives/....zip
  ```
For other users archives are created as before.

Zips are deleted after `backupExpireDays` (default 5 days) - copy data backups in time.

Component implementation (see `InvInvoiceBackup::createSqlBackup()`):
```php
if ($module->isDataBackup()) {
    $dump = $this->createSqlDump();
    try {
        $this->sqlDumpCompanyData($dump, (int)$module->sys_company_id);
        $dump->table('inv_invoice', ['id' => $ids]);
    } finally {
        $this->finishSqlDump($dump);
    }
}
```

## Usage

After configuration, need to create custom component (see example bellow) in preferred module with view.

## Important

Data column in database should be json format keyed with "id", "error", "date_from", "date_to"

## Methods 
Create custom filename by passing variables with an additional 8 char random string  and returns it.
```php
public function createFileName(string $sys_company_name, string $list_name, string $date_from, string $date_to):string
```
Creates a directory by passed name and generates first index.html from given $html string.
```php 
public function createFile(string $fileName, string $html):string
```
Creates compressed file.
```php 
public function createZip(string $backUpFolder, string $fileNameFullPath, string $fileName, array $addFiles = [], D3BackupModule $model)
```

## Examples of Custom component example :

```php 
public function compile(array $backupData, D3BackupModule $model)
{
   $moduleBackup = new ModuleBackupBase();
   $fileName = $moduleBackup->createFileName('Company', 'Invoices', $model->getDataValue('date_from'), $model->getDataValue('date_to'));
   $html = \Yii::$app->view->render('@vendor/d3modules/d3invoices/views/d3yii2-backup/index', ['backupData' => $backupData]);
   $backupFolder = $moduleBackup->createFile($fileName, $html);
   $moduleBackup->createZip($backupFolder, $moduleBackup->backupDirectory.$fileName, $fileName, [], $model);
}
```