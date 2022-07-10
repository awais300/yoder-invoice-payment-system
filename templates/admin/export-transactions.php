<?php if ($export_obj->has_error($result)) : ?>
	<div class="info">
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html($result['error_message']); ?></p>
		</div>
	</div>
<?php endif; ?>

<?php
$today = date('Y-m-d');
$next_day = date('Y-m-d', strtotime($today . ' +1 day'));
?>
<div class="t-data">
	<h1><?php _e('Export PayTrace Transactions', 'yips-customization'); ?></h1>
	<form name="form1" id="form1" method="post" action="">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="start_date"><?php _e('Start date', 'yips-customization'); ?></label></th>
					<td><input required type="date" name="start_date" type="text" id="start_date" value="<?php echo $today; ?>"></td>
				</tr>

				<tr>
					<th scope="row"><label for="end_date"><?php _e('Start date', 'yips-customization'); ?></label></th>
					<td><input required type="date" name="end_date" type="text" id="end_date" value="<?php echo $next_day; ?>"></td>
				</tr>
				<tr>
					<td style="padding: 0;">
						<?php submit_button(__('Download', 'yips-customization'), 'primary', 'submit_transactions_export'); ?>
					</td>
				</tr>
			</tbody>
			<?php wp_nonce_field('export-nonce'); ?>

		</table>
	</form>
</div>