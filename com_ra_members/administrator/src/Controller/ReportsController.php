<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 19/05/26 CB jointMembers
 * 05/06/26 CB show group code unless scope is G
 */

namespace Ramblers\Component\Ra_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;
use Ramblers\Component\Ra_tools\Site\Helpers\UserHelper;

/**
 * Reports list controller class.
 *
 * @since  1.0.0
 */
class ReportsController extends AdminController {

    protected $back = 'administrator/index.php?option=com_ra_members&view=reports';
    protected $breadcrumbs;
    protected $db;
    protected $app;
    protected $prefix;
    protected $query;
    protected $scope;
    protected $subheading;
    protected $toolsHelper;

    public function __construct() {
        parent::__construct();
        $this->db = Factory::getDbo();
        $this->toolsHelper = new ToolsHelper;
        $this->mailHelper = new MailHelper;
        $this->app = Factory::getApplication();
        $this->prefix = 'Reports: ';
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
        $this->breadcrumbs = $this->toolsHelper->buildLink('administrator/index.php', 'Dashboard');
        $this->breadcrumbs .= '>' . $this->toolsHelper->buildLink('administrator/index.php?option=com_ra_tools&view=dashboard', 'RA Dashboard');
        $this->breadcrumbs .= '>' . $this->toolsHelper->buildLink($this->back, 'Membership Reports');
        $this->scope = $this->app->input->getWord('scope', '');
        if ($this->scope == '') {
            $this->subheading = 'All records';
        } else {
            $code = $this->mailHelper->getDefaultGroup();
            if ($this->scope == 'A') {
                $code = substr($code, 0, 2);
            }

            $sql = 'SELECT id, name ';
            $sql .= 'FROM #__ra_organisations ';
            $sql .= 'WHERE code="' . $code . '"';
            $item = $this->toolsHelper->getItem($sql);
            if ($item === false) {
                $this->subheading = $code . ' Query failed: ' . htmlspecialchars((string) $this->toolsHelper->error);
            } else {
                $this->subheading = $code . ' ' . (!empty($item->name) ? htmlspecialchars($item->name) : 'N/A');
            }
        }
    }

    public function analyseListMembership() {
        ToolBarHelper::title($this->prefix . 'Analysis of members by group');
        echo $this->breadcrumbs;
        $header = 'Status,Group,Count';

        $sql = 'SELECT l.id, l.`group_code`, l.`name`';
        $sql .= 'FROM `j5_ra_mail_lists` AS l ';
        $sql .= 'WHERE l.`group_code` = l.`group_primary`';

        $lists = $this->toolsHelper->getRows($sql);
        foreach ($lists as $list) {
            echo '<h4>' . $list->group_code . ': ' . $list->name . '</h4>';
            $table = new ToolsTable();
            $table->add_header($header);

            $sql = 'SELECT l.state, p.home_group, COUNT(l.id) As `Cnt`';
            $sql .= 'FROM `j5_ra_mail_lists` AS l ';
            $sql .= 'INNER JOIN `j5_ra_mail_subscriptions` AS s ON s.list_id = l.id ';
            $sql .= 'INNER JOIN `j5_ra_profiles` AS p ON p.id = s.user_id ';
            $sql .= 'WHERE  l.`id`=' . $list->id;
            $sql .= ' GROUP BY l.state, l.`group_code`,p.home_group';
            $rows = $this->toolsHelper->getRows($sql);
            foreach ($rows as $row) {
                $table->add_item($row->state);
                $table->add_item($row->home_group);
                $table->add_item($row->Cnt);
                $table->generate_line();
            }
            $table->generate_table();
        }
        echo $this->toolsHelper->backButton($this->back);
    }

    public function analyseJoinedArea() {
        echo $this->breadcrumbs;
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $field = 'areaJoinedDate';
        $table = ' #__ra_profiles';
        $title = 'Members joined Area, by month';
        $link = 'administrator/index.php?option=com_ra_members&task=reports.showMembersJoinedArea&scope=' . $this->scope;
        $back = 'administrator/index.php?option=com_ra_members&view=reports&scope=' . $this->scope;
        $criteria = $this->buildCriterion(' ', 'home_group');
        $this->toolsHelper->showMonthMatrix($field, $table, $criteria, $title, $link, $back);
    }

