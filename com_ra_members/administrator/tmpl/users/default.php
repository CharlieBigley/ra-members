<?php
/**
 * @version    1.1.8
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2025 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 03/05/25 CB allow edit if MailMan installed (should use tools.profile, not mailman.profile)
 * 05/10/25 CB show SuperUsers
 * 05/07/26 CB copied from com_ra_tools
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
$toolsHelper = new ToolsHelper;

$isSuper = $toolsHelper->isSuperuser();
if (ComponentHelper::isEnabled('com_ra_mailman', true)) {
    $target_edit = '/administrator/index.php?option=com_ra_mailman&task=profile.edit&id=';
}
// Import CSS
$wa = $this->document->getWebAssetManager();
//$wa->useStyle('com_ra_tools.admin')
//        ->useScript('com_ra_tools.admin');

$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
if (ComponentHelper::isEnabled('com_ra_mailman', true)) {
    $mailman = true;
}
if (ComponentHelper::isEnabled('com_ra_mailman', true)) {
    $events = true;
}

$sql_lookup = 'SELECT id FROM #__user_usergroup_map AS map ';
$sql_lookup .= 'INNER JOIN #__usergroups as g on g.id = map.group_id ';
$sql_lookup .= 'WHERE map.group_id=8 AND  map.user_id=';
?>

<form action="<?php echo Route::_('index.php?option=com_ra_members&view=users'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>
                <table class="table table-striped" id="userList">
                    <thead>
                        <tr>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Real Name', 'a.name', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Email', 'a.email', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Blocked', 'a.block', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Require Reset', 'a.requireReset', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Group', 'p.home_group', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Preferred name', 'p.preferred_name', $listDirn, $listOrder); ?>
                            </th>
                            <?php
                            echo '<th>Roles</th>';
                            echo '<th>U groups</th>';
                            if ($isSuper) {
                                echo '<th>Tools</th>';
                                echo '<th>Members</th>';
                                if ($events) {
                                    echo '<th>Events</th>';
                                }
                                if ($mailman) {
                                    echo '<th>MailMan</th>';
                                }
                            }
                            ?>
                            <th scope="col" class="w-3 d-none d-lg-table-cell" >
                                <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
                                <?php echo $this->pagination->getListFooter(); ?>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody <?php if (!empty($saveOrder)) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" <?php endif; ?>>
                        <?php
                        foreach ($this->items as $i => $item) :
//                            $access = $this->showAccess($item->id);
                            $canCreate = $this->user->authorise('core.create', 'com_ra_tools');
                            $canEdit = $this->user->authorise('core.edit', 'com_ra_tools');
//                            echo $sql_lookup . $item->id;
                            $super = $toolsHelper->getValue($sql_lookup . $item->id);
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group='1' data-transition>

                                <?php
                                echo '<td>' . $item->name;
                                if (!is_null($super)) {
                                    echo '<i class="fas fa-star fa-fw"></i>';
                                }
                                echo '</td>';
                                echo '<td>' . $item->email . '</td>';
                                echo '<td>' . $item->block . '</td>';
                                echo '<td>' . $item->requireReset . '</td>';
                                echo '<td>' . $item->home_group . '</td>';
                                echo '<td>' . $item->preferred_name . '</td>';
                                echo '<td>' . $this->countRoles($item->id) . '</td>';
                                echo '<td>' . $this->countUsergroups($item->id) . '</td>';
                                if ($isSuper) {
                                    echo '<td>' . $this->checkGroup($item->id, 1) . '</td>';
                                    echo '<td>' . $this->checkGroup($item->id, 4) . '</td>';
                                    if ($events) {
                                        echo '<td>' . $this->checkGroup($item->id, 2) . '</td>';
                                    }
                                    if ($mailman) {
                                        echo '<td>' . $this->checkGroup($item->id, 3) . '</td>';
                                    }
                                    $link = $toolsHelper->buildLink($target_edit . $item->id, $item->id);
                                }
                                echo '<td class="d-none d-lg-table-cell">' . $link . '</td>';
//                                } else {
                                //                                   echo '<td class="d-none d-lg-table-cell">' . $item->id . '</td>';
                                //                               }
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <input type="hidden" name="task" value=""/>
                <input type="hidden" name="boxchecked" value="0"/>
                <input type="hidden" name="list[fullorder]" value="<?php echo $listOrder; ?> <?php echo $listDirn; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
