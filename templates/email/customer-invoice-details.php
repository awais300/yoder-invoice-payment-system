<div id="customer-invoice-email">
	<p>Dear Valued Customer,</p>

	<p>Thank you for paying the following invoice(s):</p>
	<ul>
		<?php if (!empty($invoices)) : ?>
			<?php foreach ($invoices as $invoice_num) : ?>
				<li>(Invoice #<?php echo $invoice_num; ?>)</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>

	<p>Transaction ID: <?php echo $transaction->transaction_id; ?></p>
	<p>Total Paid: $<?php echo $request_data['amount']; ?></p>
	<p>Date: <?php echo $date; ?></p>
	<p>Customer: <?php echo $customer_name; ?></p>
</div>