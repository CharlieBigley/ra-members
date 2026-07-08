<?php

/**
 * @version    1.0.0
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_members\Administrator\View\Members;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\User\CurrentUserInterface;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a list of Members.
 *
 * @since  1.0.0
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
        $this->toolsHelper = new ToolsHelper;
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
     * @since   1.0.0
     */
    protected function addToolbar() {
        $state = $this->get('State');
        $canDo = ToolsHelper::getActions('com_ra_delivery');
// find current scope
        $mailHelper = new MailHelper;
        $code = $mailHelper->getDefaultGroup();
        if ($code == 'N') {
            $title = 'Members';
        } else {
            $title = 'Members for ' . $this->toolsHelper->lookupGroup($code);
        }
        ToolbarHelper::title($title, 'generic');
//        ToolbarHelper::title(Text::_('COM_RA_MEMBERS_TITLE_MEMBERS'), "generic");

        $toolbar = Toolbar::getInstance('toolbar');

        $toolbar->standardButton('nrecords')
                ->icon('fa fa-info-circle')
                ->text(number_format($this->pagination->total) . ' Records')
                ->task('')
                ->onclick('return false')
                ->listCheck(false);
        ToolbarHelper::cancel('members.cancel', 'Return to Dashboard');

        // Set sidebar action
        Sidebar::setAction('index.php?option=com_ra_members&view=members');
    }

    /**
     * Method to order fields
     *
     * @return void
     */
    protected function getSortFields() {
        return array(
            'a.`id`' => Text::_('JGRID_HEADING_ID'),
            'a.`preferred_name`' => Text::_('COM_RA_MEMBERS_MEMBERS_PREFERRED_NAME'),
            'a.`membershipexpirydate`' => Text::_('COM_RA_MEMBERS_MEMBERS_MEMBERSHIPEXPIRYDATE'),
            'a.`membership_number`' => Text::_('COM_RA_MEMBERS_MEMBERS_MEMBERSHIP_NUMBER'),
            'a.`home_group`' => Text::_('COM_RA_MEMBERS_MEMBERS_HOME_GROUP'),
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

}
