<?php

namespace yiitron\novakit\auth;

class AuthManager extends \yii\rbac\DbManager
{
    public $itemTable = '{{%access_controls}}';
    public $itemChildTable = '{{%access_bindings}}';
    public $assignmentTable = '{{%auth_assignments}}';
    public $ruleTable = '{{%auth_rules}}';
}
 