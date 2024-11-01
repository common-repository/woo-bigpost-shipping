<table>
    <tr>
        <td>
            <p>
            <?php

            if ( ! get_option( 'woobigpost_admin_footer_text_rated' ) ) {
                $footer_text = sprintf(
                    __( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'woobigpost' ),
                    sprintf( '<strong>%s</strong>', esc_html__( 'Big Post Shipping for WooCommerce', 'woobigpost' ) ),
                    '<a href="https://wordpress.org/support/plugin/woo-bigpost-shipping/reviews?rate=5#new-post" target="_blank" class="woobigpost-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'woobigpost' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
                );
            } else {
                $footer_text = __( 'Thank you for using Big Post Shipping.', 'woocommerce' );
            }

            echo $footer_text;
            ?>
            </p>
        </td>
    </tr>
</table>
<script type="text/javascript">
    jQuery(function($) {
        jQuery( 'a.woobigpost-rating-link' ).click( function() {
            jQuery.post(ajaxurl, { action: 'woobigpost_rated' } );
            jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
        });
    });
</script>