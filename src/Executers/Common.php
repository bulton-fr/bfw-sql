<?php

namespace BfwSql\Executers;

use \Exception;

class Common
{
    /**
     * @const ERR_EXECUTE_BAD_REQUEST Exception code if a request has fail
     * during execution
     */
    const ERR_EXECUTE_BAD_REQUEST = 2201001;
    
    /**
     * @const ERR_EXECUTED_UNKNOWN_ERROR Exception code if a request has fail
     * but when the error has not been returned by PDO::errorInfos()
     */
    const ERR_EXECUTED_UNKNOWN_ERROR = 2201002;
    
    /**
     * @const ERR_CLOSE_CURSOR_NOT_PDOSTATEMENT Exception code if the method
     * closeCursor is called for a request who have not returned a
     * PDOStatement object.
     */
    const ERR_CLOSE_CURSOR_NOT_PDOSTATEMENT = 2201003;
    
    /**
     * @var \BfwSql\Queries\AbstractQuery $query Query system object
     */
    protected $query;
    
    /**
     * @var \BfwSql\SqlConnect $sqlConnect SqlConnect object
     */
    protected $sqlConnect;
    
    /**
     * @var boolean $isPreparedRequest If is a prepared request
     */
    protected $isPreparedRequest = true;
    
    /**
     * @var array $lastErrorInfos The PDO::errorInfos return for the last
     * query executed. Empty if no request has been executed.
     */
    protected $lastErrorInfos = [];
    
    /**
     * @var \PDOStatement|integer|bool $lastRequestStatement The PDOStatement
     *  for the last request executed. Or integer if the request return the number
     *  of row impacted. Or a boolean (false) if request failed.
     */
    protected $lastRequestStatement;
    
    /**
     * @var boolean $noResult If request has sent no result.
     */
    protected $noResult = false;
    
    /**
     * @var array $prepareDriversOptions SGBD driver option used for
     *  prepared request
     * 
     * @link http://php.net/manual/en/pdo.prepare.php
     */
    protected $prepareDriversOptions = array();
    
    /**
     * Constructor
     * 
     * @param \BfwSql\Queries\AbstractQuery $query Instance of query
     * system
     */
    public function __construct(\BfwSql\Queries\AbstractQuery $query)
    {
        $this->query      = $query;
        $this->sqlConnect = $query->getSqlConnect();
    }

    /**
     * Getter to access to isPreparedRequest property
     * 
     * @return boolean
     */
    public function getIsPreparedRequest(): bool
    {
        return $this->isPreparedRequest;
    }
    
    /**
     * Setter to enable or disable prepared request
     * 
     * @param boolean $preparedRequestStatus The new status for prepared request
     * 
     * @return $this
     */
    public function setIsPreparedRequest(bool $preparedRequestStatus): self
    {
        $this->isPreparedRequest = (bool) $preparedRequestStatus;
        
        return $this;
    }
    
    /**
     * Getter to access to lastErrorInfos property
     * 
     * @return array
     */
    public function getLastErrorInfos(): array
    {
        return $this->lastErrorInfos;
    }

    /**
     * Getter to access to lastRequestStatement property
     * 
     * @return \PDOStatement|integer|bool|null
     */
    public function getLastRequestStatement()
    {
        return $this->lastRequestStatement;
    }

    /**
     * Getter to access to noResult property
     * 
     * @return boolean
     */
    public function getNoResult(): bool
    {
        return $this->noResult;
    }
    
    /**
     * Getter to access at prepareDriversOptions property
     * 
     * @return array
     */
    public function getPrepareDriversOptions(): array
    {
        return $this->prepareDriversOptions;
    }
    
    /**
     * Define driver options to prepared request
     * 
     * @link http://php.net/manual/fr/pdo.prepare.php
     * 
     * @param array $driverOptions Drivers options
     * 
     * @return $this
     */
    public function setPrepareDriversOptions(array $driverOptions): self
    {
        $this->prepareDriversOptions = $driverOptions;
        
        return $this;
    }
    
    /**
     * Getter accessor to property query
     * 
     * @return \BfwSql\Queries\AbstractQuery
     */
    public function getQuery(): \BfwSql\Queries\AbstractQuery
    {
        return $this->query;
    }
    
