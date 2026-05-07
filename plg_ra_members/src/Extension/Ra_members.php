<?php

/**
 * @version    1.0.0
 * @package    plg_ra_members
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * 06/05/26 CB created from plg_ra_mailman
 */

namespace Ramblers\Plugin\Console\Ra_members\Extension;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Ramblers\Plugin\Console\Ra_members\Command\LoadusersCommand;

class Ra_members extends CMSPlugin {

    protected $app;

    public function __construct(&$subject, $config = []) {
        parent::__construct($subject, $config);

        if (!$this->app->isClient('cli')) {
            return;
        }

        $this->registerCLICommands();
    }

    public static function getSubscribedEvents(): array {
        if ($this->app->isClient('cli')) {
            return [
                Joomla\Application\ApplicationEvents\ApplicationEvents::BEFORE_EXECUTE => 'registerCLICommands',
            ];
        }
    }

    public function registerCLICommands() {

        $commandObject = new LoadusersCommand;
        $this->app->addCommand($commandObject);
    }

}
