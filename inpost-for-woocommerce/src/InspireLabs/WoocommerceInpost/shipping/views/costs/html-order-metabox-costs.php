<?php /** @var ShipX_Shipment_Model $shipment */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

use InspireLabs\WoocommerceInpost\EasyPack;
use InspireLabs\WoocommerceInpost\shipx\models\shipment\ShipX_Shipment_Model;
use InspireLabs\WoocommerceInpost\shipx\models\shipment_cost\ShipX_Shipment_Cost_Model; ?>

<?php


$inpost_for_woocommerce_calculated_charge_amount
    = $inpost_for_woocommerce_calculated_charge_amount_nc
    = $inpost_for_woocommerce_cod_charge_amount
    = $inpost_for_woocommerce_fuel_charge_amount
    = $inpost_for_woocommerce_insurance_charge_amount
    = $inpost_for_woocommerce_notification_charge_amount
    = '';

if ($shipment instanceof ShipX_Shipment_Model) {
    $inpost_for_woocommerce_cost = $shipment->getInternalData()->getShipmentCost();
} else {
    $inpost_for_woocommerce_cost = null;
}

if ($inpost_for_woocommerce_cost instanceof ShipX_Shipment_Cost_Model) {
    if (true !== $inpost_for_woocommerce_cost->isError()) {
        $inpost_for_woocommerce_calculated_charge_amount    = $inpost_for_woocommerce_cost->getCalculatedChargeAmount();
        $inpost_for_woocommerce_calculated_charge_amount_nc = $inpost_for_woocommerce_cost->getCalculatedChargeAmountNonCommission();
        $inpost_for_woocommerce_cod_charge_amount           = $inpost_for_woocommerce_cost->getCodChargeAmount();
        $inpost_for_woocommerce_fuel_charge_amount          = $inpost_for_woocommerce_cost->getFuelChargeAmount();
        $inpost_for_woocommerce_insurance_charge_amount     = $inpost_for_woocommerce_cost->getInsuranceChargeAmount();
        $inpost_for_woocommerce_notification_charge_amount  = $inpost_for_woocommerce_cost->getNotificationChargeAmount();
    }
}

?>

<ul class="shipment_costs_wrapper<?php echo null === $inpost_for_woocommerce_cost ? ' hidden' : '' ?>">

    <span style="font-weight: bold"><?php esc_html_e('Shipment costs:', 'inpost-for-woocommerce') ?> </span>
    <li>
        <ol style="list-style-type: none">
            <li>
                <span><?php esc_html_e('Calculated charge amount:', 'inpost-for-woocommerce') ?></span>
                <span id="calculated_charge_amount"> <?php echo esc_html( $inpost_for_woocommerce_calculated_charge_amount ); ?></span>
            </li>

            <li>
                <span><?php esc_html_e('Calculated charge amount (non commission):', 'inpost-for-woocommerce') ?></span>
                <span id="calculated_charge_amount_nc"><?php echo esc_html( $inpost_for_woocommerce_calculated_charge_amount_nc ); ?></span>
            </li>

            <li>
                <span><?php esc_html_e('COD charge amount:', 'inpost-for-woocommerce') ?></span>
                <span id="cod_charge_amount"> <?php echo esc_html( $inpost_for_woocommerce_cod_charge_amount ); ?></span>
            </li>

            <li>
                <span><?php esc_html_e('Fuel charge amount:', 'inpost-for-woocommerce') ?></span>
                <span id="fuel_charge_amount"> <?php echo esc_html( $inpost_for_woocommerce_fuel_charge_amount ); ?></span>
            </li>

            <li>
                <span><?php esc_html_e('Insurance charge amount:', 'inpost-for-woocommerce') ?></span>
                <span id="insurance_charge_amount"> <?php echo esc_html( $inpost_for_woocommerce_insurance_charge_amount ); ?></span>
            </li>

            <li>
                <span><?php esc_html_e('Notification charge amount:', 'inpost-for-woocommerce') ?></span>
                <span id="notification_charge_amount"> <?php echo esc_html( $inpost_for_woocommerce_notification_charge_amount ); ?></span>
            </li>
        </ol>
    </li>
</ul>

