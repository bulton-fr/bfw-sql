<?php

namespace BfwSql;

use \Exception;

/**
 * Abstract class for all Models class
 * 
 * @package bfw-sql
 * @author Vermeulen Maxime <bulton.fr@gmail.com>
 * @version 2.0
 */
abstract class AbstractModeles extends \BfwSql\Sql
{
    /**
     * @const ERR_NO_CONNECTION_CONFIGURED Exception code if no connection
     * is configured.
     */
    const ERR_NO_CONNECTION_CONFIGURED = 2101001;
    
    /**
     * @const ERR_NEED_BASEKEYNAME_DEFINED Exception code if the baseKeyName
     * should be defined (multiple connection) but it's not.
     */
    const ERR_NEED_BASEKEYNAME_DEFINED = 2101002;
    
    /**
     * @const ERR_UNKNOWN_CONNECTION_FOR_BASEKEYNAME Exception code if the
     * baseKeyName defined corresponding to no defined connection.
     */
    const ERR_UNKNOWN_CONNECTION_FOR_BASEKEYNAME = 2101003;
    
    /**
     * @var $tableName The table name
     */
    protected $tableName = '';
    
    /**
     * @var $tableNameWithPrefix the table name with prefix
     */
    protected $tableNameWithPrefix = '';
    
    /**
     * @var $baseKeyName The baseKeyName to use to connection.
     *  Use it if they are multiple database to connect in the application.
     */
    protected $baseKeyName = '';
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $sqlConnect = $this->obtainSqlConnect();
        
        parent::__construct($sqlConnect);
        $this->tableNameWithPrefix = $this->prefix.$this->tableName;
    }
    
    /**
     * Getter to property tableName
     * 
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }
    
    /**
     * Getter to property tableNameWithPrefix
     * 
     * @return string
     */
    public function getTableNameWithPrefix()
    {
        return $this->tableNameWithPrefix;
    }
    
    /**
     * Getter to property baseKeyName
     * 
     * @return string
     */
    public function getBaseKeyName()
    {
        return $this->baseKeyName;
    }
    
    /**
     * Get the BFW Application
     * It's a dedicated method for unit test or case where App is override
     * 
     * @return \BFW\Application
     */
    protected function obtainApp()
    {
        return \BFW\Application::getInstance();
    }
    
    /**
     * Obtain SqlConnect instance for the baseKeyName
     * 
     * @return \BfwSql\SqlConnect
     * 
     * @throws \Exception If there are many connection declared and if the
     *  property baseKeyName is empty
     */
    protected function obtainSqlConnect()
    {
        $listBases = $this->obtainApp()
            ->getModuleForName('bfw-sql')
            ->listBases;
        
        if (count($listBases) === 0) {
            throw new Exception(
                'There is no connection configured.',
                self::ERR_NO_CONNECTION_CONFIGURED
            );
        }
        
        if (count($listBases) > 1 && empty($this->baseKeyName)) {
            throw new Exception(
                'There are multiple connection, '
                .'so the property baseKeyName must be defined',
                self::ERR_NEED_BASEKEYNAME_DEFINED
            );
        }
        
        if (count($listBases) > 1 && !isset($listBases[$this->baseKeyName])) {
            throw new Exception(
                'There are multiple connection, '
                .'but the connection '.$this->baseKeyName.' is not defined.',
                self::ERR_UNKNOWN_CONNECTION_FOR_BASEKEYNAME
            );
        }
        
        if (count($listBases) === 1) {
            $sqlConnect = reset($listBases);
        } else {
            $sqlConnect = $listBases[$this->baseKeyName];
        }
        
        return $sqlConnect;
    }
}
