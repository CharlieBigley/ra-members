<?php

/**
 * @version    1.1.8
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 09/07/26 CB truncate affiliateMemberPrimaryGroup
 */

namespace Ramblers\Component\Ra_members\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Table\Table as Table;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use \Joomla\Database\DatabaseDriver;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\CMS\Filesystem\File;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Helper\ContentHelper;

/**
 * Member table
 *
 * @since 1.0.1
 */
class MemberTable extends Table implements VersionableTableInterface, TaggableTableInterface {

    use TaggableTableTrait;

    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     * @since  4.0.0
     */
    protected $_supportNullValue = true;

    /**
     * Check if a field is unique
     *
     * @param   string  $field  Name of the field
     *
     * @return bool True if unique
     */
    private function isUnique($field) {
        $db = $this->_db;
        $query = $db->getQuery(true);

        $query
                ->select($db->quoteName($field))
                ->from($db->quoteName($this->_tbl))
                ->where($db->quoteName($field) . ' = ' . $db->quote($this->$field))
                ->where($db->quoteName('id') . ' <> ' . (int) $this->{$this->_tbl_key});

        $db->setQuery($query);
        $db->execute();

        return ($db->getNumRows() == 0) ? true : false;
    }

    /**
     * Constructor
     *
     * @param   JDatabase  &$db  A database connector object
     */
    public function __construct(DatabaseDriver $db) {
        $this->typeAlias = 'com_ra_members.member';
        parent::__construct('#__ra_profiles', 'member_id', $db);
        $this->setColumnAlias('published', 'state');
    }

    /**
     * Get the type alias for the history table
     *
     * @return  string  The alias as described above
     *
     * @since   1.0.1
     */
    public function getTypeAlias() {
        return $this->typeAlias;
    }

    /**
     * Overloaded bind function to pre-process the params.
     *
     * @param   array  $array   Named array
     * @param   mixed  $ignore  Optional array or list of parameters to ignore
     *
     * @return  boolean  True on success.
     *
     * @see     Table:bind
     * @since   1.0.1
     * @throws  \InvalidArgumentException
     */
    public function bind($array, $ignore = '') {
        $date = Factory::getDate();
        $task = Factory::getApplication()->input->get('task');
        $user = Factory::getApplication()->getIdentity();

        if ($array['id'] == 0 && empty($array['created_by'])) {
            $array['created_by'] = Factory::getUser()->id;
        }

        if ($array['id'] == 0 && empty($array['modified_by'])) {
            $array['modified_by'] = Factory::getUser()->id;
        }

        if ($task == 'apply' || $task == 'save') {
            $array['modified_by'] = Factory::getUser()->id;
        }

        // Support for empty date field: created
        if ($array['created'] == '0000-00-00' || empty($array['created'])) {
            $array['created'] = NULL;
            $this->created = NULL;
        }

        // Support for empty date field: modified
        if ($array['modified'] == '0000-00-00' || empty($array['modified'])) {
            $array['modified'] = NULL;
            $this->modified = NULL;
        }

        // Support for empty date field: membershipexpirydate
        if ($array['membershipexpirydate'] == '0000-00-00' || empty($array['membershipexpirydate'])) {
            $array['membershipexpirydate'] = NULL;
            $this->membershipexpirydate = NULL;
        }
        // ensure group codes are uppercase
        if (isset($array['home_group'])) {
            $array['home_group'] = strtoupper(trim((string) $array['home_group']));
        }
        if (isset($array['groupCode'])) {
            $array['group_code'] = strtoupper(trim((string) $array['groupCode']));
        }
        // Ensure names are in sentence case without relying on removed Joomla helpers.
        if (isset($array['firstName'])) {
            $array['first_name'] = $this->normaliseName(trim((string) $array['firstName']));
        }
        if (isset($array['lastName'])) {
            $array['last_name'] = $this->normaliseName(trim((string) $array['lastName']));
        }   
        // Truncate affiliate group, keep just the 4 character code
        if (isset($array['affiliateMemberPrimaryGroup'])) {
            $array['affiliateMemberPrimaryGroup'] = strtoupper(substr(trim((string) $array['affiliateMemberPrimaryGroup']), 0, 4));
        }


        if (isset($array['params']) && is_array($array['params'])) {
            $registry = new Registry;
            $registry->loadArray($array['params']);
            $array['params'] = (string) $registry;
        }

        if (isset($array['metadata']) && is_array($array['metadata'])) {
            $registry = new Registry;
            $registry->loadArray($array['metadata']);
            $array['metadata'] = (string) $registry;
        }

        if (!$user->authorise('core.admin', 'com_ra_members.member.' . $array['id'])) {
            $actions = Access::getActionsFromFile(
                            JPATH_ADMINISTRATOR . '/components/com_ra_members/access.xml',
                            "/access/section[@name='member']/"
            );
            $default_actions = Access::getAssetRules('com_ra_members.member.' . $array['id'])->getData();
            $array_jaccess = array();

            foreach ($actions as $action) {
                if (key_exists($action->name, $default_actions)) {
                    $array_jaccess[$action->name] = $default_actions[$action->name];
                }
            }

            $array['rules'] = $this->JAccessRulestoArray($array_jaccess);
        }

        // Bind the rules for ACL where supported.
        if (isset($array['rules']) && is_array($array['rules'])) {
            $this->setRules($array['rules']);
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Method to store a row in the database from the Table instance properties.
     *
     * If a primary key value is set the row with that primary key value will be updated with the instance property values.
     * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     *
     * @since   1.0.1
     */
    public function store($updateNulls = true) {


        return parent::store($updateNulls);
    }

    /**
     * This function convert an array of Access objects into an rules array.
     *
     * @param   array  $jaccessrules  An array of Access objects.
     *
     * @return  array
     */
    private function JAccessRulestoArray($jaccessrules) {
        $rules = array();

        foreach ($jaccessrules as $action => $jaccess) {
            $actions = array();

            if ($jaccess) {
                foreach ($jaccess->getData() as $group => $allow) {
                    $actions[$group] = ((bool) $allow);
                }
            }

            $rules[$action] = $actions;
        }

        return $rules;
    }

    /**
     * Overloaded check function
     *
     * @return bool
     */
    public function check() {

        return parent::check();
    }

    private function normaliseName($value) {
        if ($value === '') {
            return $value;
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($value));
    }

    /**
     * Define a namespaced asset name for inclusion in the #__assets table
     *
     * @return string The asset name
     *
     * @see Table::_getAssetName
     */
    protected function _getAssetName() {
        $k = $this->_tbl_key;

        return $this->typeAlias . '.' . (int) $this->$k;
    }

}
