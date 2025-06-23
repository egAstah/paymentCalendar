$(document).ready(function () {
    $(document).on('click', '.select_type_table', function () {
        $('.list_tables').hide()
        $('.list_tables[data-id="' + $(this).attr('id') + '"]').fadeIn(300)
    })
})