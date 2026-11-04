<?php
/**
 * Return tracking number block for order metaboxes.
 *
 * Expects: $shipment, $shipment_service, $additional_package (optional), $order_id (optional).
 *
 * @package InspireLabs\WoocommerceInpost
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;

$inpost_for_woocommerce_is_additional_package = isset( $additional_package ) && $additional_package;

$inpost_for_woocommerce_return_tracking_number = '';
if ( isset( $shipment ) && $shipment instanceof ShipX_Shipment_Model ) {
	$inpost_for_woocommerce_return_tracking_number = $shipment->getInternalData()->getReturnTrackingNumber();
}
if ( empty( $inpost_for_woocommerce_return_tracking_number ) && ! empty( $order_id ) && function_exists( 'EasyPack_Helper' ) ) {
	$inpost_for_woocommerce_return_tracking_number = EasyPack_Helper()->get_return_tracking_number_from_meta( $order_id );
}

if ( ! $inpost_for_woocommerce_is_additional_package
	&& isset( $shipment, $shipment_service )
	&& $shipment instanceof ShipX_Shipment_Model
	&& ! empty( $inpost_for_woocommerce_return_tracking_number )
) :
	$inpost_for_woocommerce_return_tracking_url = $shipment_service->getReturnTrackingUrl( $inpost_for_woocommerce_return_tracking_number );
	?>
	<span style="font-weight: bold">
		<?php esc_html_e( 'Return tracking number:', 'inpost-for-woocommerce' ); ?>
	</span>
	<?php if ( ! empty( $inpost_for_woocommerce_return_tracking_url ) ) : ?>
		<a target="_blank" href="<?php echo esc_url( $inpost_for_woocommerce_return_tracking_url ); ?>">
			<?php echo esc_html( $inpost_for_woocommerce_return_tracking_number ); ?>
		</a>
	<?php else : ?>
		<?php echo esc_html( $inpost_for_woocommerce_return_tracking_number ); ?>
	<?php endif; ?>
	<div class="padding-bottom15"></div>
	<?php
endif;
