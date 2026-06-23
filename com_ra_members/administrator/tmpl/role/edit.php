<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ra_members
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
        ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');
?>

<form
    action="<?php echo Route::_('index.php?option=com_ra_members&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="role-form" class="form-validate form-horizontal">


    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'option')); ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'option', Text::_('COM_RA_MEMBERS_TAB_OPTION', true)); ?>
    <div class="row-fluid">
        <div class="col-md-12 form-horizontal">
            <fieldset class="adminform">
                <legend><?php echo Text::_('COM_RA_MEMBERS_FIELDSET_OPTION'); ?></legend>
                <?php
                echo $this->form->renderField('organisation_code');
                echo $this->form->renderField('group');
                echo $this->form->renderField('member_id');
                echo $this->form->renderField('member_name');
                echo $this->form->renderField('role');
                ?>
            </fieldset>
        </div>
    </div>
<?php echo HTMLHelper::_('uitab.endTab'); ?>
    <input type="hidden" name="jform[id]" value="<?php echo isset($this->item->id) ? $this->item->id : ''; ?>" />

<?php echo $this->form->renderField('created_by'); ?>
    <?php echo $this->form->renderField('modified_by'); ?>


<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="task" value=""/>
<?php echo HTMLHelper::_('form.token'); ?>

</form>
