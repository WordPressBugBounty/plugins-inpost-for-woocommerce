<?php
/**
 * Processing insurance amount
 *
 * @var ShipX_Shipment_Model $shipment
 */

use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

$inpost_for_woocommerce_shipment_already_created = ( $shipment instanceof ShipX_Shipment_Model && ! $additional_package );

if ( $inpost_for_woocommerce_shipment_already_created ) {
	$inpost_for_woocommerce_input_disabled = ' disabled ';
	?>
	<label disabled style="display: block" for="insurance_amounts" class="graytext">
		<?php esc_html_e( 'Additional insurance amount: ', 'inpost-for-woocommerce' ); ?>
	</label>
	<?php
} else {
	$inpost_for_woocommerce_input_disabled = '';
	?>
	<label style="display: block" for="insurance_amounts">
		<?php esc_html_e( 'Additional insurance amount: ', 'inpost-for-woocommerce' ); ?>
	</label>
	<p class="description" style="margin: 0 0 6px;">
		<?php esc_html_e( 'Select additional insurance (for values over 200 PLN). COD always requires additional insurance equal to the order value.', 'inpost-for-woocommerce' ); ?>
	</p>
	<?php
}

if ( $shipment instanceof ShipX_Shipment_Model ) {
	$inpost_for_woocommerce_insurance = '0.00';
	if ( null !== $shipment->getInsurance() ) {
		$inpost_for_woocommerce_insurance = $shipment->getInsurance()->getAmount();
	}
	?>

	<input <?php echo esc_attr( $inpost_for_woocommerce_input_disabled ); ?>
			class="insurance_amount var1"
			type="number"
			style=""
			value="<?php echo esc_attr( $inpost_for_woocommerce_insurance ); ?>"
			placeholder="0.00"
			step="any"
			min="0"
			id="insurance_amounts"
			name="insurance_amounts[]">
	<?php
} else {

	$inpost_for_woocommerce_insurance = EasyPack_Helper()->get_insurance_amount( $order_id );
	?>
	<input <?php echo esc_attr( $inpost_for_woocommerce_input_disabled ); ?>
			class="insurance_amount"
			type="number" style=""
			value="<?php echo esc_attr( $inpost_for_woocommerce_insurance ); ?>"
			placeholder="0.00"
			step="any"
			min="0"
			id="insurance_amounts"
			name="insurance_amounts[]<?php echo esc_attr( $inpost_for_woocommerce_input_disabled ); ?>">
	<?php
}
