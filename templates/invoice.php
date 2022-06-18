<div id="title">
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
        <div class="ycolumn right">
            <div class="right-content">
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
    <form id="yoder-invoice-form" name="yoder-invoice-form" action="" method="post">
        <div class="ytable">
            <div class="table-info">
                <h2>Open Invoices</h2>
                <p class="info">Please select the Invoice(s) you would like to make payment on and click the button bellow.</p>
            </div>

            <table class="w3-table w3-bordered">
                <tr class="heading">
                    <td></td>
                    <td>Invoice nr.</td>
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
                    <td>250</td>
                </tr>
                <tr class="table-data">
                    <td><input class="invoice-box" type="checkbox" name="invoice[]" class="invoice"></td>
                    <td>#226</td>
                    <td>900</td>
                </tr>
            </table>

            <div class="table-bottom">
                <div class="bottom-content">
                    <div id="cfee" class="cfee yfee">
                        <p>3% Convenience Fee: <span>0</span></p>
                    </div>
                    <div id="total-fee" class="fee yfee">
                        <p>Total: <span>0</span></p>
                    </div>
                </div>
                <input id="total_fee" name="total_fee" type="hidden" value="0" />
                <input id="convenience_fee" name="convenience_fee" type="hidden" value="0" />
                <div class="ybutton">
                    <button type="submit" class="button-submit">Pay Selected Invoice(s)</button>
                </div>
            </div>
        </div>
    </form>
</div>