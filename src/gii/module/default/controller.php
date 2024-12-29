<?php
/**
 * This is the template for generating a controller class within a module.
 */

/** @var yii\web\View $this */
/** @var yii\gii\generators\module\Generator $generator */

echo "<?php\n";
?>

namespace <?= $generator->getControllerNamespace() ?>;

/**
 * Default controller for the `<?= $generator->moduleID ?>` module
 */
class DefaultController extends \yiitron\novakit\ApiController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return [];
    }
}
