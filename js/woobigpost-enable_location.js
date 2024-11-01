jQuery(document).ready(function($){
    $("#mainform :input:not([type=hidden])").prop("readonly", true);
    $("#mainform input[type='checkbox'],  #mainform select").prop("disabled", true);
    $("#mainform #woocommerce_woobigpost_enabled").prop("disabled", false);

    //enable api details
    $("#mainform #woocommerce_woobigpost_api_url").prop("disabled", false);
    $("#mainform #woocommerce_woobigpost_api_key").prop("readonly", false);
    $("#mainform #woocommerce_woobigpost_testing_api_key").prop("readonly", false);
    $("#mainform #woocommerce_woobigpost_api_username").prop("readonly", false);
    $("#mainform #woocommerce_woobigpost_api_password").prop("readonly", false);

    //enable location details
    $("#mainform .from_name").prop("readonly", false);
    $("#mainform .from_address").prop("readonly", false);
    $("#mainform .from_suburb").prop("readonly", false);
    $("#mainform button").prop("disabled", false);

});