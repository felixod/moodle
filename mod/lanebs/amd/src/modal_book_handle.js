define(["exports", "jquery", "core/modal_factory", "mod_lanebs/modal_book"],
    function (exports, $, ModalFactory, ModalBook) {
        return {
            init: function (e, title) {
                let trigger = $(e.currentTarget);
                let id = $(trigger).closest('.item').attr('data-id');
                ModalFactory.create({type: ModalBook.TYPE, title}, trigger, id, title).then(function (modal) {
                    let modalRoot = modal.getRoot();
                    $(ModalBook.CONTENT_BLOCK).attr('data-id', id);
                    $(modalRoot).find(ModalBook.CONTENT_BLOCK).attr('data-id', id);
                    $(modalRoot).find('.modal-dialog').css('max-width', '1500px');
                    $(modalRoot).find('.modal-body').css('height', '770px');
                    $(modalRoot).find('.modal-body').css('overflow-y', 'auto');
                    $(modalRoot).find(ModalBook.CONTENT_BLOCK).trigger('cie:scrollBottom');
                });
            }
        };
    });