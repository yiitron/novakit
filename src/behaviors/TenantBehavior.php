<?php
namespace yiitron\novakit\behaviors;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;

class TenantBehavior extends Behavior
{
    public $defaultSchema = 'public';

    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'setTenantSchema',
        ];
    }

    public function setTenantSchema()
    {
        $tenantSchema = Yii::$app->request->get('tenant', $this->defaultSchema);
        Yii::$app->db->switchSchema($tenantSchema);
    }
}
