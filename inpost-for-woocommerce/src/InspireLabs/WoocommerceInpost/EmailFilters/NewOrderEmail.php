<?php


namespace InspireLabs\WoocommerceInpost\EmailFilters;

use WC_Order;
use InspireLabs\WoocommerceInpost\EasyPack;

class NewOrderEmail {

    public function init() {
        add_action( 'woocommerce_email_order_meta',
            [ $this, "print_parcel_machine_info" ],
            10,
            3 );
    }

    /**
     * @param WC_Order $wc_order
     * @param          $sent_to_admin
     * @param          $plain_text
     */
    public function print_parcel_machine_info(
        WC_Order $wc_order,
        $sent_to_admin,
        $plain_text
    ): void {

        $parcelMachine = get_post_meta( $wc_order->get_id(),
            '_parcel_machine_id', true );

        if ( empty( $parcelMachine ) ) {
            return;
        }

        $parcel_locker_methods = [
            'easypack_parcel_machines',
            'easypack_parcel_machines_cod',
            'easypack_parcel_machines_economy',
            'easypack_parcel_machines_economy_cod',
            'easypack_parcel_machines_weekend'
        ];


        $fs_method_name = get_post_meta( $wc_order->get_id(), '_fs_easypack_method_name', true);

        $shipping_method_id = '';
        
        foreach( $wc_order->get_items( 'shipping' ) as $item_id => $item ){
            $shipping_method_id = $item->get_method_id(); // The method ID			
        }

        if ( in_array( $shipping_method_id, $parcel_locker_methods )
            || ( isset( $fs_method_name ) && in_array( $fs_method_name, $parcel_locker_methods ) ) ) {            

            $notice =  __( sprintf( __( 'Selected parcel machine', 'woocommerce-inpost' ) . ': %s',
                $parcelMachine ) );

            echo "<div style='margin-bottom: 40px'>";
            echo wp_kses_post( $notice );
            echo "</div>";
        }

    }
}
