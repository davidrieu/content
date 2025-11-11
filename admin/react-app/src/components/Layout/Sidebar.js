import { FiHome, FiZap, FiFileText, FiImage, FiCalendar, FiSettings, FiTrendingUp, FiTarget, FiBookmark, FiShare2, FiEdit, FiBookOpen } from 'react-icons/fi';
import { __ } from '@wordpress/i18n';

export default function Sidebar({ currentPath, onNavigate }) {
    const socialMediaSection = [
        { path: '/', icon: FiHome, label: __('Tableau de bord', 'ai-content-studio') },
        { path: '/generate', icon: FiZap, label: __('Générer des posts', 'ai-content-studio') },
        { path: '/strategy', icon: FiTarget, label: __('Stratégie Auto', 'ai-content-studio') },
        { path: '/calendar', icon: FiCalendar, label: __('Calendrier', 'ai-content-studio') },
        { path: '/library', icon: FiBookmark, label: __('Bibliothèque', 'ai-content-studio') },
    ];

    const blogSeoSection = [
        { path: '/blog-generator', icon: FiEdit, label: __('Créer un article', 'ai-content-studio') },
        { path: '/blog-strategy', icon: FiBookOpen, label: __('Stratégie Blog', 'ai-content-studio') },
        { path: '/blog-library', icon: FiFileText, label: __('Mes articles', 'ai-content-studio') },
        { path: '/images', icon: FiImage, label: __('Images IA', 'ai-content-studio') },
        { path: '/trends', icon: FiTrendingUp, label: __('Tendances SEO', 'ai-content-studio') },
    ];

    const settingsSection = [
        { path: '/settings', icon: FiSettings, label: __('Paramètres', 'ai-content-studio') },
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
                        onNavigate(item.path);
                    }}
                >
                    <Icon className="acs-nav-icon" />
                    <span>{item.label}</span>
                </a>
            </div>
        );
    };

    return (
        <div className="acs-sidebar">
            <div className="acs-sidebar-header">
                <a href="#" className="acs-sidebar-brand" onClick={(e) => { e.preventDefault(); onNavigate('/'); }}>
                    <FiZap size={24} />
                    <span>AI Content Studio</span>
                </a>
            </div>
            <nav className="acs-sidebar-nav">
                {/* Section Réseaux Sociaux */}
                <div className="acs-nav-section">
                    <div className="acs-nav-section-header">
                        <FiShare2 size={14} />
                        <span>{__('Réseaux Sociaux', 'ai-content-studio')}</span>
                    </div>
                    {socialMediaSection.map(renderNavItem)}
                </div>

                {/* Section SEO & Blog */}
                <div className="acs-nav-section">
                    <div className="acs-nav-section-header">
                        <FiEdit size={14} />
                        <span>{__('SEO & Blog', 'ai-content-studio')}</span>
                    </div>
                    {blogSeoSection.map(renderNavItem)}
                </div>

                {/* Section Paramètres */}
                <div className="acs-nav-section">
                    {settingsSection.map(renderNavItem)}
                </div>
            </nav>
        </div>
    );
}
