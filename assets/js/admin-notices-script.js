jQuery(document).ready(function(){

    //dismiss admin notice forever

    qs_cf7_dismiss_admin_note();





});



function qs_cf7_dismiss_admin_note(){

    jQuery(".qs-cf7-api-dismiss-notice-forever").click(function(){



        var id = jQuery( this ).attr( 'id' );



        jQuery.ajax({

            type: "post",

            url: ajaxurl,

            data: {

                action: 'qs_cf7_api_admin_dismiss_notices',

                id : id

            },



        });

    });

}

