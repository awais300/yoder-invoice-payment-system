<?php
namespace Yoder\YIPS;

$value = '';
if(is_object($user)) {
    $value = get_user_meta( $user->ID, UserProfileFields::META_CUSTOMER_SAGE_FIELD, true );
}
?>
<h3>Yoder Invoice Customer info</h3>
<table class="form-table">
    <tr>
        <th><label>Sage ID</label></th>
        <td>
            <input type="text" class="regular-text" name="<?php echo UserProfileFields::META_CUSTOMER_SAGE_FIELD; ?>" value="<?php echo esc_attr( $value ); ?>" id="customer_sage_id" /><br />
        </td>
    </tr>
</table>