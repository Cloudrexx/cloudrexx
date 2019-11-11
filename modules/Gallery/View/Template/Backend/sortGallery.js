cx.jQuery( document ).ready(function() {
    cx.jQuery('.dragdrop_move').hide();
    //check if drag & drop is active
    if (cx.jQuery("#enableDragDrop").val() == 'active') {
        //hide order-number and show drag & drop icon
        cx.jQuery('.dragdrop_move').show();
        cx.jQuery('.sortingSystem').hide();

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
                        cx.jQuery('.sortingSystem').each(function (index) {
                            cx.jQuery(this).val(index + 1);
                        });
                    },
                    items: '.draggable',
                    axis: 'y'
                }
            );
        }, 100);
    }
});