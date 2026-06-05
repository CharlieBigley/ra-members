<?php

/**
 * @version    1.0.0
 * @package    com_ra_members
 */

namespace Ramblers\Component\Ra_members\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Event\Model;
use Joomla\CMS\Language\Text;

class OrganisationModel extends AdminModel
{
    protected $text_prefix = 'COM_RA_MEMBERS';

    public $typeAlias = 'com_ra_members.organisation';

    protected $item = null;

    public function getTable($type = 'Organisation', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            'com_ra_members.organisation',
            'organisation',
            [
                'control' => 'jform',
                'load_data' => $loadData,
            ]
        );

        return empty($form) ? false : $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_ra_members.edit.organisation.data', []);

        if (empty($data)) {
            if ($this->item === null) {
                $this->item = $this->getItem();
            }

            $data = $this->item;
        }

        return $data;
    }

    public function getItem($pk = null)
    {
        if ($item = parent::getItem($pk)) {
            if (isset($item->params)) {
                $item->params = json_encode($item->params);
            }

            if (!empty($item->logo) && strpos($item->logo, '/') === false) {
                $item->logo = 'images/com_ra_mailman/' . $item->logo;
            }
        }

        return $item;
    }

    protected function prepareTable($table)
    {
        if (empty($table->id) && @$table->ordering === '') {
            $db = $this->getDbo();
            $db->setQuery('SELECT MAX(ordering) FROM #__ra_organisations');
            $max = $db->loadResult();
            $table->ordering = $max + 1;
        }
    }
}