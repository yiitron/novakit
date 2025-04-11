<?php

namespace yiitron\novakit;

use yiitron\novakit\traits\Keygen;


class Migration extends \yii\db\Migration
{
    public $tableOptions = null;
    use Keygen;
    public function buildFkClause($delete = '', $update = '')
    {
        return implode(' ', ['', $delete, $update]);
    }
}