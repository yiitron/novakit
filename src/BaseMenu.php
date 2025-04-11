<?php

namespace yiitron\novakit;

class BaseMenu extends \yii\base\Component
{
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
        return true;
        if (\Yii::$app->user->can($permission)) {
            return true;
        }
        return false;
    }
    
    protected function getId()
    {
        return \Yii::$app->controller->module->id;
    }
}
