/**
 * JavaScript for availability_dripcontent form.
 *
 * @module     availability_dripcontent/form
 * @copyright  2024 Prepara Oposiciones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @class availability_dripcontent/form
 */
const Form = {
    /**
     * Initializes the form.
     * Called by the availability system when setting up the condition UI.
     *
     * @param {Object} M Moodle global object
     */
    initInner: function(M) {
        // Store parameters from PHP.
        this.modes = M.availability_dripcontent.modes;
        this.units = M.availability_dripcontent.units;
    },

    /**
     * Gets the form node for a new or existing condition.
     *
     * @param {Object} json The saved JSON data for the condition (or empty for new)
     * @return {HTMLElement} The form node
     */
    getNode: function(json) {
        const node = document.createElement('div');
        node.className = 'availability-dripcontent';

        // Default values.
        const mode = json.mode || 'coursedays';
        const unit = json.unit || 'days';
        const value = json.value || 1;
        const fromdate = json.fromdate || '';
        const todate = json.todate || '';

        // Build the form using DOM methods.
        this.buildForm(node, mode, unit, value, fromdate, todate);

        // Set up event listeners.
        this.setupEventListeners(node);

        // Initial visibility update.
        this.updateVisibility(node, mode);

        return node;
    },

    /**
     * Create a form row with label and content.
     *
     * @param {string} labelText Label text
     * @param {string} extraClass Additional CSS class
     * @return {Object} Object with row element and content div
     */
    createFormRow: function(labelText, extraClass) {
        const row = document.createElement('div');
        row.className = 'form-group row mb-2';
        if (extraClass) {
            row.className += ' ' + extraClass;
        }

        const label = document.createElement('label');
        label.className = 'col-sm-3 col-form-label';
        label.textContent = labelText;

        const contentDiv = document.createElement('div');
        contentDiv.className = 'col-sm-9';

        row.appendChild(label);
        row.appendChild(contentDiv);

        return {row, contentDiv};
    },

    /**
     * Create a select element with options.
     *
     * @param {string} className CSS class name
     * @param {Array} options Array of {value, text, selected} objects
     * @return {HTMLSelectElement} The select element
     */
    createSelect: function(className, options) {
        const select = document.createElement('select');
        select.className = 'form-control ' + className;

        options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.text;
            if (opt.selected) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        return select;
    },

    /**
     * Build the form using safe DOM methods.
     *
     * @param {HTMLElement} node Parent node
     * @param {string} mode Current mode
     * @param {string} unit Current unit
     * @param {number} value Current value
     * @param {number|string} fromdate From date timestamp
     * @param {number|string} todate To date timestamp
     */
    buildForm: function(node, mode, unit, value, fromdate, todate) {
        // Convert timestamps to date strings for input.
        const fromdateStr = fromdate ? this.timestampToDateString(fromdate) : '';
        const todateStr = todate ? this.timestampToDateString(todate) : '';

        // Mode selector row.
        const modeRow = this.createFormRow(
            M.util.get_string('mode', 'availability_dripcontent'),
            ''
        );
        const modeSelect = this.createSelect('dripcontent-mode', [
            {value: 'coursedays', text: M.util.get_string('mode_coursedays', 'availability_dripcontent'), selected: mode === 'coursedays'},
            {value: 'subscriptiondays', text: M.util.get_string('mode_subscriptiondays', 'availability_dripcontent'), selected: mode === 'subscriptiondays'},
            {value: 'daterange', text: M.util.get_string('mode_daterange', 'availability_dripcontent'), selected: mode === 'daterange'}
        ]);
        modeRow.contentDiv.appendChild(modeSelect);
        node.appendChild(modeRow.row);

        // Value + Unit row (for coursedays and subscriptiondays modes).
        const valueRow = document.createElement('div');
        valueRow.className = 'form-group row mb-2 dripcontent-timevalue';

        const valueLabel = document.createElement('label');
        valueLabel.className = 'col-sm-3 col-form-label';
        valueLabel.textContent = M.util.get_string('value', 'availability_dripcontent');

        const valueInputDiv = document.createElement('div');
        valueInputDiv.className = 'col-sm-4';
        const valueInput = document.createElement('input');
        valueInput.type = 'number';
        valueInput.className = 'form-control dripcontent-value';
        valueInput.min = '0';
        valueInput.value = value;
        valueInputDiv.appendChild(valueInput);

        const unitSelectDiv = document.createElement('div');
        unitSelectDiv.className = 'col-sm-5';
        const unitSelect = this.createSelect('dripcontent-unit', [
            {value: 'days', text: M.util.get_string('unit_days', 'availability_dripcontent'), selected: unit === 'days'},
            {value: 'months', text: M.util.get_string('unit_months', 'availability_dripcontent'), selected: unit === 'months'}
        ]);
        unitSelectDiv.appendChild(unitSelect);

        valueRow.appendChild(valueLabel);
        valueRow.appendChild(valueInputDiv);
        valueRow.appendChild(unitSelectDiv);
        node.appendChild(valueRow);

        // From date row (for daterange mode).
        const fromRow = this.createFormRow(
            M.util.get_string('fromdate', 'availability_dripcontent'),
            'dripcontent-daterange'
        );
        fromRow.row.style.display = 'none';
        const fromInput = document.createElement('input');
        fromInput.type = 'date';
        fromInput.className = 'form-control dripcontent-fromdate';
        fromInput.value = fromdateStr;
        fromRow.contentDiv.appendChild(fromInput);
        node.appendChild(fromRow.row);

        // To date row (for daterange mode).
        const toRow = this.createFormRow(
            M.util.get_string('todate', 'availability_dripcontent'),
            'dripcontent-daterange'
        );
        toRow.row.style.display = 'none';
        const toInput = document.createElement('input');
        toInput.type = 'date';
        toInput.className = 'form-control dripcontent-todate';
        toInput.value = todateStr;
        toRow.contentDiv.appendChild(toInput);
        node.appendChild(toRow.row);
    },

    /**
     * Convert Unix timestamp to date string (YYYY-MM-DD).
     *
     * @param {number} timestamp Unix timestamp
     * @return {string} Date string
     */
    timestampToDateString: function(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toISOString().split('T')[0];
    },

    /**
     * Convert date string to Unix timestamp.
     *
     * @param {string} dateStr Date string (YYYY-MM-DD)
     * @return {number|null} Unix timestamp or null
     */
    dateStringToTimestamp: function(dateStr) {
        if (!dateStr) {
            return null;
        }
        const date = new Date(dateStr + 'T00:00:00');
        return Math.floor(date.getTime() / 1000);
    },

    /**
     * Set up event listeners on the form.
     *
     * @param {HTMLElement} node Form node
     */
    setupEventListeners: function(node) {
        const modeSelect = node.querySelector('.dripcontent-mode');
        const valueInput = node.querySelector('.dripcontent-value');
        const unitSelect = node.querySelector('.dripcontent-unit');
        const fromdateInput = node.querySelector('.dripcontent-fromdate');
        const todateInput = node.querySelector('.dripcontent-todate');

        // Mode change - update visibility.
        modeSelect.addEventListener('change', () => {
            this.updateVisibility(node, modeSelect.value);
            M.core_availability.form.update();
        });

        // Value changes.
        valueInput.addEventListener('input', () => {
            M.core_availability.form.update();
        });
        unitSelect.addEventListener('change', () => {
            M.core_availability.form.update();
        });
        fromdateInput.addEventListener('change', () => {
            M.core_availability.form.update();
        });
        todateInput.addEventListener('change', () => {
            M.core_availability.form.update();
        });
    },

    /**
     * Update visibility of form elements based on mode.
     *
     * @param {HTMLElement} node Form node
     * @param {string} mode Current mode
     */
    updateVisibility: function(node, mode) {
        const timeValueRow = node.querySelector('.dripcontent-timevalue');
        const daterangeRows = node.querySelectorAll('.dripcontent-daterange');

        if (mode === 'daterange') {
            timeValueRow.style.display = 'none';
            daterangeRows.forEach(row => {
                row.style.display = 'flex';
            });
        } else {
            timeValueRow.style.display = 'flex';
            daterangeRows.forEach(row => {
                row.style.display = 'none';
            });
        }
    },

    /**
     * Fills the form values from JSON data.
     *
     * @param {Object} json The saved JSON data
     * @param {HTMLElement} node The form node
     */
    fillValue: function(json, node) {
        const modeSelect = node.querySelector('.dripcontent-mode');
        const valueInput = node.querySelector('.dripcontent-value');
        const unitSelect = node.querySelector('.dripcontent-unit');
        const fromdateInput = node.querySelector('.dripcontent-fromdate');
        const todateInput = node.querySelector('.dripcontent-todate');

        if (json.mode) {
            modeSelect.value = json.mode;
        }
        if (json.unit) {
            unitSelect.value = json.unit;
        }
        if (json.value !== undefined) {
            valueInput.value = json.value;
        }
        if (json.fromdate) {
            fromdateInput.value = this.timestampToDateString(json.fromdate);
        }
        if (json.todate) {
            todateInput.value = this.timestampToDateString(json.todate);
        }

        this.updateVisibility(node, modeSelect.value);
    },

    /**
     * Fills the errors array if form validation fails.
     *
     * @param {Array} errors Array to add error strings to
     * @param {HTMLElement} node The form node
     */
    fillErrors: function(errors, node) {
        const mode = node.querySelector('.dripcontent-mode').value;
        const value = node.querySelector('.dripcontent-value').value;
        const fromdate = node.querySelector('.dripcontent-fromdate').value;
        const todate = node.querySelector('.dripcontent-todate').value;

        if (mode !== 'daterange') {
            // Validate value.
            if (value === '' || isNaN(parseInt(value)) || parseInt(value) < 0) {
                errors.push('availability_dripcontent:error_invalidvalue');
            }
        } else {
            // Validate dates.
            if (!fromdate && !todate) {
                errors.push('availability_dripcontent:error_invaliddate');
            }
            if (fromdate && todate) {
                const from = new Date(fromdate);
                const to = new Date(todate);
                if (from >= to) {
                    errors.push('availability_dripcontent:error_dateorder');
                }
            }
        }
    },

    /**
     * Gets the current value from the form.
     *
     * @param {HTMLElement} node The form node
     * @return {Object} JSON data
     */
    getValue: function(node) {
        const mode = node.querySelector('.dripcontent-mode').value;
        const result = {
            type: 'dripcontent',
            mode: mode,
        };

        if (mode === 'daterange') {
            const fromdateStr = node.querySelector('.dripcontent-fromdate').value;
            const todateStr = node.querySelector('.dripcontent-todate').value;

            if (fromdateStr) {
                result.fromdate = this.dateStringToTimestamp(fromdateStr);
            }
            if (todateStr) {
                result.todate = this.dateStringToTimestamp(todateStr);
            }
        } else {
            result.unit = node.querySelector('.dripcontent-unit').value;
            result.value = parseInt(node.querySelector('.dripcontent-value').value) || 0;
        }

        return result;
    }
};

export default Form;
