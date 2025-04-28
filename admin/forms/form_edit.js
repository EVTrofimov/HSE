$(function() {
    $(document).find(".form-constructor .form-items").after(
        '<button class="constructor-btn show-modal-item-add">Добавить элемент</button>'
    );
    $(document).find(".form-constructor .form-item-wrap[data-type='table']").append(
        '<button class="constructor-btn show-modal-item-add" data-type="table">Добавить элемент (колонку)</button>'
    );
    $(document).find(".form-constructor .form-section-title").append('<button class="constructor-btn section-remove">Удалить</button>');

    $(document).find(".form-constructor .form-item-wrap .form-item-title").append('<button class="constructor-btn item-remove">Удалить</button>');

    $('#modal-form-item-add input[name="form-item-select-source"]').autocomplete({
        source: function( request, response ) {
            // Fetch data
            $.ajax({
                url: "/admin/forms/ajax_get_rbooks_list.php",
                type: 'post',
                dataType: "json",
                data: {
                    search: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        appendTo: "#label-form-item-select-source",
        select: function(event, ui) {
            console.log(ui.item.rbook_id);
            $('#modal-form-item-add input[name="form-item-select-source"]').data("rbook-id", ui.item.rbook_id)
        },
    });

});

$(document).on("click", ".form-create", function (e) {
    e.preventDefault();

    // Чистим HTML-форму от лишних управляющих элементов
    let form_html = $(document).find(".form-constructor").clone();
    form_html.find(".constructor-btn").remove();
    form_html = form_html.html().trim();

    let form_data = {"form_id": $("#form-edit").data("id"), "form_name": $("#form-name").val(), "form_html": form_html};
    console.log(form_data)

    $.ajax({
        url: "/admin/forms/ajax_form_edit.php",
        data: form_data,
        method: 'POST',
        dataType: 'json',
        success: function (response) {
            if(response.status === "success") {
                $('#modal-success .modal-title').text("Форма " + response.text + " успешно сохранена");
                $('#modal-success').modal('show');
            } else {
                $('#modal-error .text-error').text(response.text);
                $('#modal-error').modal('show');
            }
        }
    });
});

$(document).on("click", ".section-add", function (e) {
    e.preventDefault();

    let section_title = prompt('Введите название:').trim();

    let section_code =
        '<div class="form-section">' +
        '<p class="form-section-title">'+section_title+' <button class="constructor-btn section-remove">Удалить</button></p>' +
        '<div class="form-items"></div>' +
        '<button class="constructor-btn show-modal-item-add">Добавить элемент</button>' +
        '</div>';

    $(document).find(".form-constructor").append(section_code);
});

$(document).on("click", ".section-remove", function (e) {
    e.preventDefault();

    $(this).closest(".form-section").remove();
});

$(document).on("click", ".show-modal-item-add", function (e) {
    e.preventDefault();

    $('#modal-form-item-add').data('this', this).modal('show');
});
$(document).on("click", ".item-add", function (e) {
    e.preventDefault();

    let modal = "#modal-form-item-add";
    let pressed_btn = $(modal).data('this');

    // Название, которое выводится над input
    let item_title= $(modal + " input[name=\"form-item-title\"]").val();

    // Сокр. название для атрибута name у input
    let item_name = $(modal + " input[name=\"form-item-name\"]").val();

    console.log(item_title);
    console.log(item_name);

    if (item_title === "" || item_name === "") {
        // TODO: Обработка ошибок
        // TODO: Добавить проверку, чтобы название не начиналось с цифры
        // TODO: Проверять на существование элемент формы
        return;
    }

    let item_type = $(modal + " select[name=\"form-item-type\"] option:selected").val();

    let item_name_tr = BX.translit(item_name,
        {"max_len": 100, "change_case": "L", "replace_space": "_", "replace_other": "_"});


    let item_codes =
    {
        "text":
            '<div class="form-item-wrap" data-type="text" data-name="' + item_name_tr + '">' +
                '<div class="form_box form-item">' +
                '<label for="ID_' + item_name_tr + '" class="form_box__container">' +
                    '<p class="form-item-title">' + item_title + ' <button class="constructor-btn item-remove">Удалить</button></p>' +
                    '<div class="info"></div>' +
                '</label>' +
                '<input id="ID_' + item_name_tr + '" name="' + item_name_tr + '" class="form__item" placeholder="' + item_title + '" ' +
                    'type="text" data-type="text" data-name="' + item_name_tr + '" value="">' +
                '</div>' +
            '</div>',
        "select":
            '<div class="form-item-wrap" data-type="select" data-name="' + item_name_tr + '">' +
                '<div class="form_box form-item">' +
                    '<label for="ID_' + item_name_tr + '" class="form_box__container">' +
                        '<p class="form-item-title">' + item_title + ' <button class="constructor-btn item-remove">Удалить</button></p>' +
                        '<div class="info"></div>' +
                    '</label>' +
                    '<select id="ID_' + item_name_tr + '" name="' + item_name_tr + '" class="form__item" data-type="select" ' +
                        'data-name="' + item_name_tr + '">' +
                    '</select>' +
                '</div>' +
            '</div>',
        "textarea":
            '<div class="form-item-wrap" data-type="textarea" data-name="' + item_name_tr + '">' +
                '<div class="form_box form-item">' +
                    '<label for="ID_' + item_name_tr + '" class="form_box__container">' +
                        '<p class="form-item-title">' + item_title + ' <button class="constructor-btn item-remove">Удалить</button></p>' +
                        '<div class="info"></div>' +
                    '</label>' +
                    '<textarea id="ID_' + item_name_tr + '" name="' + item_name_tr + '" type="text" class="form__item " ' +
                        'placeholder="' + item_title + '" data-type="textarea"></textarea>' +
                '</div>' +
            '</div>',
        "file":
            '<div class="form-item-wrap" data-type="file" data-name="' + item_name_tr + '" data-title="' + item_title + '">' +
                '<div class="form_box">' +
                    '<div class="form_box__container">' +
                        '<p class="form-item-title">' + item_title + ' <button class="constructor-btn item-remove">Удалить</button></p>' +
                        '<div class="info"></div>' +
                    '</div>' +
                    '<div class="upload_container">' +
                        '<input id="ID_' + item_name_tr + '" name="' + item_name_tr + '[]" type="file" class="btn_add_files" hidden multiple="multiple"/>' +
                        '<label for="ID_' + item_name_tr + '" class="btn btn_simple btn_img">Загрузить файлы' +
                            '<img src="'+SITE_TEMPLATE_PATH+'/img/add.svg" alt="">' +
                        '</label>' +
                        '<div class="added_files"></div>' +
                    '</div>' +
                '</div>' +
            '</div>',
        "table":
            '<div class="form-item-wrap" data-type="table" data-name="' + item_name_tr + '">' +
                '<div class="form-table-rows">' +
                    '<div class="form-table-row"></div>' +
                '</div>' +
                '<button class="constructor-btn show-modal-item-add" data-type="table">Добавить элемент (колонку таблицы)</button>' +
                '<button class="btn btn_simple form-table-add-row">Добавить элемент (строку таблицы)</button>' +
            '</div>'
    };

    let table_name;
    if($(pressed_btn).data("type") === "table") {

        let table = $(pressed_btn).closest(".form-item-wrap[data-type='table']");
        console.log($(pressed_btn));
        console.log($(pressed_btn).closest(".form-item-wrap[data-type='table']"))
        table_name = table.data("name");
        let table_item_codes = {
            "text":
                '<div class="form-table-item-wrap" data-type="text" data-name="' + item_name_tr + '">' +
                    '<div class="form_box form-item">' +
                        '<label class="form_box__container">' +
                            '<p class="form-item-title">' + item_title + ' <button class="constructor-btn item-remove" data-type="table">Удалить</button></p>' +
                            '<div class="info"></div>' +
                        '</label>' +
                        '<input name="' + table_name + '[' + item_name_tr + '][]" class="form__item" ' +
                            'placeholder="' + item_title + '" ' +
                            'type="text" data-type="text" data-name="' + item_name_tr + '" value="">' +
                    '</div>' +
                '</div>',
            "select":
                '<div class="form-table-item-wrap" data-type="select" data-name="' + item_name_tr + '">' +
                    '<div class="form_box form-item">' +
                        '<label class="form_box__container">' +
                            '<p class="form-item-title">' + item_title + ' <button class="constructor-btn item-remove" data-type="table">Удалить</button></p>' +
                            '<div class="info"></div>' +
                        '</label>' +
                        '<select name="' + table_name + '[' + item_name_tr + '][]" class="form__item" data-type="select" ' +
                            'data-name="' + item_name_tr + '">' +
                        '</select>' +
                    '</div>' +
                '</div>',
        };
        console.log(table_item_codes[item_type]);

        console.log(table.find(".form-table-row"))
        table.find(".form-table-row").append(table_item_codes[item_type]);
    } else {
        $(pressed_btn).closest(".form-section").find(".form-items").append(item_codes[item_type]);
    }

    let select_options = "";
    if ("select" === item_type) {
        let rbook_id = $(modal + " input[name=\"form-item-select-source\"]").data("rbook-id");
        let select_name = $(pressed_btn).data("type") === "table" ?
            'select[name="'+table_name+'['+item_name_tr+'][]"]' : 'select[name="'+item_name_tr+'"]';

        $.ajax({
            url: "/admin/forms/ajax_get_select_vals.php",
            data: {"rbook_id": rbook_id},
            method: 'POST',
            dataType: 'json',
            success: function (response) {
                console.log(response);
                if(response.status === "success") {
                    Object.keys(response.list).forEach(key=>{
                        $(select_name).append('<option value="'+response.list[key]+'">'+response.list[key]+'</option>');
                    });
                } else {
                    $('#modal-error .text-error').text(response.text);
                    $('#modal-error').modal('show');
                }
            }
        });
    }

    $.modal.close();
});

$(document).on("click", ".item-remove", function (e) {
    e.preventDefault();

    if("table" === $(this).closest(".form-item-wrap").data("type")) {
        $(this).closest(".form-table-item-wrap").remove();
    } else {
        $(this).closest(".form-item-wrap").remove();
    }
});

$(document).on("change", "#modal-form-item-add select[name='form-item-type']", function (e) {
    e.preventDefault();
    let item_type = $("#modal-form-item-add select[name='form-item-type'] option:selected").val();
    $("#modal-form-item-add input[name='form-item-select-source']").hide();
    if("select" === item_type) {
        $("#modal-form-item-add input[name='form-item-select-source']").show();
    } else {

    }
});





