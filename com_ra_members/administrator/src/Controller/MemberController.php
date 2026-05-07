<?php

/**
 * @version    2.0.1
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2025 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 25/04/26 CB Created
 */

namespace Ramblers\Component\Ra_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Ramblers\Component\Ra_tools\Site\Helpers\SchemaHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

/**
 * Member controller class.
 *
 * @since  2.0
 */
class MemberController extends FormController {

    protected $view_list = 'members';

public function showAudit() {
    $id = $this->app->input->getInt('id');
    $toolsHelper = new ToolsHelper;
    echo '<h2>Membership audit</h2>';
    echo '<p>Changes to member record</p>';
    echo 'Audit for member id ' . $id . '<br>';
 //   return;
    $table = new ToolsTable;
    $table->add_header("Date,User,Action,Field,Old value,New value");
    $sql = 'SELECT * FROM #__ra_profiles_audit WHERE object_id = ' . $id . ' ORDER BY date_amended DESC';
    $audit = $toolsHelper->getRows($sql);
    foreach ($audit as $entry) {
        $table->add_item(HTMLHelper::_('date', $entry->date_amended, 'd M Y H:i:s'));
        $table->add_item($entry->field_name);
        $table->add_item($entry->record_type);
        $table->add_item($entry->field_value);
 //       $table->add_item($entry->old_value);
 //       $table->add_item($entry->new_value);
        $table->generate_line();
    }


    echo '<p><a href="' . Route::_('index.php?option=com_ra_members&view=members') . '">Back to members list</a></p>';
}
}
