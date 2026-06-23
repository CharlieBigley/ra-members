<?php
/**
 * @version    1.0.1
 * @package    com_ra_members
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
use \Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;


?>

<div class="item_fields">
<?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>
    </div>
    <?php endif;?>
	<table class="table">
		

		<tr>
			<th><?php echo Text::_('COM_RA_MEMBERS_FORM_LBL_MEMBER_PREFERRED_NAME'); ?></th>
			<td><?php echo $this->item->preferred_name; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_RA_MEMBERS_FORM_LBL_MEMBER_MEMBERSHIPEXPIRYDATE'); ?></th>
			<td>				<?php
			$date = $this->item->membershipexpirydate;
			echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC4')) : '-';
			?>

			</td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_RA_MEMBERS_FORM_LBL_MEMBER_MEMBERSHIP_NUMBER'); ?></th>
			<td><?php echo $this->item->membership_number; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_RA_MEMBERS_FORM_LBL_MEMBER_HOME_GROUP'); ?></th>
			<td><?php echo $this->item->home_group; ?></td>
		</tr>

	</table>

</div>

