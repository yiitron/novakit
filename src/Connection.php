<?php
namespace  yiitron\novakit;

use yii\db\Connection as BaseConnection;

class Connection extends BaseConnection
{
    public function switchSchema($schemaName)
    {
        // Validate schema name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $schemaName)) {
            throw new \InvalidArgumentException("Invalid schema name: {$schemaName}");
        }

        $this->createCommand("SET search_path TO {$schemaName}, public")->execute();
    }
}