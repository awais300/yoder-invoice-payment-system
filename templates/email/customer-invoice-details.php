<div id="customer-invoice-email">
	<p><?php echo $user->user_login; ?>,</p>

	<p>Thank you for paying the following invoice(s):</p>
	<ul>
		<?php if (!empty($invoices)) : ?>
			<?php foreach ($invoices as $invoice_num) : ?>
				<li>(Invoice #<?php echo $invoice_num; ?>)</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>

	<p>Total Paid: $<?php echo $request_data['amount']; ?></p>
	<p>Date: <?php echo $date; ?></p>
</div>