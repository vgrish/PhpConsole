<?php
namespace PhpConsole\Model\mysql;

use xPDO\xPDO;

class Code extends \PhpConsole\Model\Code
{

    public static $metaMap = array (
        'package' => 'PhpConsole\\Model',
        'version' => '3.0',
        'table' => 'phpconsole_code',
        'extends' => 'xPDO\\Om\\xPDOObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'code_id' => 0,
            'user_id' => 0,
            'createdon' => 'CURRENT_TIMESTAMP',
            'updatedon' => 'CURRENT_TIMESTAMP',
            'content' => NULL,
        ),
        'fieldMeta' => 
        array (
            'code_id' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
                'index' => 'pk',
            ),
            'user_id' => 
            array (
                'dbtype' => 'int',
                'precision' => '20',
                'phptype' => 'integer',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
                'index' => 'pk',
            ),
            'createdon' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'datetime',
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
            ),
            'updatedon' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'datetime',
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
            ),
            'content' => 
            array (
                'dbtype' => 'mediumtext',
                'phptype' => 'string',
            ),
        ),
        'indexes' => 
        array (
            'PRIMARY' => 
            array (
                'alias' => 'PRIMARY',
                'primary' => true,
                'unique' => true,
                'columns' => 
                array (
                    'code_id' => 
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                    'user_id' => 
                    array (
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'user_id' => 
            array (
                'alias' => 'user_id',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'user_id' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
    );

}
