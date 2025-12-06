import './bootstrap'
import './student.calendar.js';
import Alpine from 'alpinejs'
import Swal from 'sweetalert2'
import * as ZXing from '@zxing/browser';

// import 'bootstrap/dist/css/bootstrap.min.css';
// import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
window.Swal = Swal
window.ZXing = ZXing;
if (!window.Alpine) {
  window.Alpine = Alpine
  Alpine.start()
}
