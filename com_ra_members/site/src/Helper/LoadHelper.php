<?php

/**
 * Helper to load member data from the Central Office API feed
 * Usually run from a batch job via cron, but can be invoked from the view organisations
 *
 * If run on-line, messages are displayed directly; if in batch mode, messages are
 * stored in arrays for later display
 *
 * @version    4.7.0
 * @package    com_ra_members
 * @author     charles
 * 18/04/26 CB created
 * 05/06/26 CB include expired members
 */

namespace Ramblers\Component\Ra_members\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\UserHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\JsonHelper;

class LoadHelper {

    protected $db;
    protected $app;
    protected $toolsHelper;
    protected $jsonHelper;
    protected $mailHelper;
    protected $profileColumns;
    protected $auditColumns;
    protected $roleColumns;
    private $counter = 0;
    public $batch_mode = false; // if true, messages are added to $this->messages instead of enqueued, for display at the end of the batch process
    public $comments;
    public $comment_count;
    public $count_new = 0;
    public $count_not_updated = 0;
    public $count_updated = 0;
    public $errors;
    public $messages = array();

    public function __construct() {
        $this->app = Factory::getApplication();
        $this->db = Factory::getDbo();
        $this->jsonHelper = new JsonHelper;
        $this->mailHelper = new MailHelper;
        $this->toolsHelper = new ToolsHelper;
        $this->comments = array();
        $this->errors = array();
        $this->comment_count = 0;
    }

    private function booleanToDatabase($value) {
        if ($value === true || $value === 1 || $value === '1' || $value === 'Y' || $value === 'true') {
            return 'Y';
        }

        return null;
    }

    private function buildPreferredName($member) {
        $firstName = $this->firstWord($member['firstName'] ?? '');
        $lastName = $this->firstWord($member['lastName'] ?? '');
        $preferredName = trim($firstName . ' ' . $lastName);

        return ($preferredName === '') ? null : $preferredName;
    }

    private function buildRoleRows($member, $memberId) {
        $roleRows = array();
        $allowedRoles = array('walkLeader', 'emailSender', 'membershipSecretary');

        foreach (($member['groupMemberships'] ?? array()) as $groupMembership) {
            $groupMembership = $this->normaliseMember($groupMembership);
            $groupCode = $this->normaliseScalar($groupMembership['groupCode'] ?? null);

            if ($groupCode === null) {
                continue;
            }

            $roles = $this->normaliseMember($groupMembership['roles'] ?? array());

            foreach ($allowedRoles as $roleName) {
                if (($roles[$roleName] ?? false) === true) {
                    $roleRows[$groupCode . ':' . $roleName] = array(
                        'member_id' => $memberId,
                        'organisation_code' => $groupCode,
                        'role' => $roleName,
                    );
                }
            }
        }

        foreach (($member['areaMemberships'] ?? array()) as $areaMembership) {
            $areaMembership = $this->normaliseMember($areaMembership);
            $areaCode = $this->normaliseScalar($areaMembership['areaCode'] ?? null);

            if ($areaCode === null) {
                continue;
            }

            $roles = $this->normaliseMember($areaMembership['roles'] ?? array());

            if (($roles['emailSender'] ?? false) === true) {
                $roleRows[$areaCode . ':emailSender'] = array(
                    'member_id' => $memberId,
                    'organisation_code' => $areaCode,
                    'role' => 'emailSender',
                );
            }
        }

        return array_values($roleRows);
    }

