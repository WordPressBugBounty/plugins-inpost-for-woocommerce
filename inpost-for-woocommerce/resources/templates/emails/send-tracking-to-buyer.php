<?php
/**
 * Seller create the parcel and tracking number can be provided now
 *
 * This template was overridden by copying it to yourtheme/woocommerce/emails/customer-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 *
 * @param string $tracking_link URL for tracking shipment.
 * @param array $tracking_numbers Array of tracking numbers.
 * @param mixed $order WooCommerce order object.
 * @param mixed $mailer WooCommerce mailer instance.
 * @param string|false $heading Optional. Email heading text. Default false.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use InspireLabs\WoocommerceInpost\EasyPack;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
	<?php
	if ( ! empty( $tracking_numbers ) ) {
		if ( count( $tracking_numbers ) === 1 ) {
			$tracking_number = reset( $tracking_numbers );
			?>
			<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
			<p><?php esc_html_e( 'A tracking number has been given for your order. It will soon move on its journey', 'woocommerce-inpost' ); ?></p>
			<p><?php esc_html_e( 'Tracking link:', 'woocommerce-inpost' ); ?></p>
			<p><?php printf( '<a href="%s" target="_blank">%s</a>', esc_url( $tracking_link . $tracking_number ), esc_attr( $tracking_link . $tracking_number ) ); ?></p>
            <br>
			<?php
		} elseif ( count( $tracking_numbers ) > 1 ) {
			$multi_tracking = '';
			foreach ( $tracking_numbers as $tracking_number ) {
				$multi_tracking .= sprintf(
					'<p><a href="%s">%s</a></p>',
					esc_url( $tracking_link . $tracking_number ),
					esc_attr( $tracking_number )
				);
			}
			?>
			<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
			<p><?php esc_html_e( 'For your order was created multi-package. It will soon move on its journey', 'woocommerce-inpost' ); ?></p>
			<p><?php esc_html_e( 'Tracking links:', 'woocommerce-inpost' ); ?></p>
			<?php
			echo wp_kses_post( $multi_tracking );
		}

		?>


	<?php } ?>
<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( isset( $additional_content ) ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
