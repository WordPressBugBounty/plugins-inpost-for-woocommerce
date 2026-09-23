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
$easypack_input_disabled   = '';
$easypack_sms_checked              = '';
$easypack_email_checked            = '';
$easypack_return_documents_checked = '';

if ( $shipment instanceof ShipX_Shipment_Model && ! $additional_package ) :

	$easypack_additional_services = $shipment->getAdditionalServices();
	if ( $easypack_additional_services && is_array( $easypack_additional_services ) ) {
		if ( in_array( 'sms', $easypack_additional_services, true ) ) {
			$easypack_sms_checked = 'checked';
		}
		if ( in_array( 'email', $easypack_additional_services, true ) ) {
			$easypack_email_checked = 'checked';
		}
		if ( in_array( 'rod', $easypack_additional_services, true ) ) {
			$easypack_return_documents_checked = 'checked';
		}
	}
	$easypack_input_disabled = ' disabled ';
	?>
	<label disabled style="display: block" for="easypack_sms_enabled" class="graytext">
		<input <?php echo esc_attr( $easypack_input_disabled ); ?>
			class="easypack_sms_enabled"
			type="checkbox"
			style=""
			value="<?php echo esc_attr( $easypack_sms ); ?>"
			id="easypack_sms_enabled"
			name="easypack_sms_enabled" <?php echo esc_attr( $easypack_sms_checked ); ?>>
		<?php esc_html_e( 'SMS notifications', 'inpost-for-woocommerce' ); ?>
	</label>
	<label disabled style="display: block" for="easypack_email_enabled" class="graytext">
		<input <?php echo esc_attr( $easypack_input_disabled ); ?>
			class="easypack_email_enabled"
			type="checkbox"
			style=""
			value="<?php echo esc_attr( $easypack_email ); ?>"
			id="easypack_email_enabled"
			name="easypack_email_enabled" <?php echo esc_attr( $easypack_email_checked ); ?>>
		<?php esc_html_e( 'Email notifications', 'inpost-for-woocommerce' ); ?>
	</label>
	<label disabled style="display: block" for="easypack_return_documents_enabled" class="graytext">
		<input <?php echo esc_attr( $easypack_input_disabled ); ?>
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

	$easypack_ship_method_name        = '';
	$easypack_ship_method_instance_id = '';

	foreach ( $order->get_items( 'shipping' ) as $easypack_item_id => $easypack_item ) {
		$easypack_ship_method_name        = $easypack_item->get_method_id();
		$easypack_ship_method_instance_id = $easypack_item->get_instance_id();
	}

	$easypack_shipping_method_settings = get_option( 'woocommerce_' . $easypack_ship_method_name . '_' . $easypack_ship_method_instance_id . '_settings' );
	if ( isset( $easypack_shipping_method_settings['sms'] ) && 'yes' === $easypack_shipping_method_settings['sms'] ) {
		$easypack_sms         = $easypack_shipping_method_settings['sms'];
		$easypack_sms_checked = 'checked';
	}
	if ( isset( $easypack_shipping_method_settings['email'] ) && 'yes' === $easypack_shipping_method_settings['email'] ) {
		$easypack_email         = $easypack_shipping_method_settings['email'];
		$easypack_email_checked = 'checked';
	}
	if ( isset( $easypack_shipping_method_settings['return_documents'] ) && 'yes' === $easypack_shipping_method_settings['return_documents'] ) {
		$easypack_return_documents         = $easypack_shipping_method_settings['return_documents'];
		$easypack_return_documents_checked = 'checked';
	}
	?>
	<label style="display: block" for="easypack_sms_enabled">
		<input <?php echo esc_attr( $easypack_input_disabled ); ?>
			class="easypack_sms_enabled"
			type="checkbox"
			style=""
			value="<?php echo esc_attr( $easypack_sms ); ?>"
			id="easypack_sms_enabled"
			name="easypack_sms_enabled" <?php echo esc_attr( $easypack_sms_checked ); ?>>
		<?php esc_html_e( 'SMS notifications', 'inpost-for-woocommerce' ); ?>
	</label>
	<label style="display: block" for="easypack_email_enabled">
		<input <?php echo esc_attr( $easypack_input_disabled ); ?>
			class="easypack_email_enabled"
			type="checkbox"
			style=""
			value="<?php echo esc_attr( $easypack_email ); ?>"
			id="easypack_email_enabled"
			name="easypack_email_enabled" <?php echo esc_attr( $easypack_email_checked ); ?>>
		<?php esc_html_e( 'Email notifications', 'inpost-for-woocommerce' ); ?>
	</label>
	<label style="display: block" for="easypack_return_documents_enabled">
		<input <?php echo esc_attr( $easypack_input_disabled ); ?>
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
