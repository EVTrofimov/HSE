$(document).ready(function() {
    $(".form-item-wrap").each(function () {
        if("table" === $(this).data("type")) {
            let rows = order_form_data[$(this).data("name")];
            if (rows === undefined) return;

            // Копируем структуру строки, кроме первой.
            let rows_num = rows[Object.keys(rows)[0]].length - 1;
            for (let i = 0; i < rows_num; i++) {
                let table_row = $(this).find(".form-table-row:first-child").clone();
                $(this).find(".form-table-rows").append(table_row);
            }

            for (let col_name in rows) {
                console.log(col_name + " -> " + rows[col_name])
                $(this).find('[name="' + $(this).data("name") + '[' + col_name + '][]"').each(function (i) {
                    $(this).val(rows[col_name][i]);
                });
            }

        } else if("file" === $(this).data("type")) {
            let files = order_form_data[$(this).data("name")];

            for (let f_id in files["FILES"]) {
                if (!files["FILES"][f_id]) {
                    continue;
                }

                console.log(files["FILES"][f_id]);

                $(document).find('input[name="'+$(this).data("name")+'[]"]').closest(".upload_container").find(".added_files").append('' +
                    '<div class="added_files__item" data-type="uploaded" data-file-id="'+f_id+'" data-file-name="'+$(this).data("name")+'">' +
                    '<img src="' + SITE_TEMPLATE_PATH + '/img/doc.svg" alt="">' +
                    '<p class="uploaded_filename">' + files["FILES"][f_id]["DESCRIPTION"] + '</p>' +
                    '<button class="file-delete">&times;</button>' +
                    '</div>'
                );
            }
        } else {
            let val = order_form_data[$(this).data("name")];

            if(val === undefined) return

            $(this).find('[name="'+$(this).data("name")+'"]').val(order_form_data[$(this).data("name")]);
        }
    });

    $("form.statement-form input.form__item").each(function () {
        if ($(this).data("mask")) {
            // console.log($(this).data("mask") );

            if ('date' === $(this).data("mask")) {
                Inputmask("99.99.9999", {"placeholder": "дд.мм.гггг"}).mask(this);
            } else if ('phone' === $(this).data("mask")) {
                Inputmask("+7 999 999-99-99").mask(this);
            } else if ('number' === $(this).data("mask")) {
                // Inputmask("9{20}").mask(this);
            } else if ('email' === $(this).data("mask")) {
                Inputmask("email").mask(this);
            } else if ('price' === $(this).data("mask")) {
                Inputmask({
                    alias: 'numeric',
                    allowMinus: false,
                    digits: 2,
                    max: 99999999999999.99
                }).mask(this);
            } else if ('inn' === $(this).data("mask")) {
                Inputmask("9{12}").mask(this);
            } else if ('snils' === $(this).data("mask")) {
                Inputmask("9{11}").mask(this);
            } else if ('city' === $(this).data("mask")) {
                $("#" + $(this).attr('id')).suggestions({
                    token: DADATA_TOKEN,
                    count: 5,
                    type: "ADDRESS",
                    bounds: "city",
                    constraints: {
                        locations: { city_type_full: "город" }
                    },
                    formatSelected: function (suggestion) {
                        return suggestion.data.city;
                    },
                    formatResult: function (value, currentValue, suggestion, options) {
                        let newValue = suggestion.data.city;
                        suggestion.value = newValue;
                        return $.Suggestions.prototype.formatResult.call(this, newValue, currentValue, suggestion, options);
                    },
                    //onSelect: showCitySuggestion
                });
            } else if ('address' === $(this).data("mask")) {
                // $(this).closest(".form_box").append('<div id="selector-' + $(this).attr('id') + '" ' +
                //    'class="input-address-selector"></div>');
                $("#" + $(this).attr('id')).suggestions({
                    token: DADATA_TOKEN,
                    type: "ADDRESS",
                    count: 5,
                    onSelect: showAddressSuggestion
                });
            } else if (['text', 'name',].includes($(this).data("mask"))) {

            } else {
                // Если число, то берём передаваемую маску
                Inputmask($(this).data("mask")).mask(this);
            }
        }
    });

    //ymaps.ready(init);
});

