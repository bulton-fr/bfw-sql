<?php
/**
 * Config file for bfw-sql module
 * 
 * @author Vermeulen Maxime <bulton.fr@gmail.com>
 * @package bfw-sql
 * @version 2.0
 */

return (object) [
    /**
     * @var \stdClass[] bases All database list to connect
     */
    'bases' => [
        (object) [
            /**
             * @var string baseKeyName Key used to identify this connection.
             * Because there are possibility to connect at multiple databases, 
             * we need a key to identify this base.
             * You can keep this value empty ONLY if there are one connection.
             */
            'baseKeyName' => '',
            
            /**
             * @var string filePath Path to the file used for db
             * Used by sqlite driver for example. Keep empty if not used.
             */
            'filePath'    => '',
            
            /**
             * @var string host Datatable host to connect
             */
            'host'        => '',
            
            /**
             * @var int port Datatable port to connect
             * Mysql default is 3306
             */
            'port'        => 0,
            
            /**
             * @var string baseName Database name to connect
             */
            'baseName'    => '',
            
            /**
             * @var string user Database user used to connect
             */
            'user'        => '',
            
            /**
             * @var string password Database password used to connect
             */
            'password'    => '',
            
            /**
             * @var string baseType Database type used to connect
             *  It's the name of the PDO driver
             */
            'baseType'    => '',
            
            /**
             * @var array pdoOptions Options passed to 4th arguments
             * of PDO::__construct
             * 
             * @link http://php.net/manual/en/pdo.construct.php
             */
            'pdoOptions'  => [],
            
            /**
             * @var boolean useUtf8 Force datas to be UTF-8
             *  Used for Mysql
             * 
             * @link http://dev.mysql.com/doc/refman/5.7/en/charset-connection.html
             */
            'useUtf8'     => true,
            
            /**
             * @var string tablePrefix The prefix used for all table.
             *  Empty if not prefix to use
             */
            'tablePrefix' => ''
        ]
        // Add object (duplicate first) into the array
        // to add others sql connexions
    ]
];