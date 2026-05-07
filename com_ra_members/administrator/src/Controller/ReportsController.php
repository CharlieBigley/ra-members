<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
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
class ReportsController extends AdminController
{
	protected $back = 'administrator/index.php?option=com_ra_mailman&view=reports';
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
        $this->breadcrumbs .= '>' . $this->toolsHelper->buildLink($this->back, 'MailMan Reports');
        $this->scope = $this->app->input->getWord('scope', '');  
        if ($this->scope == ''){ 
            $this->subheading = 'All records';
        } else {
            $code = $this->mailHelper->getDefaultGroup();
            if ($this->scope == 'A'){ 
                $code = substr($code,0,2);
            }
            
            $sql = 'SELECT id, name ';
            $sql .= 'FROM #__ra_organisations ';
            $sql .= 'WHERE code="' . $code . '"';
            $item = $this->toolsHelper->getItem($sql);  
            $this->subheading =  $code . ' ' . (!empty($item->name) ? htmlspecialchars($item->name) : 'N/A');    
        }              
    }

    public function analyseEnrolment(){
        echo $this->breadcrumbs; 
        echo '<h4>Scope '  . $this->subheading . '</h4>';
        $field = 'ramblersJoinDate';
        $table = ' #__ra_profiles';
        $title = 'Members enrolled, by month';
        $link = 'administrator/index.php?option=com_ra_mailman&task=reports.showMembersEnrolled&scope=' . $this->scope;
        $back = 'administrator/index.php?option=com_ra_mailman&view=reports&scope=' . $this->scope;
        $criteria = $this->buildCriterion(' ','home_group');
        $this->toolsHelper->showMonthMatrix($field, $table, $criteria, $title, $link, $back);
    }

    public function analyseLapsing(){
        echo $this->breadcrumbs; // . $this->breadcrumbsExtra('
        echo '<h4>Scope '  . $this->subheading . '</h4>';
        $field = 'membershipExpiryDate';
        $table = ' #__ra_profiles';
        $title = 'Members lapsing, by month';
        $link = 'administrator/index.php?option=com_ra_mailman&task=reports.showMembersLapsing&scope=' . $this->scope;
        $back = 'administrator/index.php?option=com_ra_mailman&view=reports&scope=' . $this->scope;
        $criteria = $this->buildCriterion(' ','home_group');
        $this->toolsHelper->showMonthMatrix($field, $table, $criteria, $title, $link, $back);
    }   

	private function buildCriterion($operator,$field_name, $code = ''){
    // If scope is blank, no additional criterion is required
        if ($this->scope == ''){ 
            $this->subheading = 'All records';
            return '';
        }
        if ($code == ''){
            $code = $this->mailHelper->getDefaultGroup();
        }
        $sql = $operator . ' (' . $field_name;
        if ($this->scope == 'A'){ 
            $area_code = substr($code,0,2);
            $sql .=  ' LIKE "' . $area_code . '%") ';
        } else { 
            $sql .= '="' . $code . '") ';
        }
        $sql_lookup = 'SELECT id, name ';
        $sql_lookup .= 'FROM #__ra_organisations ';
        $sql_lookup .= 'WHERE code="' . $code . '"';
        $item = $this->toolsHelper->getItem($sql_lookup);  
        $this->subheading =  $code . ' ' . (!empty($item->name) ? htmlspecialchars($item->name) : 'N/A');        
 //       echo $sql . '<br>';
        return $sql;
    }

	public function membersByGroup(){
        ToolBarHelper::title('Members by Group');
        echo $this->breadcrumbs;
        echo '<h4>Scope '  . $this->subheading . '</h4>';     
        $table = new ToolsTable();
        $headers = 'Group,Total members,With email,%';
        $table->add_header($headers);
        $sql = 'SELECT home_group, COUNT(*) as cnt FROM #__ra_profiles ';
        $sql .= $this->buildCriterion('WHERE','home_group');
        $sql .= ' GROUP BY home_group ';
        $sql .= 'ORDER BY home_group';
        $areas = $this->toolsHelper->getRows($sql);
        foreach ($areas as $area){
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

    public function memberStatistics(){
        ToolBarHelper::title('Membership statistics');
        echo $this->breadcrumbs;
        echo '<h4>Scope '  . $this->subheading . '</h4>';     
        $table = new ToolsTable();
        $headers = 'Details,1,2,?';
        $table->add_header($headers);

        $sql = 'SELECT COUNT(*) as cnt FROM #__ra_profiles ';
        $sql .= $this->buildCriterion('WHERE','home_group');
        $tot = $this->toolsHelper->getValue($sql);
        
        echo 'Total members: ' . $tot . '<br>';
    // Find out how many have an email address
        $table->add_item('With email/ Without email');
        $sql = 'SELECT COUNT(p.id) as cnt FROM #__ra_profiles AS p ';
        $sql .= 'INNER JOIN #__users AS u ON u.id = p.id ';
        $sql .= $this->buildCriterion('WHERE','home_group');
        $one = $this->toolsHelper->getValue($sql); 
        $table->add_item($one);   

        $sql = 'SELECT COUNT(p.id) as cnt FROM #__ra_profiles AS p ';
        $sql .= 'LEFT JOIN #__users AS u ON u.id = p.id ';
        $sql .= $this->buildCriterion('WHERE','p.home_group');
        $sql .= ' AND u.email IS NULL ';
        
        $two = $this->toolsHelper->getValue($sql);
        $table->add_item($two);   
        $balance = $tot - $one - $two;
        $table->add_item($balance);
        $table->generate_line();

    // Find out which types of member we have, and how many of each
        $sql = 'SELECT COUNT(*) as cnt FROM #__ra_profiles ';
		$sql .= $this->buildCriterion('WHERE','home_group');
		if ($this->scope == ''){ 
			$operator = ' WHERE ';
		} else {
			$operator = ' AND ';	
		}

        $table->add_item('Member / Affiliate');
    	$criterion = $operator . 'memberType="';
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
        $one = $this->toolsHelper->getValue($sql . $criterion . 'annual' . '"');
        $two = $this->toolsHelper->getValue($sql . $criterion . 'Life' . '"');
        $table->add_item($one);
        $table->add_item($two);
        $balance = $tot - $one - $two;
        $table->add_item($balance);
        $table->generate_line();


        $table->add_item('Individual / Joint');
        $criterion = $operator . 'membershipType="';
        $one = $this->toolsHelper->getValue($sql . $criterion . 'Individual' . '"');  
        $two = $this->toolsHelper->getValue($sql . $criterion . 'Joint' . '"');
        $table->add_item($one);
        $table->add_item($two);
        $balance = $tot - $one - $two;
        $table->add_item($balance);
        $table->generate_line();

        $table->add_item('Volunteer Yes/ Volunteer No');
        $criterion = $operator . 'volunteer="';
        $one = $this->toolsHelper->getValue($sql . $criterion . 'Yes' . '"');
        $two = $this->toolsHelper->getValue($sql . $criterion . 'No' . '"');
        $table->add_item($one);
        $table->add_item($two);
        $balance = $tot - $one - $two;
        $table->add_item($balance);
        $table->generate_line();


        $table->generate_table();
        $criterion = $operator . 'emailMarketingConsent="YES"';    
        $one = $this->toolsHelper->getValue($sql . $criterion );
        echo 'Email Marketing Consent ' . $one . '<br>';

        $criterion = $operator . 'postDirectMarketing="YES"';
        $one = $this->toolsHelper->getValue($sql . $criterion );
        echo 'Post Direct Marketing Consent ' . $one . '<br>';

        $criterion = $operator . 'telephoneDirectMarketing="YES"';
        $one = $this->toolsHelper->getValue($sql . $criterion );
        echo 'Telephone Direct Marketing Consent ' . $one . '<br>';

        $criterion = $operator . 'groupMarketingConsent="YES"';    
        $one = $this->toolsHelper->getValue($sql . $criterion );
        echo 'Group Marketing Consent ' . $one . '<br>';

        $criterion = $operator . 'areaMarketingConsent="YES"';    
        $one = $this->toolsHelper->getValue($sql . $criterion );
        echo 'Area Marketing Consent ' . $one . '<br>';    

        $criterion = $operator . 'walkProgrammeOptOut="YES"';    
        $one = $this->toolsHelper->getValue($sql . $criterion );
        echo 'Walk Programme Opt-Out ' . $one . '<br>';       

        echo $this->toolsHelper->backButton($this->back);
    }

    public function showDashboard(){
         $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');
    }
    
     public function showMembersEnrolled() {
        echo $this->breadcrumbs . $this->breadcrumbsExtra('Members enrolled, by month', 'analyseEnrolment');
        echo '<h4>Scope '  . $this->subheading . '</h4>';
        $year = $this->app->input->getInt('year', '2025');
        $month = $this->app->input->getInt('month', '5');
        ToolBarHelper::title('Enrollments for ' . $month . '/' . $year);
           $sql = 'SELECT home_group, membershipNumber, preferred_name, ramblersJoinDate, membershipExpiryDate, ';
        $sql .= 'memberType, memberTerm, volunteer ';
        $sql .= 'FROM #__ra_profiles ';
        $sql .= 'WHERE YEAR(ramblersJoinDate)="' . $year . '" AND MONTH(ramblersJoinDate)="' . $month . '" ';   
        $sql .=$this->buildCriterion('AND','home_group');
        $sql .= 'ORDER BY home_group, preferred_name';
        $rows = $this->toolsHelper->getRows($sql);
        $table = new ToolsTable;
        $table->add_header('Group,Membership Number,Preferred name,Join Date,Membership Expiry Date,Type,Term,Volunteer');
        foreach ($rows as $row) {
            $table->add_item($row->home_group);
            $table->add_item($row->membershipNumber);
            $table->add_item($row->preferred_name);
            $table->add_item(HTMLHelper::_('date', $row->ramblersJoinDate, 'd M y'));
            $table->add_item(HTMLHelper::_('date', $row->membershipExpiryDate, 'd M y'));    
            $table->add_item($row->memberType);
            $table->add_item($row->memberTerm);
            $table->add_item($row->volunteer);
            $table->generate_line();
        }
        $table->generate_table();    
        echo count($rows) . ' enrollments for this month<br>';
        $back = 'administrator/index.php?option=com_ra_mailman&task=reports.analyseEnrolment&scope=' . $this->scope;
        echo $this->toolsHelper->backButton($back);
    }   

    public function showMembersLapsing(){
        echo $this->breadcrumbs . $this->breadcrumbsExtra('Members lapsing, by month', 'analyseLapsing');
        echo '<h4>Scope '  . $this->subheading . '</h4>';
        $year = $this->app->input->getInt('year', '2025');
        $month = $this->app->input->getInt('month', '5');
        ToolBarHelper::title('Members lapsing, for ' . $month . '/' . $year);
        $sql = 'SELECT home_group, membershipNumber, preferred_name, ramblersJoinDate, membershipExpiryDate, ';
        $sql .= 'memberType, memberTerm, volunteer ';
        $sql .= 'FROM #__ra_profiles ';
        $sql .= 'WHERE YEAR(membershipExpiryDate)="' . $year . '" AND MONTH(membershipExpiryDate)="' . $month . '" ';   
        $sql .=$this->buildCriterion('AND','home_group');
        $sql .= 'ORDER BY home_group, preferred_name';
//        echo $sql . '<br>';
        $rows = $this->toolsHelper->getRows($sql);
        $table = new ToolsTable;
        $table->add_header('Group,Membership Number,Preferred name,Join Date,Membership Expiry Date,Type,Term,Volunteer');
        foreach ($rows as $row) {
            $table->add_item($row->home_group);
            $table->add_item($row->membershipNumber);
            $table->add_item($row->preferred_name);
            $table->add_item(HTMLHelper::_('date', $row->ramblersJoinDate, 'd M y'));
            $table->add_item(HTMLHelper::_('date', $row->membershipExpiryDate, 'd M y'));
            $table->add_item($row->memberType);
            $table->add_item($row->memberTerm);
            $table->add_item($row->volunteer);
            $table->generate_line();
        }
        $table->generate_table();    
        echo count($rows) . ' Members lapsing this month<br>';
        $back = 'administrator/index.php?option=com_ra_mailman&task=reports.analyseLapsing&scope=' . $this->scope;
        echo $this->toolsHelper->backButton($back);
    }
 
}
