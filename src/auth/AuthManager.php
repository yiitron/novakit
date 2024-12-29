<?php

namespace yiitron\novakit\auth;

class AuthManager extends \yii\rbac\DbManager
{
    public $itemTable = '{{%auth_roles_n_permissions}}';
    public $itemChildTable = '{{%auth_role_bundles}}';
    public $assignmentTable = '{{%auth_assignments}}';
    public $ruleTable = '{{%auth_rules}}';
}
