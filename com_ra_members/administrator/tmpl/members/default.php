<?php
/**
 * @version    1.1.3
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 05/06/26 CB show email (not salesforce id)
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$user = Factory::getApplication()->getIdentity();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
?>

<form action="<?php echo Route::_('index.php?option=com_ra_members&view=members'); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>
                <table class="table table-striped" id="memberList">
                    <thead>
                        <tr>

                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Mem No', 'a.membershipNumber', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Group', 'a.home_group', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Last name', 'a.lastName', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'First name', 'a.firstName', $listDirn, $listOrder); ?>
                            </th>
                            <th scope="col" class="w-3 d-none d-lg-table-cell" >
                                <?php echo HTMLHelper::_('searchtools.sort', 'Email', 'u.email', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Mem type', 'a.memberType', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Status', 'a.memberStatus', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Memshp type', 'a.membershipType', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Term', 'a.memberTerm', $listDirn, $listOrder); ?>
                            </th>
                            <th class='left'>
                                <?php echo HTMLHelper::_('searchtools.sort', 'Exp date', 'a.membershipExpiryDate', $listDirn, $listOrder); ?>
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
                            $ordering = ($listOrder == 'a.ordering');
                            $canCreate = $user->authorise('core.create', 'com_ra_members');
                            $canEdit = $user->authorise('core.edit', 'com_ra_members');
                            $canCheckin = $user->authorise('core.manage', 'com_ra_members');
                            $canChange = $user->authorise('core.edit.state', 'com_ra_members');
                            ?>
                            <tr class="row<?php echo $i % 2; ?>" data-draggable-group='1' data-transition>

                                <?php
                                $target = '/administrator/index.php?option=com_ra_members&view=member&member_id=' . (int) $item->member_id;
                                $linkText = ($item->membershipNumber === null || $item->membershipNumber === '') ? '(blank)' : $item->membershipNumber;
                                echo '<td>' . $this->toolsHelper->buildLink($target, $linkText) . '</td>';
                                echo '<td>' . $item->home_group . '</td>';

                                echo '<td>' . $item->lastName . '</td>';
                                echo '<td>' . $item->firstName . '</td>';
                                echo '<td>' . $item->email . '</td>';
                                echo '<td>' . $item->memberType . '</td>';
                                echo '<td>' . $item->memberStatus . '</td>';
                                echo '<td>' . $item->membershipType . '</td>';
                                echo '<td>' . $item->memberTerm . '</td>';
                                ?>
                                <td>
                                    <?php
                                    $date = $item->membershipExpiryDate;
                                    echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC4')) : '-';
                                    ?>
                                </td>
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