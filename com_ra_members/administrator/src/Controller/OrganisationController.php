<?php

/**
 * @version    1.0.0
 * @package    com_ra_members
 */

namespace Ramblers\Component\Ra_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Input\Input;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

/**
 * Organisation controller class.
 */
class OrganisationController extends FormController
{
    protected $back;
    protected $callback;
    protected $toolsHelper;
    protected $view_item = 'organisation';
    protected $view_list = 'organisations';

    public function __construct(
        $config = [],
        MVCFactoryInterface $factory = null,
        CMSApplication $app = null,
        Input $input = null
    ) {
        parent::__construct($config, $factory, $app, $input);

        $this->toolsHelper = new ToolsHelper;
        $this->back = '/administrator/index.php?option=com_ra_members&view=organisations';
        $this->callback = Factory::getApplication()->getUserState('com_ra_members.reports.callback');
// Import CSS
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    public function cancel($key = null)
    {
        if ($this->callback === 'dashboard') {
            $this->setRedirect('/administrator/index.php?option=com_ra_tools&view=dashboard');

            return;
        }

        $this->setRedirect($this->back);
    }

    public function configure()
    {
        $code = Factory::getApplication()->input->getCmd('code', '');

        if (empty($code)) {
            Factory::getApplication()->enqueueMessage('Code parameter is required', 'error');
            $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');

            return;
        }

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__ra_organisations')
            ->where('code = ' . $db->quote($code));

        $db->setQuery($query);
        $id = $db->loadResult();

        if (empty($id)) {
            Factory::getApplication()->enqueueMessage('Organisation with code ' . htmlspecialchars($code) . ' not found', 'error');
            $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');

            return;
        }

        $return = base64_encode('index.php?option=com_ra_tools&view=dashboard');
        $this->setRedirect('index.php?option=com_ra_members&view=organisation&layout=edit&id=' . $id . '&return=' . $return);
    }

    public function getModel($name = 'Organisation', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        if ($name === '' || strcasecmp($name, 'Organisations') === 0) {
            $name = 'Organisation';
        }

        $model = parent::getModel($name, $prefix, $config);

        if ($model === false && strcasecmp($name, 'Organisation') !== 0) {
            $model = parent::getModel('Organisation', $prefix, $config);
        }

        return $model;
    }

    public function save($key = null, $urlVar = null)
    {
        $result = parent::save($key, $urlVar);

        if ($result) {
            if ($this->callback === 'dashboard') {
                $this->setRedirect('/administrator/index.php?option=com_ra_tools&view=dashboard');
            } else {
                $this->setRedirect($this->back);
            }
        }
    }

    public function showMembers()
    {
        $code = Factory::getApplication()->input->getCmd('code', '');
        $sql = 'SELECT name from #__ra_organisations WHERE code = ' . Factory::getContainer()->get('DatabaseDriver')->quote($code);
        $area = $this->toolsHelper->getValue($sql);
        ToolbarHelper::title('Members in Organisation ' . $area);

        $sql = 'SELECT * from #__ra_profiles ';

        if (strlen($code) === 2) {
            $sql .= 'WHERE home_group like ' . Factory::getContainer()->get('DatabaseDriver')->quote($code . '%') . ' ';
        } else {
            $sql .= 'WHERE home_group = ' . Factory::getContainer()->get('DatabaseDriver')->quote($code) . ' ';
        }

        $sql .= 'ORDER BY lastName, firstName ';

        $table = new ToolsTable;
        $table->add_header('Mem No,Preferred name,Home group,Join date,Expiry date,Member type,Member term,Status,Volunteer');
        $rows = $this->toolsHelper->getRows($sql);

        foreach ($rows as $row) {
            $table->add_item($row->membershipNumber);
            $table->add_item($row->preferred_name);
            $table->add_item($row->home_group);
            $table->add_item(HTMLHelper::_('date', $row->ramblersJoinDate, 'd M y'));
            $table->add_item(HTMLHelper::_('date', $row->membershipExpiryDate, 'd M y'));
            $table->add_item($row->memberType);
            $table->add_item($row->memberTerm);
            $table->add_item($row->memberStatus);
            $table->add_item($row->volunteer);
            $table->generate_line();
        }

        $table->generate_table();
        echo $this->toolsHelper->backButton($this->back);
    }

    public function showOrganisation()
    {
        $code = Factory::getApplication()->input->getCmd('code', 'NS');
        $sql = 'SELECT * FROM #__ra_organisations WHERE code = ' . Factory::getContainer()->get('DatabaseDriver')->quote($code);
        $area = $this->toolsHelper->getItem($sql);

        ToolbarHelper::title($area->name);

        if ($area->record_type === 'A') {
            $sql = 'SELECT name from #__ra_nations WHERE id = ' . (int) $area->nation_id;
            $nation = $this->toolsHelper->getValue($sql);
            echo 'Nation <b>' . $nation . '</b><br>';
            echo 'Cluster <b>' . $area->cluster . '</b><br>';
        }

        echo 'Code <b>' . $area->code . '</b><br>';
        echo 'Name <b>' . $area->name . '</b><br>';
        echo 'Details <b>' . $area->details . '</b><br>';
        echo 'Website <b>' . $area->website . '</b><br>';
        echo 'Head office site <b>' . $area->co_url . '</b><br>';
        echo 'Latitude <b>' . $area->latitude . '</b><br>';
        echo 'Longitude <b>' . $area->longitude . '</b><br>';
        echo $this->toolsHelper->backButton($this->back);
    }

    public function showGroups()
    {
        $code = Factory::getApplication()->input->getCmd('area', '');
        $sql = 'SELECT name from #__ra_organisations WHERE code = ' . Factory::getContainer()->get('DatabaseDriver')->quote($code);
        $area = $this->toolsHelper->getValue($sql);
        ToolbarHelper::title('Groups in Organisation ' . $area);

        $sql = 'SELECT * from #__ra_groups WHERE code like ' . Factory::getContainer()->get('DatabaseDriver')->quote($code . '%') . ' ORDER BY name';

        $table = new ToolsTable;
        $table->add_header('Code,Name,Website,CO link,Location');
        $rows = $this->toolsHelper->getRows($sql);

        foreach ($rows as $row) {
            $table->add_item($row->code);
            $table->add_item($row->name);
            $table->add_item($row->website === '' ? '' : $this->toolsHelper->buildLink($row->website, $row->website, true));
            $table->add_item($row->co_url === '' ? '' : $this->toolsHelper->buildLink($row->co_url, $row->co_url, true));
            $table->add_item($this->toolsHelper->showLocation($row->latitude, $row->longitude, 'O'));
            $table->generate_line();
        }

        $table->generate_table();
        echo $this->toolsHelper->backButton($this->back);
    }
}