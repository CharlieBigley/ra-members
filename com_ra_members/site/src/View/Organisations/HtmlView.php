<?php

/**
 * @version    1.0.0
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_members\Site\View\Organisations;

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * View for the organisations list (site application).
 */
class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    public $filterForm;
    public $activeFilters;
    public $params;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();

        $this->items        = $this->get('Items');
        $this->pagination   = $this->get('Pagination');
        $this->state        = $this->get('State');
        $this->filterForm   = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->params       = $app->getParams('com_ra_members');

        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->_prepareDocument();

        return parent::display($tpl);
    }

    protected function _prepareDocument()
    {
        $app   = Factory::getApplication();
        $menus = $app->getMenu();

        $menu = $menus->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', 'Organisations');
        }

        $title = $this->params->get('page_title', 'Organisations');

        if (empty($title)) {
            $title = $app->get('sitename');
        } elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $title = $app->get('sitename') . ' - ' . $title;
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = $title . ' - ' . $app->get('sitename');
        }

        $this->document->setTitle($title);
    }
}
