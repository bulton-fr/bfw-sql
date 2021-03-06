<?php

namespace BfwSql\Queries\Parts;

class JoinList extends AbstractList
{
    /**
     * {@inheritdoc}
     */
    protected $separator = "\n";
    
    /**
     * {@inheritdoc}
     */
    protected $usePartPrefix = false;
    
    /**
     * @var boolean $columnsWithValue If columns should have a value
     */
    protected $columnsWithValue = false;
    
    /**
     * Getter accessor to property columnsWithValue
     * 
     * @return bool
     */
    public function getColumnsWithValue(): bool
    {
        return $this->columnsWithValue;
    }
    
    /**
     * Setter accessor to property columnsWithValue
     * 
     * @param bool $columnsWithValue
     * 
     * @return $this
     */
    public function setColumnsWithValue(bool $columnsWithValue): self
    {
        $this->columnsWithValue = $columnsWithValue;
        return $this;
    }
    
    /**
     * Magic method __invoke, used when the user call object like a function
     * @link http://php.net/manual/en/language.oop5.magic.php#object.invoke
     * 
     * @param string|array $nameInfos The name (and shortcut) of the table
     * @param string $on The ON condition used by the join
     * @param string|array|null $columns Columns of this table to use
     *  into the request
     * 
     * @return void
     */
    public function __invoke($nameInfos, string $on, $columns = null)
    {
        $this->invokeCheckIsDisabled();
        
        $usedClass   = \BfwSql\UsedClass::getInstance();
        $joinClass = $usedClass->obtainClassNameToUse('QueriesPartsJoin');
        
        $join = new $joinClass($this->querySystem);
        $join->setColumnsWithValue($this->columnsWithValue);
        $join->__invoke($nameInfos, $columns, $on);
        
        $this->list[] = $join;
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
        
        foreach ($this->list as $index => $join) {
            $sqlPart .= $this->querySystem->getQuerySgbd()->listItem(
                $this->partPrefix.' '.$join->generate(),
                $index,
                $this->separator
            );
        }
        
        return $sqlPart;
    }
}
