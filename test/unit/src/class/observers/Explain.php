<?php

namespace BfwSql\Observers\test\unit;

use \atoum;

$vendorPath = realpath(__DIR__.'/../../../../../vendor');
require_once($vendorPath.'/autoload.php');
require_once($vendorPath.'/bulton-fr/bfw/test/unit/helpers/Application.php');
require_once($vendorPath.'/bulton-fr/bfw/test/unit/helpers/ObserverArray.php');
require_once($vendorPath.'/bulton-fr/bfw/test/unit/mocks/src/class/Module.php');
require_once($vendorPath.'/bulton-fr/bfw/test/unit/mocks/src/class/Subject.php');

class Explain extends Atoum
{
    use \BfwSql\Test\Helpers\CreateModule;
    
    protected $mock;
    protected $monolog;
    
    public function beforeTestMethod($testMethod)
    {
        $this->initModule();
        $this->createSqlConnect('myBase');
        
        $this->mockGenerator
            ->makeVisible('haveMonologHandler')
            ->makeVisible('analyzeUpdate')
            ->makeVisible('userQuery')
            ->makeVisible('systemQuery')
            ->makeVisible('addQueryToMonoLog')
            ->makeVisible('runExplain')
            ->makeVisible('obtainSql')
            ->generate('BfwSql\Observers\Explain')
        ;
        
        $this->monolog = $this->app->getModuleForName('bfw-sql')->monolog;
        $this->addMonologTestHandler();
        
        $this->mock = new \mock\BfwSql\Observers\Explain($this->monolog);
    }
    
    protected function addMonologTestHandler()
    {
        $this->monolog->addNewHandler((object) [
            'name' => '\Monolog\Handler\TestHandler',
            'args' => []
        ]);
    }
    
    public function testAnalyzeUpdate()
    {
        $this->assert('test Observers\Explain::analyzeUpdate - prepare')
            ->if($this->calling($this->mock)->haveMonologHandler = true)
            ->and($this->calling($this->mock)->userQuery = null)
            ->and($this->calling($this->mock)->systemQuery = null)
            ->given($subject = new \BFW\Test\Mock\Subject)
            ->and($subject->setContext(null))
        ;
        
        $this->assert('test Observers\Explain::analyzeUpdate with user query')
            ->and($subject->setAction('user query'))
            ->then
            ->variable($this->mock->update($subject))
            ->mock($this->mock)
                ->call('userQuery')
                    ->never()
                ->call('systemQuery')
                    ->never()
        ;
        
        $this->assert('test Observers\Explain::analyzeUpdate with system query')
            ->and($subject->setAction('system query'))
            ->then
            ->variable($this->mock->update($subject))
            ->mock($this->mock)
                ->call('userQuery')
                    ->never()
                ->call('systemQuery')
                    ->once()
        ;
        
        $this->assert('test Observers\Explain::analyzeUpdate with unknown')
            ->and($subject->setAction('my query'))
            ->then
            ->variable($this->mock->update($subject))
            ->mock($this->mock)
                ->call('userQuery')
                    ->never()
                ->call('systemQuery')
                    ->never()
        ;
    }
    
