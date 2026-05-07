<?php

/**
 * @version    1.0.0
 * @package    com_ra_members
 */

namespace Ramblers\Component\Ra_members\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

class OrganisationTable extends Table implements VersionableTableInterface, TaggableTableInterface
{
    use TaggableTableTrait;

    protected $_supportNullValue = true;

    public function __construct(DatabaseDriver $db)
    {
        $this->typeAlias = 'com_ra_members.organisation';
        parent::__construct('#__ra_organisations', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    public function getTypeAlias()
    {
        return $this->typeAlias;
    }

    public function bind($array, $ignore = '')
    {
        $user = Factory::getApplication()->getIdentity();

        if (!empty($array['nation_id'])) {
            if (is_array($array['nation_id'])) {
                $array['nation_id'] = implode(',', $array['nation_id']);
            } elseif (strrpos($array['nation_id'], ',') !== false) {
                $array['nation_id'] = explode(',', $array['nation_id']);
            }
        } else {
            $array['nation_id'] = 0;
        }

        if (($array['id'] ?? 0) == 0 && empty($array['created_by'])) {
            $array['created_by'] = Factory::getUser()->id;
        }

        if (isset($array['cluster'])) {
            $array['cluster'] = strtoupper(trim((string) $array['cluster']));
        }

        if (!empty($array['logo'])) {
            $array['logo'] = $this->prepareLogoPath($array['logo']);
        }

        if (isset($array['params']) && is_array($array['params'])) {
            $registry = new Registry;
            $registry->loadArray($array['params']);
            $array['params'] = (string) $registry;
        }

        if (isset($array['metadata']) && is_array($array['metadata'])) {
            $registry = new Registry;
            $registry->loadArray($array['metadata']);
            $array['metadata'] = (string) $registry;
        }

        if (!$user->authorise('core.admin', 'com_ra_members.organisation.' . ($array['id'] ?? 0))) {
            $actions = Access::getActionsFromFile(JPATH_ADMINISTRATOR . '/components/com_ra_members/access.xml', "/access/section[@name='component']/");
            $defaultActions = Access::getAssetRules('com_ra_members.organisation.' . ($array['id'] ?? 0))->getData();
            $arrayJaccess = [];

            foreach ($actions as $action) {
                if (key_exists($action->name, $defaultActions)) {
                    $arrayJaccess[$action->name] = $defaultActions[$action->name];
                }
            }

            $array['rules'] = $this->JAccessRulestoArray($arrayJaccess);
        }

        if (isset($array['rules']) && is_array($array['rules'])) {
            $this->setRules($array['rules']);
        }

        return parent::bind($array, $ignore);
    }

    private function prepareLogoPath($logo)
    {
        $logo = trim((string) $logo);

        if ($logo === '') {
            return '';
        }

        $targetDirectoryRelative = 'images/com_ra_mailman';
        $targetDirectoryAbsolute = JPATH_ROOT . '/' . $targetDirectoryRelative;
        $logoRelative = $this->normaliseLogoRelativePath($logo);

        if (!Folder::exists($targetDirectoryAbsolute)) {
            Folder::create($targetDirectoryAbsolute);
        }

        $filename = basename(str_replace('\\', '/', $logoRelative));

        if ($filename === '') {
            return '';
        }

        $targetAbsolute = $targetDirectoryAbsolute . '/' . $filename;
        $sourceAbsolute = JPATH_ROOT . '/' . ltrim($logoRelative, '/');

        if (File::exists($sourceAbsolute) && $sourceAbsolute !== $targetAbsolute) {
            File::copy($sourceAbsolute, $targetAbsolute, null, true);

            return $filename;
        }

        if (File::exists($targetAbsolute) || $sourceAbsolute === $targetAbsolute) {
            return $filename;
        }

        return $filename;
    }

    private function normaliseLogoRelativePath($logo)
    {
        $parts = explode('#', trim((string) $logo));

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $part = preg_replace('/\?.*$/', '', $part);

            if (strpos($part, 'joomlaImage://') === 0) {
                $part = preg_replace('#^joomlaImage://[^/]+/#', '', $part);
            }

            $part = ltrim($part, '/');

            if (strpos($part, 'local-images/') === 0) {
                return 'images/' . substr($part, strlen('local-images/'));
            }

            if ($part !== '') {
                return $part;
            }
        }

        return '';
    }

    public function store($updateNulls = true)
    {
        if ($this->id > 0) {
            $this->modified_by = Factory::getApplication()->getSession()->get('user')->id;
            $this->modified = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);
        }

        return parent::store($updateNulls);
    }

    private function JAccessRulestoArray($jaccessrules)
    {
        $rules = [];

        foreach ($jaccessrules as $action => $jaccess) {
            $actions = [];

            if ($jaccess) {
                foreach ($jaccess->getData() as $group => $allow) {
                    $actions[$group] = (bool) $allow;
                }
            }

            $rules[$action] = $actions;
        }

        return $rules;
    }

    public function check()
    {
        if (property_exists($this, 'ordering') && $this->id == 0) {
            $this->ordering = self::getNextOrder();
        }

        return parent::check();
    }

    protected function _getAssetName()
    {
        $k = $this->_tbl_key;

        return $this->typeAlias . '.' . (int) $this->$k;
    }

    protected function _getAssetParentId($table = null, $id = null)
    {
        $assetParent = Table::getInstance('Asset');
        $assetParentId = $assetParent->getRootId();
        $assetParent->loadByName('com_ra_members');

        if ($assetParent->id) {
            $assetParentId = $assetParent->id;
        }

        return $assetParentId;
    }

    public function delete($pk = null)
    {
        $this->load($pk);

        return parent::delete($pk);
    }
}