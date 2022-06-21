jQuery(document).ready(function($) {
    $(document).on('click', '#yoder-pay-online .invoice-box', function(e) {
        total = calculate_total_fee();
        display_totals(total);
    });

    function calculate_total_fee() {
        var total = 0;
        $('#yoder-pay-online .invoice-box').each(
            function(i, obj) {
                $this = $(this);
                var value = Number($this.parent().next().next().text());
                let is_checked = $this.is(':checked');

                if (is_checked === true) {
                    total = total + value;
                }
            }
        );

        return total;
    }

    function display_totals(total) {

        // Total fee
        total = total.toFixed(2);

        // Convenience fee (3% of total)
        cfee = 0.03 * total;
        cfee = cfee.toFixed(2);

        total_fee = Number(total) + Number(cfee);

        $('#yoder-pay-online .fee span').text(total_fee);
        $('#yoder-pay-online #total_fee').val(total_fee);

        $('#yoder-pay-online .cfee span').text(cfee);
        $('#yoder-pay-online #convenience_fee').val(cfee);
    }
});