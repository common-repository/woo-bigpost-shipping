jQuery(document).ready(function($){
    $('#woocommerce_woobigpost_0').hide();
    $("#mainform :input:not([type=hidden])").prop("readonly", false);
    $("#mainform #woocommerce_woobigpost_shipping_hds_label, #mainform #woocommerce_woobigpost_shipping_business_label, #mainform #woocommerce_woobigpost_shipping_depot_label").prop("readonly", true);
    $("#mainform input[type='checkbox'], #mainform select").prop("disabled", false);
    $("#mainform button").prop("disabled", false);
});