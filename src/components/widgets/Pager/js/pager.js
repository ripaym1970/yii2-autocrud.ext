$(function() {
    let PagerNew = function() {
        let options;
        let addToHistory = function(url, data) {
            if (options.addToHistory === true && window.history && window.history.pushState) {
                if (options.history === true) {
                    let state = data ? { data: data } : null;
                    window.history.pushState(state, '', url);
                } else {
                    window.history.replaceState(null, '', url);
                }
            }
        };

        this.init = function(data) {
            // Если нет данных, то нет пагинатора на странице
            if (typeof(data) == 'undefined') {
                return;
            }

            options = data;

            // Вешаем обработчик на клик по кнопке "Показать ещё"=data.btn
            $(document).on('click', data.btn, function() {
                // Когда кликнули, то в $(this) будет кнопка "Показать ещё"
                let el = $(this);
                // Получаем из нее параметр 'is-request'
                let isRequest = el.data('is-request');
                // Получаем родителя для '.pager-new-main-container'. Это '#products-paginator'
                let container = el.closest(el.data('container'));
                // Получаем url для ajax-запроса получения данных 'ещё'. Это '/products/index?id=136&page=2&pageSize=10', т.е. это ссылка на следующую страницу
                let url = el.data('url');
                // Получаем ссылку на 'троеточие' которое будем анимировать
                let loader = el.find('span');
                // Может быть несколько контейнеров (полный и короткий вид). Это '#products-container'
                let itemsContainerSelector = el.data('items-container-selector').split(',');

                // Если не нажимали "ещё", т.е. запрос еще не ушел
                if (!isRequest) {
                    // Поменяем, чтобы клиент не нажал несколько раз "ещё"
                    el.data('is-request', 1).attr('data-is-request', 1);
                    // Анимируем загрузку
                    loader.addClass(el.data('loader-class'));
                    options.showMoreParam.value = options.showMoreParam.value === false ? el.data('current-page') : options.showMoreParam.value;

                    // Отправляем сам запрос по url
                    // Если страница не "тяжелая", то не надо ничего добавлять/изменять.
                    // В этом случае этот js сам найдет новые элементы и обновленный пагинатор.
                    // В противном случае для ответа надо сгенерировать html-код только с новыми элементами и обновленным пагинатором.
                    $.get(url + '&' + options.showMoreParam.name + '=' + options.showMoreParam.value, function(response) {
                        if (typeof response !== 'string' && response.html) {
                            response = response.html;
                        }
                        // Если контейнер находится на верхнем уровне полученного HTML, то find его не найдет, т.к. смотрит только дочерние элменты
                        // Поэтому оборачиваем его в DIV, чтобы find искал по всему HTML
                        response = '<div>' + response + '</div>';

                        //--- заменяем пагинатор
                        container.replaceWith($(response).find(data.btn).closest(el.data('container')));

                        //--- добавляем элементы на страницу
                        $.each(itemsContainerSelector, function (i, selector) {
                            $(selector).append($(response).find(selector).children());
                        });

                        addToHistory(url);

                        //$.observer.notify('page-height-changed');

                    }).fail(function() {
                        window.location.href = url; // load by link
                    });
                }
            });
        };
    };

    // Если на странице есть триггер, установленный frontend/components/widgets/PagerNew.php
    jQuery(function ($) {
       $(document).trigger('pager-new-ready', [{
            "btn":".pager-new-btn-more-items", // Нажатие на кнопку отслеживается по этому классу
            "showMoreParam":{
                "name":"_loadMore",
                "value":false
            },
            "history":true,
            "addToHistory":true
       }]);
    });

    // ?
    $(document).on('pager-new-ready', function(e, data) {
        (new PagerNew()).init(data); // Создаем js-обработчик для пагинатора и инициируем данными из триггера
    });
});
