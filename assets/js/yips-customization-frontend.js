jQuery(document).ready(function($) {
    $(document).on('click', '#yoder-pay-online .invoice-box', function(e) {
        total = calculate_total_fee();
        display_totals(total);

        $this = $(this);
        if ($this.is(':checked') === true) {
            box_checked($this);
        } else {
            box_unchecked($this);
        }
    });

    $(document).on('click', '#SubmitButton', function(e) {
        if (!is_invoice_selected()) {
            e.preventDefault();
            return false;
        } else {
            return true;
        }
    });

    function calculate_total_fee() {
        var total = 0;
        $('#yoder-pay-online .invoice-box').each(
            function(i, obj) {
                $this = $(this);
                var value = Number($this.val());
                let is_checked = $this.is(':checked');

                if (is_checked === true) {
                    total = total + value;
                }
            }
        );

        return total;
    }

    function display_totals(total) {
        total = Number(total);

        // Total fee.
        total = round(total, 2);
        cfee = round(0.03 * total, 2); // Convenience fee (3% of total).

        total_fee_with_convenience = round(Number(total) + Number(cfee), 2);

        total_fee_with_convenience = Number(total_fee_with_convenience).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        total_fee_without_convenience = Number(total).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        cfee = Number(cfee).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (YODER_YIPS.customer_cat === 'D') {
            $('#yoder-pay-online .fee span').text(total_fee_with_convenience);
            $('#yoder-pay-online #total_fee').val(total_fee_with_convenience);

            $('#yoder-pay-online .cfee span').text(cfee);
            $('#yoder-pay-online #convenience_fee').val(cfee);
        } else {
            $('#yoder-pay-online .fee span').text(total_fee_without_convenience);
            $('#yoder-pay-online #total_fee').val(total_fee_without_convenience);
        }
    }

    function box_checked($ele) {
        $ele.parent().parent().css('background-color', '#ffffff');
    }

    function box_unchecked($ele) {
        $ele.parent().parent().css('background-color', 'inherit');
    }

    function round(num, precision) {
        var base = 10 ** precision;
        return (Math.round(num * base) / base).toFixed(precision);
    }

    function is_invoice_selected() {
        var checked = $("input.invoice-box:checked").length > 0;
        if (!checked) {
            $("<p>Please select at least one invoice</p>").dialog();
            return false;
        } else {
            return true;
        }
    }
});