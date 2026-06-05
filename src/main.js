import jquery from 'jquery';

window.jQuery = jquery;
window.$ = jquery;
globalThis.jQuery = jquery;
globalThis.$ = jquery;

import Popper from 'popper.js';

globalThis.Popper = Popper;

import 'bootstrap';
import 'overlayscrollbars';
import 'admin-lte';
import 'select2';
import 'toastr';
import 'sweetalert2';

import Swal from 'sweetalert2';
window.Swal = Swal;
globalThis.Swal = Swal;

import 'datatables.net-bs4';

import './js/theme-switch.js';
import './js/error-pages.js';
import './js/global.js';
import './js/alerta-copa.js';

import '../modulos/inicio/assets/js/inicio.js';