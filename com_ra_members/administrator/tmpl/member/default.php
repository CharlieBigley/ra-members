<?php

/**
 * @version    1.1.7
 * @package    com_ra_members
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 25/04/26 CB created
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$showDate = static function ($value) {
    if ($value === '' || is_null($value)) {
        return '';
    }

    return HTMLHelper::_('date', $value, 'd/M/y');
};

echo 'Mem No: <b>' . $this->item->membershipNumber . '</b>, SalesForce id: <b>' . $this->item->salesforceId . '</b>';
if (!is_null($this->item->member_id)) {
    echo ', Internal id: <b>' . $this->item->member_id . '</b>';
}
if (!is_null($this->item->id)) {
    echo ', User id: <b>' . $this->item->id . '</b>';
}
echo '<br>';
echo 'Name: <b>' . $this->item->title . ' ' . $this->item->firstName . ' ' . $this->item->lastName . '</b><br>';
echo 'Preferred Name: <b>' . $this->item->preferred_name . '</b><br>';
echo 'Home group: <b>' . $this->item->home_group . '</b>';
if ($this->item->home_group !== $this->item->group_code) {
    echo ', Group code: <b>';
    if (is_null($this->item->group_code)) {
        echo '(blank)';
    } else {
        echo $this->item->group_code;
    }
}
echo '</b><br>';

echo 'Address: <b>' . $this->item->address1;
if ($this->item->address2 !== '') {
    echo ', ' . $this->item->address2;
}
if ($this->item->address3 !== '') {
    echo ', ' . $this->item->address3;
}
if ($this->item->town !== '') {
    echo ', ' . $this->item->town;
}
if ($this->item->county !== '') {
    echo ', ' . $this->item->county;
}
if ($this->item->country !== '') {
    echo ', ' . $this->item->country;
}
if ($this->item->postcode !== '') {
    echo ', ' . $this->item->postcode;
}
echo '</b><br>';
echo 'Phone: <b>';
$phones = '';
if (!is_null($this->item->mobileNumber)) {
    $phones = 'Mobile <b>' . $this->item->mobileNumber . '</b>';
}
if (!is_null($this->item->landlineTelephone)) {
    if ($phones !== '') {
        $phones .= ', ';
    }
    $phones .= 'Landline <b>' . $this->item->landlineTelephone . '</b>';
}
if ($phones == '') {
    echo 'No phone';
} else {
    echo $phones;
}
echo '</b><br>';
echo 'Email: ';
if (is_null($this->item->id)) {
    // $loadHelper->checkEmail
    $sql = 'SELECT id FROM `#__users` WHERE email="' . $this->item->email_spare . '"';
    $user_id = $this->toolsHelper->getValue($sql);
    if ($user_id) {
        $sql = 'UPDATE #__ra_profiles SET id=' . (int) $user_id;
        $this->toolsHelper->executeCommand($sql);
        $sql = 'SELECT email FROM `#__users` WHERE id = ' . (int) $user_id;
        $email = $this->toolsHelper->getValue($sql);
    } else {
        $email = 'No email address';
    }
} else {
    $sql = 'SELECT email FROM `#__users` WHERE id = ' . (int) $this->item->id;
    $email = $this->toolsHelper->getValue($sql);
}
echo '<b>' . $email . '</b>';
//if ($this->item->id == 0) {
//    echo '<b>No email address</b>';
//} else {
//    $sql = 'SELECT email FROM `#__users` WHERE id = ' . (int) $this->item->id;
//    $email = $this->toolsHelper->getValue($sql);
//    echo '<b>' . $email . '</b>';
//}
echo '<br>';

// $this->toolsHelper
echo 'Member Type: <b>' . $this->item->memberType . '</b><br>';
echo 'Member Arrangement: <b>' . $this->item->membershipArrangement . '</b>';
if (!is_null($this->item->jointWith)) {
    echo ', Joint with: <b>' . $this->item->jointWith . '</b>';
    $partner = $this->mailHelper->lookupMember($this->item->jointWith);
    if (!$partner) {
        $partner = 'Not found';
    }
    echo ' ' . $partner;
}
if (!is_null($this->item->affiliateMemberPrimaryGroup)) {
    echo ', Affiliate group: <b>' . $this->item->affiliateMemberPrimaryGroup . '</b>';
}
echo '<br>';
echo 'Member Status: <b>' . $this->item->memberStatus . '</b><br>';
echo 'Membership Term:<b> ' . $this->item->memberTerm . '</b><br>';
echo 'Joined: ';
if ($this->item->ramblersJoinedDate !== '') {
    echo 'Ramblers <b>' . $showDate($this->item->ramblersJoinedDate) . '</b>';
}
if (!is_null($this->item->areaJoinedDate)) {
    echo ', Area <b>' . $showDate($this->item->areaJoinedDate) . '</b>';
}
if (!is_null($this->item->groupJoinedDate)) {
    echo ', Group <b>' . $showDate($this->item->groupJoinedDate) . '</b>';
}

echo '<br>';
echo 'Volunteer <b>';
echo ($this->item->volunteer == 'Y') ? 'Yes' : 'No';
echo '</b><br>';
echo 'Email Marketing Consent: <b>';
echo ($this->item->emailMarketingConsent == 'Y') ? 'Yes' : 'No';
echo '</b>';
if (!is_null($this->item->emailPermissionLastUpdated)) {
    echo ' Last updated <b>' . $showDate($this->item->emailPermissionLastUpdated) . '</b>';
}
echo '<br>';
echo 'Post Marketing Consent: <b>';
echo ($this->item->postMarketingConsent == 'Y') ? 'Yes' : 'No' . '</b>';
if (!is_null($this->item->postPermissionLastUpdated)) {
    echo ' Last updated <b>' . $showDate($this->item->postPermissionLastUpdated) . '</b>';
}
echo '<br>';
echo 'Telephone Marketing Consent: <b>';
echo ($this->item->telephoneDirectMarketing == 'Y') ? 'Yes' : 'No';
if (!is_null($this->item->telephonePermissionLastUpdated)) {
    echo '</b> Last updated <b>' . $showDate($this->item->telephonePermissionLastUpdated);
}
echo '</b><br>';

echo 'Group emails consent: <b>';
echo ($this->item->groupMarketingConsent == 'Y') ? 'Yes' : 'No';
echo '</b>, Area emails consent: <b>';
echo (($this->item->areaMarketingConsent == 'Y') ? 'Yes' : 'No');
echo '</b>, Other emails consent: <b>';
echo '' . ($this->item->otherMarketingConsent == 'Y') ? 'Yes' : 'No';
echo '</b><br>';

echo 'Walk Programme Opt Out: <b>';
echo ($this->item->walkProgrammeOptOut == 'Y') ? 'Yes' : 'No' . '</b><br>';
// affiliateMemberPrimaryGroup

echo '</b><br>';

if (!is_null($this->item->id)) {

// Show any Roles
    $this->showRoles();
// Show any subsc// $this->showRoles();riptions
    $this->showSubscriptions();
}

$this->showAudit();

$back = 'administrator/index.php?option=com_ra_members&view=members';
echo $this->toolsHelper->backButton($back);
echo '<br>';
//echo var_dump($this->item);
//echo '<br>';