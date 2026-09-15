<?php

/**
 * @version    1.0.0
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_members\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Organisations list controller for the site application.
 */
class OrganisationsController extends BaseController
{
    /**
     * Proxy for getModel.
     *
     * @param   string  $name    Model name.
     * @param   string  $prefix  Model prefix.
     * @param   array   $config  Configuration array.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseModel
     */
    public function getModel($name = 'Organisations', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
