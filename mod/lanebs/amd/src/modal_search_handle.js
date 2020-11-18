define(["exports", "jquery", "core/modal_factory", "mod_lanebs/modal_search"],
    function (exports, $, ModalFactory, ModalSearch) {
        return {
            init: function () {
                var trigger = $('#id_modal_show_button');
                ModalFactory.create({type: ModalSearch.TYPE}, trigger)
                    .done(function (modal) {
                        $(modal.getRoot()).find('.modal-dialog').css('max-width', '1500px');
                        $(modal.getRoot()).find('.modal-body').css('height', '770px');
                        $(modal.getRoot()).find('.modal-body').css('overflow-y', 'auto');
                        ModalSearch.prototype.getAjaxCall('mod_lanebs_auth', [], function (data) {
                            if (data['error']['code'] === 403) {
                                alert('Не правильный токен подписчика. Предоставлен гостевой доступ!');
                            }
                        });
                    });
            }
        };
    });