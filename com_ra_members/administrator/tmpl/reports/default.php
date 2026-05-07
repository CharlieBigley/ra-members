<?php
/**
 * @version     1.0.1
 * @package     com_ra_members
 * @copyright   Copyright (C) 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie <webmaster@bigley.me.uk> - https://www.stokeandnewcastleramblers.org.uk
 * 25/04/26 CB created 
 */
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

$toolsHelper = new ToolsHelper;
$objTable = new ToolsTable();
ToolBarHelper::title('Membership reports');

// Import CSS
$this->wa = $this->document->getWebAssetManager();
$this->wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$back = 'administrator/index.php?option=com_ra_tools&view=dashboard';
$breadcrumbs = $toolsHelper->buildLink('administrator/index.php', 'Home Dashboard');
$breadcrumbs .= '>' . $toolsHelper->buildLink($back, 'RA Dashboard');
echo $breadcrumbs;
?>

<form action="<?php echo Route::_('index.php?option=com_ra_events&view=reports'); ?>" method="post" name="reportsForm" id="reportsForm">
    <div id="j-main-container" class="span10">
        <div class="clearfix"> </div>
        <?php
        //$mode = $this->escape($this->state->get('list.ordering'));
        //$listDirn = $this->escape($this->state->get('list.direction'));
        $objTable->width = 30;
        $objTable->add_header('Report,Action', 'grey');

        $objTable->add_item("Members by Group");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_members&task=reports.membersByGroup", "Go", False, 'red'));
        $objTable->generate_line();

        // 'Members by Group' => 'administrator/index.php?option=com_ra_members&task=reports.membersByGroup',


        $objTable->generate_table();
        echo $toolsHelper->backButton($back);
        ?>
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</div>
</form>

