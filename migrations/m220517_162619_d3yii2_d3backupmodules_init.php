<?php

use yii\db\Migration;

class m220517_162619_d3yii2_d3backupmodules_init  extends Migration {

    public function safeUp() { 
        $this->execute('
        CREATE TABLE `d3backup_module` (
              `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
              `sys_company_id` smallint(5) unsigned NOT NULL,
              `status` ENUM(\'new\',\'processing\',\'failed\',\'done\') DEFAULT \'new\',
              `sys_model_id` tinyint(3) unsigned NOT NULL, 
              `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              `data` LONGTEXT DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `sys_company_id` (`sys_company_id`),
              KEY `sys_model_id` (`sys_model_id`),
              CONSTRAINT `d3a_backupmodules_ibfk_1` FOREIGN KEY (`sys_company_id`) REFERENCES `d3c_company` (`id`),
              CONSTRAINT `d3a_backupmodules_ibfk_2` FOREIGN KEY (`sys_model_id`) REFERENCES `sys_models` (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
        
        ');
    }

    public function safeDown() {
        echo "m220517_162619_d3yii2_d3backupmodules_init cannot be reverted.\n";
        return false;
    }
}
