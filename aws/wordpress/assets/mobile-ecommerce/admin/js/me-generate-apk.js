jQuery(document).ready(function ($) {
    $("#generate-apk").validate({
        rules: {
            package: {
              required: true
            },
        },
        showErrors: function(errorMap, errorList) {
            $("#error").show().html("<p>Your form contains "
                + this.numberOfInvalids()
                + " errors, see details below.</p>");
            this.defaultShowErrors();
        },
        wrapper: "p"
    });
});