<?php
/**
 * @version    1.0.0
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$groupsOnly = $this->state->get('filter.record_type') === 'G';

$objHelper = new ToolsHelper;
$db        = Factory::getDbo();
$self      = 'index.php?option=com_ra_members&view=organisations';

if ($this->params->get('show_page_heading')) {
    echo '<div class="page-header"><h1>' . $this->escape($this->params->get('page_heading')) . '</h1></div>' . PHP_EOL;
}

echo '<form action="' . Route::_($self) . '" method="post" name="adminForm" id="adminForm">' . PHP_EOL;
echo '<div class="row">' . PHP_EOL;
echo '<div class="col-md-12">' . PHP_EOL;
echo '<div id="j-main-container" class="j-main-container">' . PHP_EOL;
echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]);

if (empty($this->items)) {
    echo '<div class="alert alert-info">' . PHP_EOL;
    echo '<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only">';
    echo Text::_('INFO') . '</span>' . PHP_EOL;
    echo Text::_('JGLOBAL_NO_MATCHING_RESULTS') . PHP_EOL;
    echo '</div>' . PHP_EOL;
} else {
    echo '<table class="table" id="ra_organisationsList">' . PHP_EOL;
    echo '<thead><tr>' . PHP_EOL;
    echo '<th scope="col" style="width:1%; min-width:85px" class="text-center">' . PHP_EOL;
    echo HTMLHelper::_('searchtools.sort', 'Code', 'a.code', $listDirn, $listOrder) . PHP_EOL;
    echo '</th>' . PHP_EOL;

    if (!$groupsOnly) {
        echo '<th scope="col">' . PHP_EOL;
        echo HTMLHelper::_('searchtools.sort', 'Nation', 'n.name', $listDirn, $listOrder) . PHP_EOL;
        echo '</th>' . PHP_EOL;
        echo '<th class="left">' . PHP_EOL;
        echo HTMLHelper::_('searchtools.sort', 'Cluster', 'a.cluster', $listDirn, $listOrder);
        echo '</th>' . PHP_EOL;
    }

    echo '<th scope="col">' . PHP_EOL;
    echo HTMLHelper::_('searchtools.sort', 'Name', 'a.name', $listDirn, $listOrder) . PHP_EOL;
    echo '</th>' . PHP_EOL;
    echo '<th scope="col" class="d-none d-md-table-cell">' . PHP_EOL;
    echo HTMLHelper::_('searchtools.sort', 'Website', 'a.website', $listDirn, $listOrder) . PHP_EOL;
    echo '</th>' . PHP_EOL;

    if (!$groupsOnly) {
        echo '<th scope="col" class="d-none d-md-table-cell">Groups</th>' . PHP_EOL;
    }

    echo '<th scope="col" class="d-none d-md-table-cell">Members</th>' . PHP_EOL;
    echo '</tr></thead>' . PHP_EOL;
    echo '<tbody>' . PHP_EOL;

    foreach ($this->items as $i => $item) {
        echo '<tr class="row' . $i % 2 . '">' . PHP_EOL;
        echo '<td>' . $this->escape($item->code) . '</td>' . PHP_EOL;

        if (!$groupsOnly) {
            echo '<td>' . $this->escape($item->nation) . '</td>' . PHP_EOL;
            echo '<td>' . $this->escape($item->cluster) . '</td>' . PHP_EOL;
        }

        echo '<td>';
        if (!empty($item->website)) {
            echo $objHelper->buildLink($item->website, $this->escape($item->name), true, '');
        } else {
            echo $this->escape($item->name);
        }
        echo '</td>' . PHP_EOL;

        echo '<td class="d-none d-md-table-cell">';
        if (!empty($item->website)) {
            echo $objHelper->buildLink($item->website, $this->escape($item->website), true, '');
        }
        echo '</td>' . PHP_EOL;

        if (!$groupsOnly) {
            echo '<td class="d-none d-md-table-cell">';
            if ($item->record_type == 'A') {
                $safeCode   = $db->escape($item->code);
                $groupCount = $objHelper->getValue('SELECT COUNT(id) FROM #__ra_groups WHERE code LIKE "' . $safeCode . '%"');
                if ($groupCount > 0) {
                    echo $groupCount;
                }
            }
            echo '</td>' . PHP_EOL;
        }

        echo '<td class="d-none d-md-table-cell">';
        $sql_count = 'SELECT COUNT(member_id) FROM #__ra_profiles WHERE membershipNumber IS NOT NULL AND ';
        $safeCode  = $db->escape($item->code);
        if ($item->record_type == 'A') {
            $memberCount = $objHelper->getValue($sql_count . 'home_group LIKE "' . $safeCode . '%"');
        } else {
            $memberCount = $objHelper->getValue($sql_count . 'home_group = "' . $safeCode . '"');
        }
        echo is_null($memberCount) ? '0' : (int) $memberCount;
        echo '</td>' . PHP_EOL;

        echo '</tr>' . PHP_EOL;
    }

    echo '</tbody></table>' . PHP_EOL;
    echo $this->pagination->getListFooter();
}
?>

<input type="hidden" name="task" value="">
<input type="hidden" name="boxchecked" value="0">
<?php echo HTMLHelper::_('form.token'); ?>
</div>
</div>
</div>
</form>
