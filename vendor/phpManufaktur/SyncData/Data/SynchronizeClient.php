<?php

/**
 * SyncData
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/SyncData
 * @copyright 2013 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\SyncData\Data;

use phpManufaktur\SyncData\Control\Application;

/**
 * Track the synchronization of tables
 *
 * @author ralf.hertsch@phpmanufaktur.de
 *
 */
class SynchronizeClient
{
    protected $app = null;
    protected static $table_name = null;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        self::$table_name = CMS_TABLE_PREFIX.'syncdata_synchronize_client';
    }

    /**
     * Create the table
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createTable ()
    {
        $table = self::$table_name;
        $SQL = <<<EOD
    CREATE TABLE IF NOT EXISTS `$table` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `backup_id` VARCHAR(16) NOT NULL DEFAULT '',
      `backup_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
      `archive_id` INT(11) NOT NULL DEFAULT '0',
      `archive_name` VARCHAR(32) NOT NULL DEFAULT '',
      `archive_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
      `sync_files` TEXT NOT NULL,
      `sync_master` TEXT NOT NULL,
      `sync_tables` TEXT NOT NULL,
      `action` ENUM('INIT','SYNC') NOT NULL DEFAULT 'SYNC',
      `timestamp` TIMESTAMP,
      PRIMARY KEY (`id`)
    )
    COMMENT='SyncData - table for the synchronize archives processed by the client'
    ENGINE=InnoDB
    AUTO_INCREMENT=1
    DEFAULT CHARSET=utf8
    COLLATE='utf8_general_ci'
EOD;
        try {
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Created table '".self::$table_name."' for the class SynchronizeClient",
                array('method' => __METHOD__, 'line' => __LINE__));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw $e;
        }
    }

    /**
     * Delete table - switching check for foreign keys off before executing
     *
     * @throws \Exception
     */
    public function dropTable()
    {
        try {
            $table = self::$table_name;
            $SQL = <<<EOD
    SET foreign_key_checks = 0;
    DROP TABLE IF EXISTS `$table`;
    SET foreign_key_checks = 1;
EOD;
            $this->app['db']->query($SQL);
            $this->app['monolog']->addInfo("Drop table ".self::$table_name, array(__METHOD__, __LINE__));
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Insert a new record into the table
     *
     * @param array $data
     * @param string reference $id return the new ID
     * @throws \Doctrine\DBAL\DBALException
     */
    public function insert($data, &$id=null)
    {
        try {
            $insert = array();
            foreach ($data as $key => $value)
                $insert[$this->app['db']->quoteIdentifier($key)] = is_string($value) ? $this->app['utils']->sanitizeText($value) : $value;
            $this->app['db']->insert(self::$table_name, $insert);
            $id = $this->app['db']->lastInsertId();
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw $e;
        }
    }

    public function selectLastArchiveID()
    {
        try {
            $SQL = "SELECT `archive_id` FROM `".self::$table_name."` ORDER BY `archive_id` DESC LIMIT 1";
            $result = $this->app['db']->fetchColumn($SQL);
            return (!empty($result)) ? $result : 0;
        } catch (\Doctrine\DBAL\DBALException $e) {
            throw $e;
        }
    }

}