    public function checkEmail($email, $member_id, $profile_id) {
        /*
          Ensure the profile is linked to a Joomla user record for this email address.

          For a newly created profile, reuse an existing Joomla user if one already exists for
          the email address; otherwise create a new user. In either case, link the profile to
          that user and create a subscription to the default mailing list.

          For an existing linked profile, only the Joomla user email should need updating.

          This method assumes lookupUser() returns either a user object or null. If the shared
          ToolsHelper::getItem() helper returns false for SQL failures, that needs handling in
          the shared helper before callers can safely distinguish "not found" from "query failed".
         */
        if (JDEBUG) {
            $message = 'Checking email ' . $email . ' for member id ' . $member_id . ', profile id ' . $profile_id;
            echo $message . '<br>';
            $this->messages[] = $message;
        }
        // see if an existing user record exists
        $existing_user = $this->lookupUser($email);
        if ($existing_user === false) {
            $this->messages[] = 'Error looking up Joomla user for email ' . $email . ': ' . $this->toolsHelper->error;
            return false;
        }
        //   var_dump($existing_user);
        //   echo '<br>';
        //   return;
        if (is_null($profile_id) || $profile_id == 0) {
            if (is_object($existing_user) && !empty($existing_user->id)) {
                $user_id = $existing_user->id;
                if (JDEBUG) {
                    $message = 'Existing user record found with id ' . $user_id . ' for email ' . $email;
                    echo $message . '<br>';
                    $this->messages[] = $message;
                }
                // delete any pre-existing profile record
                $this->purgeProfile($existing_user->id);
                // update the profile record
                $sql = 'UPDATE #__ra_profiles SET id=' . $existing_user->id;
                $sql .= ' WHERE member_id=' . $member_id;
                echo $sql . '<br>';
                $this->toolsHelper->executeCommand($sql);
            } else {
                // create a new User record
                $userHelper = new UserHelper;
                $userHelper->name = $this->lookupPreferredName($member_id);
                $userHelper->email = $email;
                $userHelper->createUserDirect();
                $user_id = $userHelper->user_id;
                if (JDEBUG) {
                    $message = 'Created new user record with id ' . $user_id . ' for email ' . $email;
                    echo $message . '<br>';
                    $this->messages[] = $message;
                    echo 'Updating profile record with id ' . $profile_id . ' to link to user record with id ' . $user_id . '<br>';
                }
                // update the profile record
                $sql = 'UPDATE #__ra_profiles SET id=' . $user_id;
                $sql .= ' WHERE member_id=' . $member_id;
                echo $sql . '<br>';
                $this->toolsHelper->executeCommand($sql);
            }
            // Create a subscription to the primary mailing list
            $primary_list = $this->getDefaultList($member_id);
            if ($primary_list) {
                if ($this->mailHelper->subscribe($primary_list, $user_id, 1, 3)) {
                    $this->messages[] = 'Subscription created to list ' . $primary_list;
                } else {
                    $this->messages[] = 'Error creating subscription to list ' . $primary_list . ': ' . $this->mailHelper->message;
                }
            } else {
                $this->messages[] = 'No default list found for member id ' . $member_id;
            }
        } else {
            // we have a valid profile_id - update the email address if needed
            // check that the email address has not been updated
            if (is_null($existing_user) || $existing_user->email !== $email) {
                $user_id = (int) $profile_id;
                $sql = 'UPDATE #__users SET email=' . $this->db->quote($email);
// $sql .=
                $sql .= ' WHERE id=' . $user_id;
                echo $sql . '<br>';
                $this->toolsHelper->executeCommand($sql);
                if (is_object($existing_user)) {
                    $this->messages[] = 'Updated email address from ' . $existing_user->email . ' to ' . $email . ' for user record with id ' . $user_id;
                } else {
                    $this->messages[] = 'Updated email address to ' . $email . ' for user record with id ' . $user_id;
                }
            }
        }
        return true;
    }

    private function doesMemberExist($salesforceId) {
        return $this->getProfileBySalesforceId($salesforceId);
    }

    private function firstWord($token) {
        $token = trim((string) $token);

        if ($token === '') {
            return '';
        }

        $space = strpos($token, ' ');

        if ($space === false || $space === 0) {
            return $token;
        }

        if (($space === 1) && (substr($token, 0, 1) === 'O')) {
            return "O'" . substr($token, 2);
        }

        return substr($token, 0, $space);
    }

