<?php

/**
 * Class arStatement
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 2.0.7
 */
abstract class arStatement
{

    /**
     * @var string
     */
    protected $table_name_as = '';


    /**
     * @param ActiveRecord $ar
     *
     * @return string
     */
    abstract public function asSQLStatement(ActiveRecord $ar, ilDBInterface $db);


    /**
     * @return string
     */
    public function getTableNameAs()
    {
        return $this->table_name_as;
    }


    /**
     * @param string $table_name_as
     */
    public function setTableNameAs($table_name_as)
    {
        $this->table_name_as = $table_name_as;
    }

    protected function wrapFields(array $fields, ilDBInterface $db) : array
    {
        $wrapped_fields = [];
        foreach ($fields as $field) {
            $wrapped_fields[] = $this->wrapField($field, $db);
        }

        return $wrapped_fields;
    }

    protected function wrapField(string $field, ilDBInterface $db) : string
    {
        $splitted = explode('.', $field);

        if (count($splitted) === 1 && $splitted[0] === '*') {
            return $field;
        }

        if (count($splitted) === 2) {
            return $db->quoteIdentifier($splitted[0]) . '.' . $db->quoteIdentifier($splitted[1]);
        }

        return $db->quoteIdentifier($field);
    }
}