    public function analyseJoinedGroup() {
        echo $this->breadcrumbs;
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $field = 'groupJoinedDate';
        $table = ' #__ra_profiles';
        $title = 'Members joined Group, by month';
        $link = 'administrator/index.php?option=com_ra_members&task=reports.showMembersJoinedGroup&scope=' . $this->scope;
        $back = 'administrator/index.php?option=com_ra_members&view=reports&scope=' . $this->scope;
        $criteria = $this->buildCriterion(' ', 'home_group');
        $this->toolsHelper->showMonthMatrix($field, $table, $criteria, $title, $link, $back);
    }

    public function analyseJoinedRamblers() {
        echo $this->breadcrumbs;
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $field = 'ramblersJoinedDate';
        $table = ' #__ra_profiles';
        $title = 'Members joined Ramblers, by month';
        $link = 'administrator/index.php?option=com_ra_members&task=reports.showMembersJoinedRamblers&scope=' . $this->scope;
        $back = 'administrator/index.php?option=com_ra_members&view=reports&scope=' . $this->scope;
        $criteria = $this->buildCriterion(' ', 'home_group');
        $this->toolsHelper->showMonthMatrix($field, $table, $criteria, $title, $link, $back);
    }

    public function analyseLapsing() {
        echo $this->breadcrumbs; // . $this->breadcrumbsExtra('
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $field = 'membershipExpiryDate';
        $table = ' #__ra_profiles';
        $title = 'Members lapsing, by month';
        $link = 'administrator/index.php?option=com_ra_members&task=reports.showMembersLapsing&scope=' . $this->scope;
        $back = 'administrator/index.php?option=com_ra_members&view=reports&scope=' . $this->scope;
        $criteria = $this->buildCriterion(' ', 'home_group');
        $this->toolsHelper->showMonthMatrix($field, $table, $criteria, $title, $link, $back);
    }

    private function breadcrumbsExtra($label, $report) {
        // generates a link to be added to the standard breadcrumbs
        $target = 'administrator/index.php?option=com_ra_members&task=reports.' . $report;
        return '>' . $this->toolsHelper->buildLink($target, $label);
    }

    private function buildCriterion($operator, $field_name, $code = '') {
        // If scope is blank, no additional criterion is required
        if ($this->scope == '') {
            $this->subheading = 'All records';
            return '';
        }
        if ($code == '') {
            $code = $this->mailHelper->getDefaultGroup();
        }
        $sql = $operator . ' (' . $field_name;
        if ($this->scope == 'A') {
            $area_code = substr($code, 0, 2);
            $sql .= ' LIKE "' . $area_code . '%") ';
        } else {
            $sql .= '="' . $code . '") ';
        }
        $sql_lookup = 'SELECT id, name ';
        $sql_lookup .= 'FROM #__ra_organisations ';
        $sql_lookup .= 'WHERE code="' . $code . '"';
        $item = $this->toolsHelper->getItem($sql_lookup);
        if ($item === false) {
            $this->subheading = $code . ' Query failed: ' . htmlspecialchars((string) $this->toolsHelper->error);
        } else {
            $this->subheading = $code . ' ' . (!empty($item->name) ? htmlspecialchars($item->name) : 'N/A');
        }
        //       echo $sql . '<br>';
        return $sql;
    }

