<div style="margin-top: 15px;">
	<a href="#quick-quote-modal" id="qq-popup-button" class="button"><?php echo $settings['popup_button_text']; ?></a>
	<p class="no_variation" style="margin-top: 10px;"><span></span></p>

</div>

<div id="bp-modal">
	<div id="quick-quote-modal">
		<a href="#closemodal" id="qq-popup-close">x</a>
		<?php require_once( WOO_BIGPOST_DIR . '/includes/template-qoute-form/default.php' ); ?>
	</div>
</div>

<script>
	jQuery(document).ready(function(){
		jQuery('#bp-modal').hide().prependTo(document.body);

		jQuery('#qq-popup-button').on('click touchstart',function(){
			if(jQuery(this).hasClass('qq-disabled')){

			}else{
				jQuery('#bp-modal').show();
				jQuery('body').addClass('modalOn');	
			}
		})

		jQuery('#qq-popup-close').on('click touchstart',function(e){
			jQuery('#bp-modal').hide();
			jQuery('body').removeClass('modalOn');
		})
	})

</script>