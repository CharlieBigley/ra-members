<?php

/**
 * @version     1.0.1
 * @package     com_ra_memebrs
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 25/04/26 CB Created
 */

namespace Ramblers\Component\Ra_members\Administrator\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\SubscriptionHelper;
//use Ramblers\Component\Ra_mailman\Site\Helpers\UserHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\SchemaHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\UserHelper;

class SystemController extends FormController
{
    protected $app;
    protected $back;
    protected $db;
    protected $toolsHelper;

    public function __construct(
        $config = [],
        MVCFactoryInterface $factory = null,
        CMSApplication $app = null,
        Input $input = null
    ) {
        parent::__construct($config, $factory, $app, $input);

        $this->toolsHelper = new ToolsHelper;
        $this->app = Factory::getApplication();
        $this->back = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        $this->db = Factory::getDbo();
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    function checkColumn($table, $column, $mode, $details = '') {
//  $mode = A: add the field
//  $mode = U: update the field (keeping name the same)
//  $mode = D: delete the field

        $count = $this->checkColumnExists($table, $column);
        $table_name = $this->dbPrefix . $table;
        echo 'mode=' . $mode . ': Seeking ' . $table_name . '/' . $column . ', count=' . $count . "<br>";
        if (($mode == 'A') AND ($count == 1)
                OR ($mode == 'D') AND ($count == 0)) {
            return true;
        }
        if (($mode == 'U') AND ($count == 0)) {
            echo 'Field ' . $column . ' not found in ' . $table_name . '<br>';
            return false;
        }

        $sql = 'ALTER TABLE ' . $table_name . ' ';
        if ($mode == 'A') {
            $sql .= 'ADD ' . $column . ' ';
            $sql .= $details;
        } elseif ($mode == 'D') {
            $sql .= 'DROP ' . $column;
        } elseif ($mode == 'U') {
            $sql .= 'CHANGE ' . $column . ' ' . $column . ' ';
            $sql .= $details;
        }
        echo "$sql<br>";
        $response = $this->toolsHelper->executeCommand($sql);
        if ($response) {
            echo 'Success';
        } else {
            echo 'Failure';
        }
        echo ' for ' . $table_name . '<br>';
        return $count;
    }

    private function checkColumnExists($table, $column) {
        $config = Factory::getConfig();
        $database = $config->get('db');
        $this->dbPrefix = $config->get('dbprefix');

        $table_name = $this->dbPrefix . $table;
        $sql = 'SELECT COUNT(COLUMN_NAME) ';
        $sql .= "FROM information_schema.COLUMNS ";
        $sql .= "WHERE TABLE_SCHEMA='" . $database . "' AND TABLE_NAME ='" . $this->dbPrefix . $table . "' ";
        $sql .= "AND COLUMN_NAME='" . $column . "'";
//    echo "$sql<br>";

        return $this->toolsHelper->getValue($sql);
    }

    function checkTable($table, $details, $details2 = '') {

        $config = Factory::getConfig();
        $database = $config->get('db');
        $this->dbPrefix = $config->get('dbprefix');

        $table_name = $this->dbPrefix . $table;
        $sql = 'SELECT COUNT(COLUMN_NAME) ';
        $sql .= "FROM information_schema.COLUMNS ";
        $sql .= "WHERE TABLE_SCHEMA='" . $database . "' AND TABLE_NAME ='" . $table_name . "' ";
//        echo "$sql<br>";

        $count = $this->toolsHelper->getValue($sql);
        echo 'Seeking ' . $table_name . ', count=' . $count . "<br>";
        if ($count > 0) {
            return $count;
        }
        $sql = 'CREATE TABLE ' . $table_name . ' ' . $details;
        echo "$sql<br>";
        $response = $this->toolsHelper->executeCommand($sql);
        if ($response) {
            echo 'Table created OK<br>';
        } else {
            echo 'Failure<br>';
            return false;
        }
        if ($details2 != '') {
            $sql = 'ALTER TABLE ' . $table_name . ' ' . $details2;
            $response = $this->toolsHelper->executeCommand($sql);
            if ($response) {
                echo 'Table altered OK<br>';
            } else {
                echo 'Failure<br>';
                return false;
            }
        }
    }
    public function checkSchema() {
        $toolsHelper = new ToolsHelper;
        if (!$toolsHelper->isSuperuser()) {
            return;
        }
        $helper = New SchemaHelper;
// table ra_import_reports
        $details = '(
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `date_phase1` DATETIME NOT NULL ,
            `date_completed` DATETIME NULL ,
            `method_id` int(11) NOT NULL,
            `list_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `num_records` INT  NOT NULL DEFAULT "0",
            `num_errors` INT  NOT NULL DEFAULT "0",
            `num_users` INT  NOT NULL DEFAULT "0",
            `num_subs` INT  NOT NULL DEFAULT "0",
            `num_lapsed` INT  NOT NULL DEFAULT "0",
            `ip_address` VARCHAR(255)  NULL  DEFAULT "",
            `error_report` MEDIUMTEXT  DEFAULT NULL,
            `new_users` MEDIUMTEXT DEFAULT NULL,
            `new_subs` MEDIUMTEXT DEFAULT NULL,
            `lapsed_members` MEDIUMTEXT DEFAULT NULL,
            `input_file` VARCHAR(255) NOT NULL,
            `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL DEFAULT "0",
            `modified` DATETIME NULL DEFAULT NULL,
            `modified_by` INT NULL DEFAULT "0",
            `checked_out_time` DATETIME NULL  DEFAULT NULL ,
            `checked_out` INT NULL,
            `state` TINYINT(1)  NULL  DEFAULT 1,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;';
        $helper->checkTable('ra_import_reports', $details);
//        $helper->checkColumn('ra_events', 'max_bookings', 'A', 'INT NOT NULL DEFAULT "1" AFTER bookable; ');
        $target = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        echo $this->toolsHelper->backButton($target);
    }

    private function createList($code) {
        $sql = 'SELECT id FROM #__ra_mail_lists ';
        $sql .= 'WHERE group_code=' . $this->db->quote($code) . ' ';
        $sql .= 'AND name=' . $this->db->quote('Members Newsletter') . ' ';
        $id = $this->toolsHelper->getValue($sql);
        if (!$id) {
            $sql = 'INSERT INTO `#__ra_mail_lists` (`state`, `name`, `group_code`, `group_primary`, `owner_id`, `record_type`, `home_group_only`, `chat_list`, `footer`, `emails_outstanding`, `ordering`, `checked_out_time`, `created`, `created_by`, `modified`, `modified_by`) ';
            $sql .= 'VALUES ("1", "Walk  Leaders", ';
            $sql .= $this->db->quote($code) . ',NULL';
            $sql .= ' "C", "1", "1", "0", ';
            $sql .= $this->db->quote('Sent to you as a walk leader for ' . $name);
            // $sql .= 'VALUES ("1", "Members Newsletter", ';
            // $sql .= $this->db->quote($code) . ', ' . $this->db->quote($code) . ',';
            // $sql .= ' "O", "1", "1", "0", ';         
            //  $sql .= $this->db->quote('Sent to you as a member of the Ramblers ' . $name);
            $sql .= ', "0", NULL, NULL, current_timestamp(), "1", NULL, "0");'; 
            $this->toolsHelper->executeCommand($sql);
        }
    }
 
    public function createLists() {
        $sql = 'SELECT code FROM #__ra_organisations ';
        $sql .= 'ORDER BY code'; 
        $rows = $this->toolsHelper->getRows($sql);
        foreach ($rows as $row) {
            $this->createList($row->code);
        }
        $target = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        echo $this->toolsHelper->backButton($target);
    }   

    function logMessage($record_type, $ref, $message) {
        $db = Factory::getDbo();

// Create a new query object.
        $query = $this->db->getQuery(true);
// Prepare the insert query.
        $query
                ->insert($db->quoteName('#__ra_logfile'))
                ->set('record_type =' . $db->quote($record_type))
                ->set('ref = ' . $db->quote($record_type))
                ->set('message =' . $db->quote($message));

// Set the query using our newly populated query object and execute it.
        $db->setQuery($query);
        $db->execute();
    }

    public function purgeAllUsers() {
        ToolBarHelper::title($this->prefix . 'Purging Blocked users');
        if (!$this->toolsHelper->isSuperuser()) {
            echo 'Invalid access<br>';
            return;
        }
        $sql = "SELECT id, name as 'User', email  ";
        $sql .= 'FROM `#__users` ';
        $sql .= ' WHERE block=1';
        $sql .= ' ORDER BY id';
        $target = 'administrator/index.php?option=com_ra_mailman&task=system.purgeUser&id=';
        $rows = $this->toolsHelper->getRows($sql);
        foreach ($rows as $row) {
            $this->purgeUserRecord($row->id);
        }
        $userHelper = new UserHelper;
        $userHelper->purgeProfiles();
        $back = 'administrator/index.php?option=com_ra_mailman&view=reports';
        echo $this->toolsHelper->backButton($back);
    }


    public function sendEmail() {
        // Invoked from report recentMailshots to force resend on-line
        // this is the same processing as is carried out by the cron job
        $mail_list_id = $this->app->input->getInt('id', '0');

        if ($mail_list_id == 0) {
            Factory::getApplication()->enqueueMessage('mailshot id is zero', 'notice');
        } else {
            $this->toolsHelper->createLog('RA Mailman', 1, $mail_list_id, 'Sending of mailshot initiated');
            $mailHelper = new MailHelper;
            $last_mailshot = $mailHelper->lastMailshot($mail_list_id); //
//          Factory::getApplication()->enqueueMessage('mailshot id is ' . $last_mailshot->id, 'notice');
            $mailHelper->sendEmails($last_mailshot->id);
            foreach ($mailHelper->messages as $message) {
                Factory::getApplication()->enqueueMessage($message, 'info');
                $this->toolsHelper->createLog('RA Mailman', 1, $mail_list_id, $message);
            }
        }
        $back = 'index.php?option=com_ra_mailman&task=reports.recentMailshots';
        $this->setRedirect($back);
    }

    function test() {
        $toolsHelper = new ToolsHelper;
        $mailHelper = new MailHelper;
        $helper = New SchemaHelper;
        $helper->checkColumn('ra_logfile', 'sub_system', 'U', 'VARCHAR(10) NOT NULL; ');
        $target = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        echo $toolsHelper->backButton($target);
//        return;

        $date = Factory::getDate();
        echo $date . '<br>';

        $sql = 'SELECT id, group_code, name, emails_outstanding ';
        $sql .= 'FROM #__ra_mail_lists ';
        $sql .= 'WHERE emails_outstanding>0 ORDER BY group_code, name';
        $rows = $toolsHelper->getRows($sql);
        $toolsHelper->showQuery($sql);
        $id = 0;
        foreach ($rows as $row) {
            if ($id == 0) {
                $id = $row->id;
                $name = $row->group_code . '/' . $row->name;
            }
            $message .= 'Group ' . $row->group_code . ', List ' . $row->name;
            $message .= ',' . $row->emails_outstanding . ' emails to be sent<br>';
        }
        if ($id > 0) {
            $message .= 'Sending emails for ' . $name . '<br>';
            echo $message;
        }
    }
    public function UpdateNumbership() {
 //       $id = $this->objApp->input->getInt('id', '0');
        ToolBarHelper::title($this->prefix . 'UpdateNumbership');
        if (!$this->toolsHelper->isSuperuser()) {
            echo 'Invalid access<br>';
        } else {
            $sql = 'SELECT id FROM #__ra_profiles WHERE membershipNumber IS NULL';
            $rows = $this->toolsHelper->getRows($sql);
            foreach ($rows as $row) {
                $sql = 'UPDATE #__ra_profiles SET membershipNumber=' . (3 * $row->id);
                $sql .= ' WHERE id=' . $row->id;
                echo $sql . '<br>';
                $this->toolsHelper->executeCommand($sql);
            }
        }
        $back = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        echo $this->toolsHelper->backButton($back);
    }
    public function UpdateSchema() {
 //     index.php?option=com_ra_members&task=system.UpdateSchema
        ToolBarHelper::title($this->prefix . 'UpdateSchema');
        /*
        $this->checkColumn('ra_profiles', 'membershipNumber', 'A','INT NULL AFTER home_group');
        $this->checkColumn('ra_profiles', 'memberType', 'A','VARCHAR(10) NOT NULL DEFAULT "Individual" AFTER membershipNumber');
        $this->checkColumn('ra_profiles', 'memberTerm','A','VARCHAR(10) NOT NULL DEFAULT "individual" AFTER  memberType');
        $this->checkColumn('ra_profiles', 'memberStatus','A','VARCHAR(10) NOT NULL DEFAULT "active" AFTER memberTerm');
        $this->checkColumn('ra_profiles', 'membershipType','A','VARCHAR(10) NOT NULL DEFAULT "annual" AFTER memberStatus');
        $this->checkColumn('ra_profiles', 'jointWith','A','INT NULL AFTER membershipType');
        $this->checkColumn('ra_profiles', 'title','A','VARCHAR(10) NULL AFTER jointWith');
        $this->checkColumn('ra_profiles', 'initials','A','VARCHAR(100) NULL AFTER title');
        $this->checkColumn('ra_profiles', 'firstName','A','VARCHAR(100) NOT NULL AFTER initials');
        $this->checkColumn('ra_profiles', 'lastName','A','VARCHAR(100) NULL AFTER firstName');
        $this->checkColumn('ra_profiles', 'address1','A','VARCHAR(100) NULL AFTER lastName');
        $this->checkColumn('ra_profiles', 'address2','A','VARCHAR(100) NULL AFTER address1');
        $this->checkColumn('ra_profiles', 'address3','A','VARCHAR(100) NULL AFTER address2');
        $this->checkColumn('ra_profiles', 'town','A','VARCHAR(100) NULL AFTER address3');
        $this->checkColumn('ra_profiles', 'county','A','VARCHAR(100) NULL AFTER town');
        $this->checkColumn('ra_profiles', 'country','A','VARCHAR(100) NULL AFTER county');
        $this->checkColumn('ra_profiles', 'postcode','A','VARCHAR(10) NULL AFTER country');
        $this->checkColumn('ra_profiles', 'landlineTelephone','A','VARCHAR(50) NULL AFTER postcode');
        $this->checkColumn('ra_profiles', 'mobileNumber','A','VARCHAR(50) NULL AFTER landlineTelephone');
        $this->checkColumn('ra_profiles', 'membershipExpiryDate','A','DATE NULL AFTER mobileNumber');
        $this->checkColumn('ra_profiles', 'ramblersJoinedDate','A','DATE NULL AFTER membershipExpiryDate');
        $this->checkColumn('ra_profiles', 'areaJoinedDate','A','DATE NULL AFTER ramblersJoinedDate');
        $this->checkColumn('ra_profiles', 'groupJoinedDate','A','DATE NULL AFTER areaJoinedDate');
        $this->checkColumn('ra_profiles', 'volunteer','A','CHAR(1) NULL AFTER groupJoinedDate');
        $this->checkColumn('ra_profiles', 'emailMarketingConsent','A','CHAR(1) NULL AFTER volunteer');
        $this->checkColumn('ra_profiles', 'emailPermissionLastUpdated','A','DATE NULL AFTER emailMarketingConsent');
        $this->checkColumn('ra_profiles', 'postDirectMarketing','A','CHAR(1) NULL AFTER emailPermissionLastUpdated');
        $this->checkColumn('ra_profiles', 'postPermissionLastUpdated','A','DATE NULL AFTER postDirectMarketing');
        $this->checkColumn('ra_profiles', 'telephoneDirectMarketing','A','CHAR(1) NULL AFTER groupJoinedDate');
        $this->checkColumn('ra_profiles', 'telephonePermissionLastUpdated','A','DATE NULL AFTER postPermissionLastUpdated');
        $this->checkColumn('ra_profiles', 'walkProgrammeOptOut','A','CHAR(1) NULL AFTER telephonePermissionLastUpdated');
        $this->checkColumn('ra_profiles', 'affiliateMemberPrimaryGroup','A','VARCHAR(4) NULL AFTER walkProgrammeOptOut');
*/

        $this->checkColumn('ra_profiles', 'groupMarketingConsent','A','CHAR(1) NULL AFTER telephonePermissionLastUpdated');
        $this->checkColumn('ra_profiles', 'areaMarketingConsent','A','CHAR(1) NULL AFTER groupMarketingConsent');
        $this->checkColumn('ra_profiles', 'otherMarketingConsent','A','CHAR(1) NULL AFTER areaMarketingConsent');
        $this->checkColumn('ra_profiles', 'MembershipSecretary','A','CHAR(1) NULL AFTER otherMarketingConsent');
         $back = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        echo $this->toolsHelper->backButton($back);       
    }    
}
