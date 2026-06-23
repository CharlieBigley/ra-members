<?php

/**
 * @version    1.1.3
 * @package    com_ra_members
 * @author     charles
 */

namespace Ramblers\Component\Ra_members\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;
use Ramblers\Component\Ra_members\Site\Helper\LoadHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class OrganisationsController extends AdminController {

    protected $app;
    protected $toolsHelper;

    public function __construct(
            $config = [],
            MVCFactoryInterface $factory = null,
            CMSApplication $app = null,
            Input $input = null
    ) {
        parent::__construct($config, $factory, $app, $input);

        $this->app = Factory::getApplication();
        $this->toolsHelper = new ToolsHelper;
        $this->back = 'administrator/index.php?option=com_ra_members&view=organisations';
        // Import CSS
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    public function cancel($key = null, $urlVar = null) {
        $this->setRedirect('index.php?option=com_ra_tools&view=dashboard&layout=mailman');
    }

    public function getModel($name = 'Organisations', $prefix = 'Administrator', $config = ['ignore_request' => true]) {
        return parent::getModel($name, $prefix, $config);
    }

    public function loadMembers($code = 'NS03') {
        // temp code for invoking the load process
        $code = $this->app->input->getAlnum('code');

        $loadHelper = new LoadHelper;
        $result = $loadHelper->loadMembers($code);
        echo 'Load process completed<br>';
        foreach ($loadHelper->messages as $message) {
            echo $message . '<br>';
        }
        $target = 'administrator/index.php?option=com_ra_members&view=members';
        echo $this->toolsHelper->buildButton($target, 'List Members');
        echo $this->toolsHelper->backButton($this->back);
//        if ($result === true) {
//            $this->setMessage(Text::_('COM_RA_MAILMAN_LOAD_SUCCESS'), 'success');
//        } else {
//            $this->setMessage(Text::_('COM_RA_MAILMAN_LOAD_FAILURE'), 'error');
//        }
//        $this->setRedirect($this->back);
    }

    public function purgeTestdata() {
        echo 'Not implemented<br>';

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
        $objToolsHelper = new ToolsHelper;
        $objUserHelper = new UserHelper;
//        $objUserHelper->purgeTestData();
        echo $objToolsHelper->backButton('administrator/' . $this->back);
    }

}