    public function changedGroup() {
        // Show members who joined a group after first joining Ramblers within the current scope.
        ToolBarHelper::title('Changed group');
        echo $this->breadcrumbs;
        echo '<h4>Scope ' . $this->subheading . '</h4>';

        $select = array(
            'p.membershipNumber',
            'p.preferred_name',
            'p.ramblersJoinedDate',
            'p.groupJoinedDate',
            'DATEDIFF(p.groupJoinedDate, p.ramblersJoinedDate) AS days_between',
        );
        $headers = array(
            'Membership number',
        );

        if ($this->scope == 'A') {
            $select[] = 'p.home_group AS group_code';
            $headers[] = 'Group code';
        }

        $headers[] = 'Preferred name';
        $headers[] = 'Date joined Ramblers';
        $headers[] = 'Date joined Group';
        $headers[] = 'Days between';

        $sql = 'SELECT ' . implode(', ', $select) . ' ';
        $sql .= 'FROM #__ra_profiles AS p ';
        $sql .= 'WHERE p.ramblersJoinedDate IS NOT NULL ';
        $sql .= 'AND p.groupJoinedDate IS NOT NULL ';
        $sql .= 'AND p.ramblersJoinedDate < p.groupJoinedDate ';
        $sql .= $this->buildCriterion('AND', 'p.home_group');
        $sql .= ' ORDER BY p.groupJoinedDate, p.preferred_name';

        $rows = $this->toolsHelper->getRows($sql);
        $table = new ToolsTable();
        $table->add_header(implode(',', $headers));

        if ($rows !== false) {
            foreach ($rows as $row) {
                $table->add_item($row->membershipNumber);

                if ($this->scope == 'A') {
                    $table->add_item($row->group_code);
                }

                $table->add_item($row->preferred_name);
                $table->add_item(HTMLHelper::_('date', $row->ramblersJoinedDate, 'd M y'));
                $table->add_item(HTMLHelper::_('date', $row->groupJoinedDate, 'd M y'));
                $table->add_item((int) $row->days_between);
                $table->generate_line();
            }
        }

        $table->generate_table();

        $count = is_array($rows) ? count($rows) : 0;
        echo $count . ' records found<br>';
        echo $this->toolsHelper->backButton($this->back . '&scope=' . $this->scope);
    }

    public function jointMembers() {
        ToolBarHelper::title('Joint Members');
        echo $this->breadcrumbs;
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $sql = 'SELECT a.home_group, a.lastName, a.firstName, a.membershipNumber, a.jointWith, ';
        $sql .= 'j.lastName AS jointLast, j.firstName AS jointFirst ';
        $sql .= 'FROM #__ra_profiles AS a ';
        $sql .= 'LEFT JOIN #__ra_profiles AS j ON j.membershipNumber = a.jointWith ';
        $sql .= 'WHERE a.jointWith IS NOT NULL ';
        $sql .= 'ORDER BY a.home_group, a.lastName, a.firstName, a.membershipNumber';
        $table = new ToolsTable();
        if ($this->scope == 'G') {
            $heading = '';
        } else {
            $heading = 'Group,';
        }
        $heading .= 'Surname,Forename,Mem No,Joint with,Joint Surname,Joint Forename,Joint Mem No';
        $table->add_header($heading);
        echo $sql . '<br>';
        $rows = $this->toolsHelper->getRows($sql);
        foreach ($rows as $row) {
            if ($this->scope !== 'G') {
                $table->add_item($row->home_group);
            }
            $table->add_item($row->lastName);
            $table->add_item($row->firstName);
            $table->add_item($row->membershipNumber);
            $table->add_item($row->jointWith);
            $table->add_item($row->jointLast);
            $table->add_item($row->jointFirst);
            $table->add_item($row->jointWith);
            $table->generate_line();
        }
        $table->generate_table();
        echo $this->toolsHelper->backButton($this->back);
    }

