jQuery(document).ready(function ($) {

    let pickers = $('.icp-dd'),
        rowNumber = pickers.length;

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

    $('body').on('click', '.pmpro-chapters-row__add', function () {

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

    $('body').on('click', '.pmpro-chapters-row__delete', function () {

        $(this).closest('.pmpro-chapters-row').remove();

        return false;
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

});