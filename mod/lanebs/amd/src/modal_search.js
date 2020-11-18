define(["exports", "jquery", "core/ajax", "core/modal_factory", "core/modal_events", "core/notification", "core/modal",
        "core/custom_interaction_events", "core/modal_registry", "mod_lanebs/modal_book", "mod_lanebs/modal_book_handle"],
    function (exports, $, ajax, ModalFactory, ModalEvents, Notification, Modal,
              CustomEvents, ModalRegistry, ModalBook, ModalBookHandler) {

    $.fn.serializeAssoc = function() {
            var data = {};
            $.each( this.serializeArray(), function( key, obj ) {
                var a = obj.name.match(/(.*?)\[(.*?)\]/);
                if(a !== null)
                {
                    var subName = a[1];
                    var subKey = a[2];

                    if( !data[subName] ) {
                        data[subName] = [ ];
                    }

                    if (!subKey.length) {
                        subKey = data[subName].length;
                    }

                    if( data[subName][subKey] ) {
                        if( $.isArray( data[subName][subKey] ) ) {
                            data[subName][subKey].push( obj.value );
                        } else {
                            data[subName][subKey] = [ ];
                            data[subName][subKey].push( obj.value );
                        }
                    } else {
                        data[subName][subKey] = obj.value;
                    }
                } else {
                    if( data[obj.name] ) {
                        if( $.isArray( data[obj.name] ) ) {
                            data[obj.name].push( obj.value );
                        } else {
                            data[obj.name] = [ ];
                            data[obj.name].push( obj.value );
                        }
                    } else {
                        data[obj.name] = obj.value;
                    }
                }
            });
            return data;
        };

    let SELECTORS = {
        SEARCH_TEXTBOX: "[data-action='search_text']",
        START_SEARCH: "[data-action='search_button']",
        START_SEARCH_CLEAR: "[name='clear_search']",
        CANCEL_BUTTON: "[data-action='cancel_button']",
        CONTENT_BLOCK: "[data-action='content_block']",
        BREADCRUMBS: ".breadcrumbs p",
        CONTENT_NAME: "[name='content_name']",
        TRIGGER_BOOK: ".trigger_book",
        ADD_BOOK: "[name='add_book']",
        CATEGORY_BUTTON: "[data-action='category_tree']",
        CATEGORY_BLOCK: "[data-action='categories']",
        BOOK_PAGINATION: "#books_pagination li.page-item",
        START_PAGE_CLASS: "page-start",
        END_PAGE_CLASS: "page-end",
        PROGRESS: ".loader",
    };
    let LIMIT_ON_PAGE = 10;

    /**
     * Constructor for the Modal
     *
     */
    let ModalSearch = function(root) {
        Modal.call(this, root);

        if (!this.getBody().find(SELECTORS.SEARCH_TEXTBOX).length) {
            Notification.exception({message: 'text box not found'});
        }
        if (!this.getBody().find(SELECTORS.START_SEARCH).length) {
            Notification.exception({message: 'search button not found'});
        }
    };

    ModalSearch.TYPE = 'mod_lanebs-search';
    ModalSearch.prototype = Object.create(Modal.prototype);
    ModalSearch.prototype.constructor = ModalSearch;
    ModalSearch.prototype.breadcrumbs = {};

    ModalSearch.prototype.registerEventListeners = function () {
        Modal.prototype.registerEventListeners.call(this);
        let disabledEnterSubmit = function (e) {
            if (e.keyCode === 13) {
                submitFunction(e);
                return false;
            }
        };
        let disabledKeyUp = function (e) {
            if (e.keyCode !== false) {
                e.preventDefault();
                return false;
            }
        };
        let submitFunction = function (e) {
            e.preventDefault();
            e.stopPropagation();
            let id = $(SELECTORS.CATEGORY_BLOCK).attr('data-id');
            let args = {
                searchParam: $(e.target.form).serializeAssoc(),
                page: 1,
                limit: LIMIT_ON_PAGE,
                catId: id
            };
            $(SELECTORS.PROGRESS).toggleClass('hide');
            ModalSearch.prototype.getAjaxCall('mod_lanebs_search_books', args, ModalSearch.prototype.getSearchResult)
                .then(function () {
                    ModalSearch.prototype.resetPagination();
                    $(SELECTORS.PROGRESS).toggleClass('hide');
                });
        };

        let getCategoryTree = function (e) {
            e.preventDefault();
            e.stopPropagation();
            let categoryId = [null];
            let args = {
                categoryId: categoryId
            };
            ModalSearch.prototype.getAjaxCall('mod_lanebs_category_tree', args, ModalSearch.prototype.printCategories);
        };

        let setPagination = function (e) {
            if ($(e.currentTarget).hasClass('disabled')) {
                return true;
            }
            let page, maxPage;
            if ($(SELECTORS.CONTENT_BLOCK).attr('data-page') === undefined) {
                maxPage = 0;
            }
            else {
                maxPage = parseInt($(SELECTORS.CONTENT_BLOCK).attr('data-page'));
            }
            if ($(e.currentTarget).hasClass(SELECTORS.START_PAGE_CLASS)) {
                if (maxPage > 0) {
                    page = 1;
                }
                else {
                    page = 0;
                }
            }
            else if ($(e.currentTarget).hasClass(SELECTORS.END_PAGE_CLASS)) {
                page = maxPage;
            }
            else {
                page = parseInt($(e.currentTarget).attr('data-page'));
            }
            let id = $(SELECTORS.CATEGORY_BLOCK).attr('data-id');
            let args = {
                searchParam: {'searchString': $(SELECTORS.SEARCH_TEXTBOX).val()},
                page: page,
                limit: LIMIT_ON_PAGE,
                catId: id
            };
            let prevPage = $(SELECTORS.BOOK_PAGINATION).find('a.prev').closest('li.page-item');
            let nextPage = $(SELECTORS.BOOK_PAGINATION).find('a.next').closest('li.page-item');
            let currentPage = $(SELECTORS.BOOK_PAGINATION).find('a.active').closest('li.page-item');
            if (page >= 2) {
                $(prevPage).attr('data-page', page - 1);
                $(prevPage).removeClass('disabled');
            }
            else if (page === 1) {
                $(prevPage).attr('data-page', page - 1);
                $(prevPage).addClass('disabled');
            }
            if (maxPage === page) {
                $(nextPage).addClass('disabled');
            }
            else {
                $(nextPage).removeClass('disabled');
            }
            $(nextPage).attr('data-page', page+1);
            $(currentPage).attr('data-page', page);
            $(currentPage).find('a.active').text(page+' из '+maxPage);
            if (page !== 0 && maxPage !== 0) {
                ModalSearch.prototype.getAjaxCall('mod_lanebs_search_books', args, ModalSearch.prototype.getSearchResult);
            }
        };

        this.getModal().on(CustomEvents.events.activate, SELECTORS.START_SEARCH, submitFunction.bind(this));
        this.getModal().on('click', SELECTORS.START_SEARCH_CLEAR, function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(SELECTORS.SEARCH_TEXTBOX).val('');
            $(SELECTORS.START_SEARCH).trigger('click');
            return false;
        });
        this.getModal().on('keypress', SELECTORS.SEARCH_TEXTBOX, disabledEnterSubmit.bind(this));
        this.getModal().on(CustomEvents.events.activate, SELECTORS.BOOK_PAGINATION, setPagination.bind(this));
        this.getModal().on('keydown', SELECTORS.CONTENT_NAME, disabledKeyUp.bind(this));

        this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function () {
            $(this).trigger('hide');
        }.bind(this));

        this.getModal().on(CustomEvents.events.activate, SELECTORS.CATEGORY_BUTTON, getCategoryTree.bind(this));
    };

    ModalSearch.prototype.getSearchResult = function (response) {
        $(SELECTORS.CONTENT_BLOCK).empty();
        let maxPage = Math.ceil(response.body.total / LIMIT_ON_PAGE);
        if (response.body.items.length) {
            $.each(response.body.items, function(number, item) {
                let descriptionBlock =
                    '<a class="" data-toggle="collapse" href="#collapseDescription'+item.id+'" role="button">' +
                        'Посмотреть описание' +
                    '</a>' +
                    '<div class="collapse" id="collapseDescription'+item.id+'">' + item.description + '</div>';
                let authorBlock = '<span class="book_author">'+ item.author +'</span><br>';
                if (item.description === null || item.description === '') {
                    item.description = '';
                    descriptionBlock = '';
                }
                if (item.author === null || item.author === '') {
                    item.author = '';
                    authorBlock = '';
                }
                $(SELECTORS.CONTENT_BLOCK).append(
                    '<div class="item d-flex" data-id="' + item.id + '">' +
                        '<div class="icon" style="flex:0.03;">' +
                            '<mat-icon class="mat-icon material-icons" style="color:#4285f4;" role="img">book</mat-icon>'+
                        '</div>' +
                        '<div class="item_content" style="flex:0.75;">' +
                            authorBlock +
                            '<span class="book_title">' + item.title + '</span><br>' +
                            descriptionBlock +
                        '</div>' +
                        '<div style="flex:0.23;">' +
                            '<button type="button" name="add_book" class="btn btn-sm ml-3" style="color: #174c8d;' +
                                    'background-color: white;border-color: #4285f4;">Добавить</button>' +
                            '<button type="button" class="trigger_book btn btn-sm ml-3 float-right" style="color: #174c8d;' +
                                    'background-color: white;border-color: #4285f4;">Предпросмотр</button>'+
                            '<br>' +
                            '<span class="book_year float-right" style="margin-top:10px;">'+ item.year +'</span><br>' +
                        '</div>' +
                    '</div><hr style="margin-top:5px;">');
            });
        }
        else {
            $(SELECTORS.CONTENT_BLOCK).append('<div class="item">Книги не найдены</div>');
        }
        $(SELECTORS.CONTENT_BLOCK).attr('data-page', maxPage);
        $(SELECTORS.TRIGGER_BOOK).on('click', function (e) {
            //if ($(ModalBook.CONTENT_BLOCK).length) {}
            let title = 'Предпросмотр';
            ModalBookHandler.init(e, title);
        });
        $(SELECTORS.ADD_BOOK).on('click', function (e) {
            let id = $(e.target).closest('.item').attr('data-id');
            let contentName = $('[name="content_name"]');
            //$('[name="content_name"]').text($(e.currentTarget).siblings('.book_title').text());
            contentName.val($(e.target).closest('.item').find('.item_content .book_title').text());
            contentName.removeClass('is-invalid');
            contentName.siblings('#id_error_content_name').text('');
            $('[name="content"]').val(id);
            $(SELECTORS.CANCEL_BUTTON).trigger('click');
        });
    };

    ModalSearch.prototype.printCategories = function (response) {
        $(SELECTORS.CATEGORY_BLOCK).empty();
        $.each(response.body.items, function (number, item) {
            if (item.available === false) {
                return false;
            }
            $(SELECTORS.CATEGORY_BLOCK).append(
                '<div style="cursor:pointer;color:#174c8d;background-color:white;" ' +
                     'class="item btn-sm" data-id="'+item.id+'" data-expand="'+item.hasChild+'" ' +
                     'data-parent="'+item.parent_id+'">' +
                    '<span>'+item.name+'</span>' +
                '</div>');

            $(SELECTORS.CATEGORY_BLOCK).find('[data-id="'+item.id+'"]').click({item: item}, function (e) {
                e.stopPropagation();
                e.preventDefault();
                let id = $(e.currentTarget).attr('data-id');
                $(SELECTORS.CATEGORY_BLOCK).attr('data-id', id);
                ModalSearch.prototype.clearCurrentCrumb(response.body.items);
                ModalSearch.prototype.breadcrumbs[$(this).find('span').text()] = item;
                if (item.hasChild) {
                    let args = {
                        categoryId: [id]
                    };
                    ModalSearch.prototype.getAjaxCall('mod_lanebs_category_tree', args, ModalSearch.prototype.printCategories);
                }
                else {
                    if ($(this).hasClass('bg-primary')) {
                        $(this).removeClass('bg-primary');
                        let parentId = $(SELECTORS.CATEGORY_BLOCK).find('.item:last').attr('data-parent');
                        $(SELECTORS.CATEGORY_BLOCK).attr('data-id', parentId);
                        id = parentId;
                        ModalSearch.prototype.clearCurrentCrumb(response.body.items);
                    }
                    else {
                        $(this).addClass('bg-primary');
                        $(this).siblings().removeClass('bg-primary');
                        $(SELECTORS.CATEGORY_BLOCK).attr('data-id', id);
                    }
                    /*let args = {
                        searchParam: {'searchString': $(SELECTORS.SEARCH_TEXTBOX).val()},
                        page: 1,
                        limit: LIMIT_ON_PAGE,
                        catId: id
                    };
                    ModalSearch.prototype.getAjaxCall('mod_lanebs_search_books', args, ModalSearch.prototype.getSearchResult)
                        .then(function () {ModalSearch.prototype.resetPagination();});*/
                }
                ModalSearch.prototype.printBreadcrumbs();
                $(SELECTORS.START_SEARCH).trigger('click');
            });
        });

        let parent_id = $(SELECTORS.CATEGORY_BLOCK).find('.item:last').attr('data-parent');
        $(SELECTORS.CATEGORY_BLOCK).prepend(
            '<div style="cursor:pointer;margin-bottom:15px;color:#174c8d;background-color:white;" ' +
                  'class="btn-sm category_back" data-id="'+parent_id+'">' +
                '<span>НАЗАД</span>' +
            '</div>');
        $(SELECTORS.CATEGORY_BLOCK).find('.category_back').on('click', function () {
            let parent_id = $(this).attr('data-id');
            let id = ModalSearch.prototype.searchParentId(parent_id);
            if (id === null) {
                id = 'null';
            }
            $(SELECTORS.CATEGORY_BLOCK).attr('data-id', id);
            let args = {
                categoryId: [id]
            };
            if ($(SELECTORS.CATEGORY_BLOCK).find('.item.bg-primary').length > 0) {
                ModalSearch.prototype.clearCrumbs(2);
            }
            else {
                ModalSearch.prototype.clearCrumbs(1);
            }
            ModalSearch.prototype.printBreadcrumbs();
            ModalSearch.prototype.getAjaxCall('mod_lanebs_category_tree', args, ModalSearch.prototype.printCategories);
        });

    };

    ModalSearch.prototype.printBreadcrumbs = function () {
        let crumbs = ModalSearch.prototype.breadcrumbs;
        let html = '';
        $(SELECTORS.BREADCRUMBS).empty();
        $.each(crumbs, function (item, number) {
            html += '<span class="item" data-id="'+number.id+'">'+item+'</span> -> ';
        });
        html = html.slice(0, -3);
        $(SELECTORS.BREADCRUMBS).append(html);
    };

    ModalSearch.prototype.getAjaxCall = function (methodname, args, callback) {
        return ajax.call([
            {
                methodname: methodname,
                args,
            }
        ])[0].then(function(response) {
            return response;
        }).done(function(response) {
            callback(JSON.parse(response['body']));
            return true;
        }).fail(function (response) {
            alert(response);
            return false;
        });
    };

    ModalSearch.prototype.resetPagination = function () {
        let maxPage = parseInt($(SELECTORS.CONTENT_BLOCK).attr('data-page'));
        $(SELECTORS.BOOK_PAGINATION).find('a.prev').closest('li.page-item').addClass('disabled');
        $(SELECTORS.BOOK_PAGINATION).find('a.prev').closest('li.page-item').attr('data-page', 0);
        $(SELECTORS.BOOK_PAGINATION).find('a.active').closest('li.page-item').attr('data-page', 1);
        $(SELECTORS.BOOK_PAGINATION).find('a.next').closest('li.page-item').attr('data-page', 2);
        if (maxPage > 1) {
            $(SELECTORS.BOOK_PAGINATION).find('a.active').text(1+' из '+maxPage);
            $(SELECTORS.BOOK_PAGINATION).find('a.next').closest('li.page-item').removeClass('disabled');
        }
        else {
            $(SELECTORS.BOOK_PAGINATION).find('a.active').text(maxPage+' из '+maxPage);
            $(SELECTORS.BOOK_PAGINATION).find('a.next').closest('li.page-item').addClass('disabled');
        }
    };

    ModalSearch.prototype.clearCurrentCrumb = function (data) {
        let tmp = ModalSearch.prototype.breadcrumbs;
        let lastCrumb = tmp[Object.keys(tmp)[Object.keys(tmp).length - 1]];
        $.each(data, function (number, item) {
            if (undefined !== lastCrumb) {
                if (item.id === lastCrumb.id) {
                    delete ModalSearch.prototype.breadcrumbs[item.name];
                }
            }
        });
    };

    ModalSearch.prototype.clearCrumbs = function (count) {
        let keys = null;
        let last = null;
        for (let i = 0; i < count; i++) {
            keys = Object.keys(ModalSearch.prototype.breadcrumbs);
            last = keys[keys.length-1];
            delete ModalSearch.prototype.breadcrumbs[last];
        }
    };

    ModalSearch.prototype.searchParentId = function (id) {
        let parentId = null;
        $.each(ModalSearch.prototype.breadcrumbs, function (number, item) {
            if (item.id == id) {
                parentId = item.parent_id;
            }
        });
        return parentId;
    };

    ModalRegistry.register(ModalSearch.TYPE, ModalSearch, 'mod_lanebs/modal_search');

    return ModalSearch;
});