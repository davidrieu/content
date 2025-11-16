import { FiChevronRight, FiUser, FiBell } from 'react-icons/fi';
import { __ } from '@wordpress/i18n';
import LanguageSwitcher from '../common/LanguageSwitcher';
import '../common/LanguageSwitcher.css';

export default function Topbar({ currentPage, profile }) {
    return (
        <div className="acs-topbar">
            <div className="acs-breadcrumb">
                <span>{__('Accueil', 'ai-content-studio')}</span>
                {currentPage && currentPage !== 'Dashboard' && (
                    <>
                        <FiChevronRight size={14} />
                        <span>{currentPage}</span>
                    </>
                )}
            </div>
            <div className="d-flex align-items-center gap-3">
                <LanguageSwitcher />
                <button className="acs-btn acs-btn-sm" style={{ background: 'transparent', border: 'none', color: 'var(--acs-gray-600)' }}>
                    <FiBell size={18} />
                </button>
                <div className="d-flex align-items-center gap-2">
                    <div style={{
                        width: '32px',
                        height: '32px',
                        borderRadius: '50%',
                        background: 'var(--acs-primary)',
                        color: 'white',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontWeight: 600,
                        fontSize: '0.875rem'
                    }}>
                        {profile?.business_name?.charAt(0).toUpperCase() || 'U'}
                    </div>
                    <div style={{ fontSize: '0.875rem', fontWeight: 500 }}>
                        {profile?.business_name || __('Utilisateur', 'ai-content-studio')}
                    </div>
                </div>
            </div>
        </div>
    );
}
