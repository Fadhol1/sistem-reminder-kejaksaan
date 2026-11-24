import './bootstrap';
import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

flatpickr.localize(Indonesian);

window.initDatepickers = function () {
    const dateInputs = document.querySelectorAll('input[type="date"], .datepicker');
    dateInputs.forEach((el) => {
        if (el._flatpickr) return;

        const defaultValue = el.value || el.getAttribute('value') || '';

        flatpickr(el, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true,
            defaultDate: defaultValue ? defaultValue : undefined,
            monthSelectorType: 'static',
            onReady: function (selectedDates, dateStr, instance) {
                if (instance.altInput) {
                    instance.altInput.className = el.className;
                    instance.altInput.placeholder = 'HH/BB/TTTT (DD/MM/YYYY)';
                    if (el.hasAttribute('required')) {
                        instance.altInput.setAttribute('required', 'required');
                    }
                }
            }
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    window.initDatepickers();
});

