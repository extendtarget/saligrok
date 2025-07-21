<script>
    $(function() {
        "use strict";

        var title = "Login as Customer";
        var height = 680;
        var width = 390;
        var left = (screen.width/2)-(width/2);
        var top = (screen.height/2)-(height/2);
        var storeUrl = "{{ config('setting.storeUrl') }}"
        var base_url = storeUrl+"/auth/login-as-customer/";

        $('body').on("click", ".loginAsCustomerBtn", function(e) {
           let user_id = $(this).attr("data-id");
           var url = base_url + user_id
           var windowFeatures = 'width='+width+', height='+height+', top='+top+', left='+left +', directories=no, titlebar=no, toolbar=no, menubar=no,location=no, resizable=no,scrollbars=no,status=no';
           window.open(url, title, windowFeatures);
        });

        $('body').on("click", "#manualOrderForGuest", function(e) {
            var url = base_url;
            var windowFeatures = 'width='+width+', height='+height+', top='+top+', left='+left +', directories=no, titlebar=no, toolbar=no, menubar=no,location=no, resizable=no,scrollbars=no,status=no';
            window.open(url, title, windowFeatures);
        });
        
    });
</script>