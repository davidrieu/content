import { FiHome, FiZap, FiFileText, FiImage, FiCalendar, FiSettings, FiTrendingUp, FiTarget, FiBookmark, FiShare2, FiEdit, FiBookOpen } from 'react-icons/fi';
import { useTranslation } from '../../contexts/TranslationContext';

export default function Sidebar({ currentPath, onNavigate, show, onClose }) {
    const { t } = useTranslation();

    const handleNavigate = (path) => {
        onNavigate(path);
        // Close sidebar on mobile after navigation
        if (onClose) {
            onClose();
        }
    };

    const socialMediaSection = [
        { path: '/', icon: FiHome, label: t('Tableau de bord') },
        { path: '/generate', icon: FiZap, label: t('Générer des posts') },
        { path: '/images', icon: FiImage, label: t('Images IA') },
        { path: '/strategy', icon: FiTarget, label: t('Stratégie Auto') },
        { path: '/calendar', icon: FiCalendar, label: t('Calendrier') },
        { path: '/library', icon: FiBookmark, label: t('Bibliothèque') },
    ];

    const blogSeoSection = [
        { path: '/blog-generator', icon: FiEdit, label: t('Créer un article') },
        { path: '/blog-strategy', icon: FiBookOpen, label: t('Stratégie Blog') },
        { path: '/blog-library', icon: FiFileText, label: t('Mes articles') },
        { path: '/images', icon: FiImage, label: t('Images IA') },
        { path: '/trends', icon: FiTrendingUp, label: t('Tendances SEO') },
    ];

    const settingsSection = [
        { path: '/settings', icon: FiSettings, label: t('Paramètres') },
    ];

    const renderNavItem = (item) => {
        const Icon = item.icon;
        const isActive = currentPath === item.path;
        return (
            <div key={item.path} className="acs-nav-item">
                <a
                    href="#"
                    className={`acs-nav-link ${isActive ? 'active' : ''}`}
                    onClick={(e) => {
                        e.preventDefault();
                        handleNavigate(item.path);
                    }}
                >
                    <Icon className="acs-nav-icon" />
                    <span>{item.label}</span>
                </a>
            </div>
        );
    };

    return (
        <>
            {/* Overlay for mobile */}
            {show && <div className={`acs-sidebar-overlay ${show ? 'show' : ''}`} onClick={onClose} />}

            <div className={`acs-sidebar ${show ? 'show' : ''}`}>
            <div className="acs-sidebar-header">
                <a href="#" className="acs-sidebar-brand" onClick={(e) => { e.preventDefault(); onNavigate('/'); }}>
                    <img
                        src="/wp-content/uploads/2025/11/RUNNWRITE-AI-LOGO-WHITE.png"
                        alt="RunnWrite AI"
                        className="acs-sidebar-logo"
                    />
                </a>
            </div>
            <nav className="acs-sidebar-nav">
                {/* Section Réseaux Sociaux */}
                <div className="acs-nav-section">
                    <div className="acs-nav-section-header">
                        <FiShare2 size={14} />
                        <span>{t('Réseaux Sociaux')}</span>
                    </div>
                    {socialMediaSection.map(renderNavItem)}
                </div>

                {/* Section SEO & Blog */}
                <div className="acs-nav-section">
                    <div className="acs-nav-section-header">
                        <FiEdit size={14} />
                        <span>{t('SEO & Blog')}</span>
                    </div>
                    {blogSeoSection.map(renderNavItem)}
                </div>

                {/* Section Paramètres */}
                <div className="acs-nav-section">
                    {settingsSection.map(renderNavItem)}
                </div>
            </nav>
        </div>
        </>
    );
}
