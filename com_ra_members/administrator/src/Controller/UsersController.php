<?php

/**
 * @version    1.1.8
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2025 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *  * 05/07/26 CB copied from com_ra_tools
 */

namespace Ramblers\Component\Ra_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Users list controller class.
 *
 * @since  2.0
 */
class UsersController extends AdminController {

    public function cancel($key = null, $urlVar = null) {
        $this->setRedirect('index.php?option=com_ra_tools&view=dashboard');
    }

    /**
     * Method to clone existing Users
     *
     * @return  void
     *
     * @throws  Exception
     */
    public function duplicate() {
        // Check for request forgeries
        $this->checkToken();

        // Get id(s)
        $pks = $this->input->post->get('cid', array(), 'array');

        try {
            if (empty($pks)) {
                throw new \Exception(Text::_('COM_RA_TOOLS_NO_ELEMENT_SELECTED'));
            }

            ArrayHelper::toInteger($pks);
            $model = $this->getModel();
            $model->duplicate($pks);
            $this->setMessage(Text::_('COM_RA_TOOLS_ITEMS_SUCCESS_DUPLICATED'));
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
        }

        $this->setRedirect('index.php?option=com_ra_members&view=users');
    }

    /**
     * Proxy for getModel.
     *
     * @param   string  $name    Optional. Model name
     * @param   string  $prefix  Optional. Class prefix
     * @param   array   $config  Optional. Configuration array for model
     *
     * @return  object	The Model
     *
     * @since   2.0
     */
    public function getModel($name = 'User', $prefix = 'Administrator', $config = array()) {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

    public function toggleAccess() {
        $app = Factory::getApplication();
        $toolsHelper = new ToolsHelper;
        $user_id = $app->input->getCmd('user', '1');
        $group_id = $app->input->getCmd('group', '1');
        $mode = $app->input->getCmd('mode', '1');
        if ($mode == 'X') {
            // not currently present
            $sql1 = 'INSERT INTO #__user_usergroup_map (user_id,group_id) VALUES (';
            $sql2 = $user_id . ',' . $group_id . ')';
        } else {
            $sql1 = 'DELETE FROM #__user_usergroup_map WHERE ';
            $sql2 = 'user_id=' . $user_id . ' AND group_id=' . $group_id;
        }
        echo $sql1 . $sql2 . '<br>';

        $toolsHelper->executeCommand($sql1 . $sql2);

        $sql = 'SELECT user_id FROM #__user_usergroup_map WHERE user_id=' . $user_id . ' AND ';
        $sql .= 'group_id=' . $group_id;
//        echo $sql . '<br>';

        $this->setRedirect('index.php?option=com_ra_members&view=users');
    }

}
