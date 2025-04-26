<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/** @var yii\web\View $this */
/** @var yii\gii\generators\model\Generator $generator */
/** @var string $tableName full table name */
/** @var string $className class name */
/** @var string $queryClassName query class name */
/** @var yii\db\TableSchema $tableSchema */
/** @var array $properties list of properties (property => [type, name. comment]) */
/** @var string[] $labels list of attribute labels (name => label) */
/** @var string[] $rules list of validation rules */
/** @var array $relations list of relations (name => relation declaration) */

echo "<?php\n";
?>

namespace <?= $generator->ns ?>;

use Yii;
/**
 *@OA\Schema(
 *  schema="<?= $className ?>",
<?php foreach ($properties as $property => $data): 
    $label = \yii\helpers\Inflector::camel2words($property);
?>
 *  @OA\Property(property="<?=$property?>", type="<?=$data['type']=='int'?'integer':rtrim($data['type'],'|null')?>",title="<?=ucfirst(str_replace('_',' ',$property))?>", example="<?=$data['type']=='int'?'integer':rtrim($data['type'],'|null')?>"),
<?php endforeach; ?>
 * )
 */

class <?= $className ?> extends \<?=(explode('\\',$generator->ns))[0].'\hooks\\'. ltrim('BaseModel', '\\') . "\n" ?>
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '<?= $generator->generateTableName($tableName) ?>';
    }
<?php if ($generator->db !== 'db'): ?>

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('<?= $generator->db ?>');
    }
<?php endif; ?>
    /**
     * list of fields to output by the payload.
     */
    public function fields()
    {
        return  
            [
        <?php foreach ($properties as $property => $data): 
            $field="'".$property."'";
            if($property == 'status'){
                $field = "'status' => function () {
                    return \$this->recordStatus;
                }";
            }
            if($property == 'created_at' || $property == 'updated_at' || $property == 'is_deleted' || $property == 'id' || $property == 'crypt_id'){
                continue;
            }
        ?>
    <?= "{$field}," . "\n" ?>
        <?php endforeach; ?>
    ];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [<?= empty($rules) ? '' : ("\n            " . implode(",\n            ", $rules) . ",\n        ") ?>];
    }
    
<?php foreach ($relations as $name => $relation): ?>

    /**
     * Gets query for [[<?= $name ?>]].
     *
     * @return <?= $relationsClassHints[$name] . "\n" ?>
     */
    public function get<?= $name ?>()
    {
        <?= $relation[0] . "\n" ?>
    }
<?php endforeach; ?>
<?php if ($queryClassName): ?>
<?php
    $queryClassFullName = ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName;
    echo "\n";
?>
    /**
     * {@inheritdoc}
     * @return <?= $queryClassFullName ?> the active query used by this AR class.
     */
    public static function find()
    {
        return new <?= $queryClassFullName ?>(get_called_class());
    }
<?php endif; ?>
}
