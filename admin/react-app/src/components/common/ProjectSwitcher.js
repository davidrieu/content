import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { FiChevronDown, FiPlus, FiCheck } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import './ProjectSwitcher.css';

export default function ProjectSwitcher({ onProjectChange, onAddProject }) {
    const [projects, setProjects] = useState([]);
    const [activeProject, setActiveProject] = useState(null);
    const [isOpen, setIsOpen] = useState(false);
    const [loading, setLoading] = useState(true);
    const [switching, setSwitching] = useState(false);

    useEffect(() => {
        loadProjects();
    }, []);

    const loadProjects = async () => {
        try {
            setLoading(true);

            // Load all projects
            const projectsResponse = await apiFetch({
                path: '/acs/v1/projects',
                method: 'GET',
            });

            if (projectsResponse.success) {
                setProjects(projectsResponse.data.projects || []);

                // Load active project
                const activeResponse = await apiFetch({
                    path: '/acs/v1/projects/active',
                    method: 'GET',
                });

                if (activeResponse.success && activeResponse.data.project) {
                    setActiveProject(activeResponse.data.project);
                }
            }
        } catch (error) {
            console.error('Error loading projects:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSwitchProject = async (projectId) => {
        if (switching || projectId === activeProject?.id) {
            setIsOpen(false);
            return;
        }

        try {
            setSwitching(true);

            const response = await apiFetch({
                path: `/acs/v1/projects/switch/${projectId}`,
                method: 'POST',
            });

            if (response.success) {
                setActiveProject(response.data.project);
                setIsOpen(false);

                // Notify parent component
                if (onProjectChange) {
                    onProjectChange(response.data.project);
                }

                // Reload the page to refresh all data with new project
                window.location.reload();
            }
        } catch (error) {
            console.error('Error switching project:', error);
            alert(__('Erreur lors du changement de projet', 'ai-content-studio'));
        } finally {
            setSwitching(false);
        }
    };

    const handleAddProject = () => {
        setIsOpen(false);
        if (onAddProject) {
            onAddProject();
        }
    };

    if (loading) {
        return (
            <div className="acs-project-switcher-loading">
                <div className="acs-spinner" style={{ width: '20px', height: '20px', borderWidth: '2px' }} />
            </div>
        );
    }

    if (!activeProject) {
        return null;
    }

    return (
        <div className="acs-project-switcher">
            <button
                className="acs-project-switcher-trigger"
                onClick={() => setIsOpen(!isOpen)}
                disabled={switching}
            >
                <div className="acs-project-info">
                    <span className="acs-project-name">{activeProject.project_name || activeProject.business_name}</span>
                    <span className="acs-project-meta">
                        {projects.length} {projects.length === 1 ? __('projet', 'ai-content-studio') : __('projets', 'ai-content-studio')}
                    </span>
                </div>
                <FiChevronDown className={`acs-project-chevron ${isOpen ? 'open' : ''}`} />
            </button>

            {isOpen && (
                <>
                    <div className="acs-project-switcher-overlay" onClick={() => setIsOpen(false)} />
                    <div className="acs-project-switcher-dropdown">
                        <div className="acs-project-list">
                            {projects.map((project) => (
                                <button
                                    key={project.id}
                                    className={`acs-project-item ${project.id === activeProject.id ? 'active' : ''}`}
                                    onClick={() => handleSwitchProject(project.id)}
                                    disabled={switching}
                                >
                                    <div className="acs-project-item-content">
                                        <div className="acs-project-item-name">
                                            {project.project_name || project.business_name}
                                        </div>
                                        {project.business_name && project.project_name !== project.business_name && (
                                            <div className="acs-project-item-business">
                                                {project.business_name}
                                            </div>
                                        )}
                                    </div>
                                    {project.id === activeProject.id && (
                                        <FiCheck className="acs-project-check" />
                                    )}
                                </button>
                            ))}
                        </div>

                        <div className="acs-project-switcher-footer">
                            <button
                                className="acs-project-add-button"
                                onClick={handleAddProject}
                                disabled={switching}
                            >
                                <FiPlus size={18} />
                                {__('Ajouter un projet', 'ai-content-studio')}
                            </button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
