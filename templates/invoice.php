<?php if (isset($payment['transaction_success']) && $payment['transaction_success'] === true) : ?>
    <script>
        window.location.replace('<?php echo $thankyou_page; ?>');
    </script>
<?php endif; ?>
<div id="title" style="display: none;">
    <div class="container">
        <div class="ten columns">
            <h1><?php the_title(); ?></h1>
        </div>
    </div>
</div>

<div id="yoder-pay-online" class="invoice-page">
    <div class="yrow">
        <div class="ycolumn left">
            <p>Account# <span>2548796</span></p>
            <p class="ybold">Company Name</p>
        </div>
        <div class="ycolumn right vertical-center">
            <div class="right-content vertical-center">
                <div class="inner-col icon">
                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                </div>
                <div class="inner-col text">
                    <p>123 Organizatin Road</p>
                    <p>Elkhard, IN 58742</p>
                </div>
            </div>
        </div>
    </div>
    <form id="ProtectForm" name="ProtectForm" action="" method="post">
        <div class="ytable">
            <div class="table-info">
                <h2>Open Invoices</h2>
                <p class="info">Please select the Invoice(s) you would like to make payment on and click the button bellow.</p>
            </div>

            <div class="w3-responsive">
                <table class="w3-bordered">
                    <tr class="heading">
                        <td></td>
                        <td>Invoice</td>
                        <td>Amount Due</td>
                    </tr>
                    <tr class="table-data">
                        <td><input class="invoice-box" type="checkbox" name="invoice[]" class="invoice"></td>
                        <td>#222</td>
                        <td>800.29</td>
                    </tr>
                    <tr class="table-data">
                        <td><input class="invoice-box" type="checkbox" name="invoice[]" class="invoice"></td>
                        <td>#225</td>
                        <td>250.00</td>
                    </tr>
                    <tr class="table-data">
                        <td><input class="invoice-box" type="checkbox" name="invoice[]" class="invoice"></td>
                        <td>#226</td>
                        <td>900.00</td>
                    </tr>
                </table>
            </div>

            <div class="table-bottom">
                <div class="bottom-content">
                    <div id="cfee" class="cfee yfee">
                        <p>3% Convenience Fee: <span>0</span></p>
                    </div>
                    <div id="total-fee" class="fee yfee">
                        <p>Total: <span>0</span></p>
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
                                    'border_color': '#ca1631',
                                    'label_color': '#000',
                                    'label_size': '20px',
                                    'background_color': 'white',
                                    'border_style': 'solid',
                                    'font_size': '15pt',
                                    'height': '30px',
                                    'width': '64px'
                                },
                                'cc': {
                                    'font_color': '#323232',
                                    'border_color': '#ca1631',
                                    'label_color': '#000',
                                    'label_size': '20px',
                                    'background_color': 'white',
                                    'border_style': 'solid',
                                    'font_size': '15pt',
                                    'height': '30px',
                                    'width': '250px'
                                },
                                'exp': {
                                    'font_color': '#323232',
                                    'border_color': '#ca1631',
                                    'label_color': '#000',
                                    'label_size': '20px',
                                    'background_color': 'white',
                                    'border_style': 'solid',
                                    'font_size': '15pt',
                                    'height': '30px',
                                    'width': '64px',
                                    'type': 'dropdown'
                                }
                            },
                            authorization: {
                                'clientKey': "<?php echo $client_key; ?>"
                            }
                        }).then(function(instance) {
                            PTPayment.getControl("securityCode").label.text("CSC");
                            PTPayment.getControl("creditCard").label.text("CC#");
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

                                // To trigger the validation of sensitive data payment fields within the iframe before calling the tokenization process:
                                PTPayment.validate(function(validationErrors) {
                                    if (validationErrors.length >= 1) {
                                        if (validationErrors[0]['responseCode'] == '35') {
                                            // Handle validation Errors here
                                            // This is an example of using dynamic styling to show the Credit card number entered is invalid
                                            instance.style({
                                                'cc': {
                                                    'border_color': 'red'
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
                            alert(JSON.stringify(err));
                        }

                        function submitPayment(r) {

                            var hpf_token = document.getElementById("HPF_Token");
                            var enc_key = document.getElementById("enc_key");
                            hpf_token.value = r.message.hpf_token;
                            enc_key.value = r.message.enc_key;
                            document.getElementById("ProtectForm").submit();

                        }
                    </script>
                </div>
                <input type="hidden" id="HPF_Token" name="HPF_Token">
                <input type="hidden" id="enc_key" name="enc_key">
                <input type="hidden" id="invoice_submit" name="invoice_submit" value="Submit">
                <?php wp_nonce_field('invoice-nonce'); ?>
            </div>

            <div class="ybutton paytrace-form">
                <button type="submit" id="SubmitButton" name="invoice_btn" value="" class="button-submit">Pay Selected Invoice(s)</button>
            </div>

            <?php if (isset($payment['error_message']) && !empty($payment['error_message'])) : ?>
                <div class="w3-panel w3-pale-red w3-display-container w3-border">
                    <span onclick="this.parentElement.style.display='none'" class="w3-button w3-large w3-display-topright">×</span>
                    <p><?php echo $payment['error_message']; ?></p>
                </div>
            <?php endif; ?>
    </form>
</div>