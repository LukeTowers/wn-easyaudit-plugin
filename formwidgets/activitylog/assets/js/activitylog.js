/*
 * Field ActivityLog plugin
 *
 * Data attributes:
 * - data-control="field-activitylog" - enables the plugin on an element
 * - data-option="value" - an option with a value
 *
 * JavaScript API:
 * $('a#someElement').fieldActivityLog({...})
 */

+ function ($) {
    "use strict";
    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype

    // FIELD ACTIVITYLOG CLASS DEFINITION
    // ============================

    var ActivityLog = function (element, options) {
        this.options = options

        this.$el = $(element)

        $.oc.foundation.controlUtils.markDisposable(element)
        Base.call(this)
        this.init()
    }

    ActivityLog.prototype = Object.create(BaseProto)
    ActivityLog.prototype.constructor = ActivityLog

    ActivityLog.DEFAULTS = {
        // @TODO: Any necessary processes here
    }

    ActivityLog.prototype.init = function () {
        this.$el.on('dispose-control', this.proxy(this.dispose))
    }

    ActivityLog.prototype.dispose = function () {
        this.$el.off('dispose-control', this.proxy(this.dispose));
        this.$el.removeData('oc.activitylog');
        this.$el = null
        this.options = null
        BaseProto.dispose.call(this)
    }

    // Deprecated
    ActivityLog.prototype.unbind = function () {
        this.dispose()
    }

    // FIELD ACTIVITYLOG PLUGIN DEFINITION
    // ============================

    var old = $.fn.fieldActivityLog

    $.fn.fieldActivityLog = function (option) {
        var args = Array.prototype.slice.call(arguments, 1),
            result
        this.each(function () {
            var $this = $(this)
            var data = $this.data('oc.activitylog')
            var options = $.extend({}, ActivityLog.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.activitylog', (data = new ActivityLog(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.fieldActivityLog.Constructor = ActivityLog

    // FIELD ACTIVITYLOG NO CONFLICT
    // =================

    $.fn.fieldActivityLog.noConflict = function () {
        $.fn.fieldActivityLog = old
        return this
    }

    // FIELD ACTIVITYLOG DATA-API
    // ===============

    $(document).render(function () {
        $('[data-control="field-activitylog"]').fieldActivityLog()
    });

}(window.jQuery);


+ function ($) {
    "use strict";

    var ActivityLogWidget = function () {

        this.clickActivityRecord = function (recordId, sessionKey) {
            var newPopup = $('<a />'),
                $container = $('#' + recordId),
                requestData = paramToObj('data-request-data', $container.data('request-data'))

            newPopup.popup({
                handler: 'onViewLogItemDetails',
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

    $.oc.activityLogWidget = new ActivityLogWidget;
}(window.jQuery);
