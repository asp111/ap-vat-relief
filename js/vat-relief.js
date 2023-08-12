;(function( $ )
{
$( document ).ready(
    function($) {

        // on first checkout load, before the post data value is saved
        if( ($('input[name="vat_relief_type"]:checked').val() == 'no') ) {
            $('#vat_relief_inner_box').hide();
            $('#ap_vat_relief_confirm').prop( "checked", false );
        }

        $('input[name="vat_relief_type"]').on('change', function() {
            if( ($('input[name="vat_relief_type"]:checked').val() == 'no') ) {
                $('#vat_relief_inner_box').hide();
                $('#ap_vat_relief_confirm').prop( "checked", false );
            }
            else if( ($('input[name="vat_relief_type"]:checked').val() == 'person') ) {
                $('#vat_relief_inner_box').show();
                $('#person_vat_relief').show();
                $('#charity_vat_relief').hide();
            }
            else if( ($('input[name="vat_relief_type"]:checked').val() == 'charity') ) {
                $('#vat_relief_inner_box').show();
                $('#charity_vat_relief').show();
                $('#person_vat_relief').hide();
            }

            $('body').trigger('update_checkout');
        });


        $('#ap_vat_relief_confirm').click(
            function() {
                $('body').trigger('update_checkout');
            }
        );
    }
);

}( jQuery ));