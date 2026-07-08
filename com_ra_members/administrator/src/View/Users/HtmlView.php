<?php

/**
 * @version    1.1.8
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2025 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 27/08/25 CB Help
 * 21/09/25 CB check user id not null in checkGroup
 * 05/07/26 CB copied from com_ra_tools
 */

namespace Ramblers\Component\Ra_members\Administrator\View\Users;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\CMS\User\CurrentUserInterface;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a list of Users.
 *
 * @since  2.0
 */
class HtmlView extends BaseHtmlView implements CurrentUserInterface {

    protected $items;
    protected $pagination;
    protected $state;
    protected $toolsHelper;
    protected $user;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return void
     *
     * @throws Exception
     */
    public function display($tpl = null) {
        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->user = $this->getCurrentUser();

// Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->addToolbar();

        $this->sidebar = Sidebar::render();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   2.0
     */
    protected function addToolbar() {
        // Suppress menu side panel
        Factory::getApplication()->input->set('hidemainmenu', true);
        // Set sidebar action
        Sidebar::setAction('index.php?option=com_ra_members&view=users');
        $this->toolsHelper = new ToolsHelper;
        $state = $this->get('State');
        $canDo = ToolsHelper::getActions();

        ToolbarHelper::title(Text::_('List Users'), "generic");

        $toolbar = Toolbar::getInstance('toolbar');

// Check if the form exists before showing the add/edit buttons
        $formPath = JPATH_COMPONENT_ADMINISTRATOR . '/src/View/Users';

//        if (file_exists($formPath)) {
//            if ($canDo->get('core.create')) {
//                $toolbar->addNew('user.add');
//            }
//        }
// code from https://docs.joomla.org/J4.x:Joomla_4_Tips_and_Tricks:_Number_of_Records
        $toolbar->standardButton('nrecords')
                ->icon('fa fa-info-circle')
                ->text(number_format($this->pagination->total) . ' Records')
                ->task('')
                ->onclick('return false')
                ->listCheck(false);
        /*
          $toolbar->standardButton('print')
          ->icon('fa fa-info-circle')
          ->text('Print ' . number_format($this->pagination->total) . ' Records')
          ->task('area_list.print')
          ->buttonClass('btn btn-action')
          ->listCheck(false);
         */
        ToolbarHelper::cancel('users.cancel', 'Return to Dashboard');
        $help_url = 'https://docs.stokeandnewcastleramblers.org.uk/mail-manager.html?view=article&id=511:7&catid=33';
        ToolbarHelper::help('', false, $help_url);
    }

    /**
     * Method to order fields
     *
     * @return void
     */
    protected function getSortFields() {
        return array(
            'a.`id`' => Text::_('JGRID_HEADING_ID'),
            'a.`state`' => Text::_('JSTATUS'),
            'a.`name`' => Text::_('COM_RA_TOOLS_USERS_NAME'),
            'a.`email`' => Text::_('COM_RA_TOOLS_USERS_EMAIL'),
        );
    }

    /**
     * Check if state is set
     *
     * @param   mixed  $state  State
     *
     * @return bool
     */
    public function getState($state) {
        return isset($this->state->{$state}) ? $this->state->{$state} : false;
    }

    public function checkGroup($user_id, $mode = 1) {
        if (is_null($user_id)) {
            return '';
        }
// checks to see if the given user has access to one of the components

        if ($mode == 1) {
            $component = 'com_ra_tools';
        } elseif ($mode == 2) {
            $component = 'com_ra_events';
        } elseif ($mode == 3) {
            $component = 'com_ra_mailman';
        } elseif ($mode == 4) {
            $component = 'com_ra_members';
        }
        $sql = 'SELECT id FROM #__usergroups ';
        $sql .= 'WHERE title="' . $component . '"';
        $group_id = $this->toolsHelper->getValue($sql);

        if ($group_id) {
            $sql = 'SELECT group_id FROM #__user_usergroup_map ';
            $sql .= 'WHERE user_id=' . $user_id . ' AND ';
            $sql .= 'group_id=' . $group_id;
            //        echo $sql . '<br>';
            $test = $this->toolsHelper->getValue($sql);
        } else {
            Factory::getApplication()->enqueueMessage('User Group not found for ' . $component, 'warning');
            $test = NULL;
        }
        if (is_null($test)) {
            $icon = 'X';
        } else {
            $icon = 'F';
        }

        $target = 'administrator/index.php?option=com_ra_members&task=users.toggleAccess&user=' . $user_id;
        $target .= '&group=' . $group_id . '&mode=' . $icon;
        return $this->toolsHelper->imageButton($icon, $target);
    }

    public function countRoles($user_id) {
        $sql = 'SELECT COUNT(r.id) FROM #__ra_roles as r ';
        $sql .= 'INNER JOIN  #__ra_profiles as p ON p.member_id = r.member_id ';
        $sql .= 'INNER JOIN  #__users as u ON u.id = p.id ';
        $sql .= 'WHERE u.id=' . $user_id;
        //        echo $sql . '<br>';
        return $this->toolsHelper->getValue($sql);
    }

    public function countUsergroups($user_id) {
        $sql = 'SELECT COUNT(group_id) FROM #__user_usergroup_map ';
        $sql .= 'WHERE user_id=' . $user_id;
        //        echo $sql . '<br>';
        return $this->toolsHelper->getValue($sql);
    }

}
