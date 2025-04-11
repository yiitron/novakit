<?php

namespace yiitron\novakit;

class ApiModule extends \yii\base\Module
{
    public function init()
    {
        parent::init();
        \Yii::$app->user->enableSession = false;
    }
}
