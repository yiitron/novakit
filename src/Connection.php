<?php

namespace  yiitron\novakit;

use Yii;
use yii\db\Connection as BaseConnection;

class Connection extends BaseConnection
{
    public $tenants ;

    public function init()
    {
        parent::init();
        if (\Yii::$app instanceof \yii\web\Application) {
            // Attach event to set schema after the DB connection is opened
            $this->on(self::EVENT_AFTER_OPEN, function () {
                $this->switchSchema();
            });
        }
    }

    private function setSchemaFromSubdomain()
    {
        $host = Yii::$app->request->hostName; // e.g., tenant1.example.com
        $subdomain = explode('.', $host)[0];  // Extract subdomain (e.g., tenant1)
        $activeTenants = Yii::$app->db->createCommand("SELECT subdomain_id, schema_name FROM public.tenants WHERE status='10'")->queryAll();
        $this->tenants = array_column($activeTenants, 'schema_name', 'subdomain_id');
        // Map the subdomain to the schema
        if (array_key_exists($subdomain, $this->tenants)) {
            $schema = $this->tenants[$subdomain];
            $this->createCommand("SET search_path TO {$schema}")->execute();
        } else {
            $defaultSchema = 'public'; // Change to your default schema if needed
            Yii::$app->db->createCommand("SET search_path TO {$defaultSchema}")->execute();
        }
    }
    public function switchSchema($schemaName = null)
    {
        if ($schemaName === null) {
            $this->setSchemaFromSubdomain();
        } else {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $schemaName)) {
                throw new \InvalidArgumentException("Invalid schema name: {$schemaName}");
            }
            $this->createCommand("SET search_path TO {$schemaName}")->execute();
        }
    }
	public function createSchema($name)
    {
        $this->db->createCommand("CREATE SCHEMA IF NOT EXISTS $name")->execute();
    }
}
