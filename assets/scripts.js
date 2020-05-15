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

});