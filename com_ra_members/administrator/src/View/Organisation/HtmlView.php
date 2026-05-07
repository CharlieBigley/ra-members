<?php

/**
 * @version    1.0.0
 * @package    com_ra_members
 */

namespace Ramblers\Component\Ra_members\Administrator\View\Organisation;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class HtmlView extends BaseHtmlView
{
    protected $app;
    protected $canDo;
    protected $state;
    protected $item;
    protected $form;
    protected $toolsHelper;

    public function display($tpl = null)
    {
        $this->app = Factory::getApplication();
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');
        $this->canDo = ContentHelper::getActions('com_ra_members');

        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->toolsHelper = new ToolsHelper();
        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        $this->app->input->set('hidemainmenu', true);

        $user = $this->app->getIdentity();
        $checkedOut = isset($this->item->checked_out) ? !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id')) : false;

        if (strlen((string) $this->item->code) == 2) {
            $header = 'Area ' . $this->toolsHelper->lookupArea($this->item->code);
        } else {
            $header = 'Group ' . $this->toolsHelper->lookupGroup($this->item->code);
        }

        $header .= ' (' . $this->item->code . ')';
        ToolbarHelper::title(Text::_($header), 'generic');

        if (!$checkedOut && ($this->canDo->get('core.edit') || $this->canDo->get('core.create'))) {
            ToolbarHelper::apply('organisation.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('organisation.save', 'JTOOLBAR_SAVE');
        }
        // We can be invoked from the dashboard, in which case we want to return there when cancelling
        $callback = $this->app->getInput()->get('callback', '', 'cmd');
        if($callback == 'dashboard') {
            $target = 'reports.showDashboard';
        } else {
            $target = 'organisation.cancel';
        }
        ToolbarHelper::cancel($target, empty($this->item->id) ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}