<?php
defined('_JEXEC') or die;
?>

<div class="item_fields">
	<table class="table table-striped">
		<thead>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$displayFields = [
				'id' => 'ID',
				'code' => 'Code',
				'name' => 'Name',
				'nation_id' => 'Nation',
				'details' => 'Details',
				'website' => 'Website',
				'co_url' => 'CO URL',
				'cluster' => 'Cluster',
				'latitude' => 'Latitude',
				'longitude' => 'Longitude',
				'email_header' => 'Email Header',
				'logo' => 'Logo',
				'logo_align' => 'Logo Alignment',
				'colour_header' => 'Header Colour',
				'colour_body' => 'Body Colour',
				'colour_footer' => 'Footer Colour',
			];

			$colorFields = ['colour_header', 'colour_body', 'colour_footer'];

			foreach ($displayFields as $fieldName => $fieldLabel) {
				if (!isset($this->item->$fieldName)) {
					continue;
				}

				$value = $this->item->$fieldName;
				echo '<tr>' . PHP_EOL;
				echo '<td><strong>' . htmlspecialchars($fieldLabel) . '</strong></td>' . PHP_EOL;

				if (in_array($fieldName, $colorFields, true) && !empty($value)) {
					echo '<td style="background-color: ' . htmlspecialchars($value) . '; padding: 20px; min-height: 40px; border-radius: 4px;">';
					echo '<code style="background: rgba(255,255,255,0.8); padding: 4px 8px; border-radius: 2px;">' . htmlspecialchars($value) . '</code>';
					echo '</td>' . PHP_EOL;
				} else {
					if ($fieldName === 'website' || $fieldName === 'co_url' || $fieldName === 'logo') {
						if (!empty($value)) {
							if ($fieldName === 'logo') {
								$logo = (strpos($value, '/') === false) ? 'images/com_ra_mailman/' . $value : $value;
								echo '<td><a href="' . htmlspecialchars($logo) . '" target="_blank">' . htmlspecialchars($value) . '</a><br>';
								echo '<img src="' . htmlspecialchars($logo) . '" alt="Logo preview" style="max-width: 180px; max-height: 120px; margin-top: 8px;" />';
								echo '</td>' . PHP_EOL;
							} else {
								echo '<td><a href="' . htmlspecialchars($value) . '" target="_blank">' . htmlspecialchars($value) . '</a></td>' . PHP_EOL;
							}
						} else {
							echo '<td><em>Empty</em></td>' . PHP_EOL;
						}
					} else {
						echo '<td>' . (!empty($value) ? htmlspecialchars($value) : '<em>Empty</em>') . '</td>' . PHP_EOL;
					}
				}

				echo '</tr>' . PHP_EOL;
			}
			?>
		</tbody>
	</table>
</div>