import { FiChevronRight, FiUser, FiBell, FiMenu } from 'react-icons/fi';
import { useTranslation } from '../../contexts/TranslationContext';
import LanguageSwitcher from '../common/LanguageSwitcher';
import '../common/LanguageSwitcher.css';

export default function Topbar({ currentPage, profile, onToggleSidebar }) {
    const { t } = useTranslation();

    return (
        <div className="acs-topbar">
            <div style={{ display: 'flex', alignItems: 'center', gap: '0' }}>
                {/* Hamburger menu for mobile */}
                <button
                    className="acs-hamburger"
                    onClick={onToggleSidebar}
                    aria-label="Toggle menu"
                >
                    <FiMenu size={24} />
                </button>

                <div className="acs-breadcrumb">
                <span>{t('Accueil')}</span>
                {currentPage && currentPage !== 'Dashboard' && (
                    <>
                        <FiChevronRight size={14} />
                        <span>{currentPage}</span>
                    </>
                )}
                </div>
            </div>
            <div className="d-flex align-items-center gap-3">
                <button className="acs-btn acs-btn-sm" style={{ background: 'transparent', border: 'none', color: 'var(--acs-gray-600)' }}>
                    <FiBell size={18} />
                </button>
                <LanguageSwitcher />
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
                        {profile?.business_name || t('Utilisateur')}
                    </div>
                </div>
            </div>
        </div>
    );
}
