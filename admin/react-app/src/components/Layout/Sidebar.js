import { FiHome, FiZap, FiFileText, FiImage, FiCalendar, FiSettings, FiTrendingUp } from 'react-icons/fi';
import { __ } from '@wordpress/i18n';

export default function Sidebar({ currentPath, onNavigate }) {
    const menuItems = [
        { path: '/', icon: FiHome, label: __('Tableau de bord', 'ai-content-studio') },
        { path: '/generate', icon: FiZap, label: __('Générer des posts', 'ai-content-studio') },
        { path: '/articles', icon: FiFileText, label: __('Articles de blog', 'ai-content-studio') },
        { path: '/images', icon: FiImage, label: __('Images IA', 'ai-content-studio') },
        { path: '/calendar', icon: FiCalendar, label: __('Calendrier', 'ai-content-studio') },
        { path: '/trends', icon: FiTrendingUp, label: __('Tendances', 'ai-content-studio') },
        { path: '/settings', icon: FiSettings, label: __('Paramètres', 'ai-content-studio') },
    ];

    return (
        <div className="acs-sidebar">
            <div className="acs-sidebar-header">
                <a href="#" className="acs-sidebar-brand" onClick={(e) => { e.preventDefault(); onNavigate('/'); }}>
                    <FiZap size={24} />
                    <span>AI Content Studio</span>
                </a>
            </div>
            <nav className="acs-sidebar-nav">
                {menuItems.map((item) => {
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
                })}
            </nav>
        </div>
    );
}
