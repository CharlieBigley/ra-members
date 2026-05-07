<?php
/**
 * @version    CVS: 1.0.3
 * @package    Com_Ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_members\Administrator\View\Roles;
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Ramblers\Component\Ra_members\Administrator\Helper\Ra_membersHelper;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
/**
 * View class for a list of Roles.
 *
 * @since  1.0.3
 */
class HtmlView extends BaseHtmlView
{
	protected $items;

	protected $pagination;

	protected $state;
	protected $toolsHelper;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->toolsHelper = new ToolsHelper();
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
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
	 * @since   1.0.3
	 */
	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = Ra_membersHelper::getActions();

		ToolbarHelper::title(Text::_('Roles'), "generic");

		$toolbar = Toolbar::getInstance('toolbar');


            $toolbar->standardButton('nrecords')
                    ->icon('fa fa-info-circle')
                    ->text(number_format($this->pagination->total) . ' Records')
                    ->task('')
                    ->onclick('return false')
                    ->listCheck(false);
       
        ToolbarHelper::cancel('roles.cancel', 'Return to Dashboard');
//        $help_url = 'https://docs.stokeandnewcastleramblers.org.uk/mail-manager.html?view=article&id=420:mm-02-2-mailing-lists&catid=34';
//        ToolbarHelper::help('', false, $help_url);

		// Set sidebar action
		Sidebar::setAction('index.php?option=com_ra_members&view=roles');
	}
	
	/**
	 * Method to order fields 
	 *
	 * @return void 
	 */
	protected function getSortFields()
	{
		return array(
			'a.`id`' => Text::_('JGRID_HEADING_ID'),
			'a.`preferred_name`' => Text::_('COM_RA_MEMBERS_ROLES_PREFERRED_NAME'),
			'a.`role`' => Text::_('COM_RA_MEMBERS_ROLES_ROLE'),
			'a.`membership_number`' => Text::_('COM_RA_MEMBERS_ROLES_MEMBERSHIP_NUMBER'),
			'a.`home_group`' => Text::_('COM_RA_MEMBERS_ROLES_HOME_GROUP'),
		);
	}

	/**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 *
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
