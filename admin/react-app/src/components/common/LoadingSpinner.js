import { Spinner } from '@wordpress/components';

export default function LoadingSpinner({ message = 'Chargement...' }) {
    return (
        <div className="acs-loading-spinner">
            <Spinner />
            <p>{message}</p>
        </div>
    );
}
