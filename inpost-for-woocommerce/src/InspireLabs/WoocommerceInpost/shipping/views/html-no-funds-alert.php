<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

if ( 'offer_selected' === $status ) : ?>
	<p class="easypack_error" style="color:red; font-weight:bold;">
		<?php
		printf(
			'%1s <a href="https://manager.paczkomaty.pl/auth/login" target="_blank" style="color:blue;">%1s</a> %1s',
			esc_html__( 'The package has not been created! You do not have funds in', 'inpost-for-woocommerce' ),
			esc_html__( 'your Parcel Manager account', 'inpost-for-woocommerce' ),
			esc_html__( 'or a contract for InPost services.', 'inpost-for-woocommerce' )
		);
		?>
	</p>
	<p class="easypack_error" style="color:red; font-weight:bold;">	
		<?php echo esc_html__( 'Re-creating a package is possible in the Package Manager after topping up funds.', 'inpost-for-woocommerce' ); ?>
	</p>
<?php endif; ?>
<?php
if ( isset( $api_status_update_response ) && is_array( $api_status_update_response ) && ! empty( $api_status_update_response ) ) :

	if ( isset( $api_status_update_response['selected_offer']['unavailability_reasons'] )
		|| isset( $api_status_update_response['offers'][0]['unavailability_reasons'] )
		|| isset( $api_status_update_response['unavailability_reasons'] ) ) :


		$inpost_for_woocommerce_unavailability_reasons = array();

		if ( isset( $api_status_update_response['offers'][0]['unavailability_reasons'] ) ) :
			$inpost_for_woocommerce_unavailability_reasons = $api_status_update_response['offers'][0]['unavailability_reasons'];
		elseif ( isset( $api_status_update_response['selected_offer']['unavailability_reasons'] ) ) :
			$inpost_for_woocommerce_unavailability_reasons = $api_status_update_response['selected_offer']['unavailability_reasons'];
		else :

			$inpost_for_woocommerce_unavailability_reasons = $api_status_update_response['unavailability_reasons'] ?? array();
		endif;

		if ( is_array( $inpost_for_woocommerce_unavailability_reasons ) && ! empty( $inpost_for_woocommerce_unavailability_reasons ) ) :
			$inpost_for_woocommerce_details = '';
			foreach ( $inpost_for_woocommerce_unavailability_reasons as $inpost_for_woocommerce_arr ) {
				if ( is_array( $inpost_for_woocommerce_arr ) ) {
					foreach ( $inpost_for_woocommerce_arr as $inpost_for_woocommerce_k => $inpost_for_woocommerce_v ) {
						if ( 'sender' === $inpost_for_woocommerce_k && 'post_code_invalid' === $inpost_for_woocommerce_v ) {
							$inpost_for_woocommerce_details .= esc_html__( 'Wrong sender postal code', 'inpost-for-woocommerce' );
						} elseif ( 'receiver' === $inpost_for_woocommerce_k && 'post_code_invalid' === $inpost_for_woocommerce_v ) {
							$inpost_for_woocommerce_details .= esc_html__( 'Wrong receiver postal code', 'inpost-for-woocommerce' );
						} else {
							$inpost_for_woocommerce_details .= $inpost_for_woocommerce_k . ': ' . $inpost_for_woocommerce_v . ' ';
						}
					}
				}
			}

			if ( ! empty( $inpost_for_woocommerce_details ) ) :
				?>
				<p class="easypack_error" style="color:red; font-weight:bold;">
					<?php echo esc_html( $inpost_for_woocommerce_details ); ?>
				</p>
			<?php endif;
		endif;
	endif;
endif; ?>
