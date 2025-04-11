<?php
/**
 * This is the template for generating a controller class within a module.
 */

/** @var yii\web\View $this */
/** @var yii\gii\generators\module\Generator $generator */

echo "<?php\n";
?>

namespace <?= $generator->getControllerNamespace('console') ?>;

/**
 * Default console controller for the `<?= $generator->moduleID ?>` module
 */

class ConsoleController extends \yii\console\Controller
{
    
}
