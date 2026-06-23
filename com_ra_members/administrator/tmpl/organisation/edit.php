<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
        ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');
// Import CSS
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
?>

<form
    action="<?php echo Route::_('index.php?option=com_ra_members&view=organisation&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate form-horizontal">

    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'organisation']); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'organisation', Text::_('Organisation', true)); ?>
    <div class="row-fluid">
        <div class="col-md-12 form-horizontal">
            <fieldset class="adminform">
                <?php
                echo $this->form->renderField('nation_id');
                echo $this->form->renderField('code');
                echo $this->form->renderField('name');
                echo $this->form->renderField('details');
                echo $this->form->renderField('mailman_active');
                echo $this->form->renderField('website');
                echo $this->form->renderField('co_url');

                if ($this->item->record_type === 'A') {
                    echo $this->form->renderField('cluster');
                }

                echo $this->form->renderField('latitude');
                echo $this->form->renderField('longitude');
                ?>
            </fieldset>
        </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'email_header', Text::_('Email Header', true)); ?>
    <div class="row-fluid">
        <div class="col-md-12 form-horizontal">
            <fieldset class="adminform">
                <?php if ($this->form->getField('email_header')) : ?>
                    <?php echo $this->form->renderField('email_header'); ?>
                <?php endif; ?>
                <?php echo $this->form->renderField('logo'); ?>
                <?php if ($this->form->getField('logo_align')) : ?>
                    <?php echo $this->form->renderField('logo_align'); ?>
                <?php endif; ?>
                <?php if ($this->form->getField('colour_header')) : ?>
                    <?php echo $this->form->renderField('colour_header'); ?>
                <?php endif; ?>
                <?php if ($this->form->getField('colour_body')) : ?>
                    <?php echo $this->form->renderField('colour_body'); ?>
                <?php endif; ?>
                <?php if ($this->form->getField('colour_footer')) : ?>
                    <?php echo $this->form->renderField('colour_footer'); ?>
                <?php endif; ?>
            </fieldset>
        </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'welcome_letter', Text::_('Welcome Letter', true)); ?>
    <div class="row-fluid">
        <div class="col-md-12 form-horizontal">
            <fieldset class="adminform">
                <?php if ($this->form->getField('welcome_letter')) : ?>
                    <?php echo $this->form->renderField('welcome_letter'); ?>
                <?php endif; ?>
            </fieldset>
        </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'reminder_letter', Text::_('Reminder Letter', true)); ?>
    <div class="row-fluid">
        <div class="col-md-12 form-horizontal">
            <fieldset class="adminform">
                <?php if ($this->form->getField('reminder_letter')) : ?>
                    <?php echo $this->form->renderField('reminder_letter'); ?>
                <?php endif; ?>
            </fieldset>
        </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php
    echo HTMLHelper::_('uitab.addTab', 'myTab', 'roles', Text::_('Roles', true));

    $target = 'administrator/index.php?option=com_ra_members&view=role&layout=edit&code=' . $this->item->code;
    echo $this->toolsHelper->buildButton($target, 'Add Role');
    $target_delete = 'administrator/index.php?option=com_ra_members&task=organisation.deleteRole&code=' . $this->item->code . '&id=';
    $sql = 'SELECT r.id, r.*, p.preferred_name ';
    $sql .= 'FROM #__ra_roles AS r ';
    $sql .= 'LEFT JOIN #__ra_profiles AS p ON p.id=r.member_id ';
    $sql .= 'WHERE r.organisation_code = ' . Factory::getContainer()->get('DatabaseDriver')->quote($this->item->code) . ' ';
    $sql .= 'ORDER BY r.role ';
    //   echo $sql . '<br>';
    $rows = $this->toolsHelper->getRows($sql);
    if ($rows) {
        $table = new ToolsTable;
        $table->add_header('Role,Member,Last updated,Action');
        $rows = $this->toolsHelper->getRows($sql);

        foreach ($rows as $row) {
            $table->add_item($row->role);
            $table->add_item($row->preferred_name);
            $table->add_item(HTMLHelper::_('date', $row->last_updated, 'd M y'));
            $link = $this->toolsHelper->buildButton($target_delete . $row->id, 'Delete Role', false, 'red');
            $table->add_item($link);
            $table->generate_line();
        }
        $table->generate_table();
    } else {
        echo 'No roles found<br>';
    }

    echo HTMLHelper::_('uitab.endTab');
    ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', Text::_('Publishing', true)); ?>
    <div class="row-fluid">
        <div class="col-md-12 form-horizontal">
            <fieldset class="adminform">
                <?php echo $this->form->renderField('id'); ?>
                <?php echo $this->form->renderField('state'); ?>
                <?php echo $this->form->renderField('created'); ?>
                <?php echo $this->form->renderField('created_by'); ?>
                <?php echo $this->form->renderField('modified'); ?>
                <?php echo $this->form->renderField('modified_by'); ?>
                <?php echo $this->form->renderField('last_updated'); ?>
            </fieldset>
        </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>

</form>