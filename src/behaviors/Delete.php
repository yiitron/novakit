<?php
namespace yiitron\novakit\behaviors;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

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
    protected function getValue($event)
    {
        if ($this->value instanceof Expression) {
            return $this->value;
        } else {
            return $this->value !== null ? call_user_func($this->value, $event) : 1;
        }
    }

    /**
     * Get restored record field value
     */
    protected function getRestoreValue()
    {
        if ($this->restoreValue instanceof Expression) {
            return $this->restoreValue;
        } else {
            return $this->restoreValue !== null ? call_user_func($this->restoreValue) : 0;
        }
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
        // set attribute value
        $attribute = $this->attribute;
        $this->owner->$attribute = $this->getValue(null);

        // save record
        $this->owner->save(false, [$attribute]);
    }

    /**
     * Restore record deleted by soft-delete
     */
    public function restore()
    {
        // set attribute value
        $attribute = $this->attribute;
        $this->owner->$attribute = $this->getRestoreValue();

        // save record
        $this->owner->save(false, [$attribute]);
    }

    /**
     * Delete record normally. From database
     */
    public function forceDelete()
    {
        // detach behaviour and delete normally
        $model = $this->owner;
        $this->detach();

        $model->delete();
    }
}
