YUI.add('moodle-availability_dripcontent-form', function (Y, NAME) {

/**
 * JavaScript for form editing dripcontent conditions.
 *
 * @module moodle-availability_dripcontent-form
 */
M.availability_dripcontent = M.availability_dripcontent || {};

/**
 * @class M.availability_dripcontent.form
 * @extends M.core_availability.plugin
 */
M.availability_dripcontent.form = Y.Object(M.core_availability.plugin);

/**
 * Enrolment methods available in the course.
 * @property enrolmentMethods
 * @type Object
 */
M.availability_dripcontent.form.enrolmentMethods = {};

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Object} params Parameters from PHP (modes, units, enrolmentmethods)
 */
M.availability_dripcontent.form.initInner = function(params) {
    this.modes = params.modes;
    this.units = params.units;
    this.enrolmentMethods = params.enrolmentmethods || {};
};

/**
 * Gets the form node for a new or existing condition.
 *
 * @method getNode
 * @param {Object} json The saved JSON data for the condition (or empty for new)
 * @return {Y.Node} The form node
 */
M.availability_dripcontent.form.getNode = function(json) {
    // Default values.
    var mode = json.mode || 'coursedays';
    var unit = json.unit || 'days';
    var value = json.value !== undefined ? json.value : 1;
    var fromdate = json.fromdate || '';
    var todate = json.todate || '';
    var enrolmentmethods = json.enrolmentmethods || [];

    // Build HTML.
    var html = '<div class="availability-dripcontent">';

    // Mode selector.
    html += '<div class="form-group row mb-2">';
    html += '<label class="col-sm-4 col-form-label">' +
            M.util.get_string('mode', 'availability_dripcontent') + '</label>';
    html += '<div class="col-sm-8"><select name="mode" class="form-control">';
    html += '<option value="coursedays"' + (mode === 'coursedays' ? ' selected' : '') + '>' +
            M.util.get_string('mode_coursedays', 'availability_dripcontent') + '</option>';
    html += '<option value="coursestartdays"' + (mode === 'coursestartdays' ? ' selected' : '') + '>' +
            M.util.get_string('mode_coursestartdays', 'availability_dripcontent') + '</option>';
    html += '<option value="subscriptiondays"' + (mode === 'subscriptiondays' ? ' selected' : '') + '>' +
            M.util.get_string('mode_subscriptiondays', 'availability_dripcontent') + '</option>';
    html += '<option value="daterange"' + (mode === 'daterange' ? ' selected' : '') + '>' +
            M.util.get_string('mode_daterange', 'availability_dripcontent') + '</option>';
    html += '</select></div></div>';

    // Value + Unit row (for time-based modes).
    var timeValueDisplay = mode === 'daterange' ? 'none' : 'flex';
    html += '<div class="form-group row mb-2 dripcontent-timevalue" style="display:' + timeValueDisplay + '">';
    html += '<label class="col-sm-4 col-form-label">' +
            M.util.get_string('value', 'availability_dripcontent') + '</label>';
    html += '<div class="col-sm-4"><input type="number" name="value" class="form-control" min="0" value="' +
            value + '"></div>';
    html += '<div class="col-sm-4"><select name="unit" class="form-control">';
    html += '<option value="days"' + (unit === 'days' ? ' selected' : '') + '>' +
            M.util.get_string('unit_days', 'availability_dripcontent') + '</option>';
    html += '<option value="weeks"' + (unit === 'weeks' ? ' selected' : '') + '>' +
            M.util.get_string('unit_weeks', 'availability_dripcontent') + '</option>';
    html += '<option value="months"' + (unit === 'months' ? ' selected' : '') + '>' +
            M.util.get_string('unit_months', 'availability_dripcontent') + '</option>';
    html += '</select></div></div>';

    // Enrolment methods row (for subscription mode).
    var enrolDisplay = mode === 'subscriptiondays' ? 'flex' : 'none';
    html += '<div class="form-group row mb-2 dripcontent-enrolmethods" style="display:' + enrolDisplay + '">';
    html += '<label class="col-sm-4 col-form-label">' +
            M.util.get_string('enrolmentmethods', 'availability_dripcontent') + '</label>';
    html += '<div class="col-sm-8"><select name="enrolmentmethods" class="form-control" multiple size="4">';
    html += '<option value=""' + (enrolmentmethods.length === 0 ? ' selected' : '') + '>' +
            M.util.get_string('allenrolmentmethods', 'availability_dripcontent') + '</option>';
    for (var key in this.enrolmentMethods) {
        if (this.enrolmentMethods.hasOwnProperty(key)) {
            var selected = enrolmentmethods.indexOf(key) !== -1 ? ' selected' : '';
            html += '<option value="' + key + '"' + selected + '>' +
                    this.enrolmentMethods[key] + '</option>';
        }
    }
    html += '</select></div></div>';

    // Date range rows.
    var dateDisplay = mode === 'daterange' ? 'flex' : 'none';
    var fromdateStr = fromdate ? this.timestampToDateString(fromdate) : '';
    var todateStr = todate ? this.timestampToDateString(todate) : '';

    html += '<div class="form-group row mb-2 dripcontent-daterange" style="display:' + dateDisplay + '">';
    html += '<label class="col-sm-4 col-form-label">' +
            M.util.get_string('fromdate', 'availability_dripcontent') + '</label>';
    html += '<div class="col-sm-8"><input type="date" name="fromdate" class="form-control" value="' +
            fromdateStr + '"></div></div>';

    html += '<div class="form-group row mb-2 dripcontent-daterange" style="display:' + dateDisplay + '">';
    html += '<label class="col-sm-4 col-form-label">' +
            M.util.get_string('todate', 'availability_dripcontent') + '</label>';
    html += '<div class="col-sm-8"><input type="date" name="todate" class="form-control" value="' +
            todateStr + '"></div></div>';

    html += '</div>';

    var node = Y.Node.create('<span class="availability_dripcontent">' + html + '</span>');

    // Add event handlers (first time only).
    if (!M.availability_dripcontent.form.addedEvents) {
        M.availability_dripcontent.form.addedEvents = true;

        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            M.availability_dripcontent.form.updateVisibility(this.ancestor('.availability_dripcontent'));
            M.core_availability.form.update();
        }, '.availability_dripcontent select[name=mode]');

        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_dripcontent select[name=unit]');

        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_dripcontent input[name=value]');

        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_dripcontent input[name=fromdate]');

        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_dripcontent input[name=todate]');

        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_dripcontent select[name=enrolmentmethods]');
    }

    return node;
};