    public function lapsedMembers() {
        // Show members whose expiry date has passed or is today within the current scope,
        // including a count of matching role rows for each member.
        ToolBarHelper::title('Lapsed members');
        echo $this->breadcrumbs;
        echo '<h4>Scope ' . $this->subheading . '</h4>';

        $select = array(
            'p.membershipExpiryDate',
            'p.membershipNumber',
            'p.preferred_name',
            'COUNT(r.id) AS role_count',
            'p.groupJoinedDate',
            'DATEDIFF(CURDATE(), p.membershipExpiryDate) AS days_lapsed',
        );
        $headers = array(
            'Membership expiry date',
            'Membership number',
        );

        if ($this->scope !== 'G') {
            $select[] = 'p.home_group AS group_code';
            $headers[] = 'Group code';
        }

        $headers[] = 'Preferred name';
        $headers[] = 'Roles';
        $headers[] = 'Group joined';
        $headers[] = 'Days since lapse';

        $sql = 'SELECT ' . implode(', ', $select) . ' ';
        $sql .= 'FROM #__ra_profiles AS p ';
        $sql .= 'LEFT JOIN #__ra_roles AS r ON r.member_id = p.member_id ';
        $sql .= 'WHERE p.membershipExpiryDate IS NOT NULL ';
        $sql .= 'AND p.membershipExpiryDate <= CURDATE() ';
        $sql .= $this->buildCriterion('AND', 'p.home_group');
        $sql .= 'GROUP BY p.member_id, p.membershipExpiryDate, p.membershipNumber, p.preferred_name, p.groupJoinedDate';

        if ($this->scope !== 'G') {
            $sql .= ', p.home_group';
        }

        $sql .= ' ORDER BY p.membershipExpiryDate, p.preferred_name';

        $rows = $this->toolsHelper->getRows($sql);
        $table = new ToolsTable();
        $table->add_header(implode(',', $headers));

        if ($rows !== false) {
            foreach ($rows as $row) {
                $table->add_item(HTMLHelper::_('date', $row->membershipExpiryDate, 'd M y'));
                $table->add_item($row->membershipNumber);

                if ($this->scope !== 'G') {
                    $table->add_item($row->group_code);
                }

                $table->add_item($row->preferred_name);
                $table->add_item((int) $row->role_count);
                $table->add_item($row->groupJoinedDate ? HTMLHelper::_('date', $row->groupJoinedDate, 'd M y') : '');
                $table->add_item((int) $row->days_lapsed);
                $table->generate_line();
            }
        }

        $table->generate_table();

        $count = is_array($rows) ? count($rows) : 0;
        echo $count . ' records found<br>';
        echo $this->toolsHelper->backButton($this->back . '&scope=' . $this->scope);
    }

    public function membersByGroup() {
        ToolBarHelper::title('Members by Group');
        echo $this->breadcrumbs;
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $table = new ToolsTable();
        $headers = 'Group,Total members,With email,%';
        $table->add_header($headers);
        $sql = 'SELECT home_group, COUNT(*) as cnt FROM #__ra_profiles ';
        $sql .= $this->buildCriterion('WHERE', 'home_group');
        $sql .= ' GROUP BY home_group ';
        $sql .= 'ORDER BY home_group';
        $areas = $this->toolsHelper->getRows($sql);
        foreach ($areas as $area) {
            $table->add_item($area->home_group);
            $table->add_item($area->cnt);
            $tot = $area->cnt;
            $sql = 'SELECT COUNT(*) as cnt FROM #__ra_profiles AS p ';
            $sql .= 'INNER JOIN #__users AS u ON u.id = p.id ';
            $sql .= 'WHERE u.email IS NOT NULL ';
            $sql .= 'AND p.home_group="' . $area->home_group . '"';

            $with = $this->toolsHelper->getValue($sql);
            $table->add_item($with);
            $percent = $with * 100 / $tot;
            $table->add_item(round($percent));
            $table->generate_line();
        }
        $table->generate_table();
        echo $this->toolsHelper->backButton($this->back);
    }

