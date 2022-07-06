<?php

namespace Yoder\YIPS;

use Yoder\YIPS\User\UserMeta;

$value = '';
if (is_object($user)) {
    $value = get_user_meta($user->ID, UserMeta::META_CUSTOMER_SAGE_ID, true);
}
?>
<h3><?php _e('Yoder Invoice Customer info', 'yips-customization'); ?></h3>
<table class="form-table">
    <tr>
        <th><label><?php _e('Customer Sage ID Number', 'yips-customization'); ?></label></th>
        <td>
            <input type="text" class="regular-text" name="<?php echo esc_attr(UserMeta::META_CUSTOMER_SAGE_ID); ?>" value="<?php echo esc_attr($value); ?>" id="customer_sage_id" /><br />
        </td>
    </tr>
</table>