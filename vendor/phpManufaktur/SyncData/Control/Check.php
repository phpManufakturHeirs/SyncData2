<?php

/**
 * SyncData
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/SyncData
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\SyncData\Control;

use phpManufaktur\SyncData\Control\Application;
use phpManufaktur\SyncData\Data\BackupMaster;
use phpManufaktur\SyncData\Data\General;
use phpManufaktur\SyncData\Data\BackupTables;

class Check
{

    protected $app = null;
    protected static $backup_id = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function checkTable($table)
    {
        try {
            $General = new General($this->app);
            $BackupTables = new BackupTables($this->app);

            // get the actual checksum of the table
            $checksum = $General->getTableContentChecksum(CMS_TABLE_PREFIX.$table['table_name']);
            if ($checksum !== $table['last_checksum']) {
                echo "differ: ".$table['table_name']." $checksum - ".$table['last_checksum']."<br>";
            }
        } catch (\Exception $e) {
        }
    }

    public function exec()
    {
        $BackupMaster = new BackupMaster($this->app);
        // first we need the last backup ID
        if (false === (self::$backup_id = $BackupMaster->getLastBackupID())) {
            $result = "Got no backup ID for processing a check for changed tables and files. Please create a backup first!";
            $this->app['monolog']->addInfo($result);
            return $result;
        }
        if (false === ($tables = $BackupMaster->selectTablesByBackupID(self::$backup_id))) {
            $result = "Found no tables for the backup ID ".self::$backup_id;
            $this->app['monolog']->addInfo($result);
            return $result;
        }
        foreach ($tables as $table) {
            $this->app['monolog']->addInfo("Check table ".$table['table_name']." for changes");
            $this->checkTable($table);
        }

        //print_r($tables);
        return 'ok';
    }

}