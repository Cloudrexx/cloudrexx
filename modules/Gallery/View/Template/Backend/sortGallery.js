jQuery( document ).ready(function() {
    jQuery('.dragdrop_move').hide();
    //check if drag & drop is active
    if (jQuery("#enableDragDrop").val() == 'active') {
        //hide order-number and show drag & drop icon
        jQuery('.dragdrop_move').show();
        jQuery('.sortingSystem').hide();

        /**
         * activate sortable
         *
         * Timeout is needed for the method sortable() to apply
         * $(document).ready isn't enough
         **/
        setTimeout(function() {
            cx.jQuery('.adminlist tbody').sortable({
                    update: function () {
                        //sets index number for sortingSystem
                        jQuery('.sortingSystem').each(function (index) {
                            jQuery(this).val(index + 1);
                        });
                    },
                    items: '.draggable',
                    axis: 'y'
                }
            );
        }, 100);
    }
});