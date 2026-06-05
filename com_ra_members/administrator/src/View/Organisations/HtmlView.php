<?php

/**
 * @version    1.0.0
 * @package    com_ra_members
 */

namespace Ramblers\Component\Ra_members\Administrator\View\Organisations;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();
        Factory::getApplication()->setUserState('com_ra_members.reports.callback', '');

        return parent::display($tpl);
    }

    protected function addToolbar()
    {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $toolbar = Toolbar::getInstance('toolbar');
        ToolbarHelper::title('Ramblers Organisations');
        $this->canDo = ContentHelper::getActions('com_ra_members');

        if ($this->canDo->get('core.create')) {
            $toolbar->addNew('organisations.add');
        }

        if ($this->canDo->get('core.edit.state')) {
            $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);
        }

        $toolbar->standardButton('nrecords')
            ->icon('fa fa-info-circle')
            ->text(number_format($this->pagination->total) . ' Records')
            ->task('')
            ->onclick('return false')
            ->listCheck(false);

        ToolbarHelper::cancel('organisations.cancel', 'Return to Dashboard');
    }
}