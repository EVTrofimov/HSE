
$(document).on("click", ".form-save", function (e) {
    e.preventDefault();

    let form_data ={
        "id": $("#statement-edit").data("id"),
        "name": $("#statement-name").val(),
        "template_pdf_header": $("#template_pdf_header").val(),
        "template_pdf_body": $("#template_pdf_body").val(),
    };
    console.log(form_data)

    $.ajax({
        url: "/admin/statements/ajax_statement_edit.php",
        data: form_data,
        method: 'POST',
        dataType: 'json',
        success: function (response) {
            console.log(response)
            if(response.status === "success") {
                $('#modal-success .modal-title').text("Заявление " + response.text + " успешно сохранено");
                $('#modal-success').modal('show');
            } else {
                $('#modal-error .text-error').text(response.text);
                $('#modal-error').modal('show');
            }
        },
    });
});

