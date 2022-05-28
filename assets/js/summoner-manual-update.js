jQuery(document).ready( function() {

    jQuery(".summoner_update_button").click( function(e) {
        e.preventDefault();
        post_id = jQuery(this).attr("data-post_id")
        nonce = jQuery(this).attr("data-nonce")

        jQuery(this).html('loading')


        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : '/wp-admin/admin-ajax.php',
            data : {action: "summoner_update_button", post_id : post_id, nonce: nonce},
            success: function(response) {
                if(response.type == "success" || "0") {
                    location.reload();
                }
                else {
                    alert("Your update could not be completed. Response: " + response)
                }
            }
        })

    })

})