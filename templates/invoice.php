<?php if (isset($payment['transaction_success']) && $payment['transaction_success'] === true) : ?>
    <script>
        window.location.replace('<?php echo '/' . $thankyou_page; ?>');
    </script>
<?php endif; ?>

<?php
$customer_cat = '';
if ($invoice_obj->has_customer($customer)) {
    $customer_data = $customer['customer'];
    if (isset($customer_data['UDF_CUSTCAT'])) {
        $customer_cat = $customer_data['UDF_CUSTCAT'];
    }
    $customer_cat = trim($customer_cat);
    echo "<script>const YODER_YIPS = { customer_cat: '{$customer_cat}' };</script>";
}
?>

<div id="title" style="display: none;">
    <div class="container">
        <div class="ten columns">
            <h1><?php //the_title(); 
                ?></h1>
        </div>
    </div>
</div>

<div id="yoder-pay-online" class="invoice-page">
    <?php if ($invoice_obj->has_customer($customer)) : $customer_data = $customer['customer']; ?>
        <div class="yrow">
            <div class="ycolumn left">
                <p>Account# <span><?php echo esc_html($customer_data['CustomerNo']); ?></span></p>
                <p class="ybold"><?php echo esc_html($customer_data['CustomerName']); ?></p>
            </div>
            <div class="ycolumn right vertical-center">
                <div class="right-content vertical-center">
                    <div class="inner-col icon">
                        <i class="fa fa-map-marker" aria-hidden="true"></i>
                    </div>
                    <div class="inner-col text">
                        <p><?php echo esc_html($invoice_obj->get_customer_address($customer)); ?></p>
                        <p><?php echo esc_html($customer_data['City']) ?>, <?php echo esc_html($customer_data['State']); ?> <?php echo esc_html($customer_data['ZipCode']); ?></p>
                    </div>
                </div>
            </div>
            <div class="ycolumn last"><a href="<?php echo esc_url($logout_url); ?>">Logout</a></div>
        </div>
    <?php endif; ?>
    <?php if ($invoice_obj->has_error($customer)) : ?>
        <div class="yrow">
            <div class="w3-panel w3-pale-red w3-display-container w3-border">
                <span onclick="this.parentElement.style.display='none'" class="w3-button w3-large w3-display-topright">×</span>
                <h6><?php echo esc_html($customer['error_message']); ?></h6>
            </div>
        </div>
    <?php endif; ?>

    <form id="ProtectForm" name="ProtectForm" action="" method="post">
        <div class="ytable">
            <div class="table-info">
                <h2><?php _e('Open Invoices', 'yips-customization'); ?></h2>
                <p class="info"><?php _e('Please select the Invoice(s) you would like to make payment on and click the button bellow.', 'yips-customization'); ?></p>
            </div>

            <div class="w3-responsive">
                <table class="w3-bordered">
                    <tr class="heading">
                        <td class="all-toggle"><input type="checkbox" id="select-all" name="select-all"><?php _e('Select All', 'yips-customization'); ?></td>
                        <td><?php _e('Invoice', 'yips-customization'); ?></td>
                        <td><?php _e('Due Date', 'yips-customization'); ?></td>
                        <td><?php _e('Amount Due', 'yips-customization'); ?></td>
                    </tr>
                    <?php
                    if (!$invoice_obj->has_error($invoices) && $invoice_obj->has_invoice($invoices)) :
                        foreach ($invoices['invoices'] as $invoice) :
                            $invoice_num = $invoice['InvoiceNo'];
                            $invoice_amount = $invoice['Amount'];
                            $invoice_due_date = $invoice['InvoiceDueDate'];
                            $invoice_amount_formatted = number_format($invoice['Amount'], 2, '.', ',');
                    ?>
                            <tr class="table-data">
                                <td><input class="invoice-box" type="checkbox" name="invoice[<?php echo $invoice_num; ?>]" value="<?php echo esc_attr($invoice_amount); ?>" class="invoice"></td>
                                <td>#<?php echo esc_html($invoice_num); ?></td>
                                <td><?php echo esc_html($invoice_obj->get_formatted_date($invoice_due_date)); ?></td>
                                <td><?php echo esc_html($invoice_amount_formatted);  ?></td>
                            </tr>
                        <?php
                        endforeach;
                    else :
                        ?>
                    <?php endif; ?>
                </table>
                <?php if (!$invoice_obj->has_invoice($invoices)) : ?>
                    <div class="w3-panel w3-pale-green w3-display-container w3-border">
                        <span onclick="this.parentElement.style.display='none'" class="w3-button w3-large w3-display-topright">×</span>
                        <h6><?php _e('No pending invoice found.', 'yips-customization'); ?></h6>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($invoice_obj->has_invoice($invoices)) : ?>
                <h2 class="sub-heading"><?php _e('Pay selected invoices online', 'yips-customization'); ?></h2>
                <div class="table-bottom">
                    <div class="bottom-content">
                        <?php if ($customer_cat === 'D') : ?>
                            <div id="cfee" class="cfee yfee">
                                <p><?php _e('3% Convenience Fee: ', 'yips-customization'); ?><span>0</span></p>
                            </div>
                        <?php endif; ?>
                        <div id="total-fee" class="fee yfee">
                            <p><?php _e('Total: '); ?><span>0</span></p>
                        </div>
                    </div>
                    <input id="total_fee" name="amount" type="hidden" value="0" />
                    <input id="convenience_fee" name="convenience_fee" type="hidden" value="0" />
                </div>

                <div class="paytrace-form">
                    <div id='pt_hpf_form'>
                        <script>
                            PTPayment.setup({
                                styles: {
                                    'code': {
                                        'font_color': '#323232',
                                        'border_color': '#ccc',
                                        'label_color': '#000',
                                        'label_size': '20px',
                                        'background_color': 'white',
                                        'border_style': 'solid',
                                        'input_border_width': '1px',
                                        'font_size': '13pt',
                                        'label_size': '17px',
                                        'height': '26px',
                                        'width': '75px'
                                    },
                                    'cc': {
                                        'font_color': '#323232',
                                        'input_margin': '0 0 10px 0',
                                        'border_color': '#ccc',
                                        'label_color': '#000',
                                        'label_size': '20px',
                                        'background_color': 'white',
                                        'border_style': 'solid',
                                        'input_border_width': '1px',
                                        'font_size': '13pt',
                                        'label_size': '17px',
                                        'height': '30px',
                                        'width': '250px'
                                    },
                                    'exp': {
                                        'font_color': '#323232',
                                        'border_color': '#ccc',
                                        'label_color': '#000',
                                        'label_size': '20px',
                                        'background_color': 'white',
                                        'border_style': 'solid',
                                        'input_border_width': '1px',
                                        'font_size': '13pt',
                                        'label_size': '17px',
                                        'height': '30px',
                                        'width': '75px',
                                        'type': 'dropdown'
                                    }
                                },
                                authorization: {
                                    'clientKey': "<?php echo $client_key; ?>"
                                }
                            }).then(function(instance) {
                                PTPayment.getControl("securityCode").label.text("CVV/CVC/CSC");
                                PTPayment.getControl("creditCard").label.text("Card Number");
                                PTPayment.getControl("expiration").label.text("Exp Date");

                                //PTPayment.style({'cc': {'label_color': 'red'}});
                                //PTPayment.style({'code': {'label_color': 'red'}});
                                //PTPayment.style({'exp': {'label_color': 'red'}});
                                //PTPayment.style({'exp':{'type':'dropdown'}});

                                //PTPayment.theme('horizontal');
                                // this can be any event we chose. We will use the submit event and stop any default event handling and prevent event handling bubbling.
                                document.getElementById("ProtectForm").addEventListener("submit", function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    disable_btn();

                                    // To trigger the validation of sensitive data payment fields within the iframe before calling the tokenization process:
                                    PTPayment.validate(function(validationErrors) {
                                        if (validationErrors.length >= 1) {
                                            if (validationErrors[0]['responseCode'] == '35') {
                                                PTPayment.style({
                                                    'cc': {
                                                        'border_color': 'red'
                                                    }
                                                });
                                                enable_btn();
                                            } else {
                                                PTPayment.style({
                                                    'cc': {
                                                        'border_color': '#ccc'
                                                    }
                                                });
                                            }

                                            if (validationErrors[0]['responseCode'] == '44' || validationErrors[0]['responseCode'] == '43') {
                                                PTPayment.style({
                                                    'exp': {
                                                        'border_color': 'red'
                                                    }
                                                });
                                                enable_btn();
                                            } else {
                                                PTPayment.style({
                                                    'exp': {
                                                        'border_color': '#ccc'
                                                    }
                                                });
                                            }
                                        } else {
                                            // no error so tokenize
                                            instance.process()
                                                .then((r) => {
                                                    submitPayment(r);
                                                }, (err) => {
                                                    handleError(err);
                                                });
                                        }
                                    });

                                });
                            });


                            function handleError(err) {
                                enable_btn();
                                alert(JSON.stringify(err));
                            }

                            function submitPayment(r) {
                                var hpf_token = document.getElementById("HPF_Token");
                                var enc_key = document.getElementById("enc_key");
                                hpf_token.value = r.message.hpf_token;
                                enc_key.value = r.message.enc_key;
                                document.getElementById("ProtectForm").submit();
                            }

                            function disable_btn() {
                                var btn = document.getElementById('SubmitButton');
                                btn.disabled = true;
                                btn.classList.add('btn_disable');
                            }

                            function enable_btn() {
                                var btn = document.getElementById('SubmitButton');
                                btn.disabled = false;
                                btn.classList.remove('btn_disable');
                            }
                        </script>
                    </div>
                    <input type="hidden" id="HPF_Token" name="HPF_Token">
                    <input type="hidden" id="enc_key" name="enc_key">
                    <input type="hidden" id="invoice_submit" name="invoice_submit" value="Submit">
                    <?php wp_nonce_field('invoice-nonce'); ?>
                </div>

                <div class="ybutton paytrace-form">
                    <button type="submit" id="SubmitButton" name="invoice_btn" value="" class="button-submit"><?php _e('Pay Selected Invoice(s)', 'yips-customization'); ?></button>
                </div>

                <?php if (isset($payment['error_message']) && !empty($payment['error_message'])) : ?>
                    <div class="w3-panel w3-pale-red w3-display-container w3-border">
                        <span onclick="this.parentElement.style.display='none'" class="w3-button w3-large w3-display-topright">×</span>
                        <h6><?php echo esc_html($payment['error_message']); ?></h6>
                    </div>
                <?php endif; ?>
            <?php endif; //has_invoice 
            ?>
    </form>
</div>