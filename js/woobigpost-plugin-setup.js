jQuery(document).ready(function($){
    $("#mainform :input:not([type=hidden])").prop("readonly", true);
    $("#mainform input[type='checkbox'],  #mainform select").prop("disabled", true);
    $("#mainform #woocommerce_woobigpost_api_url").prop("disabled", false);
    $("#mainform #woocommerce_woobigpost_api_key").prop("readonly", false);
    $("#mainform #woocommerce_woobigpost_testing_api_key").prop("readonly", false);
    $("#mainform #woocommerce_woobigpost_api_username").prop("readonly", false);
    $("#mainform #woocommerce_woobigpost_api_password").prop("readonly", false);
    $("#mainform #woocommerce_woobigpost_enabled").prop("disabled", false);
    $("#mainform button").prop("disabled", true);
    $("#mainform button[type='submit']").prop("disabled", false);
});