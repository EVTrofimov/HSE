$(document).on("click", ".show-modal-form-add", function (e) {
    e.preventDefault();

    $('#modal-form-add').data('this', this).modal('show');
});

$(document).on("click", ".form-add", function (e) {
    e.preventDefault();

    let form_data = {"form_name": $("#form-name").val(), };
    console.log(form_data)

    $.ajax({
        url: "/admin/forms/ajax_form_add.php",
        data: form_data,
        method: 'POST',
        dataType: 'json',
        success: function (response) {
            if(response.status === "success") {
                // $('#modal-success .modal-title').text("Форма " + response.text + " успешно создана");
                // $('#modal-success').modal('show');
                location.reload();
            } else {
                $('#modal-error .text-error').text(response.text);
                $('#modal-error').modal('show');
            }
        }
    });
});