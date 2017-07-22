<?php

namespace Modules\testModules\tests;

trait TestSqlSelect
{
    protected function testSqlSelectRun()
    {
        echo 'Run TestSqlSelect'."\n";
        
        $this->checkTest([$this, 'testSqlSelectFetchRow']);
        $this->checkTest([$this, 'testSqlSelectFetchAll']);
        
        echo "\n";
    }
    
    protected function testSqlSelectFetchRow()
    {
        $this->newTest('test \BfwSql\SqlSelect::fetchRow');
        
        $this->runExec(
            'UPDATE `test_runner`'
            . ' SET `title`=\'test_unitaire\', `enabled`=1'
            . ' WHERE `id`=2'
        );
        
        $req = $this->select('object')
                    ->from($this->tableName, ['id', 'title'])
                    ->where('enabled=:enable', [':enable' => 1]);
        
        $res = $req->fetchRow();
        
        $expectedObj = (object) [
            'id'    => '2',
            'title' => 'test_unitaire'
        ];
        
        //http://php.net/manual/en/language.oop5.object-comparison.php
        if ($res != $expectedObj) {
            return false;
        }
        
        return true;
    }
    
    protected function testSqlSelectFetchAll()
    {
        $req = $this->select('object')
                    ->from($this->tableName, ['id', 'title']);
        $res = $req->fetchAll();
        
        $this->newTest('test \BfwSql\SqlSelect::fetchAll is a generator');
        if (!$res instanceof \Generator) {
            return false;
        }
        
        $testNameStart  = 'test \BfwSql\SqlSelect::fetchAll generator content';
        $this->newTest($testNameStart);
        
        foreach ($res as $index => $sqlLine) {
            $this->newTest($testNameStart.' - idx '.$index.' - test is_object');
            if (!is_object($sqlLine)) {
                return false;
            }
            
            $this->newTest($testNameStart.' - idx '.$index.' - test property id exist');
            if (!property_exists($sqlLine, 'id')) {
                return false;
            }
            
            $this->newTest($testNameStart.' - idx '.$index.' - test property title exist');
            if (!property_exists($sqlLine, 'title')) {
                return false;
            }
        }
        
        return true;
    }
}
