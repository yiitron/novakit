<?php

namespace  yiitron\novakit;

use yii\helpers\ArrayHelper;

class AcidModel extends \yii\base\Model
{
    /**
     * Creates and populates a set of models.
     *
     * @param string $creator
     * @param array $multipleModels
     * @return array
     */
    public static function createMultiple($creator, $multipleModels = [], $id = 'id')
    {
        $model    = new $creator['modelClass'];
        //$formName = $model->formName();
        $post     = $creator['data']; //Yii::$app->request->post($formName);
        $models   = [];

        if (! empty($multipleModels)) {
            $keys = array_keys(ArrayHelper::map($multipleModels, $id, $id));
            $multipleModels = array_combine($keys, $multipleModels);
        }

        if ($post && is_array($post)) {
            foreach ($post as $i => $item) {
                if (isset($item[$id]) && !empty($item[$id]) && isset($multipleModels[$item[$id]])) {
                    $models[] = $multipleModels[$item[$id]];
                } else {
                    $models[] = new $creator['modelClass'];
                }
            }
        }

        unset($model, /* $formName, */ $post);

        return $models;
    }
}
