jQuery(document).ready(function ($) {

    $('body').on('click', '.chapter-tabs__title', function () {

        let that = $(this),
            tabsWrapper = that.closest('.chapter-tabs'),
            contentWrapper = that.closest('.list-wrapper-content'),
            activePane = contentWrapper.find('.chapter-pane_active'),
            activeTab = tabsWrapper.find('.chapter-tabs__tab_active'),
            paneId = that.data('pane');

        if (activeTab.length) {
            activeTab.removeClass('chapter-tabs__tab_active');
        }

        if (activePane.length) {
            activePane.removeClass('chapter-pane_active');
        }

        that.parent().addClass('chapter-tabs__tab_active');
        $('#' + paneId).addClass('chapter-pane_active');

        return false;
    });

    if (ajax_chapters.hide_renewal_button) {
        $('#pmpro_renewal_button').remove();
        if ($('.pmpro-renew-button').length) {
            $('.pmpro-renew-button').closest('.wpb_row').remove();
        }
    }

    if (ajax_chapters.hide_become_button) {
        $('#pmpro_become_member_button').remove();
    }

});