<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$groupsOnly = $this->state->get('filter.record_type') === 'G';

$objHelper = new ToolsHelper;
$self = 'index.php?option=com_ra_members&view=organisations';
$target = 'administrator/index.php?option=com_ra_members&view=organisation&layout=edit&id=';
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
    echo '<table class="table" id="ra_areasList">' . PHP_EOL;
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
    echo '<th scope="col">' . PHP_EOL;
    echo HTMLHelper::_('searchtools.sort', 'Email Header', 'a.email_header', $listDirn, $listOrder) . PHP_EOL;
    echo '</th>' . PHP_EOL;
    echo '<th scope="col" class="d-none d-md-table-cell">' . PHP_EOL;
    echo HTMLHelper::_('searchtools.sort', 'Logo', 'a.logo', $listDirn, $listOrder) . PHP_EOL;
    echo '</th>' . PHP_EOL;

    if (!$groupsOnly) {
        echo '<th scope="col" class="d-none d-md-table-cell">Groups</th>' . PHP_EOL;
    }

    echo '<th scope="col" class="d-none d-md-table-cell">Members</th>' . PHP_EOL;
    echo '<th scope="col" class="d-none d-md-table-cell">ID</th>' . PHP_EOL;
    echo '</tr></thead>' . PHP_EOL;
    echo '<tbody>' . PHP_EOL;

    foreach ($this->items as $i => $item) {
        echo '<tr class="row' . $i % 2 . '">' . PHP_EOL;
        echo '<td class="article-status">' . $item->code . '</td>' . PHP_EOL;

        if (!$groupsOnly) {
            echo '<td>' . $item->nation . '</td>' . PHP_EOL;
            echo '<td>' . $item->cluster . '</td>' . PHP_EOL;
        }

        echo '<td class="has-context">';

        if ($this->canDo->get('core.edit')) {
            echo $objHelper->buildLink($target . $item->id, $item->name, false, '');
        } else {
            echo $this->escape($item->name);
        }

        echo '</td>' . PHP_EOL;
        echo '<td>' . ($item->email_header != '' ? $this->escape($item->email_header) : '') . '</td>' . PHP_EOL;
        echo '<td class="d-none d-md-table-cell">';

        if ($item->logo != '') {
            $logo = (strpos($item->logo, '/') === false) ? 'images/com_ra_mailman/' . $item->logo : $item->logo;
            echo $objHelper->buildLink($logo, $item->logo, true, '');
        }

        echo '</td>' . PHP_EOL;

        if (!$groupsOnly) {
            echo '<td>';

            if ($item->record_type == 'A') {
                $groupCount = $objHelper->getValue('SELECT COUNT(id) FROM #__ra_groups WHERE code LIKE "' . $item->code . '%"');

                if ($groupCount > 0) {
                    echo '<a href="' . Route::_('index.php?option=com_ra_members&task=organisation.showGroups&area=' . $item->code) . '">';
                    echo $groupCount . '</a>' . PHP_EOL;
                }
            }

            echo '</td>' . PHP_EOL;
        }

        echo '<td>';
        $sql_count = 'SELECT COUNT(id) FROM #__ra_profiles WHERE membershipNumber IS NOT NULL AND ';
        if ($item->record_type == 'A') {
            $memberCount = $objHelper->getValue($sql_count . 'home_group LIKE "' . $item->code . '%"');
        } else {
            $memberCount = $objHelper->getValue($sql_count . 'home_group = "' . $item->code . '"');
        }
        if (is_null($memberCount)) {
            echo '0'    ;
        } else {
            echo '<a href="' . Route::_('index.php?option=com_ra_members&task=organisation.showMembers&code=' . $item->code) . '">';
            echo $memberCount . '</a>' . PHP_EOL;
        }

        if ($item->mailman_active == '1') {
            $load = 'index.php?option=com_ra_members&task=organisations.loadMembers&code=' . $item->code;
            echo ' <a href="' . Route::_($load) . '" class="ms-2">';
            echo '<span class="fa fa-sync" aria-hidden="true"></span><span class="sr-only">Load</span>';
            echo '</a>' . PHP_EOL;
        }

        echo '</td>' . PHP_EOL;
        echo '<td class="d-none d-md-table-cell">' . $item->id . '</td>' . PHP_EOL;
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