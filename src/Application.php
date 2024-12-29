<?php

namespace yiitron\novakit;

class Application extends \yii\web\Application
{
    
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'request' => ['class' => 'yii\web\Request'],
            'response' => ['class' => 'yii\web\Response'],
            'session' => ['class' => 'yii\web\Session'],
            'user' => ['class' => 'yiitron\novakit\auth\AuthUser'],
            'errorHandler' => ['class' => 'yii\web\ErrorHandler'],
        ]);
    }
}
