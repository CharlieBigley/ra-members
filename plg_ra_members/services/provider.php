<?php

/**

 * @version    1.0.0
 * @package    plg_ra_members
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * 07/04/26 CB created from plg_ra_mailman
 */
defined('_JEXEC') || die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Ramblers\Plugin\Console\Ra_members\Extension\Ra_members;


return new class implements ServiceProviderInterface {

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function register(Container $container) {
        $container->set(
                PluginInterface::class,
                function (Container $container) {
                    $subject = $container->get(DispatcherInterface::class);
                    $config = (array) PluginHelper::getPlugin('console', 'ra_members');
                    return new Ra_members($subject, $config);
                }
        );
    }
};