    public function memberStatistics() {
        ToolBarHelper::title('Membership statistics');
        echo $this->breadcrumbs;
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $table = new ToolsTable();
        $headers = 'Details,1,2,?';
        $table->add_header($headers);

        $sql = 'SELECT COUNT(*) as cnt FROM #__ra_profiles ';
        $sql .= $this->buildCriterion('WHERE', 'home_group');
        $tot = $this->toolsHelper->getValue($sql);

        echo 'Total members: ' . $tot . '<br>';
        // Find out how many have an email address
        $table->add_item('With email/ Without email');
        $sql = 'SELECT COUNT(p.id) as cnt FROM #__ra_profiles AS p ';
        $sql .= 'INNER JOIN #__users AS u ON u.id = p.id ';
        $sql .= $this->buildCriterion('WHERE', 'home_group');
        $one = $this->toolsHelper->getValue($sql);
        $table->add_item($one);

        $sql = 'SELECT COUNT(p.id) as cnt FROM #__users AS u  ';
        $sql .= 'LEFT JOIN #__ra_profiles AS p ON p.id = u.id ';
        $sql .= $this->buildCriterion('WHERE', 'p.home_group');
        $sql .= ' AND u.email IS NULL ';
//        echo $sql . '<br>';
        $two = $this->toolsHelper->getValue($sql);
        $table->add_item($two);
        $balance = $tot - $one - $two;
        $table->add_item($balance);
        $table->generate_line();

        // Find out which types of member we have, and how many of each
        $sql = 'SELECT COUNT(*) as cnt FROM #__ra_profiles ';
        $sql .= $this->buildCriterion('WHERE', 'home_group');
        if ($this->scope == '') {
            $operator = ' WHERE ';
        } else {
            $operator = ' AND ';
        }

        $table->add_item('Member / Affiliate');
        $criterion = $operator . 'memberType="';
        $one = $this->toolsHelper->getValue($sql . $criterion . 'Member' . '"');
        $two = $this->toolsHelper->getValue($sql . $criterion . 'Affiliate' . '"');
        $table->add_item($one);
        $table->add_item($two);
        $balance = $tot - $one - $two;
        $table->add_item($balance);
        $table->generate_line();

        $table->add_item('Active / Pending');
        $criterion = $operator . 'memberStatus="';
        $one = $this->toolsHelper->getValue($sql . $criterion . 'Active' . '"');

        $two = $this->toolsHelper->getValue($sql . $criterion . 'payment pending' . '"');
        $table->add_item($one);
        $table->add_item($two);
        $balance = $tot - $one - $two;
        $table->add_item($balance);
        $table->generate_line();

        $table->add_item('Annual / Life');
        $criterion = $operator . 'memberTerm="';
        $one = $this->toolsHelper->getValue($sql . $criterion . 'Annual' . '"');
        $two = $this->toolsHelper->getValue($sql . $criterion . 'Life' . '"');
        $table->add_item($one);
        $table->add_item($two);
        $balance = $tot - $one - $two;
        $table->add_item($balance);
        $table->generate_line();

        $table->add_item('Individual / Joint');
        $criterion = $operator . 'membershipArrangement="';
        $one = $this->toolsHelper->getValue($sql . $criterion . 'Individual' . '"');
        $two = $this->toolsHelper->getValue($sql . $criterion . 'Joint' . '"');
        $table->add_item($one);
        $table->add_item($two);
        $balance = $tot - $one - $two;
        $table->add_item($balance);
        $table->generate_line();

        $table->add_item('Volunteer Yes/ Volunteer No');
        $criterion = $operator . 'volunteer="';
        $one = $this->toolsHelper->getValue($sql . $criterion . 'Y' . '"');
        $two = $this->toolsHelper->getValue($sql . $criterion . 'N' . '"');
        $table->add_item($one);
        $table->add_item($two);
        $balance = $tot - $one - $two;
        $table->add_item($balance);
        $table->generate_line();

        $table->generate_table();
        $criterion = $operator . 'emailMarketingConsent="YES"';
        $one = $this->toolsHelper->getValue($sql . $criterion);
        echo 'Email Marketing Consent ' . $one . '<br>';

        $criterion = $operator . 'postDirectMarketing="YES"';
        $one = $this->toolsHelper->getValue($sql . $criterion);
        echo 'Post Direct Marketing Consent ' . $one . '<br>';

        $criterion = $operator . 'telephoneDirectMarketing="YES"';
        $one = $this->toolsHelper->getValue($sql . $criterion);
        echo 'Telephone Direct Marketing Consent ' . $one . '<br>';

        $criterion = $operator . 'groupMarketingConsent="YES"';
        $one = $this->toolsHelper->getValue($sql . $criterion);
        echo 'Group Marketing Consent ' . $one . '<br>';

        $criterion = $operator . 'areaMarketingConsent="YES"';
        $one = $this->toolsHelper->getValue($sql . $criterion);
        echo 'Area Marketing Consent ' . $one . '<br>';

        $criterion = $operator . 'walkProgrammeOptOut="YES"';
        $one = $this->toolsHelper->getValue($sql . $criterion);
        echo 'Walk Programme Opt-Out ' . $one . '<br>';

        echo $this->toolsHelper->backButton($this->back);
    }

