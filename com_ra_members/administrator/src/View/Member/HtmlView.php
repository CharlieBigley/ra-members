<?php

/**
 * @version    2.0.1
 * @package    com_ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2025 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 25/04/26 CB Created
 */

namespace Ramblers\Component\Ra_members\Administrator\View\Member;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\User\CurrentUserInterface;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;
/**
 * View class for a single Profile record.
 *
 * @since  2.0
 */
class HtmlView extends BaseHtmlView implements CurrentUserInterface {

    protected $state;
    protected $item;
    protected $toolsHelper;
    protected $user;
    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return void
     *
     * @throws Exception
     */
    public function display($tpl = null) {
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->user = $this->getCurrentUser();
        $this->toolsHelper = new ToolsHelper;
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }
        ToolbarHelper::title(Text::_('Member'), "generic");

        parent::display($tpl);
    }

    public function showAudit() {
        $sql = 'SELECT * FROM #__ra_profiles_audit WHERE object_id = ' . (int) $this->item->member_id . ' ORDER BY date_amended DESC';
        $audit = $this->toolsHelper->getRows($sql);
        if (count($audit) > 0) {
             echo '<h2>Changes to member record</h2>';
            $table = new ToolsTable;
            $table->add_header("Date,User,Action,Field,Old value,New value");
            foreach ($audit as $entry) {
                $table->add_item(HTMLHelper::_('date', $entry->date_amended, 'd M Y H:i:s'));
                $table->add_item($entry->field_name);
                $table->add_item($entry->record_type);
                $table->add_item($entry->field_value);
    //       $table->add_item($entry->old_value);
    //       $table->add_item($entry->new_value);
                $table->generate_line();
            }
            $table->generate_table();
//        } else {
//            echo '<p>No changes to this member record since 1 Jan 2024.</p>';
        }

    }

}
