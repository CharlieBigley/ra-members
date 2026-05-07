<?php
/**
 * @version    1.0.2
 * @component  com_ra_members
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 25/04/26 CB created
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
echo 'SalesForce id: <b>' . $this->item->salesforceId . '</b>, Mem No: <b>' . $this->item->membershipNumber . '</b>';
echo ', Internal id: <b>' . $this->item->id . '</b><br>';
echo 'Name: <b>' . $this->item->title . ' ' . $this->item->firstName . ' ' . $this->item->lastName . '</b><br>';
echo 'Home group: <b>' . $this->item->home_group . '</b><br>';
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
echo 'Phone: ';
if ($this->item->mobileNumber !== '') {
    echo 'Mobile <b>' . $this->item->mobileNumber . '</b>';
}
if ($this->item->landlineTelephone !== '') {
    echo ', Landline <b>' . $this->item->landlineTelephone . '</b>';
}
echo '</b><br>';
echo 'Member Type: <b>' . $this->item->memberType . '</b>';
if ($this->item->affiliateMemberPrimaryGroup !== '') {
    echo ', Affiliate group: <b>' . $this->item->affiliateMemberPrimaryGroup . '</b>';
}
echo '<br>';
echo 'Membership Type: <b>' . $this->item->membershipType . '</b><br>';
echo 'Member Status: <b>' . $this->item->memberStatus . '</b><br>';
echo 'Membership Term:<b> ' . $this->item->memberTerm . '</b><br>';
echo 'Joined: ';
if ($this->item->ramblersJoinDate !== '') {
    echo 'Ramblers <b>' . $this->item->ramblersJoinDate . '</b>';
}
if (!is_null($this->item->areaJoinDate)) {
    echo ', Area <b>' . $this->item->areaJoinDate . '</b>';
}
if (!is_null($this->item->groupJoinDate)) {
    echo ', Group <b>' . $this->item->groupJoinDate . '</b>';
}

echo '<br>';
echo 'Volunteer <b>';
echo ($this->item->volunteer == 'Y') ? 'Yes' : 'No' . '</b><br>';
echo 'Email Marketing Consent: <b>';
echo ($this->item->emailMarketingConsent == 'Y') ? 'Yes' : 'No' . '</b>';
if (!is_null($this->item->emailPermissionLastUpdated)) {
    echo ' Last updated <b>' . $this->item->emailPermissionLastUpdated . '</b>';
}
echo '<br>';
echo 'Post Marketing Consent: <b>';
echo ($this->item->postMarketingConsent == 'Y') ? 'Yes' : 'No' . '</b>';
if (!is_null($this->item->postPermissionLastUpdated)) {
    echo ' Last updated <b>' . $this->item->postPermissionLastUpdated . '</b>';
}
echo '<br>';
echo 'Telephone Marketing Consent: <b>';
echo ($this->item->telephoneDirectMarketing == 'Y') ? 'Yes' : 'No' . '</b>';
if (!is_null($this->item->telephonePermissionLastUpdated)) {
    echo ' Last updated <b>' . $this->item->telephonePermissionLastUpdated . '</b>';
}
echo '<br>';

echo 'Group emails consent: <b>';
echo ($this->item->groupMarketingConsent == 'Y') ? 'Yes' : 'No' ;
echo '</b>, Area emails consent: <b>';
echo (($this->item->areaMarketingConsent == 'Y') ? 'Yes' : 'No') ;
echo '</b>, Other emails consent: <b>';
echo '' . ($this->item->otherMarketingConsent == 'Y') ? 'Yes' : 'No';
echo '</b><br>';

echo 'Walk Programme Opt Out: <b>';         
echo ($this->item->walkProgrammeOptOut == 'Y') ? 'Yes' : 'No' . '</b><br>';
// affiliateMemberPrimaryGroup

echo '</b><br>';
$this->showAudit();
// $target = 'administrator/index.php?option=com_ra_members&task=member.showAudit&id=' . $this->item->id;
// echo $this->toolsHelper->buildButton($target, 'Membership audit');

$back = 'administrator/index.php?option=com_ra_members&view=members';
echo $this->toolsHelper->backButton($back);
echo '<br>';
//echo var_dump($this->item);
//echo '<br>';