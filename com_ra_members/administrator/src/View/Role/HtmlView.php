<?php

/**
 * @version    1.1.7
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_members\Administrator\View\Role;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use Ramblers\Component\Ra_tools\Site\Helpers\TableHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a single Role.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView {

    protected $state;
    protected $item;
    protected $form;

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
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }
        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addToolbar() {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $user = Factory::getApplication()->getIdentity();
        $isNew = ($this->item->id == 0);

        if (isset($this->item->checked_out)) {
            $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        } else {
            $checkedOut = false;
        }

        $canDo = ToolsHelper::getActions('com_ra_delivery');

        ToolbarHelper::title(Text::_('Add Role'), "generic");

        // If not checked out, can save the item.
        if (!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create')))) {
            //           ToolbarHelper::apply('role.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('role.save', 'JTOOLBAR_SAVE');
        }

        if (empty($this->item->id)) {
            ToolbarHelper::cancel('role.cancel', 'JTOOLBAR_CANCEL');
        } else {
            ToolbarHelper::cancel('role.cancel', 'JTOOLBAR_CLOSE');
        }
    }

}
