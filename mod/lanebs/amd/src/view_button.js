define(["exports", "jquery", "mod_lanebs/modal_book_handle"],
    function (exports, $, ModalBookHandle) {
        return {
            init: function(title) {
                let BUTTON_SELECTOR = '[data-action="book_modal"]';
                $(BUTTON_SELECTOR).on('click', function(e) {
                    ModalBookHandle.init(e, title);
                });
            }
        };
    });