    private function formatDateForDatabase($value) {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d');
        } catch (\Exception $e) {
            if (strlen($value) >= 10) {
                return substr($value, 0, 10);
            }

            return null;
        }
    }

    private function getAuditColumns() {
        if ($this->auditColumns === null) {
            $this->auditColumns = $this->db->getTableColumns('#__ra_profiles_audit', false);
        }

        return $this->auditColumns;
    }

    private function getCurrentUserId() {
        $user = $this->app->getSession()->get('user');

        if (is_object($user) && isset($user->id)) {
            return (int) $user->id;
        }

        return 0;
    }

    public function getDefaultList($member_id) {
        $sql = 'SELECT DISTINCT l.id FROM #__ra_mail_lists AS l ';
        $sql .= 'LEFT JOIN #__ra_profiles AS p1 ON p1.home_group=l.group_code ';
        $sql .= 'LEFT JOIN #__ra_profiles AS p2 ON p2.home_group=l.group_primary ';
        $sql .= 'WHERE p1.member_id=' . (int) $member_id;
        return $this->toolsHelper->getValue($sql);
    }

    public function getJson($code) {
        $endpoint = '/api/groups/' . $code . '/members';
        /*
          $site_id is the id of the record in api_sites
          $endpoint is the project_code/view_name (e.g. /api/index.php/v1/ra_events/events)
          Derived from EventsHelper/getRemoteEvents, but generalised
         */
        $sql = 'SELECT * FROM #__ra_api_sites WHERE title="' . $code . '"';
        $site = $this->toolsHelper->getItem($sql);
        if ($site === false) {
            $this->messages[] = 'Error looking up API site for code ' . $code . ': ' . $this->toolsHelper->error;
            return false;
        }
        if (is_null($site)) {
            $this->messages[] = 'No API site found for code ' . $code;
            return false;
        }
        $token = trim($site->token);

        $baseUrl = rtrim(trim((string) $site->url), '/');
        $baseUrl = preg_replace('#/api/groups$#', '', $baseUrl);
        $baseUrl = preg_replace('#/api$#', '', $baseUrl);

        $url = $baseUrl . $endpoint;

        // Force inclusion of records even if expriry date hase passed
        $url .= '?includeExpired=true';

        $error = '';
        $responseHeaders = '';

        if (JDEBUG) {
            $message = 'Site id ' . $site->id . ', ';
            $message .= 'Seeking data from ' . $url;
            $this->messages[] = $message;
            $message = 'Token is ' . $token;
            $this->messages[] = $message;
        }
//      set up maximum time of 5 minutes
        $max = 5 * 60;
        set_time_limit($max);

// HTTP request headers
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false, // do not include header in output
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => 'utf-8',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => $max,
            CURLOPT_TIMEOUT => $max,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
            CURLOPT_CUSTOMREQUEST => 'GET',
//            CURLOPT_REFERER => "com_ra_tools", // say who wants the feed
            CURLOPT_HTTPHEADER => $headers,
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // do not follow redirects
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // do not output result
                ]
        );

        $responseData = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($responseData == false) {
            $error = curl_error($curl);

            if ($httpCode !== 200) {
                $message = 'Error: ' . $httpCode;
                $message .= ', ' . $error;
                if ($this->toolsHelper->isSuperuser()) {
                    $message .= ' ' . $url;
                }
                $this->messages[] = $message;
                $this->messages[] = 'Error ' . $error;
                return false;
            }
        }
