<?php

/**
 * @version    1.1.7
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
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

/**
 * View class for a single Profile record.
 *
 * @since  2.0
 */
class HtmlView extends BaseHtmlView implements CurrentUserInterface {

    protected $callback;
    protected $isSuper;
    protected $item;
    protected $state;
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
        $this->mailHelper = new Mailhelper;
        $this->toolsHelper = new ToolsHelper;
        $this->isSuper = $this->toolsHelper->isSuperuser();
        $app = Factory::getApplication();
        $this->callback = $app->input->getWord('callback', '');
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }
        ToolbarHelper::title(Text::_('Member'), "generic");

        parent::display($tpl);
    }

    public function showAudit() {
        $sql = 'SELECT * FROM #__ra_profiles_audit ';
        $sql .= 'WHERE object_id = ' . (int) $this->item->id . ' ORDER BY date_amended DESC';
        $audit = $this->toolsHelper->getRows($sql);
        if ($audit) {
            echo '<h2>Changes to member record</h2>';
            $table = new ToolsTable;
            $table->add_header("Date,Action,Field,Details");
            foreach ($audit as $entry) {
                $table->add_item(HTMLHelper::_('date', $entry->date_amended, 'd M Y H:i:s'));
                $table->add_item($entry->record_type);
                $table->add_item($entry->field_name);
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

    public function showRoles() {
        echo '<h4>Roles</h4>';
        $target_add = 'administrator/index.php?option=com_ra_members&task=role.add';
        $target_add .= '&member_id=' . $this->item->member_id;
        $target_add .= '&group_code=' . $this->item->home_group;
        $target_add .= '&callback=' . $this->callback;
        $target_add .= '&menu_id=' . $this->menu_id;
        echo $this->toolsHelper->buildButton($target_add, 'Add Role');
        $sql = 'SELECT r.*, o.name ';
        //       $sql .= 'u.name AS `Subscriber`, ';
//        $sql .= 'DATE(s.created) AS `Created`, ';
        $sql .= 'FROM `#__ra_roles` AS r ';
        $sql .= 'LEFT JOIN `#__ra_organisations` AS `o` ON o.code = r.organisation_code ';
        $sql .= 'LEFT JOIN `#__ra_profiles` AS `p` ON p.id = r.member_id ';
        $sql .= 'WHERE r.member_id=' . $this->item->member_id;
//$sql .= ' OR l.owner_id=' . $this->user->id;
        $sql .= ' ORDER BY r.organisation_code, r.role ';
        $rows = $this->toolsHelper->getRows($sql);

        if ($this->toolsHelper->rows == 0) {
            echo 'No roles <br>';
            return;
        }

        $table = new ToolsTable();
        $title = 'Role,Group,Last_updated';
        if ($this->toolsHelper->isSuperuser()) {
            $tile .= ',Action';
            $target_delete = 'administrator/index.php?option=com_ra_members&task=role.delete&id=';
        } else {
            echo 'No<br>';
        }
        $table->add_header($title);
        foreach ($rows as $row) {
            $table->add_item($row->role);
            $table->add_item($row->name);
            //               $table->add_item(HTMLHelper::_('date', $row->expiry_date, 'd-M-Y')); // $pretty_date = HTMLHelper::_('date', $row->expiry_date, 'd-M-Y');
            $table->add_item(HTMLHelper::_('date', $row->last_updated, 'd-M-Y'));
            $table->add_item($details);
            if ($this->isSuper) {
                $details .= $this->toolsHelper->buildButton($target_delete . $row->id, 'Delete');
                $table->add_item($details);
            }
            $table->generate_line();
            $count++;
        }


        $table->generate_table();
    }

    public function showSubscriptions() {
        echo '<h4>Subscriptions</h4>';

        $target_info = 'index.php?option=com_ra_mailman&task=profile.showSubscriptionDetails&menu_id=' . $this->menu_id . '&id=';
        $target_renew = 'index.php?option=com_ra_mailman&task=mail_lst.renew&Itemid=' . $this->menu_id;
        $target_renew .= '&user_id=' . $this->item->id . '&list_id=';

        $sql = 'SELECT s.id, s.list_id, ';
        $sql .= 'u.name AS `Subscriber`, ';
        $sql .= 'DATE(s.created) AS `Created`, ';
        $sql .= 's.modified, s.expiry_date, s.reminder_sent,';
        $sql .= 'l.group_code, l.name AS `list`, ';
        $sql .= 'm.name AS `Method`, ma.name as Access ';
        $sql .= 'FROM `#__ra_mail_subscriptions` AS s ';
        $sql .= 'INNER JOIN `#__ra_mail_methods` AS `m` ON m.id = s.method_id ';
        $sql .= 'LEFT JOIN `#__users` AS `u` ON u.id = s.user_id ';
        $sql .= 'LEFT JOIN `#__ra_mail_lists` AS `l` ON l.id = s.list_id ';
        $sql .= 'LEFT JOIN #__ra_mail_access AS ma ON ma.id = s.record_type ';
        $sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = s.user_id ';
        $sql .= 'WHERE s.user_id=' . $this->item->id;
//$sql .= ' OR l.owner_id=' . $this->user->id;
        $sql .= ' ORDER BY l.group_code, l.name ';
        $rows = $this->toolsHelper->getRows($sql);
        if ($this->toolsHelper->rows == 0) {
            echo 'No subscriptions <br>';
            return;
        } else {
            $table = new ToolsTable();
            $table->add_header(',Group,Title,Expiry date,Reminder, Action');
            $count = 1;
            foreach ($rows as $row) {
                $lists[] = $row->list_id;
                $table->add_item('<b>' . $count . '</b>. ');
                $table->add_item($row->group_code);
                $table->add_item($row->list);
                $table->add_item(HTMLHelper::_('date', $row->expiry_date, 'd-M-Y')); // $pretty_date = HTMLHelper::_('date', $row->expiry_date, 'd-M-Y');
                $table->add_item(HTMLHelper::_('date', $row->reminder_sent, 'd-M-Y'));
                $details = $this->toolsHelper->buildButton($target_info . $row->id, 'Details');
                $details .= $this->toolsHelper->buildButton($target_renew . $row->list_id, 'Renew');
                $table->add_item($details);
                $table->generate_line();
                $count++;
            }
        }

        $table->generate_table();
    }

}
