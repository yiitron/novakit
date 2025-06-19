<?php

namespace yiitron\novakit\controllers\console;

/**
 * VoyageController handles the migration process for multi-tenant schemas.
 * It allows for applying and reverting migrations for each tenant schema.
 * 
 * @package yiitron\novakit\controllers\console
 */

use Yii;
use yii\db\Query;
use yii\rbac\Item;
use yii\helpers\Console;
use yii\console\ExitCode;
use iam\hooks\AuthConfigs;
use yii\helpers\ArrayHelper;
use iam\models\static\rbac\AuthItem;
use Psy\Command\ExitCommand;
use yiitron\novakit\auth\AuthManager;

class VoyageController extends \yii\console\controllers\MigrateController
{
    public $interactive = false;
    public $tenant; // Command-line argument for specific tenant
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['tenant']);
    }
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), ['t' => 'tenant']);
    }
    public function actionUp($limit = 0)
    {
       // $this->stdout($this->db->schemaMap[$_SERVER['CORE_DB_DRIVER']]."Starting migration process...\n", Console::FG_CYAN);
        //return ExitCode::UNSPECIFIED_ERROR;
        if (isset($_SERVER['APP_MODE']) && $_SERVER['APP_MODE'] === strtolower('multi')) {
            if (explode(':', Yii::$app->db->dsn)[0] !== "pgsql") {
                $this->stdout("Multi mode is configured to only work with PostgreSQL.\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $tenants = $this->getTenantSchemas();
            if (empty($tenants)) {
                $this->stdout("No tenants found.\n", Console::FG_RED);
                return ExitCode::OK;
            }
            // Filter tenants if a specific tenant is provided
            if ($this->tenant) {
                $tenants = array_intersect($tenants, [$this->tenant]);
            }
            foreach ($tenants as $tenant) {
                $this->stdout("Running migrations for schema: {$tenant}\n", Console::FG_PURPLE);
                $this->db->switchSchema($tenant);
                $this->db->schemaMap[$_SERVER['CORE_DB_DRIVER']]['defaultSchema'] = $tenant;
                $this->migrationTable = '{{%' . $tenant . '_migration}}';
                // Get new migrations for the current tenant schema
                $migrations = $this->getNewMigrations();
                if (empty($migrations)) {
                    $this->updateRbac();
                    $this->stdout("No new migrations found for schema: {$tenant}. Your system is up-to-date.\n", Console::FG_CYAN);
                    continue;
                }
                $total = count($migrations);
                $limit = (int) $limit;
                if ($limit > 0) {
                    $migrations = array_slice($migrations, 0, $limit);
                }
                $n = count($migrations);
                if ($n === $total) {
                    $this->stdout("Total $n new " . ($n === 1 ? 'migration' : 'migrations') . " to be applied for schema: {$tenant}:\n", Console::FG_YELLOW);
                } else {
                    $this->stdout("Total $n out of $total new " . ($total === 1 ? 'migration' : 'migrations') . " to be applied for schema: {$tenant}:\n", Console::FG_YELLOW);
                }
                foreach ($migrations as $migration) {
                    $nameLimit = $this->getMigrationNameLimit();
                    if ($nameLimit !== null && strlen($migration) > $nameLimit) {
                        $this->stdout("\nThe migration name '$migration' is too long. Its not possible to apply this migration.\n", Console::FG_RED);
                        return ExitCode::UNSPECIFIED_ERROR;
                    }
                    $this->stdout("\t$migration\n");
                }
                $this->stdout("\n");
                $applied = 0;
                if ($this->confirm('Apply the above ' . ($n === 1 ? 'migration' : 'migrations') . ' for schema: ' . $tenant . '?')) {
                    foreach ($migrations as $migration) {
                        if (!$this->migrateUp($migration)) {
                            $this->stdout("\n$applied from $n " . ($applied === 1 ? 'migration was' : 'migrations were') . " applied for schema: {$tenant}.\n", Console::FG_RED);
                            $this->stdout("\nMigration failed. The rest of the migrations are canceled.\n", Console::FG_RED);
                            return ExitCode::UNSPECIFIED_ERROR;
                        }
                        $applied++;
                    }
                    $this->stdout("\n$n " . ($n === 1 ? 'migration was' : 'migrations were') . " applied for schema: {$tenant}.\n", Console::FG_CYAN);
                    $this->stdout("\nMigrated up successfully for schema: {$tenant}.\n", Console::FG_CYAN);
                    $this->updateRbac();
                }
            }
            $this->stdout("All tenant migrations completed.\n", Console::FG_BLUE);
            return ExitCode::OK;
        } else {
            // Fallback to the default behavior if not in multi-tenant mode
            parent::actionUp($limit);
            $this->updateRbac();
            return ExitCode::OK;
        }
    }
    public function actionDown($limit = 1)
    {
        if (isset($_SERVER['APP_MODE']) && $_SERVER['APP_MODE'] === strtolower('multi')) {
            if (explode(':', Yii::$app->db->dsn)[0] !== "pgsql") {
                $this->stdout("Multi mode is configured to only work with PostgreSQL.\n");
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $tenants = $this->getTenantSchemas();
            if (empty($tenants)) {
                $this->stdout("No tenants found.\n");
                return ExitCode::OK;
            }
            // Filter tenants if a specific tenant is provided
            if ($this->tenant) {
                $tenants = array_intersect($tenants, [$this->tenant]);
            }
            foreach ($tenants as $tenant) {
                $this->stdout("Reverting migrations for schema: {$tenant}\n");
                $this->db->switchSchema($tenant);
                $this->db->schemaMap[$_SERVER['CORE_DB_DRIVER']]['defaultSchema'] = $tenant;
                $this->migrationTable = '{{%' . $tenant . '_migration}}';
                // Get migration history for the current tenant schema
                $migrations = $this->getMigrationHistory($limit);
                if (empty($migrations)) {
                    $this->stdout("No migrations to revert for schema: {$tenant}.\n", Console::FG_GREEN);
                    continue;
                }
                $n = count($migrations);
                $this->stdout("Total $n " . ($n === 1 ? 'migration' : 'migrations') . " to be reverted for schema: {$tenant}:\n", Console::FG_YELLOW);

                foreach ($migrations as $version => $time) {
                    $this->stdout("\t$version\n");
                }
                $this->stdout("\n");
                $reverted = 0;
                if ($this->confirm('Revert the above ' . ($n === 1 ? 'migration' : 'migrations') . ' for schema: ' . $tenant . '?')) {
                    foreach ($migrations as $version => $time) {
                        if (!$this->migrateDown($version)) {
                            $this->stdout("\n$reverted from $n " . ($reverted === 1 ? 'migration was' : 'migrations were') . " reverted for schema: {$tenant}.\n", Console::FG_RED);
                            $this->stdout("\nMigration revert failed. The rest of the migrations are canceled.\n", Console::FG_RED);
                            return ExitCode::UNSPECIFIED_ERROR;
                        }
                        $reverted++;
                    }

                    $this->stdout("\n$n " . ($n === 1 ? 'migration was' : 'migrations were') . " reverted for schema: {$tenant}.\n", Console::FG_GREEN);
                    $this->stdout("\nMigrated down successfully for schema: {$tenant}.\n", Console::FG_GREEN);
                }
            }
            $this->stdout("All tenant migrations reverted.\n");
            return ExitCode::OK;
        } else {
            // Fallback to the default behavior if not in multi-tenant mode
            return parent::actionDown($limit);
        }
    }
    protected function getTenantSchemas()
    {
        $this->ensureTableExists();
        $model = Yii::$app->db->createCommand("SELECT schema_name FROM public.tenants WHERE status='10'")->queryColumn();
        return array_merge($model, ['public']);
    }
    protected function ensureTableExists()
    {
        $schemaName = Yii::$app->db->schema->defaultSchema;
        if (!Yii::$app->db->createCommand("SELECT schema_name FROM information_schema.schemata WHERE schema_name = :schema")
            ->bindValue(':schema', $schemaName)
            ->queryScalar()) {
            Yii::$app->db->createCommand("CREATE SCHEMA IF NOT EXISTS $schemaName")->execute();
        }
        $tableName = Yii::$app->db->tablePrefix . 'tenants';
        if (Yii::$app->db->schema->getTableSchema($tableName) === null) {
            // Suppress migration output
            ob_start();
            $migration = new class extends \yii\db\Migration {
                public $demoSchema = 'demo';
                public function safeUp()
                {
                    $this->createTable('{{%tenants}}', [
                        'tenant_id' => $this->primaryKey(),
                        'tenant_name' => $this->string()->notNull()->unique(),
                        'subdomain_id' => $this->string(50)->notNull()->unique(),
                        'schema_name' => $this->string(50)->notNull()->unique(),
                        'data' => $this->text(),
                        'is_deleted' => $this->integer(2)->notNull()->defaultValue(0),
                        'status' => $this->integer(3)->notNull()->defaultValue(10),
                        'created_at' => $this->integer()->notNull(),
                        'updated_at' => $this->integer()->notNull(),
                    ], null);
                    $this->insert('{{%tenants}}', array(
                        'tenant_name' => $_SERVER['APP_NAME'],
                        'subdomain_id' => $this->demoSchema,
                        'schema_name' => $this->demoSchema,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ));
                    Yii::$app->db->createCommand("CREATE SCHEMA IF NOT EXISTS $this->demoSchema")->execute();
                }
            };
            $migration->safeUp(); // Run the migration
            ob_end_clean(); // Suppress output
        }
    }
    protected function getNewMigrations()
    {
        $applied = [];
        foreach ($this->getMigrationHistory(null) as $class => $time) {
            $applied[trim($class, '\\')] = true;
        }
        $migrationPaths = [];
        if (is_array($this->migrationPath)) {
            foreach ($this->migrationPath as $path) {
                $migrationPaths[] = [$path, ''];
            }
        } elseif (!empty($this->migrationPath)) {
            $migrationPaths[] = [$this->migrationPath, ''];
        }
        foreach ($this->migrationNamespaces as $namespace) {
            $migrationPaths[] = [$this->getNamespacePath($namespace), $namespace];
        }
        $migrations = [];

        // Determine current schema for multi-tenant or single mode
        if (isset($_SERVER['APP_MODE']) && $_SERVER['APP_MODE'] === strtolower('multi')) {
            $currentSchema = $this->db->schemaMap[$_SERVER['CORE_DB_DRIVER']]['defaultSchema'];
        } else {
            $currentSchema = null; // single mode, no schema filtering
        }

        foreach ($migrationPaths as $item) {
            list($migrationPath, $namespace) = $item;
            if (!file_exists($migrationPath)) {
                continue;
            }
            $handle = opendir($migrationPath);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $migrationPath . DIRECTORY_SEPARATOR . $file;
                if (preg_match('/^(m(\d{6}_?\d{6})\D.*?)\.php$/is', $file, $matches) && is_file($path)) {
                    $class = $matches[1];
                    if (!empty($namespace)) {
                        $class = $namespace . '\\' . $class;
                    }
                    // Require the file if the class does not already exist.
                    if (!class_exists($class, false)) {
                        require_once $path;
                    }
                    // Handle schema filtering only in multi-tenant mode
                    if ($currentSchema !== null && class_exists($class)) {
                        $reflection = new \ReflectionClass($class);
                        $defaultProperties = $reflection->getDefaultProperties();

                        // If applicableSchemas is defined, then skip this migration if current schema is not in the list.
                        if (isset($defaultProperties['applicableSchemas']) && is_array($defaultProperties['applicableSchemas'])) {
                            if (!in_array($currentSchema, $defaultProperties['applicableSchemas'], true)) {
                                continue;
                            }
                        }
                        // If excludedSchemas is defined, then skip this migration if current schema is in the list.
                        if (isset($defaultProperties['excludedSchemas']) && is_array($defaultProperties['excludedSchemas'])) {
                            if (in_array($currentSchema, $defaultProperties['excludedSchemas'], true)) {
                                continue;
                            }
                        }
                    }
                    $time = str_replace('_', '', $matches[2]);
                    if (!isset($applied[$class])) {
                        // Use a composite key so that migrations get sorted in chronological order.
                        $migrations[$time . '\\' . $class] = $class;
                    }
                }
            }
            closedir($handle);
        }
        ksort($migrations);
        return array_values($migrations);
    }
    protected function createMigrationHistoryTable()
    {
        // Determine schema and table name
        if (isset($_SERVER['APP_MODE']) && $_SERVER['APP_MODE'] === strtolower('multi')) {
            $schema = $this->db->schemaMap[$_SERVER['CORE_DB_DRIVER']]['defaultSchema'];
            $this->stdout("Checking migration history table for schema: {$schema}...\n", Console::FG_CYAN);
        } else {
            $schema = null;
            $this->stdout("Checking migration history table (single mode)...\n", Console::FG_CYAN);
        }

        // Check if table already exists
        if ($this->db->schema->getTableSchema($this->migrationTable, true) !== null) {
            $this->stdout("Migration history table already exists. Skipping creation.\n", Console::FG_CYAN);
            return;
        }

        $this->stdout("*** Creating migration history table" . ($schema ? " for schema: {$schema}" : "") . "...\n", Console::FG_GREEN);

        // Create table
        $this->db->createCommand()->createTable($this->migrationTable, [
            'version' => 'VARCHAR(180) NOT NULL',
            'apply_time' => 'INTEGER',
        ])->execute();

        // Add primary key
        $this->db->createCommand()->addPrimaryKey('pk_migration_version', $this->migrationTable, 'version')->execute();

        $this->stdout("Migration history table created successfully.\n");
    }
    protected function getMigrationHistory($limit)
    {
        // Ensure the migration history table exists
        $this->createMigrationHistoryTable();
        $query = (new Query())
            ->select(['version', 'apply_time'])
            ->from($this->migrationTable)
            ->orderBy(['apply_time' => SORT_DESC, 'version' => SORT_DESC]);
        if (empty($this->migrationNamespaces)) {
            $query->limit($limit);
            $rows = $query->all($this->db);
            $history = ArrayHelper::map($rows, 'version', 'apply_time');
            unset($history[self::BASE_MIGRATION]);
            return $history;
        }
        $rows = $query->all($this->db);
        $history = [];
        foreach ($rows as $key => $row) {
            if ($row['version'] === self::BASE_MIGRATION) {
                continue;
            }
            if (preg_match('/m?(\d{6}_?\d{6})(\D.*)?$/is', $row['version'], $matches)) {
                $time = str_replace('_', '', $matches[1]);
                $row['canonicalVersion'] = $time;
            } else {
                $row['canonicalVersion'] = $row['version'];
            }
            $row['apply_time'] = (int) $row['apply_time'];
            $history[] = $row;
        }
        usort($history, function ($a, $b) {
            if ($a['apply_time'] === $b['apply_time']) {
                if (($compareResult = strcasecmp($b['canonicalVersion'], $a['canonicalVersion'])) !== 0) {
                    return $compareResult;
                }
                return strcasecmp($b['version'], $a['version']);
            }
            return ($a['apply_time'] > $b['apply_time']) ? -1 : +1;
        });
        $history = array_slice($history, 0, $limit);
        $history = ArrayHelper::map($history, 'version', 'apply_time');
        return $history;
    }
    protected function updateRbac()
    {
        $authManager = Yii::$app->getAuthManager();
        if ($authManager instanceof AuthManager) {
            $this->stdout("Updating RBAC...\n", Console::FG_CYAN);
            $auth = AuthConfigs::authManager();
            //default roles
            $this->stdout("*** Creating default roles...\n", Console::FG_YELLOW);
            if (!$auth->getChildren('su')) {
                foreach (['su' => 'Super User', 'editor' => 'Editor', 'creator' => 'Creator', 'viewer' => 'Viewer', 'api' => 'API User', 'deletor' => 'Deletor', 'restore' => 'Restore'] as $key => $value) {
                    $model = new AuthItem(null);
                    $model->type = Item::TYPE_ROLE;
                    $model->name = $key;
                    $model->data = $value;
                    $model->save(false);
                    if ($key === 'su') {
                        $auth->assign($auth->getRole($key), 1); // Assign the super user role to the first user (usually admin)
                    }
                    $this->stdout("    > Role: $value created.\n", Console::FG_GREEN);
                }

                $this->stdout("Default roles created successfully.\n", Console::FG_CYAN);
            } else {
                $this->stdout("Default roles mounted.\n", Console::FG_CYAN);
            }
            //assign roles
            (new AuthItem($auth->getRole('su')))->addChildren(['editor', 'viewer', 'creator', 'api', 'deletor']);
            //default permissions
            $this->stdout("*** Mounting permissions...\n", Console::FG_YELLOW);
            $successful = $failed = 0;
            foreach ((new AuthItem(null))->scanPermissions() as $key => $value) {
                $model = new AuthItem(null);
                $model->type = Item::TYPE_PERMISSION;
                $model->name = $key;
                $model->description = $value;
                $start = microtime(true);
                if ($model->save(false)) {
                    $str = strtolower($model->name);
                    if (str_contains($str, '-')) {
                        (new AuthItem($auth->getRole('api')))->addChildren([$model->name]);
                        $this->stdout("    > Permission: $model->name mounted to api role... done (time: " . sprintf('%.3f', (microtime(true) - $start)) . "s)\n", Console::FG_GREEN);
                    } else {
                        if (str_contains($str, 'create')) {
                            (new AuthItem($auth->getRole('creator')))->addChildren([$model->name]);
                            $this->stdout("    > Permission: $model->name mounted to creator role... done (time: " . sprintf('%.3f', (microtime(true) - $start)) . "s)\n", Console::FG_GREEN);
                        } elseif (str_contains($str, 'update')) {
                            (new AuthItem($auth->getRole('editor')))->addChildren([$model->name]);
                            $this->stdout("    > Permission: $model->name mounted to editor role... done (time: " . sprintf('%.3f', (microtime(true) - $start)) . "s)\n", Console::FG_GREEN);
                        } elseif (str_contains($str, 'list') || str_contains($str, 'view')) {
                            (new AuthItem($auth->getRole('viewer')))->addChildren([$model->name]);
                            $this->stdout("    > Permission: $model->name mounted to viewer role... done (time: " . sprintf('%.3f', (microtime(true) - $start)) . "s)\n", Console::FG_GREEN);
                        } elseif (str_contains($str, 'delete') || str_contains($str, 'remove') || str_contains($str, 'destroy') || str_contains($str, 'trash')) {
                            (new AuthItem($auth->getRole('deletor')))->addChildren([$model->name]);
                            $this->stdout("    > Permission: $model->name mounted to deletor role... done (time: " . sprintf('%.3f', (microtime(true) - $start)) . "s)\n", Console::FG_GREEN);
                        } elseif (str_contains($str, 'restore')) {
                            (new AuthItem($auth->getRole('restore')))->addChildren([$model->name]);
                            $this->stdout("    > Permission: $model->name mounted to restore role... done (time: " . sprintf('%.3f', (microtime(true) - $start)) . "s)\n", Console::FG_GREEN);
                        } else {
                            (new AuthItem($auth->getRole('su')))->addChildren([$model->name]);
                            $this->stdout("    > Permission: $model->name mounted to super user role... done (time: " . sprintf('%.3f', (microtime(true) - $start)) . "s)\n", Console::FG_GREEN);
                        }
                    }
                    $successful++;
                } else {
                    $failed++;
                }
            }
            $this->stdout("*** " . $successful . " permissions mounted and assigned, " . $failed . " permissions failed to mount.\n", Console::FG_YELLOW);
            $this->stdout("RBAC updated successfully.\n", Console::FG_CYAN);
        } else {
            $this->stdout("AuthManager is not configured to use database. Skipping RBAC update.\n", Console::FG_RED);
        }
    }
}