/*$(document).on('input', 'input[data-mask="city"], input[data-mask="address"]', function (e) {

    let type = $(this).data('mask')
    let types = 'street,district,locality,area,province,country,house';
    if("city" == type) {
        types = 'locality';
    }

    let input_str = $(this).val();

    let input_id = $(this).attr('id');
    BX.ajax.runComponentAction(
        'kit:statement',
        'getSuggest', {
            mode: 'class',
            signedParameters: params.signedParameters,
            data: {"input": input_str, "types": types},
        }).then(
        function (response) {
            console.log('success', response.data)
            // let results = response.data.results;
            // let results_str = "";
            // let el_str = "";
            // for (let i in results) {
            //     // console.log(results[i].title.text);
            //     if("city" == type) {
            //         el_str = results[i].title.text;
            //     } else {
            //         el_str = results[i].address.formatted_address;
            //     }
            //     results_str += "<a class='selector-address-value link link_black' data-input-id='"+input_id+"'>"+el_str+"</a>";
            // }
            //
            // $("#selector-"+input_id).html(results_str).show();
        },
        function (response) {
            console.log('error', response)
            if ((response.errors[0].code === 1) || (response.errors[0].code === 2)) {
                //$.fancybox.open(response.errors[0].message);
            }

            if (response.status === 'error') {
                //в случае ошибки, будет вызван этот обработчик
                // $.fancybox.open('Ошибка сохранения! ' + response.errors[0].message);
            }
        }
    );
});*/

// $(document).on('click', '.selector-address-value', function (e) {
//     $("#selector-"+$(this).data("input-id")).hide()
//     $("#"+$(this).data("input-id")).val($(this).text());
// })

// $(document).mouseup( function(e){ // событие клика по веб-документу
//     let div = $( '.input-address-selector' ); // тут указываем ID элемента
//     if ( !div.is(e.target) // если клик был не по нашему блоку
//         && div.has(e.target).length === 0 ) { // и не по его дочерним элементам
//         div.hide(); // скрываем его
//     }
// });
// $(document).on('keyup', function(e) {
//     if ( e.key == "Escape" ) {
//         $( '.input-address-selector' ).hide();
//     }
// });


$(document).on('click', '.btn_show_next_step', function (e) {
    e.preventDefault();
    $(this).closest(".step_block").hide(1000).next(".step_block").show(1000);
});
$(document).on('change', ".btn_add_files", function (e) {
    // e.preventDefault();
    if (undefined === this.files[0])
        return;

    // console.log($(this).closest(".upload_container"));
    let file_name = '';
    for(let i = 0; i < this.files.length; i++) {
        file_name = this.files[i].name;
        if (file_name.length > 70) {
            let new_fn = file_name.substring(0, 35);
            new_fn += ' ... ';
            new_fn += file_name.substring(file_name.length - 35, file_name.length);
            file_name = new_fn;
        }
        $(this).closest(".upload_container").find(".added_files").append('' +
            '<div class="added_files__item" data-type="new">' +
            '<img src="' + SITE_TEMPLATE_PATH + '/img/doc.svg" alt="">' +
            '<p class="uploaded_filename">' + file_name + '</p>' +
            '<button class="file-delete">&times;</button>' +
            '</div>'
        );
    }
});

$(document).on('click', ".file-delete", function (e) {
    e.preventDefault();
    $(this).closest(".upload_container").find(".btn_add_files").val('');
    let $file = $(this).closest(".added_files__item");


    if($file.data("type") === "uploaded") {
        let file_id = $file.data("file-id");
        let file_name = $file.data("file-name");
        let order_id = $("input[name=\"ORDER_ID\"]").val();
        let sessid = $("#sessid").val();

        BX.ajax.runComponentAction(
            'kit:statement',
            'removeFileFromResult', {
                mode: 'class',
                signedParameters: params.signedParameters,
                data: {"file_id": file_id, "order_id": order_id, "file_name": file_name, "sessid": sessid},
            }).then(
            function (response) {
                console.log('success', response.data)
                $file.fadeOut(300, function() { $(this).remove();});
            },
            function (response) {
                console.log('error', response)
                $('#modal-error .text-error').text(response.errors[0].message);
                $('#modal-error').modal('show');
            }
        );
    } else {
        $file.fadeOut(300, function() { $(this).remove();});
    }
});

