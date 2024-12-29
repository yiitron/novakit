<?php

namespace yiitron\novakit\audit;

class AuditModel extends \yii\db\ActiveRecord
{
    
    public static function tableName()
    {
        return '{{%audit_trail}}';
    }
    public function rules()
    {
        return [
            [['audit_time', 'request_method', 'model_name', 'operation', 'field_name', 'old_value', 'new_value', 'user_id', 'ip_address'], 'required'],
            [['old_value', 'duration', 'memory_max', 'request_route', 'new_value','user_agent'], 'string'],
        ];
    }
}
