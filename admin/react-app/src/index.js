import { render } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import App from './App';
import './styles/app.css';
import './i18n'; // Initialize i18next

// Configure API Fetch
apiFetch.use(apiFetch.createNonceMiddleware(window.acsData.nonce));
apiFetch.use(apiFetch.createRootURLMiddleware(window.acsData.apiUrl));

// Mount React app - works for both admin and frontend
const adminRoot = document.getElementById('acs-admin-root');
const frontendRoot = document.getElementById('acs-frontend-root');

if (adminRoot) {
    render(<App />, adminRoot);
} else if (frontendRoot) {
    render(<App isFrontend={true} />, frontendRoot);
}
