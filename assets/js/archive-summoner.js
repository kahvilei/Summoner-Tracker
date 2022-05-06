jQuery(document).ready( function() {

    jQuery(".summoner_update_button").click( function(e) {
        e.preventDefault();
        post_id = jQuery(this).attr("data-post_id")
        nonce = jQuery(this).attr("data-nonce")

        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : myAjax.ajaxurl,
            data : {action: "summoner_update_button", post_id : post_id, nonce: nonce},
            success: function(response) {
                if(response.type == "success") {
                    jQuery("#summoner_update_text").html(response)
                }
                else {
                    alert("Your update could not be completed")
                }
            }
        })

    })

})