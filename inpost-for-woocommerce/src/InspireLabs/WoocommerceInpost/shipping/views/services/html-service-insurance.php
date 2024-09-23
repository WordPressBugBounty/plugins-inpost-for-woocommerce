<?php /** @var ShipX_Shipment_Model $shipment */

use InspireLabs\WoocommerceInpost\EasyPack;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Parcel_Model; ?>

<?php if ($shipment instanceof ShipX_Shipment_Model && ! $additional_package): ?>
<?php $inputDisabled = ' disabled '?>
    <label disabled style="display: block" for="insurance_amounts" class="graytext">
        <?php _e('Insurance amount: ', 'woocommerce-inpost'); ?>
    </label>
<?php else: ?>
    <?php $inputDisabled = ''?>
    <label style="display: block" for="insurance_amounts">
        <?php _e('Insurance amount: ', 'woocommerce-inpost'); ?>
    </label>
<?php endif?>

<?php if ($shipment instanceof ShipX_Shipment_Model ): ?>
    
	<?php $insurance = '0.00';
	if ( null !== $shipment->getInsurance() ) {
		$insurance = $shipment->getInsurance()->getAmount();
	} ?>

	<input <?php echo esc_attr( $inputDisabled ); ?>
           class="insurance_amount var1"
           type="number"
           style=""
           value="<?php echo esc_attr( $insurance ); ?>"
           placeholder="0.00"
           step="any"
           min="0"
           id="insurance_amounts"
           name="insurance_amounts[]">
<?php else: ?>
	
    <?php
    $insurance = 0;
    $insurance_mode = get_option('easypack_insurance_amount_mode', '2' );
    if( '1' === $insurance_mode ) { // from order amount
        $order = wc_get_order( $order_id );
        if( $order && ! is_wp_error($order) && is_object($order) ) {
            $insurance = $order->get_total();
            $insurance = floatval($insurance);
        }
    }
    if( '2' === $insurance_mode ) { // from settings
        $insurance = floatval( get_option('easypack_insurance_amount_default') );
    }
    ?>

    <input <?php echo esc_attr( $inputDisabled ); ?>
           class="insurance_amount"
		   type="number" style=""
           value="<?php echo esc_attr( $insurance ); ?>"
           placeholder="0.00"
           step="any"
           min="0"
           id="insurance_amounts"
           name="insurance_amounts[]<?php echo esc_attr( $inputDisabled ); ?>">
<?php endif; ?>
