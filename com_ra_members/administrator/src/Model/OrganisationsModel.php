<?php

/**
 * @version    1.0.0
 * @package    com_ra_members
 */

namespace Ramblers\Component\Ra_members\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class OrganisationsModel extends ListModel
{
    protected $search_fields;

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'a.code',
                'a.name',
                'a.cluster',
                'n.name',
                'a.email_header',
                'a.logo',
                'a.website',
                'a.co_url',
                'record_type',
                'cluster',
                'mailman_active',
            ];

            $this->search_fields = [
                'a.code',
                'a.name',
                'a.cluster',
                'n.name',
                'a.email_header',
                'a.logo',
                'a.website',
                'a.co_url',
            ];
        }

        parent::__construct($config);
    }

    protected function getListQuery()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('a.*')
            ->select('n.name as nation')
            ->select('c.name as cluster_name')
            ->from($db->quoteName('#__ra_organisations', 'a'))
            ->leftJoin('#__ra_nations AS n ON n.id = a.nation_id')
            ->leftJoin('#__ra_clusters AS c ON c.code = a.cluster');

        $search = $this->getState('filter.search');
        $recordType = $this->getState('filter.record_type');
        $cluster = $this->getState('filter.cluster');
        $mailmanActive = $this->getState('filter.mailman_active');

        if (!empty($recordType)) {
            $query->where('a.record_type = ' . $db->quote($recordType));
        }

        if (!empty($cluster)) {
            $query->where('a.cluster = ' . $db->quote($cluster));
        }

        if (!empty($mailmanActive)) {
            $query->where('a.mailman_active = ' . $db->quote($mailmanActive));
        }

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } else {
                $query = ToolsHelper::buildSearchQuery($search, $this->search_fields, $query);
            }
        }

        $orderCol = $this->state->get('list.ordering', 'a.name');
        $orderDirn = $this->state->get('list.direction', 'asc');

        if ($orderCol === 'n.name') {
            $orderCol = $db->quoteName('n.name') . ' ' . $orderDirn . ', ' . $db->quoteName('a.name');
        }

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        if (JDEBUG) {
            Factory::getApplication()->enqueueMessage('sql = ' . (string) $query, 'notice');
        }

        return $query;
    }

    protected function populateState($ordering = 'a.name', $direction = 'asc')
    {
        parent::populateState($ordering, $direction);

        $recordType = $this->getUserStateFromRequest($this->context . '.filter.record_type', 'filter_record_type');
        $this->setState('filter.record_type', $recordType);

        $cluster = $this->getUserStateFromRequest($this->context . '.filter.cluster', 'filter_cluster');
        $this->setState('filter.cluster', $cluster);

        $mailmanActive = $this->getUserStateFromRequest($this->context . '.filter.mailman_active', 'filter_mailman_active');
        $this->setState('filter.mailman_active', $mailmanActive);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.record_type');
        $id .= ':' . $this->getState('filter.cluster');
        $id .= ':' . $this->getState('filter.mailman_active');

        return parent::getStoreId($id);
    }
}