$(document).on("click", ".btn_create_form", function (e) {
    e.preventDefault();

    let has_error = false,
        step_block = $(this).closest(".step_block");

    $(document).find(".form_box, .big_form, .form__checkbox").removeClass("invalid");

    $("form.statement-form input, form.statement-form textarea" ).each(function () {
        // console.log( ['text', 'email', 'date'].includes($(this).data('mask')));
         console.log($(this).attr('name') + ': ' + $(this).val());
        // console.log($(this).attr('required'))
        if ($(this).val() === ''
            && $(this).attr('required') === 'required'
            && !['email', 'file'].includes($(this).attr('type'))
        ) {
            has_error = true;
            $(this).closest(".form_box, .big_form").addClass("invalid");
            console.log("Ошибка: " + $(this).attr('name'));
        }

        // Убрали временно обязательность для файлов
        // if ('file' === $(this).attr('type') && !this.files.length && !$('.added_files__item[data-type="uploaded"]').length) {
        //     has_error = true;
        //     $(this).closest(".form_box").addClass("invalid");
        //     $(this).closest(".form_box").find()
        //
        //     console.log("Ошибка: " + $(this).attr('name'));
        // }

        // Проверяем email
        if ('email' === $(this).attr('type')
            && !String($(this).val())
                .toLowerCase()
                .match(
                    /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|.(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
                )
        ) {
            has_error = true;
            $(this).closest(".form_box, .big_form").addClass("invalid");
            console.log("Ошибка: " + $(this).attr('name'));
        }
    });

    let checked_flag = false;
    $("form.statement-form .radio_inputs_wrap").each(function () {
        checked_flag = false;
        $(this).find("input").each(function () {
            if ($(this).is(":checked")) {
                checked_flag = true;
            }
        });

        if ($(this).data('required') === 'required' && !checked_flag) {
            has_error = true;
            $(this).closest(".form_box").addClass("invalid");
            console.log("Ошибка: " + $(this).find("input").attr('name'));
        }
    });


    let ch_selector = '';
    ['checkbox-user-agreement', 'checkbox-personal', 'checkbox-data-is-correct'].forEach(function (ch) {
        ch_selector = $("input[name='" + ch + "']");
        if (!ch_selector.is(":checked")) {
            has_error = true;
            ch_selector.closest("label").addClass("invalid");
            console.log("Ошибка: " + ch);
        }
    });

    if (has_error) {
        console.log("Ошибка: что-то заполнено не так.");
        $('#modal-error .text-error').text('Вы не заполнили обязательные поля.');
        $('#modal-error').modal('show');
        return;
    }
    let form = $(".step_block[data-step='2'] form").serializeArray();
    let form_data = [];
    form_data = new FormData($("#statement-form")[0]);
    // $.each(form, function (i, field) {
    //     // form_data[field.name] = field.value;
    //     form_data.append(field.name, field.value);
    // });
    // form_data.append($(".btn_add_files").attr('name'), $(".btn_add_files")[0].files[0]);

    // for (let p of form_data) {
    //     let name = p[0];
    //     let value = p[1];
    //
    //     console.log(name, value)
    // }

    console.log(form_data);

    BX.ajax.runComponentAction(
        'kit:statement',
        'createFormResult', {
            mode: 'class',
            signedParameters: params.signedParameters,
            data: form_data,
        }).then(
        function (response) {
            console.log('success', response.data)
            // BX.localStorage.set("service_edit_data", {'order_id': response.data.order_id, "order_guid": response.data.order_guid});

            window.location.href = response.data.redirect_url;
        },
        function (response) {
            console.log('error', response)
            $('#modal-error .text-error').text(response.errors[0].message);
            $('#modal-error').modal('show');
        }
    );
});

$(document).on('click', '.prev_step', function (e) {
    e.preventDefault();
    $(this).closest(".step_block").hide(1000).prev(".step_block").show(1000);
});


function join(arr) {
    var separator = arguments.length > 1 ? arguments[1] : ", ";
    return arr.filter(function(n){return n}).join(separator);
}
function typeDescription(type) {
    var TYPES = {
        'INDIVIDUAL': 'Индивидуальный предприниматель',
        'LEGAL': 'Организация'
    }
    return TYPES[type];
}

function showCitySuggestion(suggestion) {
    console.log(suggestion);
    let data = suggestion.data;
    if (!data)
        return;

    $(this).val(suggestion.value);
}

function showAddressSuggestion(suggestion) {
    console.log(suggestion);
    let data = suggestion.data;
    if (!data)
        return;

    $(this).val(suggestion.value);
    // $("#type").text(
    //     typeDescription(data.type) + " (" + data.type + ")"
    // );
    console.log(data);
    // $(this).val(data.inn + ', ' + suggestion.value + ', ' + data.address.value);
    // console.log(data.inn);
    // console.log(suggestion.value);
}
function showSuggestion(suggestion) {
    console.log(suggestion);
    let data = suggestion.data;
    if (!data)
        return;

    $(this).val(suggestion.value);
    // $("#type").text(
    //     typeDescription(data.type) + " (" + data.type + ")"
    // );
    console.log(data);
    // $(this).val(data.inn + ', ' + suggestion.value + ', ' + data.address.value);
    // console.log(data.inn);
    // console.log(suggestion.value);
}


$(document).on("click", ".fill_test_values", function (e) {
    e.preventDefault();

    for (let key in TEST_DATA) {
        $('[name="'+key+'"]').val(TEST_DATA[key]);
    }
});

$(document).on("click", ".form-table-add-row", function (e) {
    e.preventDefault();

    let table = $(this).closest(".form-item-wrap[data-type='table']");
    let table_row = $(table).find(".form-table-row:first-child" ).clone();

    table_row.find("input").val('');
    table_row.find("select").val(table_row.find("select option:first").val());
    console.log(table_row);

    table.find(".form-table-rows").append(table_row);
});