    /**
     * Getter to access at sqlConnect property
     * 
     * @return \BfwSql\SqlConnect
     */
    public function getSqlConnect(): \BfwSql\SqlConnect
    {
        return $this->sqlConnect;
    }
    
    /**
     * Execute the assembled request
     * 
     * @return array The pdo errorInfo array
     */
    protected function executeQuery(): array
    {
        $this->sqlConnect->upNbQuery();
        $this->query->assemble();
        
        try {
            if ($this->isPreparedRequest) {
                $req = $this->executePreparedQuery();
            } else {
                $req = $this->executeNotPreparedQuery();
            }
            
            $this->lastRequestStatement = $req;
        } catch (\Throwable $e) {
            \BFW\Application::getInstance()
                ->getModuleList()
                ->getModuleByName('bfw-sql')
                ->monolog
                ->getLogger()
                ->warning(
                    'Exception while query execution : '.$e->getMessage()
                )
            ;
            
            throw new \Exception($e->getMessage(), (int) $e->getCode(), $e);
        } finally {
            $this->lastErrorInfos = $this->sqlConnect->getPDO()->errorInfo();
            $this->callObserver();
        }
        
        return $this->lastErrorInfos;
    }
    
    /**
     * Execute a prepared request
     * 
     * @return \PDOStatement|bool
     */
    protected function executePreparedQuery()
    {
        $pdo = $this->sqlConnect->getPDO();
        $req = $pdo->prepare(
            $this->query->getAssembledRequest(),
            $this->prepareDriversOptions
        );

        $req->execute($this->query->getPreparedParams());
        
        return $req;
    }
    
    /**
     * Execute a not prepared request
     * 
     * @return \PDOStatement|integer|bool
     */
    protected function executeNotPreparedQuery()
    {
        $pdoMethodToCall = 'exec';
        if ($this->query instanceof \BfwSql\Queries\Select) {
            $pdoMethodToCall = 'query';
        }

        $pdo = $this->sqlConnect->getPDO();
        return $pdo->{$pdoMethodToCall}($this->query->getAssembledRequest());
    }
    
    /**
     * Execute the assembled request and check if there are errors
     * Update property noResult
     * 
     * @throws \Exception If the request fail
     * 
     * @return \PDOStatement|integer
     */
    public function execute()
    {
        $error = $this->executeQuery();
        
        //Throw an exception if they are an error with the request
        if ($error[0] !== null && $error[0] !== '00000') {
            throw new Exception(
                $error[2],
                self::ERR_EXECUTE_BAD_REQUEST
            );
        }
        
        if ($this->lastRequestStatement === false) {
            throw new Exception(
                'An error occurred during the execution of the request',
                self::ERR_EXECUTED_UNKNOWN_ERROR
            );
        }
        
        $this->noResult = false;
        if ($this->obtainImpactedRows() === 0) {
            $this->noResult = true;
        }

        return $this->lastRequestStatement;
    }
    
    /**
     * Closes the cursor, enabling the statement to be executed again.
     * 
     * @link http://php.net/manual/fr/pdostatement.closecursor.php
     * 
     * @throws \Exception If the property $lastRequestStatement is not a
     * PDOStatement object.
     * 
     * @return bool
     */
    public function closeCursor(): bool
    {
        if ($this->lastRequestStatement instanceof \PDOStatement === false) {
            throw new Exception(
                'The cursor can\'t be close because the request '
                .'not have return a PDOStatement object.',
                self::ERR_CLOSE_CURSOR_NOT_PDOSTATEMENT
            );
        }
        
        return $this->lastRequestStatement->closeCursor();
    }
    
    /**
     * Return the number of impacted rows by the last request
     * 
     * @return int|bool
     */
    public function obtainImpactedRows()
    {
        if (is_object($this->lastRequestStatement)) {
            //If pdo::query or pdo::prepare
            return $this->lastRequestStatement->rowCount();
        } elseif (is_integer($this->lastRequestStatement)) {
            //If pdo::exec
            return $this->lastRequestStatement;
        }
        
        //Security if call without executed a request
        return false;
    }
    
    /**
     * Send a notify to application observers
     * 
     * @return void
     */
    protected function callObserver()
    {
        $app     = \BFW\Application::getInstance();
        $subject = $app->getSubjectList()->getSubjectByName('bfw-sql');
        $subject->addNotification('system query', clone $this);
    }
}
