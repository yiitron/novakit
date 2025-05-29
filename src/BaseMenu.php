<?php

namespace yiitron\novakit;

use yiitron\novakit\auth\AuthUser;

class BaseMenu extends \yii\base\Component
{
    public $permissions = [];
    public $user;

    public function __construct($config = [])
    {
        if (isset($config['permissions'])) {
            $this->permissions = $config['permissions'];
        }
        parent::__construct($config);
    }

    public function loadMenus()
    {
        return array_values(array_filter(array_map(function ($item) {
            if (isset($item['visible']) && !$item['visible']) {
                return null;
            }
            if (isset($item['submenus'])) {
                $item['submenus'] = array_values(array_filter(array_map(function ($subItem) {
                    return (isset($subItem['visible']) && !$subItem['visible']) ? null : array_diff_key($subItem, ['visible' => true]);
                }, $item['submenus'])));
                if (empty($item['submenus'])) {
                    return null;
                }
            }
            return array_diff_key($item, ['visible' => true]);
        }, $this->menus())));
    }
    protected function checkRights($permission)
    {
        if (in_array($permission, $this->permissions, true)) {
            return true;
        }
        return false;
    }

    protected function getId()
    {
        return \Yii::$app->controller->module->id;
    }
}