    public function testSystemQuery()
    {
        $this->assert('test Observers\Basic::systemQuery - prepare')
            ->if($this->calling($this->mock)->haveMonologHandler = true)
            ->and($this->calling($this->mock)->addQueryToMonoLog = null)
            ->and($this->calling($this->mock)->runExplain = null)
            ->then
            
            ->given($context = new \BfwSql\Actions\Test\Mocks\Select($this->sqlConnect, 'object'))
            ->if($context->setAssembledRequest('SELECT * FROM users'))
            ->and($context->setLastErrorInfos([
                0 => '00000',
                1 => null,
                2 => null
            ]))
            
            ->given($subject = new \BFW\Test\Mock\Subject)
            ->and($subject->setAction('system query'))
            ->and($subject->setContext($context))
            ->then
        ;
        
        $this->assert('test Observers\Basic::systemQuery with AbstractAction in context')
            ->variable($this->mock->update($subject))
            ->object($this->mock->getSql())
                ->isInstanceOf('\BfwSql\Sql')
            ->object($this->mock->getSql()->getSqlConnect())
                ->isIdenticalTo($context->getSqlConnect())
            ->object($this->mock->getExplain())
                ->isEqualTo((object) [
                    'status' => \BfwSql\Observers\Explain::EXPLAIN_OK,
                    'datas'  => []
                ])
            ->mock($this->mock)
                ->call('runExplain')
                    ->once()
                ->call('addQueryToMonoLog')
                    ->withArguments(
                        'SELECT * FROM users',
                        [
                            0 => '00000',
                            1 => null,
                            2 => null
                        ]
                    )
                        ->once()
        ;
        
        $this->assert('test Observers\Basic::systemQuery with incorrect context instance')
            ->if($subject->setContext(new \stdClass))
            ->then
            ->exception(function() use ($subject) {
                $this->mock->update($subject);
            })
                ->hasCode(\BfwSql\Observers\Basic::ERR_SYSTEM_QUERY_CONTEXT_CLASS)
        ;
        
        $this->assert('test Observers\Basic::systemQuery with an another incorrect context instance')
            ->if($subject->setContext(new \BfwSql\Actions\Test\Mocks\AbstractActions($this->sqlConnect)))
            ->then
            ->variable($this->mock->update($subject))
                ->isNull()
            ->mock($this->mock)
                ->call('runExplain')
                    ->never()
        ;
    }
    
    public function testRunExplain()
    {
        $this->assert('test Observers\Basic::runExplain - prepare')
            ->if($this->calling($this->mock)->haveMonologHandler = true)
            ->and($this->calling($this->mock)->addQueryToMonoLog = null)
            ->then
            
            ->given($context = new \BfwSql\Actions\Test\Mocks\Select($this->sqlConnect, 'object'))
            ->if($context->setAssembledRequest('SELECT * FROM users'))
            ->and($context->setLastErrorInfos([
                0 => '00000',
                1 => null,
                2 => null
            ]))
            
            ->given($subject = new \BFW\Test\Mock\Subject)
            ->and($subject->setAction('system query'))
            ->and($subject->setContext($context))
            ->then
            
            ->given($pdoStatement = new \mock\PDOStatement)
            ->if($this->calling($this->pdo)->prepare = $pdoStatement)
            ->and($this->calling($pdoStatement)->execute = null)
            ->then
            
            ->given($sql = new \mock\BfwSql\Sql($this->sqlConnect))
            ->if($this->calling($this->mock)->obtainSql = $sql)
            ->and($this->calling($sql)->query = null)
        ;
        
        $this->assert('test Observers\Basic::runExplain when explain fail')
            ->if($this->calling($pdoStatement)->execute = false)
            ->then
            ->variable($this->mock->update($subject))
            ->mock($pdoStatement)
                ->call('fetch')
                    ->never()
            ->object($this->mock->getExplain())
                ->isEqualTo((object) [
                    'status' => \BfwSql\Observers\Explain::EXPLAIN_FAILED,
                    'datas'  => []
                ])
        ;
        
        $this->assert('test Observers\Basic::runExplain when explain empty')
            ->if($this->calling($pdoStatement)->execute = true)
            ->and($this->calling($pdoStatement)->fetch = false)
            ->then
            ->variable($this->mock->update($subject))
            ->mock($pdoStatement)
                ->call('fetch')
                    ->once()
            ->object($this->mock->getExplain())
                ->isEqualTo((object) [
                    'status' => \BfwSql\Observers\Explain::EXPLAIN_EMPTY,
                    'datas'  => []
                ])
        ;
        
        $this->assert('test Observers\Basic::runExplain when explain result')
            ->if($this->calling($pdoStatement)->execute = true)
            ->and($this->calling($pdoStatement)->fetch = function() {
                return [
                    'id'            => 1,
                    'select_type'   => 'SIMPLE',
                    'table'         => 'users',
                    'partitions'    => null,
                    'type'          => 'ALL',
                    'possible_keys' => null,
                    'key'           => null,
                    'key_len'       => null,
                    'ref'           => null,
                    'rows'          => 943,
                    'filtered'      => 100,
                    'Extra'         => null
                ];
            })
            ->then
            ->variable($this->mock->update($subject))
            ->mock($pdoStatement)
                ->call('fetch')
                    ->once()
            ->object($this->mock->getExplain())
                ->isEqualTo((object) [
                    'status' => \BfwSql\Observers\Explain::EXPLAIN_OK,
                    'datas'  => [
                        'id'            => 1,
                        'select_type'   => 'SIMPLE',
                        'table'         => 'users',
                        'partitions'    => null,
                        'type'          => 'ALL',
                        'possible_keys' => null,
                        'key'           => null,
                        'key_len'       => null,
                        'ref'           => null,
                        'rows'          => 943,
                        'filtered'      => 100,
                        'Extra'         => null
                    ]
                ])
        ;
    }
    
