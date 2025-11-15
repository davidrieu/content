import { FiHome, FiZap, FiFileText, FiImage, FiCalendar, FiSettings, FiTrendingUp, FiTarget, FiBookmark, FiShare2, FiEdit, FiBookOpen } from 'react-icons/fi';
import { useTranslation } from 'react-i18next';

export default function Sidebar({ currentPath, onNavigate }) {
    const { t } = useTranslation();

    const socialMediaSection = [
        { path: '/', icon: FiHome, label: t('sidebar.dashboard') },
        { path: '/generate', icon: FiZap, label: t('sidebar.generatePosts') },
        { path: '/strategy', icon: FiTarget, label: t('sidebar.autoStrategy') },
        { path: '/calendar', icon: FiCalendar, label: t('sidebar.calendar') },
        { path: '/library', icon: FiBookmark, label: t('sidebar.library') },
    ];

    const blogSeoSection = [
        { path: '/blog-generator', icon: FiEdit, label: t('sidebar.createArticle') },
        { path: '/blog-strategy', icon: FiBookOpen, label: t('sidebar.blogStrategy') },
        { path: '/blog-library', icon: FiFileText, label: t('sidebar.myArticles') },
        { path: '/images', icon: FiImage, label: t('sidebar.aiImages') },
        { path: '/trends', icon: FiTrendingUp, label: t('sidebar.seoTrends') },
    ];

    const settingsSection = [
        { path: '/settings', icon: FiSettings, label: t('sidebar.settings') },
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
                        <span>{t('sidebar.socialMedia')}</span>
                    </div>
                    {socialMediaSection.map(renderNavItem)}
                </div>

                {/* Section SEO & Blog */}
                <div className="acs-nav-section">
                    <div className="acs-nav-section-header">
                        <FiEdit size={14} />
                        <span>{t('sidebar.seoBlog')}</span>
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
