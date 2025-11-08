import { render } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import App from './App';
import './styles/app.css';

// Configure API Fetch
apiFetch.use(apiFetch.createNonceMiddleware(window.acsData.nonce));
apiFetch.use(apiFetch.createRootURLMiddleware(window.acsData.apiUrl));

// Mount React app
const rootElement = document.getElementById('acs-admin-root');

if (rootElement) {
    render(<App />, rootElement);
}
