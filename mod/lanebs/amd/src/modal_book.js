define(["exports", "jquery", "core/ajax", "core/modal_factory", "core/modal_events",
        "core/notification", "core/modal", "core/custom_interaction_events", "core/modal_registry"],
    function (exports, $, ajax, ModalFactory, ModalEvents,
              Notification, Modal, CustomEvents, ModalRegistry) {

    let SELECTORS = {
        CLOSE_BUTTON: "[data-action='close_button']",
        CONTENT_BLOCK: "[data-action='book_content_block']",
        CLOSE_CROSS: ".close",
        ROOT_MODAL: "[data-region='modal-container']",

    };

    /**
     * Constructor for the Modal
     *
     */
    let ModalBook = function(root) {
        Modal.call(this, root);

        if (!this.getFooter().find(SELECTORS.CLOSE_BUTTON).length) {
            Notification.exception({message: 'close button not found'});
        }
    };

    ModalBook.TYPE = 'mod_lanebs-book';
    ModalBook.CONTENT_BLOCK = SELECTORS.CONTENT_BLOCK;
    ModalBook.prototype = Object.create(Modal.prototype);
    ModalBook.prototype.constructor = ModalBook;

    ModalBook.prototype.registerEventListeners = function () {
        Modal.prototype.registerEventListeners.call(this);

        this.getModal().on(CustomEvents.events.activate, SELECTORS.CLOSE_BUTTON, function (e) {
            e.preventDefault();
            e.stopPropagation();
            this.getRoot().trigger('click');
        }.bind(this));
        this.getModal().on(CustomEvents.events.scrollBottom, SELECTORS.CONTENT_BLOCK, function (e) {
            let id = [$(e.currentTarget).attr('data-id')];
            ajax.call([
                {methodname: 'mod_lanebs_book_content', args: {
                        id: id
                    }}
            ])[0].then(function (response) {
                return response;
            }).
            done(function (response) {
                let error = JSON.parse(response['body']).error;
                if (error && error.code === 404) {
                    let errorMsg = 'Ресурс отсутствует в Вашем пользовании. Обратитесь к администратору.';
                    alert(errorMsg);
                    ModalBook.prototype.getBookResult(errorMsg);
                }
                else {
                    ModalBook.prototype.getBookResult(response['body']);
                }
            }).fail(function (response) {
                alert(response);
            });
        });
        this.getModal().on(CustomEvents.events.activate, SELECTORS.CLOSE_CROSS, function (e) {
            e.preventDefault();
            e.stopPropagation();
            this.getRoot().trigger('click');
        }.bind(this));
        this.getRoot().on(CustomEvents.events.activate, function (e) {
            let root = $(SELECTORS.ROOT_MODAL);
            if (root.length > 1) { // потому что эта модалка иногда вторая (после поиска), а иногда первая, внутри модуля.
                root = root[1];    // Костыль, но что делать
            }
            else {
                root = root[0];
            }
            if (e.target === root) {
                this.getRoot().remove();
            }
            return true;
        }.bind(this));
    };

    ModalBook.prototype.getBookResult = function (response) {
        //console.log(response);
        let iframeBook = document.getElementById('book_iframe');
        iframeBook.contentWindow.document.open();
        iframeBook.contentWindow.document.write(response);
        iframeBook.contentWindow.document.close();
    };


    ModalRegistry.register(ModalBook.TYPE, ModalBook, 'mod_lanebs/modal_book');

    return ModalBook;
});