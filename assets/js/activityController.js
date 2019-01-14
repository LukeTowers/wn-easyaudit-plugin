/*
 * Scripts for the Relation controller behavior.
 */

+ function ($) {
    "use strict";

    var ActivityController = function () {

        this.clickActivityRecord = function (recordId, sessionKey) {
            var newPopup = $('<a />'),
                $container = $('#' + recordId),
                requestData = paramToObj('data-request-data', $container.data('request-data'))

            newPopup.popup({
                handler: 'onClickViewList',
                size: 'huge',
                extraData: $.extend({}, requestData, {
                    'recordId': recordId,
                    '_session_key': sessionKey
                })
            });

        }

        function paramToObj(name, value) {
            if (value === undefined) value = ''
            if (typeof value == 'object') return value

            try {
                return JSON.parse(JSON.stringify(eval("({" + value + "})")))
            } catch (e) {
                throw new Error('Error parsing the ' + name + ' attribute value. ' + e)
            }
        }

    }

    $.oc.activityController = new ActivityController;
}(window.jQuery);