    public function showDashboard() {
        $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');
    }

    public function showMembersJoinedArea() {
        echo $this->breadcrumbs . $this->breadcrumbsExtra('Members joined Area, by month', 'analyseJoinedArea');
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $year = $this->app->input->getInt('year', '2025');
        $month = $this->app->input->getInt('month', '5');
        ToolBarHelper::title('Members joined Area for ' . $month . '/' . $year);
        $sql = 'SELECT home_group, membershipNumber, preferred_name, areaJoinedDate, membershipExpiryDate, ';
        $sql .= 'memberType, memberTerm, volunteer ';
        $sql .= 'FROM #__ra_profiles ';
        $sql .= 'WHERE YEAR(areaJoinedDate)="' . $year . '" AND MONTH(areaJoinedDate)="' . $month . '" ';
        $sql .= $this->buildCriterion('AND', 'home_group');
        $sql .= 'ORDER BY home_group, preferred_name';
        $rows = $this->toolsHelper->getRows($sql);
        $table = new ToolsTable;
        $table->add_header('Group,Membership Number,Preferred name,Join Date,Membership Expiry Date,Type,Term,Volunteer');
        foreach ($rows as $row) {
            $table->add_item($row->home_group);
            $table->add_item($row->membershipNumber);
            $table->add_item($row->preferred_name);
            $table->add_item(HTMLHelper::_('date', $row->areaJoinedDate, 'd M y'));
            $table->add_item(HTMLHelper::_('date', $row->membershipExpiryDate, 'd M y'));
            $table->add_item($row->memberType);
            $table->add_item($row->memberTerm);
            $table->add_item($row->volunteer);
            $table->generate_line();
        }
        $table->generate_table();
        echo count($rows) . ' Members joined Ramblers this month<br>';
        $back = 'administrator/index.php?option=com_ra_members&task=reports.analyseJoinedArea&scope=' . $this->scope;
        echo $this->toolsHelper->backButton($back);
    }

    public function showMembersJoinedGroup() {
        echo $this->breadcrumbs . $this->breadcrumbsExtra('Members joined Group, by month', 'analyseJoinedGroup');
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $year = $this->app->input->getInt('year', '2025');
        $month = $this->app->input->getInt('month', '5');
        ToolBarHelper::title('Members joined Group for ' . $month . '/' . $year);
        $sql = 'SELECT home_group, membershipNumber, preferred_name, groupJoinedDate, membershipExpiryDate, ';
        $sql .= 'memberType, memberTerm, volunteer ';
        $sql .= 'FROM #__ra_profiles ';
        $sql .= 'WHERE YEAR(groupJoinedDate)="' . $year . '" AND MONTH(groupJoinedDate)="' . $month . '" ';
        $sql .= $this->buildCriterion('AND', 'home_group');
        $sql .= 'ORDER BY home_group, preferred_name';
        $rows = $this->toolsHelper->getRows($sql);
        $table = new ToolsTable;
        $table->add_header('Group,Membership Number,Preferred name,Join Date,Membership Expiry Date,Type,Term,Volunteer');
        foreach ($rows as $row) {
            $table->add_item($row->home_group);
            $table->add_item($row->membershipNumber);
            $table->add_item($row->preferred_name);
            $table->add_item(HTMLHelper::_('date', $row->groupJoinedDate, 'd M y'));
            $table->add_item(HTMLHelper::_('date', $row->membershipExpiryDate, 'd M y'));
            $table->add_item($row->memberType);
            $table->add_item($row->memberTerm);
            $table->add_item($row->volunteer);
            $table->generate_line();
        }
        $table->generate_table();
        echo count($rows) . ' Members joined Ramblers this month<br>';
        $back = 'administrator/index.php?option=com_ra_members&task=reports.analyseJoinedRamblers&scope=' . $this->scope;
        echo $this->toolsHelper->backButton($back);
    }

