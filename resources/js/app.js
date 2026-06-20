import './bootstrap';

import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import 'flatpickr/dist/flatpickr.min.css';

// Date picker berbahasa Indonesia (tampil "12 Juni 2026", nilai disimpan Y-m-d).
flatpickr.localize(Indonesian);
window.flatpickr = flatpickr;
