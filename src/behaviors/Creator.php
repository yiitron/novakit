<?php

namespace yiitron\novakit\behaviors;

use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use Yii;

class Creator extends AttributeBehavior
{
    public $attribute = 'created_by';
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'addCreator'
        ];
    }
    public function addCreator($event)
    {
        $model = $event->sender;
        $attribute = $this->attribute;
        if ($model->hasAttribute($attribute)) {
            if (isset($this->owner->$attribute)) {
                $this->owner->$attribute = Yii::$app->user->identity ? Yii::$app->user->identity->user_id : 0;
            } else {
                return false;
            }
        }
    }
}
