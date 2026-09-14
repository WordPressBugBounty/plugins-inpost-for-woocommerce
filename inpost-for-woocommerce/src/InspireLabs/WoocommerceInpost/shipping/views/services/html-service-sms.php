<?php
/**
 * Courier additional services (SMS / email / return documents).
 *
 * @var ShipX_Shipment_Model $shipment
 * @var WC_Order             $order
 * @var bool                 $additional_package
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;

$easypack_sms              = 'no';
$easypack_email            = 'no';
$easypack_return_documents = 'no';
$inputDisabled             = '';
$easypack_sms_checked              = '';
$easypack_email_checked            = '';
$easypack_return_documents_checked = '';

if ( $shipment instanceof ShipX_Shipment_Model && ! $additional_package ) :

	$additional_services = $shipment->getAdditionalServices();
	if ( $additional_services && is_array( $additional_services ) ) {
		if ( in_array( 'sms', $additional_services, true ) ) {
			$easypack_sms_checked = 'checked';
		}
		if ( in_array( 'email', $additional_services, true ) ) {
			$easypack_email_checked = 'checked';
		}
		if ( in_array( 'rod', $additional_services, true ) ) {
			$easypack_return_documents_checked = 'checked';
		}
	}
	$inputDisabled = ' disabled ';
	?>
	<label disabled style="display: block" for="easypack_sms_enabled" class="graytext">
		<input <?php echo esc_attr( $inputDisabled ); ?>
			class="easypack_sms_enabled"
			type="checkbox"
			style=""
			value="<?php echo esc_attr( $easypack_sms ); ?>"
			id="easypack_sms_enabled"
			name="easypack_sms_enabled" <?php echo esc_attr( $easypack_sms_checked ); ?>>
		<?php esc_html_e( 'SMS notifications', 'inpost-for-woocommerce' ); ?>
	</label>
	<label disabled style="display: block" for="easypack_email_enabled" class="graytext">
		<input <?php echo esc_attr( $inputDisabled ); ?>
			class="easypack_email_enabled"
			type="checkbox"
			style=""
			value="<?php echo esc_attr( $easypack_email ); ?>"
			id="easypack_email_enabled"
			name="easypack_email_enabled" <?php echo esc_attr( $easypack_email_checked ); ?>>
		<?php esc_html_e( 'Email notifications', 'inpost-for-woocommerce' ); ?>
	</label>
	<label disabled style="display: block" for="easypack_return_documents_enabled" class="graytext">
		<input <?php echo esc_attr( $inputDisabled ); ?>
			class="easypack_return_documents_enabled"
			type="checkbox"
			style=""
			value="<?php echo esc_attr( $easypack_return_documents ); ?>"
			id="easypack_return_documents_enabled"
			name="easypack_return_documents_enabled" <?php echo esc_attr( $easypack_return_documents_checked ); ?>>
		<?php esc_html_e( 'Return documents', 'inpost-for-woocommerce' ); ?>
	</label>
	<?php
else :

	$ship_method_name        = '';
	$ship_method_instance_id = '';

	foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
		$ship_method_name        = $item->get_method_id();
		$ship_method_instance_id = $item->get_instance_id();
	}

	$shipping_method_settings = get_option( 'woocommerce_' . $ship_method_name . '_' . $ship_method_instance_id . '_settings' );
	if ( isset( $shipping_method_settings['sms'] ) && 'yes' === $shipping_method_settings['sms'] ) {
		$easypack_sms         = $shipping_method_settings['sms'];
		$easypack_sms_checked = 'checked';
	}
	if ( isset( $shipping_method_settings['email'] ) && 'yes' === $shipping_method_settings['email'] ) {
		$easypack_email         = $shipping_method_settings['email'];
		$easypack_email_checked = 'checked';
	}
	if ( isset( $shipping_method_settings['return_documents'] ) && 'yes' === $shipping_method_settings['return_documents'] ) {
		$easypack_return_documents         = $shipping_method_settings['return_documents'];
		$easypack_return_documents_checked = 'checked';
	}
	?>
	<label style="display: block" for="easypack_sms_enabled">
		<input <?php echo esc_attr( $inputDisabled ); ?>
			class="easypack_sms_enabled"
			type="checkbox"
			style=""
			value="<?php echo esc_attr( $easypack_sms ); ?>"
			id="easypack_sms_enabled"
			name="easypack_sms_enabled" <?php echo esc_attr( $easypack_sms_checked ); ?>>
		<?php esc_html_e( 'SMS notifications', 'inpost-for-woocommerce' ); ?>
	</label>
	<label style="display: block" for="easypack_email_enabled">
		<input <?php echo esc_attr( $inputDisabled ); ?>
			class="easypack_email_enabled"
			type="checkbox"
			style=""
			value="<?php echo esc_attr( $easypack_email ); ?>"
			id="easypack_email_enabled"
			name="easypack_email_enabled" <?php echo esc_attr( $easypack_email_checked ); ?>>
		<?php esc_html_e( 'Email notifications', 'inpost-for-woocommerce' ); ?>
	</label>
	<label style="display: block" for="easypack_return_documents_enabled">
		<input <?php echo esc_attr( $inputDisabled ); ?>
			class="easypack_return_documents_enabled"
			type="checkbox"
			style=""
			value="<?php echo esc_attr( $easypack_return_documents ); ?>"
			id="easypack_return_documents_enabled"
			name="easypack_return_documents_enabled" <?php echo esc_attr( $easypack_return_documents_checked ); ?>>
		<?php esc_html_e( 'Return documents', 'inpost-for-woocommerce' ); ?>
	</label>
	<?php
endif;
?>
