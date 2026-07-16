<?php
/**
 * @version     1.1.7
 * @package     com_ra_members
 * @copyright   Copyright (C) 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie <webmaster@bigley.me.uk> - https://www.stokeandnewcastleramblers.org.uk
 * 25/04/26 CB created
 * 22/06/26 CB new reports for Volunteers, Affilites
 * 16/07/26 CB new formatting
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
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

ToolBarHelper::title('Membership reports');

// Import CSS
$this->wa = $this->document->getWebAssetManager();
$this->wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
$this->wa->registerAndUseStyle('dashboard', 'com_ra_tools/dashboard.css');

$back = 'administrator/index.php?option=com_ra_tools&view=dashboard';
$breadcrumbs = $this->toolsHelper->buildLink('administrator/index.php', 'Dashboard');
$breadcrumbs .= '>' . $this->toolsHelper->buildLink($back, 'RA Dashboard');
echo $breadcrumbs;

// find current scope
$mailHelper = new MailHelper;
$code = $mailHelper->getDefaultGroup();

if (!empty($code) && $code !== 'N') {
    $sql = 'SELECT id, name ';
    $sql .= 'FROM #__ra_organisations ';
    $sql .= 'WHERE code="' . $code . '"';
    $item = $this->toolsHelper->getItem($sql);
    if ($item === false) {
        $subheading = $code . ' Query failed: ' . htmlspecialchars((string) $this->toolsHelper->error);
    } else {
        $subheading = $code . ' ' . (!empty($item->name) ? htmlspecialchars($item->name) : 'N/A');
    }
    $group_report_heading = 'Group reports for ' . $code; 
} else {
    $subheading = 'All records';
    $group_report_heading = 'Group reports';
}
echo '<h4>Scope ' . $subheading . '</h4>';
?>

<?php

$admin_reports = [
    // only show these reports to superusers

    'Users awaiting password reset' => 'administrator/index.php?option=com_ra_mailman&task=reports.resetUsers',
//    'Search all Logfile records' => 'administrator/index.php?option=com_ra_tools&view=logfiles&callback=dashboard',
    'Show recent Logfile records' => 'administrator/index.php?option=com_ra_tools&task=reports.showLogfile&option=com_ra_mailman',
];

$reports = [
    'Membership statistics' => 'administrator/index.php?option=com_ra_members&task=reports.memberStatistics',
//    'Members by Group' => 'administrator/index.php?option=com_ra_members&task=reports.membersByGroup',
//    'Analysis of members by group' => 'administrator/index.php?option=com_ra_members&task=reports.analyseListMembership',
    'Recent updates' => 'administrator/index.php?option=com_ra_members&task=reports.recentUpdates',
    'Recent joiners' => 'administrator/index.php?option=com_ra_members&task=reports.recentJoiners',
    'Changed group' => 'administrator/index.php?option=com_ra_members&task=reports.changedGroup',
    'Volunteers' => 'administrator/index.php?option=com_ra_members&task=reports.generalReport&mode=V',
    'Affiliate Members' => 'administrator/index.php?option=com_ra_members&task=reports.generalReport&mode=A',
    'Joint members' => 'administrator/index.php?option=com_ra_members&task=reports.jointMembers',
    'Lapsed members' => 'administrator/index.php?option=com_ra_members&task=reports.lapsedMembers',
    'Members with duplicate names' => 'administrator/index.php?option=com_ra_members&task=reports.duplicateNames',
    'Members joined Ramblers, by month' => 'administrator/index.php?option=com_ra_members&task=reports.analyseJoinedRamblers',
    'Members joined Area, by month' => 'administrator/index.php?option=com_ra_members&task=reports.analyseJoinedArea',
    'Members joined Group, by month' => 'administrator/index.php?option=com_ra_members&task=reports.analyseJoinedGroup',
    'Members lapsing, by month' => 'administrator/index.php?option=com_ra_members&task=reports.analyseLapsing',
    'Export members' => 'administrator/index.php?option=com_ra_members&task=reports.exportMembers',
];
//if ($code !== 'N') {
//    $reports[] = 'Export members' => 'administrator/index.php?option=com_ra_members&task=reports.exportMembers';
//}

$systemReports = array();

if ($this->toolsHelper->isSuperuser()) {
    $systemReports = $admin_reports;
}

if (($this->toolsHelper->isSuperuser()) || ($code == 'N')) {
    $systemReports = array_merge($systemReports, $reports);
}

$areaReports = array();
$show_area_reports = $this->params->get('show_area_reports', 0);

if ($show_area_reports == '1') {
    foreach ($reports as $caption => $task) {
        $areaReports[$caption] = $task . '&scope=A';
    }
    $areaReports['Members by Group'] = 'administrator/index.php?option=com_ra_members&task=reports.membersByGroup&scope=A';
}

$groupReports = array();

if ($code !== 'N') {
    foreach ($reports as $caption => $task) {
        $groupReports[$caption] = $task . '&scope=G';
    }
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_ra_tools&view=reports'); ?>" method="post" name="reportsForm" id="reportsForm">
    <div id="j-main-container" class="span10">
        <div class="clearfix"> </div>
        <?php
        echo '<div class="dashboard-grid">';
        echo $this->toolsHelper->buildDashboardReportBlock('System reports', $systemReports);
        if ($show_area_reports == '1') {       
            echo $this->toolsHelper->buildDashboardReportBlock('Area reports', $areaReports);
        }
        echo $this->toolsHelper->buildDashboardReportBlock($group_report_heading, $groupReports);
        echo '</div>';
        echo $this->toolsHelper->backButton($back);
        ?>
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</div>
</form>
