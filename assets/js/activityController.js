/*
 * Scripts for the Relation controller behavior.
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

    $.oc.activityController = new ActivityController;
}(window.jQuery);