    public function showMembersJoinedRamblers() {
        echo $this->breadcrumbs . $this->breadcrumbsExtra('Members joined Ramblers, by month', 'analyseJoinedRamblers');
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $year = $this->app->input->getInt('year', '2025');
        $month = $this->app->input->getInt('month', '5');
        ToolBarHelper::title('Members joined Ramblers for ' . $month . '/' . $year);
        $sql = 'SELECT home_group, membershipNumber, preferred_name, ramblersJoinedDate, membershipExpiryDate, ';
        $sql .= 'memberType, memberTerm, volunteer ';
        $sql .= 'FROM #__ra_profiles ';
        $sql .= 'WHERE YEAR(ramblersJoinedDate)="' . $year . '" AND MONTH(ramblersJoinedDate)="' . $month . '" ';
        $sql .= $this->buildCriterion('AND', 'home_group');
        $sql .= 'ORDER BY home_group, preferred_name';
        $rows = $this->toolsHelper->getRows($sql);
        $table = new ToolsTable;
        $table->add_header('Group,Membership Number,Preferred name,Join Date,Membership Expiry Date,Type,Term,Volunteer');
        foreach ($rows as $row) {
            $table->add_item($row->home_group);
            $table->add_item($row->membershipNumber);
            $table->add_item($row->preferred_name);
            $table->add_item(HTMLHelper::_('date', $row->ramblersJoinedDate, 'd M y'));
            $table->add_item(HTMLHelper::_('date', $row->membershipExpiryDate, 'd M y'));
            $table->add_item($row->memberType);
            $table->add_item($row->memberTerm);
            $table->add_item($row->volunteer);
            $table->generate_line();
        }
        $table->generate_table();
        echo count($rows) . ' Members joined Ramblers this month<br>';
        $back = 'administrator/index.php?option=com_ra_members&task=reports.analyseJoinedRamblers&scope=' . $this->scope;
        echo $this->toolsHelper->backButton($back);
    }

    public function showMembersLapsing() {
        echo $this->breadcrumbs . $this->breadcrumbsExtra('Members lapsing, by month', 'analyseLapsing');
        echo '<h4>Scope ' . $this->subheading . '</h4>';
        $year = $this->app->input->getInt('year', '2025');
        $month = $this->app->input->getInt('month', '5');
        ToolBarHelper::title('Members lapsing, for ' . $month . '/' . $year);
        $sql = 'SELECT home_group, membershipNumber, preferred_name, ramblersJoinedDate, membershipExpiryDate, ';
        $sql .= 'memberType, memberTerm, volunteer ';
        $sql .= 'FROM #__ra_profiles ';
        $sql .= 'WHERE YEAR(membershipExpiryDate)="' . $year . '" AND MONTH(membershipExpiryDate)="' . $month . '" ';
        $sql .= $this->buildCriterion('AND', 'home_group');
        $sql .= 'ORDER BY home_group, preferred_name';
//       echo $sql . '<br>';
//       die;
        $rows = $this->toolsHelper->getRows($sql);
        if ($rows == false) {
//             echo $sql . '<br>';
            echo 'No lapsing members found for this month';
            echo $this->toolsHelper->backButton($this->back);
            return;
        }
        $table = new ToolsTable;
        $table->add_header('Group,Membership Number,Preferred name,Join Date,Membership Expiry Date,Type,Term,Volunteer');
        foreach ($rows as $row) {
            $table->add_item($row->home_group);
            $table->add_item($row->membershipNumber);
            $table->add_item($row->preferred_name);
            $table->add_item(HTMLHelper::_('date', $row->ramblersJoinedDate, 'd M y'));
            $table->add_item(HTMLHelper::_('date', $row->membershipExpiryDate, 'd M y'));
            $table->add_item($row->memberType);
            $table->add_item($row->memberTerm);
            $table->add_item($row->volunteer);
            $table->generate_line();
        }
        $table->generate_table();
        echo count($rows) . ' Members lapsing this month<br>';
        $back = 'administrator/index.php?option=com_ra_members&task=reports.analyseLapsing&scope=' . $this->scope;
        echo $this->toolsHelper->backButton($back);
    }

}
