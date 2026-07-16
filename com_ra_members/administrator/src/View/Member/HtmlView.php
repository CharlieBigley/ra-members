<?php

/**
 * @version    1.1.7
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 15/07/26 CB set values for member_id / organisation_code from user state
 */

namespace Ramblers\Component\Ra_members\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Versioning\VersionableModelTrait;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\CMS\Event\Model;
use Joomla\CMS\Event\AbstractEvent;
use \Joomla\Database\DatabaseInterface;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Role model.
 *
 * @since  1.0.0
 */
class RoleModel extends AdminModel {

    use VersionableModelTrait;

    /**
     * @var    string  The prefix to use with controller messages.
     *
     * @since  1.0.0
     */
    protected $text_prefix = 'COM_RA_MEMBERS';

    /**
     * @var    string  Alias to manage history control
     *
     * @since  1.0.0
     */
    public $typeAlias = 'com_ra_members.role';

    /**
     * @var    null  Item data
     *
     * @since  1.0.0
     */
    protected $item = null;
    protected $code;
    protected $member_id;

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $type    The table type to instantiate
     * @param   string  $prefix  A prefix for the table class name. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table    A database object
     *
     * @since   1.0.0
     */
    public function getTable($type = 'Role', $prefix = 'Administrator', $config = array()) {
        return parent::getTable($type, $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      An optional array of data for the form to interogate.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = array(), $loadData = true) {
        // Initialise variables.
        $app = Factory::getApplication();
        $toolsHelper = new ToolsHelper;
        // Get the form.
        $form = $this->loadForm(
                'com_ra_members.role',
                'role',
                array(
                    'control' => 'jform',
                    'load_data' => $loadData
                )
        );
        if ($this->member_id == '0') {
            $form->setFieldAttribute('member_name', 'visible', 'false');
            $form->setFieldAttribute('member_name', 'required', 'false');
        } else {
            $form->setFieldAttribute('member_name', 'visible', 'true');
            $form->setFieldAttribute('member_id', 'visible', 'false');
            $form->setFieldAttribute('member_id', 'required', 'false');
        }
        // User state will have been set by the calling program
        $member_id = $app->getUserState('com_ra_members.member.member_id','0');
        $group_code = $app->getUserState('com_ra_members.member.group_code','');
        if ($member_id > 0) {
            $form->setFieldAttribute('member_id', 'default', $member_id);
            $form->setFieldAttribute('member_id', 'readonly', 'true');
        }
        if ($group_code !== '') {
            $form->setFieldAttribute('organisation_code', 'default', $group_code);
            // Only SuperUsers can change the group code
            if (!$toolsHelper->isSuperuser()) {
               $form->setFieldAttribute('organisation_code', 'readonly', 'true');
               $group_name = $toolsHelper->lookupGroup($group_code);
            }
            $form->setFieldAttribute('group_name', 'default', $group_name);
            $form->setFieldAttribute('group_name', 'visible', 'true');
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $data = Factory::getApplication()->getUserState('com_ra_members.edit.role.data', array());

        if (empty($data)) {
            if ($this->item === null) {
                $this->item = $this->getItem();
            }

            $data = $this->item;

            // Support for multiple or not foreign key field: role
            $array = array();

            foreach ((array) $data->role as $value) {
                if (!is_array($value)) {
                    $array[] = $value;
                }
            }
            if (!empty($array)) {

                $data->role = $array;
            }
        }
        return $data;
    }

    /**
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  mixed    Object on success, false on failure.
     *
     * @since   1.0.0
     */
    public function getItem($pk = null) {

        if ($item = parent::getItem($pk)) {
            if (isset($item->params)) {
                $item->params = json_encode($item->params);
            }

            // Do any procesing on fields here if needed
        }

        return $item;
    }

    /**
     * Method to duplicate an Role
     *
     * @param   array  &$pks  An array of primary key IDs.
     *
     * @return  boolean  True if successful.
     *
     * @throws  Exception
     */
    public function duplicate(&$pks) {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $dispatcher = $this->getDispatcher();

        // Access checks.
        if (!$user->authorise('core.create', 'com_ra_members')) {
            throw new \Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
        }

        $context = $this->option . '.' . $this->name;

        // Include the plugins for the save events.
        PluginHelper::importPlugin($this->events_map['save']);

        $table = $this->getTable();

        foreach ($pks as $pk) {

            if ($table->load($pk, true)) {
                // Reset the id to create a new record.
                $table->id = 0;

                if (!$table->check()) {
                    throw new \Exception($table->getError());
                }


                // Create the before save event.
                $beforeSaveEvent = AbstractEvent::create(
                                $this->event_before_save,
                                [
                                    'context' => $context,
                                    'subject' => $table,
                                    'isNew' => true,
                                    'data' => $table,
                                ]
                );

                // Trigger the before save event.
                $dispatchResult = Factory::getApplication()->getDispatcher()->dispatch($this->event_before_save, $beforeSaveEvent);

                // Check if dispatch result is an array and handle accordingly
                $result = isset($dispatchResult['result']) ? $dispatchResult['result'] : [];

                // Proceed with your logic
                if (in_array(false, $result, true) || !$table->store()) {
                    throw new \Exception($table->getError());
                }

                // Trigger the after save event.
                Factory::getApplication()->getDispatcher()->dispatch(
                        $this->event_after_save,
                        AbstractEvent::create(
                                $this->event_after_save,
                                [
                                    'context' => $context,
                                    'subject' => $table,
                                    'isNew' => true,
                                    'data' => $table,
                                ]
                        )
                );
            } else {
                throw new \Exception($table->getError());
            }
        }

        // Clean cache
        $this->cleanCache();

        return true;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  Table Object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function prepareTable($table) {
        jimport('joomla.filter.output');

//        if (empty($table->id)) {
//           // Set ordering to the last item if not set
//        }
    }

}
