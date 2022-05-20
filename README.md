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