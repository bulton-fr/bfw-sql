<?php

namespace BfwSql\Queries\Parts;

use \BfwSql\Queries\AbstractQuery;

class ColumnList extends AbstractList
{
    /**
     * @var \BfwSql\Queries\Parts\Table $table The table where are columns
     */
    protected $table;
    
    /**
     * {@inheritdoc}
     */
    protected $separator = ',';
    
    /**
     * {@inheritdoc}
     * 
     * @param \BfwSql\Queries\AbstractQuery $querySystem
     * @param \BfwSql\Queries\Parts\Table $table The table where are column
     */
    public function __construct(AbstractQuery $querySystem, Table $table)
    {
        parent::__construct($querySystem);
        $this->table = $table;
    }
    
    /**
     * Getter accessor to property table
     * 
     * @return \BfwSql\Queries\Parts\Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }
    
    /**
     * Magic method __invoke, used when the user call object like a function
     * @link http://php.net/manual/en/language.oop5.magic.php#object.invoke
     * 
     * @param array $columns The list of columns to declare
     *  The key into the array is the shortcut of the column.
     *  The value is the column name.
     *  If no key is defined (so integer), the column will not have shortcut.
     * 
     * @return void
     */
    public function __invoke(array $columns)
    {
        $this->invokeCheckIsDisabled();
        
        $usedClass   = \BfwSql\UsedClass::getInstance();
        $columnClass = $usedClass->obtainClassNameToUse('QueriesPartsColumn');
        
        foreach ($columns as $shortcut => $name) {
            if (is_int($shortcut)) {
                $column = new $columnClass($this->table, $name);
            } else {
                $column = new $columnClass($this->table, $name, $shortcut);
            }
            
            $this->list[] = $column;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function generate(): string
    {
        if ($this->isDisabled === true) {
            return '';
        }
        
        $sqlPart = '';
        
        foreach ($this->list as $index => $column) {
            $sqlPart .= $this->querySystem->getQuerySgbd()->listItem(
                $column->generate(),
                $index,
                $this->separator
            );
        }
        
        return $sqlPart;
    }
}