    public function testAddQueryToMonoLog()
    {
        $this->assert('test Observers\Basic::addQueryToMonoLog')
            ->given($monologHandler = $this->monolog->getHandlers()[0])
            ->then
            
            ->given($context = new \BfwSql\Actions\Test\Mocks\Select($this->sqlConnect, 'object'))
            ->if($context->setAssembledRequest('SELECT * FROM users'))
            ->and($context->setLastErrorInfos([
                0 => '00000',
                1 => null,
                2 => null
            ]))
            
            ->given($subject = new \BFW\Test\Mock\Subject)
            ->and($subject->setAction('system query'))
            ->and($subject->setContext($context))
            ->then
            
            ->given($sql = new \mock\BfwSql\Sql($this->sqlConnect))
            ->if($this->calling($this->mock)->obtainSql = $sql)
            ->and($this->calling($sql)->query = null)
            ->then
            
            ->given($pdoStatement = new \mock\PDOStatement)
            ->if($this->calling($this->pdo)->prepare = $pdoStatement)
            ->and($this->calling($pdoStatement)->execute = true)
            ->and($this->calling($pdoStatement)->fetch = function() {
                return [
                    'id'            => 1,
                    'select_type'   => 'SIMPLE',
                    'table'         => 'users',
                    'partitions'    => null,
                    'type'          => 'ALL',
                    'possible_keys' => null,
                    'key'           => null,
                    'key_len'       => null,
                    'ref'           => null,
                    'rows'          => 943,
                    'filtered'      => 100,
                    'Extra'         => null
                ];
            })
            ->then
            
            ->variable($this->mock->update($subject))
                ->isNull()
            ->boolean($monologHandler->hasDebugRecords())
                ->isTrue()
            ->array($records = $monologHandler->getRecords())
                ->isNotEmpty()
            ->variable($records[0]['level'])
                ->isEqualTo(\Monolog\Logger::DEBUG)
            ->given($datetime = $records[0]['datetime'])
            ->string($records[0]['formatted'])
                ->isEqualTo(
                    '['.$datetime->format('Y-m-d H:i:s').'] bfw-sql.DEBUG: '
                    .'Type: system query ; Query: SELECT * FROM users ; '
                    .'Errors: Array (     [0] => 00000     [1] =>      [2] =>  )  ; '
                    .'Explain status: ok ; '
                    .'Explain datas: Array ('
                    .'     [id] => 1'
                    .'     [select_type] => SIMPLE'
                    .'     [table] => users'
                    .'     [partitions] => '
                    .'     [type] => ALL'
                    .'     [possible_keys] => '
                    .'     [key] => '
                    .'     [key_len] => '
                    .'     [ref] => '
                    .'     [rows] => 943'
                    .'     [filtered] => 100'
                    .'     [Extra] =>  '
                    .')'
                    .'  [] []'
                    ."\n"
                )
        ;
    }
}