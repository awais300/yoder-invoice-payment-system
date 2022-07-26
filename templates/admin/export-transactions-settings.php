<?php

namespace Yoder\YIPS;

use Yoder\YIPS\Cron\Cron;
use Yoder\YIPS\Admin\ExportTransactions;

$settings = get_option((ExportTransactions::instance())::EXPORT_SETTINGS);
?>
<div class="t-settings">
	<h1><?php _e('Export Settings', 'yips-customization'); ?></h1>
	<form name="form1" id="form1" method="post" action="">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="Email"><?php _e('Email', 'yips-customization'); ?></label></th>
					<td>
						<input class="regular-text" type="email" name="export-email" id="export-email" value="<?php echo esc_attr($settings['export-email']); ?>">
						<p>
							<i><?php _e('The automatic CSV export will be sent to this email address.', 'yips-customization'); ?></i>
						</p>
						<p>
							<i><?php _e('Default email: ' . (Cron::instance())::DEFAULT_EMAIL); ?></i>
						</p>
					</td>
				</tr>
				<tr>
					<td style="padding: 0;">
						<?php submit_button(__('Save Settings', 'yips-customization'), 'primary', 'submit_transactions_export'); ?>
					</td>
				</tr>
			</tbody>
			<?php wp_nonce_field('export-nonce'); ?>

		</table>
	</form>
</div>