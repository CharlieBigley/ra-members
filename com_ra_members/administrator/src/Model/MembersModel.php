<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_members\Administrator\Model;
// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\Database\ParameterType;
use \Joomla\Utilities\ArrayHelper;
use Ramblers\Component\Ra_members\Administrator\Helper\Ra_membersHelper;
use \Joomla\Database\DatabaseInterface;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Methods supporting a list of Members records.
 *
 * @since  1.0.0
 */
class MembersModel extends ListModel
{
	protected $searchFields = [];
	/**
	* Constructor.
	*
	* @param   array  $config  An optional associative array of configuration settings.
	*
	* @see        JController
	* @since      1.6
	*/
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'created', 'a.created',
				'modified', 'a.modified',
				'preferred_name', 'a.preferred_name',
				'membershipExpiryDate', 'a.membershipExpiryDate',
				'membershipNumber', 'a.membershipNumber',
				'home_group', 'a.home_group',
				'firstName', 'a.firstName',
				'lastName', 'a.lastName',
				'memberType', 'a.memberType',
				'membershipType', 'a.membershipType',
				'memberTerm', 'a.memberTerm',
				'salesforceId', 'a.salesforceId',
				'memberStatus',
				'volunteer',
				'walkProgrammeOptOut',
			);

			$this->searchFields = array(
				'a.preferred_name',
				'a.membershipNumber',
				'a.home_group',
				'a.firstName',
				'a.lastName',
//				'a.memberType',
//				'a.memberStatus',
//				'a.membershipType',
//				'a.memberTerm',
				'a.salesforceId',
			);
		}

		parent::__construct($config);
	}
	

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// List state information.
		parent::populateState('a.lastName', 'ASC');

		$context = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $context);

		// Split context into component and optional section
		if (!empty($context))
		{
			$parts = FieldsHelper::extract($context);

			if ($parts)
			{
				$this->setState('filter.component', $parts[0]);
				$this->setState('filter.section', $parts[1]);
			}
		}
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string A store id.
	 *
	 * @since   1.0.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.memberType');
		$id .= ':' . $this->getState('filter.memberStatus');
		$id .= ':' . $this->getState('filter.memberTerm');
		$id .= ':' . $this->getState('filter.membershipType');
		$id .= ':' . $this->getState('filter.volunteer');
		$id .= ':' . $this->getState('filter.walkProgrammeOptOut');

		
		return parent::getStoreId($id);
		
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   1.0.0
	 */
	protected function getListQuery()
	{
		// See if we are running the full version
        $toolsHelper = new ToolsHelper;
        $mailHelper = new MailHelper;
        $group = $mailHelper->getDefaultGroup();
	// Create a new query object.
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'DISTINCT a.*'
			)
		);
		$query->from('`#__ra_profiles` AS a');
		$query->where('membershipNumber IS NOT NULL');


		$memberType = $this->getState('filter.memberType');
		if ($memberType !== '')
		{
			$query->where($db->quoteName('a.memberType') . ' = ' . $db->quote($memberType));
		}

		$memberStatus = $this->getState('filter.memberStatus');
		if ($memberStatus !== '')
		{
			$query->where($db->quoteName('a.memberStatus') . ' = ' . $db->quote($memberStatus));
		}

		$memberTerm = $this->getState('filter.memberTerm');
		if ($memberTerm !== '')
		{
			$query->where($db->quoteName('a.memberTerm') . ' = ' . $db->quote($memberTerm));
		}

		$membershipType = $this->getState('filter.membershipType');
		if ($membershipType !== '')
		{
			$query->where($db->quoteName('a.membershipType') . ' = ' . $db->quote($membershipType));
		}

		$volunteer = $this->getState('filter.volunteer');
		if ($volunteer !== '')
		{
			$query->where($db->quoteName('a.volunteer') . ' = ' . $db->quote($volunteer));
		}

		$walkProgrammeOptOut = $this->getState('filter.walkProgrammeOptOut');
		if ($walkProgrammeOptOut !== '')
		{
			$query->where($db->quoteName('a.walkProgrammeOptOut') . ' = ' . $db->quote($walkProgrammeOptOut));
		}
		
        // For non full version, only show Roles for the current User's Group
        if (($group !== 'N') AND ($toolsHelper->isSuperuser() === false)) {
             $query->where('a.group_code=' . $this->_db->quote($group));
        }	
		// Filter by search 
        $searchWord = $this->getState('filter.search');

        if (!empty($searchWord)) {
            $query = ToolsHelper::buildSearchQuery($searchWord, $this->searchFields , $query);
        }
		
		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.lastName');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

        if (JDEBUG) {
			Factory::getApplication()->enqueueMessage($this->_db->replacePrefix($query), 'message');
        } 
		return $query;
	}

	/**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();
		

		return $items;
	}
}