//        if (curl_errno($curl)) {
//            echo curl_error($curl);
//        }
        curl_close($curl);

        if ($httpCode !== 200) {
            $message = 'Error: ' . $httpCode;
            if ($httpCode == 401) {
                $message .= 'Authorization Required (Token missing or invalid)';
            } else {
                $message .= $error;
            }
            $this->messages[] = $message;
            $this->messages[] = 'Endpoint: ' . $url;
            if ($responseHeaders !== '') {
                $this->messages[] = 'Response data: ' . trim($responseData);
            }
//            return false;
        }
        $details = json_decode($responseData, true);
        if ($details === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->messages[] = 'JSON decode error: ' . json_last_error_msg();
        }
        if (JDEBUG) {
            //           echo '<b>Start of details</b><br>';
            //           var_dump($details);
            //           echo '<br><b>End of details</b><br>';
            //           echo '<b>Start of response</b><br>';
            //           echo $responseData;
            //           echo '<br>========<br>';
        }
        return $details;
    }

    private function getProfileBySalesforceId($salesforceId) {
        $query = $this->db->getQuery(true)
                ->select('*')
                ->from($this->db->quoteName('#__ra_profiles'))
                ->where($this->db->quoteName('salesforceId') . ' = ' . $this->db->quote($salesforceId));

        $this->db->setQuery($query, 0, 1);

        return $this->db->loadObject();
    }

    private function getProfileColumns() {
        if ($this->profileColumns === null) {
            $this->profileColumns = $this->db->getTableColumns('#__ra_profiles', false);
        }

        return $this->profileColumns;
    }

    private function getProfileReference($profile) {
        if (is_object($profile)) {
            if (isset($profile->member_id) && (int) $profile->member_id > 0) {
                return (int) $profile->member_id;
            }

            if (isset($profile->id) && (int) $profile->id > 0) {
                return (int) $profile->id;
            }
        }

        return 0;
    }

    private function getRoleColumns() {
        if ($this->roleColumns === null) {
            $this->roleColumns = $this->db->getTableColumns('#__ra_roles', false);
        }

        return $this->roleColumns;
    }

    private function lookupPreferredName($member_id) {
        $sql = 'SELECT preferred_name FROM #__ra_profiles WHERE member_id=' . (int) $member_id;
        return $this->toolsHelper->getValue($sql);
    }

    private function lookupUser($email) {
        $sql = 'SELECT id, name, username, email FROM #__users WHERE email=';
        $sql .= $this->db->quote($email);
        return $this->toolsHelper->getItem($sql);
    }

    private function normaliseMember($member) {
        if (is_object($member)) {
            $member = json_decode(json_encode($member), true);
        }

        return is_array($member) ? $member : array();
    }

    private function normaliseScalar($value) {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Y' : '';
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $value = trim((string) $value);

        return ($value === '') ? null : $value;
    }

    private function normaliseEnumValue($value, array $allowedValues, $fieldName, $fallbackValue = null) {
        $value = $this->normaliseScalar($value);

        if ($value === null) {
            return null;
        }

        $lowerValue = strtolower($value);

        foreach ($allowedValues as $allowedValue) {
            if ($lowerValue === strtolower($allowedValue)) {
                return $allowedValue;
            }
        }

        $fallback = $allowedValues[0] ?? null;

        if ($fallbackValue !== null) {
            foreach ($allowedValues as $allowedValue) {
                if (strtolower($fallbackValue) === strtolower($allowedValue)) {
                    $fallback = $allowedValue;
                    break;
                }
            }
        }

        $message = 'Unknown ' . $fieldName . ' value "' . $value . '" from feed';

        if ($fallback !== null) {
            $message .= '; using fallback "' . $fallback . '"';
        }

        $this->messages[] = $message;
        $this->logMessage($message, '3');

        return $fallback;
    }

    private function purgeProfile($id) {
        // Deletes profile record and audit records
        $sql = 'DELETE FROM #__ra_profiles_audit WHERE object_id=' . $id;
        $this->toolsHelper->executeCommand($sql);
        $sql = 'DELETE FROM #__ra_profiles WHERE id=' . $id;
        $this->toolsHelper->executeCommand($sql);
    }

    private function quoteValue($value) {
        if ($value === null) {
            return 'NULL';
        }

        return $this->db->quote($value);
    }

    private function mapMemberToProfileData($member) {
        $columns = $this->getProfileColumns();
        $data = array();

        $fieldMap = array(
            'salesforceId' => 'salesforceId',
            'membershipNumber' => 'membershipNumber',
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'title' => 'title',
            'initials' => 'initials',
            'email' => 'email',
            'mobileNumber' => 'mobileNumber',
            'landlineTelephone' => 'landlineTelephone',
            'address1' => 'address1',
            'address2' => 'address2',
            'address3' => 'address3',
            'town' => 'town',
            'county' => 'county',
            'country' => 'country',
            'postcode' => 'postcode',
            'groupName' => 'groupName',
            'groupCode' => 'groupCode',
            'memberType' => 'memberType',
            'memberTerm' => 'memberTerm',
            'memberStatus' => 'memberStatus',
            'membershipArrangement' => 'membershipArrangement',
            'jointWith' => 'jointWith',
            'areaName' => 'areaName',
            'affiliateMemberPrimaryGroup' => 'affiliateMemberPrimaryGroup',
        );

        foreach ($fieldMap as $inputField => $columnName) {
            if (array_key_exists($columnName, $columns)) {
                $data[$columnName] = $this->normaliseScalar($member[$inputField] ?? null);
            }
        }

        if (array_key_exists('memberStatus', $columns)) {
            $data['memberStatus'] = $this->normaliseEnumValue(
                    $member['memberStatus'] ?? null,
                    array('Active', 'Payment Pending'),
                    'memberStatus',
                    'Payment Pending'
            );
        }

        if (array_key_exists('memberTerm', $columns)) {
            $data['memberTerm'] = $this->normaliseEnumValue(
                    $member['memberTerm'] ?? null,
                    array('Annual', 'Life'),
                    'memberTerm',
                    'Annual'
            );
        }

        if (array_key_exists('memberType', $columns)) {
            $data['memberType'] = $this->normaliseEnumValue(
                    $member['memberType'] ?? null,
                    array('Member', 'Affiliate'),
                    'memberType',
                    'Single'
            );
        }

        if (array_key_exists('membershipType', $columns)) {
            $data['membershipType'] = $this->normaliseEnumValue(
                    $member['membershipType'] ?? null,
                    array('Single', 'Joint'),
                    'membershipType',
                    'Affiliate'
            );
        }

        if (array_key_exists('membershipArrangement', $columns)) {
            $data['membershipArrangement'] = $this->normaliseEnumValue(
                    $member['membershipArrangement'] ?? $member['membershipType'] ?? null,
                    array('Individual', 'Joint'),
                    'membershipArrangement',
                    'Affiliate'
            );
        }

        if (array_key_exists('type', $columns)) {
            $data['type'] = $this->normaliseEnumValue(
                    $member['membershipType'] ?? $member['type'] ?? null,
                    array('Member', 'Affiliate'),
                    'type',
                    'Affiliate'
            );
        }

        if (array_key_exists('home_group', $columns)) {
            $data['home_group'] = $this->normaliseScalar($member['groupCode'] ?? null);
        }

        if (array_key_exists('preferred_name', $columns)) {
            $data['preferred_name'] = $this->buildPreferredName($member);
        }

        $dateFields = array(
            'membershipExpiryDate' => array('membershipExpiryDate'),
            // Interim measure: accept both feed names until all sites emit ramblersJoinedDate.
            'ramblersJoinedDate' => array('ramblersJoinedDate', 'ramblersJoinDate'),
            'areaJoinedDate' => array('areaJoinedDate'),
            'groupJoinedDate' => array('groupJoinedDate'),
            'emailPermissionLastUpdated' => array('emailPermissionLastUpdated'),
            'postPermissionLastUpdated' => array('postPermissionLastUpdated'),
            'telephonePermissionLastUpdated' => array('telephonePermissionLastUpdated'),
        );

        foreach ($dateFields as $columnName => $inputFields) {
            if (!array_key_exists($columnName, $columns)) {
                continue;
            }

            foreach ($inputFields as $inputField) {
                $formattedDate = $this->formatDateForDatabase($member[$inputField] ?? null);

                if ($formattedDate !== null) {
                    $data[$columnName] = $formattedDate;
                    break;
                }
            }
        }

        $booleanFields = array(
            'volunteer' => 'volunteer',
            'emailMarketingConsent' => 'emailMarketingConsent',
            'areaMarketingConsent' => 'areaMarketingConsent',
            'groupMarketingConsent' => 'groupMarketingConsent',
            'otherMarketingConsent' => 'otherMarketingConsent',
            'postDirectMarketing' => 'postDirectMarketing',
            'telephoneDirectMarketing' => 'telephoneDirectMarketing',
            'walkProgrammeOptOut' => 'walkProgrammeOptOut',
        );

        foreach ($booleanFields as $inputField => $columnName) {
            if (array_key_exists($columnName, $columns)) {
                $data[$columnName] = $this->booleanToDatabase($member[$inputField] ?? null);
            }
        }

        if (array_key_exists('state', $columns) && !isset($data['state'])) {
            $data['state'] = 1;
        }

        return $data;
    }

    private function createProfileAudit($objectId, $recordType, $fieldName = '', $oldValue = null, $newValue = null) {
        if ((int) $objectId <= 0) {
            return;
        }

        $this->toolsHelper->createAuditRecord($fieldName, $oldValue, $newValue, $objectId, 'ra_profiles');
    }

    private function insertProfile($data) {
        $columns = $this->getProfileColumns();
        $now = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);
        $userId = $this->getCurrentUserId();

        if (array_key_exists('created', $columns) && !array_key_exists('created', $data)) {
            $data['created'] = $now;
        }

        if (array_key_exists('created_by', $columns) && !array_key_exists('created_by', $data)) {
            $data['created_by'] = $userId;
        }

        $quotedColumns = array();
        $quotedValues = array();

        foreach ($data as $columnName => $value) {
            if (!array_key_exists($columnName, $columns)) {
                continue;
            }

            $quotedColumns[] = $this->db->quoteName($columnName);
            $quotedValues[] = $this->quoteValue($value);
        }

        if (empty($quotedColumns)) {
            return null;
        }

        $query = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__ra_profiles'))
                ->columns($quotedColumns)
                ->values(implode(',', $quotedValues));

        $this->db->setQuery($query)->execute();

        return $this->getProfileBySalesforceId($data['salesforceId']);
    }

    private function syncRoles($profile, $member) {
        $columns = $this->getRoleColumns();

        if (!array_key_exists('member_id', $columns)) {
            return;
        }

        $memberId = $this->getProfileReference($profile);

        if ($memberId <= 0) {
            return;
        }

        $deleteQuery = $this->db->getQuery(true)
                ->delete($this->db->quoteName('#__ra_roles'))
                ->where($this->db->quoteName('member_id') . ' = ' . (int) $memberId);

        $this->db->setQuery($deleteQuery)->execute();

        $roleRows = $this->buildRoleRows($member, $memberId);

        foreach ($roleRows as $roleRow) {
            $query = $this->db->getQuery(true)
                    ->insert($this->db->quoteName('#__ra_roles'))
                    ->columns(array(
                        $this->db->quoteName('member_id'),
                        $this->db->quoteName('organisation_code'),
                        $this->db->quoteName('role'),
                    ))
                    ->values(
                    (int) $roleRow['member_id'] . ','
                    . $this->db->quote($roleRow['organisation_code']) . ','
                    . $this->db->quote($roleRow['role'])
            );

            $this->db->setQuery($query)->execute();
        }
    }

    private function syncMember($member) {

        $member = $this->normaliseMember($member);
        $salesforceId = $this->normaliseScalar($member['salesforceId'] ?? null);
        if (JDEBUG) {
            $this->messages[] = 'Syncing member with Salesforce ID: ' . $salesforceId;
        }

        if ($salesforceId === null) {
            $this->logMessage('Skipped record without salesforceId', '3');
            return false;
        }

        $data = $this->mapMemberToProfileData($member);

//         if (JDEBUG) {
//            var_dump($data);
//             echo '<br>';
//         }
        $profile = $this->getProfileBySalesforceId($salesforceId);

        if ($profile === null) {
            $profile = $this->insertProfile($data);

            if ($profile === null) {
                $this->logMessage('Failed to create profile for ' . $salesforceId, '3');
                return false;
            }
            // Find the new profile record, to get the member_id for the audit record
            $profile = $this->getProfileBySalesforceId($salesforceId);
            $this->messages[] = 'Created new profile for Salesforce ID: ' . $salesforceId . ' with member_id ' . $profile->member_id;
            $this->count_new++;
            $this->createProfileAudit($this->getProfileReference($profile), 'C', '', null, '');
        } else {
            $changes = $this->updateProfile($profile, $data);

            if (empty($changes)) {
                $this->count_not_updated++;
            } else {
                $this->count_updated++;

                foreach ($changes as $fieldName => $change) {
                    $this->createProfileAudit($this->getProfileReference($profile), 'U', $fieldName, $change['old'], $change['new']);
                }

                $profile = $this->getProfileBySalesforceId($salesforceId);
            }
        }

        $this->syncRoles($profile, $member);
//var_dump($profile);
//echo '<br><br>';
        if (!empty($member['email'])) {
            $this->checkEmail($member['email'], $profile->member_id, $profile->id);
        }

        return true;
    }

    public function loadMembers($code = 'NS03') {
        $startedAt = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);

        $this->logMessage('Processing ' . $code, 1);
        $this->messages = array();
        $members = $this->getJson($code);
        if ($members === false) {
            $this->messages[] = 'getJson was false for ' . $code;
            return false;
        }
        //       die('Load members for ' . $code . ', got ' . count($members) . ' records');
        $count = $this->processMembers($members);
        $this->updateOrganisationLastUpdated($code, $startedAt);
        $this->messages[] = $count . ' records processed for ' . $code;
        if ($count > 0) {
            $this->messages[] = 'New records ' . $this->count_new . ', Updated records '
                    . $this->count_updated . ', Not updated records ' . $this->count_not_updated
                    . ', Watermark ' . $startedAt;
        }
        return;
    }

    /**
     *   Store a log entry
     */
    public function logMessage($text, $record_type = '3') {

        $query = $this->db->getQuery(true);

        $query->insert('#__ra_logfile')
                ->set("record_type = " . $this->db->quote($record_type))
                ->set("message = " . $this->db->quote($text))
                ->set("sub_system = 'RA Mailman'")
                ->set("ref = " . $this->db->quote('LoadMemb'))
        ;

        $result = $this->db->setQuery($query)->execute();
    }

    public function processMembers($members) {
        $count = 0;
        $this->count_new = 0;
        $this->count_updated = 0;
        //var_dump($members);
        //echo '<br>';
        if (is_object($members) && isset($members->members)) {
            $members = $members->members;
        } elseif (is_array($members) && isset($members['members'])) {
            $members = $members['members'];
        }

        if (!is_array($members) && !($members instanceof \Traversable)) {
            $this->logMessage('No member data returned from feed', '3');
            return 0;
        }

        foreach ($members as $member) {
//            if ($count == 0){
//                var_dump($member);
//                echo '<br>';
//            }
            if ($this->syncMember($member)) {
                $count++;
            }
        }

        return $count;
    }

    private function updateOrganisationLastUpdated($code, $startedAt) {
        $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__ra_organisations'))
                ->set($this->db->quoteName('last_updated') . ' = ' . $this->db->quote($startedAt))
                ->where($this->db->quoteName('code') . ' = ' . $this->db->quote($code));

        $this->db->setQuery($query)->execute();
    }

    private function updateProfile($profile, $data) {
        $changes = array();
        $sets = array();
        $columns = $this->getProfileColumns();

        foreach ($data as $columnName => $newValue) {
            $oldValue = property_exists($profile, $columnName) ? $profile->$columnName : null;

            if ($this->valuesDiffer($oldValue, $newValue)) {
                $changes[$columnName] = array(
                    'old' => $oldValue,
                    'new' => $newValue,
                );
                $sets[] = $this->db->quoteName($columnName) . ' = ' . $this->quoteValue($newValue);
            }
        }

        if (empty($changes)) {
            return $changes;
        }

        $userId = $this->getCurrentUserId();
        $now = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);

        if (array_key_exists('modified', $columns)) {
            $sets[] = $this->db->quoteName('modified') . ' = ' . $this->db->quote($now);
        }

        if (array_key_exists('modified_by', $columns)) {
            $sets[] = $this->db->quoteName('modified_by') . ' = ' . (int) $userId;
        }

        $query = $this->db->getQuery(true)
                ->update($this->db->quoteName('#__ra_profiles'))
                ->set($sets)
                ->where($this->db->quoteName('salesforceId') . ' = ' . $this->db->quote($profile->salesforceId));

        $this->db->setQuery($query)->execute();

        return $changes;
    }

    private function valuesDiffer($oldValue, $newValue) {
        return $this->normaliseScalar($oldValue) !== $this->normaliseScalar($newValue);
    }

}
