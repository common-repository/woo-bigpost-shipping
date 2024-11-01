<?php 
	global $product; 

	$type = $product->get_type();
	$product_weight = $product->get_weight();
	$product_length = $product->get_length();
	$product_width = $product->get_width();
	$product_height = $product->get_height();

	$settings = woobigpost_get_plugin_settings();
?>

<div id="woobigpost-widget" style="position: relative;"></div>

<?php if($type == "simple"): ?>
<script defer>
	bigpost.key = "<?php echo $settings['bigpost_key']; ?>"; //hello
	bigpost.element_id = "woobigpost-widget"; //enter the id of the div where you want to show the widget
	bigpost.product_id = "<?php echo $product->get_sku(); ?>"; //change this to your product sku
	bigpost.is_wp = true;
	bigpost.boxes = '<?php echo json_encode([array("name"=>$product->get_sku(), "length"=>$product_length, "height"=>$product_height, "width"=>$product_width, "weight"=>$product_weight)]); ?>'; //'[{"name":"Item 2","length":12,"height":34,"width":34,"weight":50}]'; //change this to your boxes. make 
	bigpost.margin = false;
	bigpost.getWidget();
</script>
<?php else: ?>
<script defer>

	jQuery(document).ready(function(){

		jQuery('input.variation_id').change( function(){

			var variations = jQuery('.variations_form').data('product_variations');

			variations.forEach(function(row){
			    if(row.variation_id == jQuery('input.variation_id').val()){
					bigpost.key = "<?php echo $settings['bigpost_key']; ?>"; //hello
					bigpost.element_id = "woobigpost-widget"; //enter the id of the div where you want to show the widget
					bigpost.product_id = row.sku; //change this to your product sku
					bigpost.is_wp = true;
					bigpost.boxes = '[{"name":"'+row.sku+'","length":'+row.dimensions.length+',"height":'+row.dimensions.height+',"width":'+row.dimensions.width+',"weight":'+row.weight+'}]'; 
					bigpost.margin = false;
					bigpost.getWidget();
			    }
			})
		});	
	})
	
</script>
<?php endif; ?>