/**
 * Convert Unix timestamp to date string (YYYY-MM-DD).
 *
 * @method timestampToDateString
 * @param {Number} timestamp Unix timestamp
 * @return {String} Date string
 */
M.availability_dripcontent.form.timestampToDateString = function(timestamp) {
    var date = new Date(timestamp * 1000);
    var year = date.getFullYear();
    var month = ('0' + (date.getMonth() + 1)).slice(-2);
    var day = ('0' + date.getDate()).slice(-2);
    return year + '-' + month + '-' + day;
};

/**
 * Convert date string to Unix timestamp.
 *
 * @method dateStringToTimestamp
 * @param {String} dateStr Date string (YYYY-MM-DD)
 * @return {Number|null} Unix timestamp or null
 */
M.availability_dripcontent.form.dateStringToTimestamp = function(dateStr) {
    if (!dateStr) {
        return null;
    }
    var date = new Date(dateStr + 'T00:00:00');
    return Math.floor(date.getTime() / 1000);
};

/**
 * Update visibility of form elements based on mode.
 *
 * @method updateVisibility
 * @param {Y.Node} node Form node
 */
M.availability_dripcontent.form.updateVisibility = function(node) {
    var mode = node.one('select[name=mode]').get('value');
    var timeValueRow = node.one('.dripcontent-timevalue');
    var daterangeRows = node.all('.dripcontent-daterange');
    var enrolRow = node.one('.dripcontent-enrolmethods');

    if (mode === 'daterange') {
        timeValueRow.setStyle('display', 'none');
        enrolRow.setStyle('display', 'none');
        daterangeRows.each(function(row) {
            row.setStyle('display', 'flex');
        });
    } else if (mode === 'subscriptiondays') {
        timeValueRow.setStyle('display', 'flex');
        enrolRow.setStyle('display', 'flex');
        daterangeRows.each(function(row) {
            row.setStyle('display', 'none');
        });
    } else {
        timeValueRow.setStyle('display', 'flex');
        enrolRow.setStyle('display', 'none');
        daterangeRows.each(function(row) {
            row.setStyle('display', 'none');
        });
    }
};

/**
 * Fills the value object from form fields.
 *
 * @method fillValue
 * @param {Object} value Object to fill
 * @param {Y.Node} node The form node
 */
M.availability_dripcontent.form.fillValue = function(value, node) {
    var mode = node.one('select[name=mode]').get('value');
    value.mode = mode;

    if (mode === 'daterange') {
        var fromdateStr = node.one('input[name=fromdate]').get('value');
        var todateStr = node.one('input[name=todate]').get('value');

        if (fromdateStr) {
            value.fromdate = this.dateStringToTimestamp(fromdateStr);
        }
        if (todateStr) {
            value.todate = this.dateStringToTimestamp(todateStr);
        }
    } else {
        value.unit = node.one('select[name=unit]').get('value');
        value.value = parseInt(node.one('input[name=value]').get('value'), 10) || 0;

        // For subscription mode, include enrolment methods if selected.
        if (mode === 'subscriptiondays') {
            var enrolSelect = node.one('select[name=enrolmentmethods]');
            var selectedMethods = [];
            enrolSelect.all('option:checked').each(function(opt) {
                var val = opt.get('value');
                if (val !== '') {
                    selectedMethods.push(val);
                }
            });
            if (selectedMethods.length > 0) {
                value.enrolmentmethods = selectedMethods;
            }
        }
    }
};

/**
 * Fills errors if validation fails.
 *
 * @method fillErrors
 * @param {Array} errors Array to add error strings to
 * @param {Y.Node} node The form node
 */
M.availability_dripcontent.form.fillErrors = function(errors, node) {
    var mode = node.one('select[name=mode]').get('value');
    var value = node.one('input[name=value]').get('value');
    var fromdate = node.one('input[name=fromdate]').get('value');
    var todate = node.one('input[name=todate]').get('value');

    if (mode !== 'daterange') {
        // Validate value.
        if (value === '' || isNaN(parseInt(value, 10)) || parseInt(value, 10) < 0) {
            errors.push('availability_dripcontent:error_invalidvalue');
        }
    } else {
        // Validate dates.
        if (!fromdate && !todate) {
            errors.push('availability_dripcontent:error_invaliddate');
        }
        if (fromdate && todate) {
            var from = new Date(fromdate);
            var to = new Date(todate);
            if (from >= to) {
                errors.push('availability_dripcontent:error_dateorder');
            }
        }
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
