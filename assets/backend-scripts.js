jQuery(document).ready(function ($) {

    let pickers = $('.icp-dd'),
        rowNumber = pickers.length,
        body = $('body');

    if (pickers.length) {
        pickers.iconpicker();

        pickers.on('iconpickerSelected', function (event) {
            let iconInput = $(this).closest('.pmpro-chapters-row').find('.pmpro-chapters-row__social-icon');
            console.log($(this));
            console.log($(this).closest('.pmpro-chapters-row'));
            if (iconInput.length) {
                iconInput.val(event.iconpickerValue);
            }
        });
    }

    body.on('click', '.pmpro-chapters-row__add', function () {

        let newRow = add_social_row(++rowNumber);

        $(this).before(newRow);

        let pickers = $('.icp-dd');
        if (pickers.length) {
            pickers.iconpicker();

            pickers.on('iconpickerSelected', function (event) {
                let iconInput = $(this).closest('.pmpro-chapters-row').find('.pmpro-chapters-row__social-icon');

                if (iconInput.length) {
                    iconInput.val(event.iconpickerValue);
                }
            });
        }

        return false;
    });

    body.on('click', '.pmpro-chapters-row__delete', function () {

        $(this).closest('.pmpro-chapters-row').remove();

        return false;
    });

    body.on('change', '#chapter_country', function () {
        let state_select = $('#chapter_state'),
            data = {
                country_code: $(this).val(),
                action: 'chapters_get_states',
            };

        $.post(ajax_chapters.ajax_url, data, function (resp) {
            if (resp.success) {
                if (0 === resp.data.output_states.length) {
                    state_select.closest('.pmpro-chapters-row').fadeOut();
                } else {
                    state_select.closest('.pmpro-chapters-row').fadeIn();
                }
                $('#chapter_state').html(resp.data.output_states);
            }
        });
    });

    body.on('click', '#pmpro-chapters-import', function () {
        let form = $('#pmpro-cahpters-import-form'),
            fileUploader = form.find('input[type="file"]');
        fileUploader.trigger('click');
        fileUploader.change(function () {
            form.submit();
        });
    });

    body.on('click', '#pmpro-chapters-total-import', function () {
        let form = $('#pmpro-cahpters-total-import-form'),
            fileUploader = form.find('input[type="file"]');
        fileUploader.trigger('click');
        fileUploader.change(function () {
            form.submit();
        });
    });

    function add_social_row(rowNumber) {

        let output = '  <div class="pmpro-chapters-row">' +
            '               <label for="chapter_social_' + rowNumber + '" class="pmpro-chapters-row__label">Link :</label>' +
            '                <div class="btn-group">' +
            '                    <button type="button" class="btn btn-primary iconpicker-component">' +
            '                        <i class="fas fa-link"></i>' +
            '                    </button>' +
            '                    <button type="button" class="icp icp-dd btn btn-primary dropdown-toggle"' +
            '                            data-selected="fa-link" data-toggle="dropdown">' +
            '                        <span class="caret"></span>' +
            '                        <span class="sr-only">Toggle Dropdown</span>' +
            '                    </button>' +
            '                    <div class="dropdown-menu"></div>' +
            '                </div>' +
            '                <input type="hidden" name="chapter_social[' + rowNumber + '][icon]" class="pmpro-chapters-row__social-icon"' +
            '                       value="fas fa-link">' +
            '                <input type="text" class="pmpro-chapters-row__input" id="chapter_social_' + rowNumber + '"' +
            '                       name="chapter_social[' + rowNumber + '][href]">' +
            '                <a href="#" class="pmpro-chapters-row__delete"><i class="fas fa-backspace"></i></a>' +
            '            </div>';

        return output;
    }

    function prepareDropdowns() {

        if ($('#chapter_president_id').length) {
            $('#chapter_president_id').selectWoo();
        }

        if ($('#chapter_region').length) {
            $('#chapter_region').selectWoo();
        }

        if ($('#chapter_country').length) {
            $('#chapter_country').selectWoo();
        }

        if ($('#chapter_state').length) {
            $('#chapter_state').selectWoo();
        }

    }

    prepareDropdowns();

});