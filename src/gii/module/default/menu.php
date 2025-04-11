<?php
/**
 * This is the template for generating the menu class for a module.
 */

echo "<?php\n";
?>
namespace <?= $generator->moduleID ?>\hooks;

/**
 * This is the menu class for <?= $generator->moduleID ?> module.
 *
 */
class Menu extends \yiitron\novakit\BaseMenu
{
    protected  function menus()
    {
        return [
            ['label' => 'Dashboard', 'icon' => 'paw', 'route' => '/'.$this->id, 'visible' => $this->checkRights('<?=strtolower($generator->moduleID) ?>Dashboard')],
            //This is a sample two level menu
            /*[
                'label' => 'Sample Menu',
                'icon' => 'info',
                'route' => '#',
                'submenus' => [
                    ['label' => 'Sample 1', 'route' => '/'.$this->id.'/sample-1', 'visible' => $this->checkRights('<?=strtolower($generator->moduleID) ?>Sample1')],
                    ['label' => 'Sample 2', 'route' => '/'.$this->id.'/sample-2', 'visible' => $this->checkRights('<?=strtolower($generator->moduleID) ?>Sample2')],
                    ['label' => 'Sample 3', 'route' => '/'.$this->id.'/sample-3', 'visible' => $this->checkRights('<?=strtolower($generator->moduleID) ?>Sample3')],
                ]
            ],*/
        ];
    }
    
}

