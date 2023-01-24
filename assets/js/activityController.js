/*
 * Scripts for the Activity Log controller behavior.
 */
+ function ($) {
    "use strict";

    var ActivityController = function () {

        this.clickActivityRecord = function (recordId, triggerEl, handler) {
            handler = typeof handler !== 'undefined' ? handler : 'onViewLogItemDetails';

            $(triggerEl).popup({
                handler: handler,
                size: 'huge',
                extraData: {
                    'luketowers-easyaudit-recordId': recordId,
                }
            });
        }
    }

    $.wn.activityController = new ActivityController;
}(window.jQuery);
