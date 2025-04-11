<?php

namespace yiitron\novakit\behaviors;

use Yii;
use yii\helpers\Json;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\AttributeBehavior;

class Delete extends AttributeBehavior
{
    /**
     * @var string the attribute that will receive the deleted value
     * Set this property to false if you do not want to mark record as deleted
     */
    public $attribute = 'is_deleted';

    /**
     * @var callable|Expression
     * This can be either an anonymous function that returns the value
     */
    public $value;

    /**
     * @var callable|Expression
     * This can be either an anonymous function that returns the value
     */
    public $restoreValue;

    /**
     * @inheritdoc
     */
    protected function getValue($event = null)
    {
        return $this->value instanceof Expression ? $this->value : ($this->value !== null ? call_user_func($this->value, $event) : 1);
    }

    /**
     * Get restored record field value
     */
    protected function getRestoreValue()
    {
        return $this->restoreValue instanceof Expression ? $this->restoreValue : ($this->restoreValue !== null ? call_user_func($this->restoreValue) : 0);
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'doDisableDelete'
        ];
    }

    /**
     * @param \yii\base\Event $event
     */
    public function doDisableDelete($event)
    {
        $this->remove();
        $event->isValid = false;
    }

    /**
     * Soft delete record
     */
    public function remove()
    {
        $attribute = $this->attribute;
        $this->owner->$attribute = $this->getValue();
        $this->owner->save(false, [$attribute]);
    }

    /**
     * Restore record deleted by soft-delete
     */
    public function restore()
    {
        $attribute = $this->attribute;
        $this->owner->$attribute = $this->getRestoreValue();
        $this->owner->save(false, [$attribute]);
    }

    /**
     * Delete record normally. From database
     */
    public function forceDelete()
    {
        $model = $this->owner;
        $this->detach();
        $model->delete();
    }

}
