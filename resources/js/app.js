import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

const stripLeadingZero = (input) => {
    if (!input || input.dataset.stripLeadingZero !== 'true') {
        return;
    }
    const value = input.value || '';
    if (value.startsWith('0')) {
        input.value = value.replace(/^0+/, '');
    }
};

document.addEventListener('input', (event) => {
    stripLeadingZero(event.target);
});

document.addEventListener('blur', (event) => {
    stripLeadingZero(event.target);
}, true);
