<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 22/06/26 CB delete function
 * 15/07/25 CB add function cancel
 */

namespace Ramblers\Component\Ra_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Versioning\VersionableControllerTrait;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHtml;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

/**
 * Role controller class.
 *
 * @since  1.0.0
 */
class RoleController extends FormController {

    use VersionableControllerTrait;

    protected $app;
    protected $db;
    protected $toolsHelper;
    protected $view_list = 'roles';

    public function __construct(array $config = array(), \Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null) {
//        die('Mail_lstController');
        parent::__construct($config, $factory);
        $this->db = Factory::getDbo();
        $this->toolsHelper = new ToolsHelper;
        $this->app = Factory::getApplication();
//       $this->mailHelper = new Mailhelper;
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    public function cancel($key = null, $urlVar = null) {
        $target = 'index.php?option=com_ra_members&';
        // User state will have been set by the calling program
        $member_id = $this->app->getUserState('com_ra_members.member.member_id','0');
        $callback = $this->app->getUserState('com_ra_members.member.callback','');
        $this->app->enqueueMessage('Role controller: callback= ' . $callback, 'Info"');
        if ($callback == 'member') {
            $target .= 'view=member&id=' . $member_id;
        } elseif ($callback == 'organisation') {
            $target .= 'view=organisation&id=' . $member_id;
        } else {
            $target .= 'view=roles';
        }
        $this->app->setUserState('com_ra_members.member.member_id',null);
        $this->app->setUserState('com_ra_members.member.callback',null);
        $this->setRedirect($target);
    }

    public function deleteRole() {
        $id = $this->app->input->getInt('id', '0');
        $callback = $this->app->input->getWord('callback', 'member');
        $this->app->enqueueMessage('Deleting Role ' . $id, 'Info"');
        try {
            $table = $this->getTable('Role', 'Ramblers\\Component\\Ra_members\\Administrator\\Table\\');

// Load the record into memory and execute the delete
            if ($table->load($id)) {
                if ($table->delete()) {
                    $this->setMessage('Record successfully deleted.');
                } else {
                    $this->setMessage('Failed to delete the record.', 'error');
                }
            } else {
                $this->setMessage('Record not found.', 'warning');
            }
        } catch (\Exception $e) {
// Catch database or syntax exceptions safely
            $this->setMessage($e->getMessage(), 'error');
        }

// Redirect the user back to the list view
    $this->setRedirect('index.php?option=com_ra_members&view=' . $callback);
    }

}
