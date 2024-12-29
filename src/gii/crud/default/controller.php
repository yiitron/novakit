<?php
/**
 * This is the template for generating a CRUD controller class file.
 */
 
use yii\db\ActiveRecordInterface;
use yii\helpers\StringHelper;


/** @var yii\web\View $this */
/** @var yii\gii\generators\crud\Generator $generator */

$controllerClass = StringHelper::basename($generator->controllerClass);
$modelClass = StringHelper::basename($generator->modelClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);
if ($modelClass === $searchModelClass) {
    $searchModelAlias = $searchModelClass . 'Search';
}

/* @var $class ActiveRecordInterface */
$class = $generator->modelClass;
$pks = $class::primaryKey();
$urlParams = $generator->generateUrlParams();
$actionParams = $generator->generateActionParams();
$actionParamComments = $generator->generateActionParamComments();
$namespace = StringHelper::dirname(ltrim($generator->controllerClass, '\\'));
$prefix =explode('\\',$namespace)[0];
echo "<?php\n";
?>

namespace <?= StringHelper::dirname(ltrim($generator->controllerClass, '\\')) ?>;

use Yii;
use <?= ltrim($generator->modelClass, '\\') ?>;
<?php if (!empty($generator->searchModelClass)): ?>
use <?= ltrim($generator->searchModelClass, '\\') . (isset($searchModelAlias) ? " as $searchModelAlias" : "") ?>;
<?php else: ?>
<?php endif; ?>
/**
 * @OA\Tag(
 *     name="<?=$modelClass?>",
 *     description="Available endpoints for <?=$modelClass?> model"
 * )
 */
class <?= $controllerClass ?> extends <?= '\\'.ltrim($generator->baseControllerClass, '\\') ?>
{
    public $permissions = [
        '<?=$prefix.ucfirst($generator->getControllerID())?>List'=>'View <?= $modelClass ?> List',
        '<?=$prefix.ucfirst($generator->getControllerID())?>Create'=>'Add <?= $modelClass ?>',
        '<?=$prefix.ucfirst($generator->getControllerID())?>Update'=>'Edit <?= $modelClass ?>',
        '<?=$prefix.ucfirst($generator->getControllerID())?>Trash'=>'Delete <?= $modelClass ?>',
        '<?=$prefix.ucfirst($generator->getControllerID())?>Restore'=>'Restore <?= $modelClass ?>',
        ];
    public function actionIndex()
    {
        Yii::$app->user->can('<?=$prefix.ucfirst($generator->getControllerID())?>List');
<?php if (!empty($generator->searchModelClass)): ?>
        <?php $smodel = isset($searchModelAlias) ? $searchModelAlias : $searchModelClass ?>
        $searchModel = new <?= $smodel ?>();
        $search = $this->queryParameters(Yii::$app->request->queryParams,'<?= $smodel ?>');
        $dataProvider = $searchModel->search($search);
        return $this->payloadResponse($dataProvider,['oneRecord'=>false]);
<?php else: ?>
        $dataProvider = new ActiveDataProvider([
            'query' => <?= $modelClass ?>::find(),
            /*
            'pagination' => [
                'pageSize' => 50
            ],
            'sort' => [
                'defaultOrder' => [
<?php foreach ($pks as $pk): ?>
                    <?= "'$pk' => SORT_DESC,\n" ?>
<?php endforeach; ?>
                ]
            ],
            */
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
<?php endif; ?>
    }

    public function actionView($id)
    {
        Yii::$app->user->can('<?=$prefix.ucfirst($generator->getControllerID())?>View');
        return $this->payloadResponse($this->findModel($id));
    }

    public function actionCreate()
    {
        Yii::$app->user->can('<?=$prefix.ucfirst($generator->getControllerID())?>Create');
        $model = new <?= $modelClass ?>();
        $model->loadDefaultValues();
        $dataRequest['<?= $modelClass ?>'] = Yii::$app->request->getBodyParams();
        if($model->load($dataRequest) && $model->save()) {
            return $this->payloadResponse($model,['statusCode'=>201,'message'=>'<?= $modelClass ?> added successfully']);
        }
        return $this->errorResponse($model->getErrors()); 
    }

    public function actionUpdate($id)
    {
        Yii::$app->user->can('<?=$prefix.ucfirst($generator->getControllerID())?>Update');
        $dataRequest['<?= $modelClass ?>'] = Yii::$app->request->getBodyParams();
        $model = $this->findModel($id);
        if($model->load($dataRequest) && $model->save()) {
           return $this->payloadResponse($this->findModel($id),['statusCode'=>202,'message'=>'<?= $modelClass ?> updated successfully']);
        }
        return $this->errorResponse($model->getErrors()); 
    }

    public function actionTrash($id)
    {
        $model = $this->findModel($id);
        if ($model->is_deleted) {
            Yii::$app->user->can('<?=$prefix.ucfirst($generator->getControllerID())?>Restore');
            $model->restore();
            return $this->toastResponse(['statusCode'=>202,'message'=>'<?= $modelClass ?> restored successfully']);
        } else {
            Yii::$app->user->can('<?=$prefix.ucfirst($generator->getControllerID())?>Delete');
            $model->delete();
            return $this->toastResponse(['statusCode'=>202,'message'=>'<?= $modelClass ?> deleted successfully']);
        }
        return $this->errorResponse($model->getErrors()); 
    }

    protected function findModel(<?= $actionParams ?>)
    {
<?php
$condition = [];
foreach ($pks as $pk) {
    $condition[] = "'$pk' => \$$pk";
}
$condition = '[' . implode(', ', $condition) . ']';
?>
        if (($model = <?= $modelClass ?>::findOne(<?= $condition ?>)) !== null) {
            return $model;
        }
        throw new \yii\web\NotFoundHttpException(<?= $generator->generateString('Record not found.') ?>);
    }
}
