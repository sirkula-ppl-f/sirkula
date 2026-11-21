import './bootstrap';
import { initAdminUsers } from './admin-users';
import { initConfirmDialogs } from './confirm-dialog';
import { initForms } from './forms';
import { initNavbar } from './navbar';
import { initPaymentCountdown } from './payment-countdown';

initNavbar();
initPaymentCountdown();
initForms();
initConfirmDialogs();
initAdminUsers();
