import './bootstrap';
import { initAdminUsers } from './admin-users';
import { initCatalogFilter } from './catalog-filter';
import { initConfirmDialogs } from './confirm-dialog';
import { initForms } from './forms';
import { initNavbar } from './navbar';
import { initPaymentCountdown } from './payment-countdown';

initNavbar();
initCatalogFilter();
initPaymentCountdown();
initForms();
initConfirmDialogs();
initAdminUsers();
