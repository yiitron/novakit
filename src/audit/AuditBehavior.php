<?php

namespace yiitron\novakit\audit;

use Yii;
use yii\helpers\Url;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Class AuditTrailBehaviour
 *
 */
class AuditBehavior extends Behavior
{
    /**
     * string
     */
    const NO_USER_ID = "NO_USER_ID";

    /**
     * @param $class
     * @param $attribute
     *
     * @return string
     */
    public static function getLabel($class, $attribute)
    {
        $labels = $class::attributeLabels();
        if (isset($labels[$attribute])) {
            return $labels[$attribute];
        } else {
            return ucwords(str_replace('_', ' ', $attribute));
        }
    }

    /**
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
        ];
    }

    /**
     * @param      $event
     *
     * @param null $attributes
     *
     * @return mixed
     */
    public function afterSave($event, $attributes = null)
    {
        $this->ensureTableExists();
        $app = Yii::$app;
        $request = $app->request;
        $userId = self::NO_USER_ID;
        if(!Yii::$app->user->isGuest){
            $userId = Yii::$app->user->identity->user_id;
        }
        $newAttributes = $this->owner->getAttributes();
        $oldAttributes = $event->changedAttributes;

        // $action = Yii::$app->controller->action->id;

        //AuditModel::ensureTableExists();
        if (!$this->owner->isNewRecord) {
            // compare old and new
            foreach ($oldAttributes as $name => $oldValue) {
                if (!empty($newAttributes)) {
                    $newValue = $newAttributes[$name];
                } else {
                    $newValue = 'NA';
                }
                if ($oldValue != $newValue && $name != 'updated_at') {
                    $log = new AuditModel();
                    $log->old_value = $oldValue;
                    $log->new_value = $newValue;
                    $log->operation = $name == 'is_deleted' ? 'DELETE' : 'UPDATE';
                    $log->model_name = Yii::$app->controller->module->id.': '.substr(get_class($this->owner), strrpos(get_class($this->owner), '\\') + 1);
                    $log->field_name = $name;
                    $log->audit_time = $log->created_at = $log->updated_at = time();
                    $log->user_id = $userId;
                    $log->ip_address = $request->getUserIP();
                    $log->request_method = $request->method;
                    $log->duration = (microtime(true) - YII_BEGIN_TIME);
                    $log->memory_max = memory_get_peak_usage();
                    $log->request_route = $app->requestedAction ? $app->requestedAction->uniqueId : null;
                    $log->user_agent = $request->userAgent;
                    $log->headers = json_encode($request->headers->toArray(), JSON_UNESCAPED_SLASHES);
                    $log->query_params = json_encode($request->queryParams, JSON_UNESCAPED_SLASHES);
                    $log->body_params = json_encode($request->bodyParams, JSON_UNESCAPED_SLASHES);
                    $log->raw_body = $request->getRawBody();
                    $log->url = Url::base(true).$request->url;
                    $log->save(false);
                }
            }
        } else {
            foreach ($newAttributes as $name => $value) {
                if ($name != 'created_at' || $name != 'updated_at') {
                    $log = new AuditModel();
                    $log->old_value = 'NA';
                    $log->new_value = $value;
                    $log->operation = 'INSERT';
                    $log->model_name = Yii::$app->controller->module->id.': '.substr(get_class($this->owner), strrpos(get_class($this->owner), '\\') + 1);
                    $log->field_name = $name;
                    $log->audit_time = $log->created_at = $log->updated_at = time();
                    $log->user_id = $userId;
                    $log->ip_address = $request->getUserIP();
                    $log->request_method = $request->method;
                    $log->duration = (microtime(true) - YII_BEGIN_TIME);
                    $log->memory_max = memory_get_peak_usage();
                    $log->request_route = $app->requestedAction ? $app->requestedAction->uniqueId : null;
                    $log->user_agent = $request->userAgent;
                    $log->headers = json_encode($request->headers->toArray(), JSON_UNESCAPED_SLASHES);
                    $log->query_params = json_encode($request->queryParams, JSON_UNESCAPED_SLASHES);
                    $log->body_params = json_encode($request->bodyParams, JSON_UNESCAPED_SLASHES);
                    $log->raw_body = $request->getRawBody();
                    $log->url = Url::base(true).$request->url;
                    $log->save();
                }
            }
        }
        return true;
    }
    protected function ensureTableExists()
    {
        Yii::$app->db->schema->refreshTableSchema('{{%audit_trail}}');
        if (Yii::$app->db->schema->getTableSchema('{{%audit_trail}}') === null) {
            // Run migration to create the table dynamically
            (new AuditTableMigration())->safeUp();
        }
    }
}
