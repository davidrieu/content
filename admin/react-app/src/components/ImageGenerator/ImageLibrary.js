import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import {
    FiImage, FiDownload, FiTrash2, FiEye, FiGrid, FiList,
    FiFilter, FiSearch, FiCalendar
} from 'react-icons/fi';

const ImageLibrary = () => {
    const [images, setImages] = useState([]);
    const [loading, setLoading] = useState(true);
    const [viewMode, setViewMode] = useState('grid'); // grid or list
    const [selectedImage, setSelectedImage] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [filterQuality, setFilterQuality] = useState('all');
    const [filterStyle, setFilterStyle] = useState('all');
    const [pagination, setPagination] = useState({
        current_page: 1,
        per_page: 20,
        total: 0,
        total_pages: 0,
    });

    useEffect(() => {
        loadImages();
    }, [pagination.current_page]);

    const loadImages = async () => {
        setLoading(true);
        try {
            const response = await apiFetch({
                path: `/acs/v1/images?page=${pagination.current_page}&per_page=${pagination.per_page}`,
                method: 'GET',
            });

            if (response.success) {
                setImages(response.data.images);
                setPagination(response.data.pagination);
            }
        } catch (err) {
            console.error('Error loading images:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (imageId) => {
        if (!window.confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
            return;
        }

        try {
            const response = await apiFetch({
                path: `/acs/v1/images/${imageId}`,
                method: 'DELETE',
            });

            if (response.success) {
                setImages(images.filter(img => img.id !== imageId));
                if (selectedImage?.id === imageId) {
                    setSelectedImage(null);
                }
            }
        } catch (err) {
            console.error('Error deleting image:', err);
            alert('Erreur lors de la suppression de l\'image.');
        }
    };

    const handleDownload = async (image) => {
        try {
            const response = await fetch(image.url);
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${image.title || 'image'}.png`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (err) {
            console.error('Download error:', err);
        }
    };

    const filteredImages = images.filter(image => {
        const matchesSearch = image.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            image.prompt.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesQuality = filterQuality === 'all' || image.quality === filterQuality;
        const matchesStyle = filterStyle === 'all' || image.style === filterStyle;
        return matchesSearch && matchesQuality && matchesStyle;
    });

    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <div className="acs-image-library">
            <div className="acs-page-header">
                <div className="acs-page-header-content">
                    <h1 className="acs-page-title">
                        <FiImage /> Bibliothèque d'Images
                    </h1>
                    <p className="acs-page-subtitle">
                        Retrouvez toutes vos images générées avec l'IA
                    </p>
                </div>
                <div className="acs-page-actions">
                    <button
                        className="acs-button acs-button-primary"
                        onClick={() => window.location.hash = '#/images'}
                    >
                        <FiImage />
                        Générer une image
                    </button>
                </div>
            </div>

            {/* Filters and Search */}
            <div className="acs-library-toolbar">
                <div className="acs-search-box">
                    <FiSearch />
                    <input
                        type="text"
                        placeholder="Rechercher par titre ou description..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="acs-input"
                    />
                </div>

                <div className="acs-filters">
                    <select
                        className="acs-select"
                        value={filterQuality}
                        onChange={(e) => setFilterQuality(e.target.value)}
                    >
                        <option value="all">Toutes qualités</option>
                        <option value="standard">Standard</option>
                        <option value="hd">HD</option>
                    </select>

                    <select
                        className="acs-select"
                        value={filterStyle}
                        onChange={(e) => setFilterStyle(e.target.value)}
                    >
                        <option value="all">Tous styles</option>
                        <option value="vivid">Vivid</option>
                        <option value="natural">Natural</option>
                    </select>
                </div>

                <div className="acs-view-toggle">
                    <button
                        className={`acs-view-btn ${viewMode === 'grid' ? 'active' : ''}`}
                        onClick={() => setViewMode('grid')}
                        title="Vue grille"
                    >
                        <FiGrid />
                    </button>
                    <button
                        className={`acs-view-btn ${viewMode === 'list' ? 'active' : ''}`}
                        onClick={() => setViewMode('list')}
                        title="Vue liste"
                    >
                        <FiList />
                    </button>
                </div>
            </div>

            {/* Images Display */}
            {loading ? (
                <div className="acs-loading-state">
                    <div className="acs-spinner"></div>
                    <p>Chargement des images...</p>
                </div>
            ) : filteredImages.length === 0 ? (
                <div className="acs-empty-state">
                    <FiImage size={64} />
                    <h3>Aucune image trouvée</h3>
                    <p>
                        {searchTerm || filterQuality !== 'all' || filterStyle !== 'all'
                            ? 'Aucune image ne correspond à vos critères de recherche.'
                            : 'Vous n\'avez pas encore généré d\'images.'
                        }
                    </p>
                    {!searchTerm && filterQuality === 'all' && filterStyle === 'all' && (
                        <button
                            className="acs-button acs-button-primary"
                            onClick={() => window.location.hash = '#/images'}
                        >
                            <FiImage />
                            Générer votre première image
                        </button>
                    )}
                </div>
            ) : (
                <>
                    <div className={`acs-images-${viewMode}`}>
                        {filteredImages.map((image) => (
                            <div key={image.id} className="acs-image-item">
                                {viewMode === 'grid' ? (
                                    <div className="acs-image-card">
                                        <div
                                            className="acs-image-thumbnail"
                                            onClick={() => setSelectedImage(image)}
                                        >
                                            <img src={image.url} alt={image.title} />
                                            <div className="acs-image-overlay">
                                                <button className="acs-overlay-btn">
                                                    <FiEye />
                                                </button>
                                            </div>
                                        </div>
                                        <div className="acs-image-card-body">
                                            <h4 className="acs-image-title">{image.title}</h4>
                                            <div className="acs-image-meta-tags">
                                                <span className="acs-badge acs-badge-sm">{image.size}</span>
                                                <span className="acs-badge acs-badge-sm">{image.quality}</span>
                                            </div>
                                            <p className="acs-image-date">
                                                <FiCalendar />
                                                {formatDate(image.created_at)}
                                            </p>
                                            <div className="acs-image-card-actions">
                                                <button
                                                    className="acs-icon-btn"
                                                    onClick={() => handleDownload(image)}
                                                    title="Télécharger"
                                                >
                                                    <FiDownload />
                                                </button>
                                                <button
                                                    className="acs-icon-btn acs-icon-btn-danger"
                                                    onClick={() => handleDelete(image.id)}
                                                    title="Supprimer"
                                                >
                                                    <FiTrash2 />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="acs-image-row">
                                        <div
                                            className="acs-image-row-thumbnail"
                                            onClick={() => setSelectedImage(image)}
                                        >
                                            <img src={image.url} alt={image.title} />
                                        </div>
                                        <div className="acs-image-row-content">
                                            <h4>{image.title}</h4>
                                            <p className="acs-image-prompt-preview">{image.prompt}</p>
                                            <div className="acs-image-meta-tags">
                                                <span className="acs-badge acs-badge-sm">{image.size}</span>
                                                <span className="acs-badge acs-badge-sm">{image.quality}</span>
                                                <span className="acs-badge acs-badge-sm">{image.style}</span>
                                            </div>
                                        </div>
                                        <div className="acs-image-row-date">
                                            <FiCalendar />
                                            {formatDate(image.created_at)}
                                        </div>
                                        <div className="acs-image-row-actions">
                                            <button
                                                className="acs-icon-btn"
                                                onClick={() => setSelectedImage(image)}
                                                title="Voir"
                                            >
                                                <FiEye />
                                            </button>
                                            <button
                                                className="acs-icon-btn"
                                                onClick={() => handleDownload(image)}
                                                title="Télécharger"
                                            >
                                                <FiDownload />
                                            </button>
                                            <button
                                                className="acs-icon-btn acs-icon-btn-danger"
                                                onClick={() => handleDelete(image.id)}
                                                title="Supprimer"
                                            >
                                                <FiTrash2 />
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>

                    {/* Pagination */}
                    {pagination.total_pages > 1 && (
                        <div className="acs-pagination">
                            <button
                                className="acs-button acs-button-secondary"
                                disabled={pagination.current_page === 1}
                                onClick={() => setPagination(prev => ({ ...prev, current_page: prev.current_page - 1 }))}
                            >
                                Précédent
                            </button>
                            <span className="acs-pagination-info">
                                Page {pagination.current_page} sur {pagination.total_pages}
                            </span>
                            <button
                                className="acs-button acs-button-secondary"
                                disabled={pagination.current_page === pagination.total_pages}
                                onClick={() => setPagination(prev => ({ ...prev, current_page: prev.current_page + 1 }))}
                            >
                                Suivant
                            </button>
                        </div>
                    )}
                </>
            )}

            {/* Image Detail Modal */}
            {selectedImage && (
                <div className="acs-modal-overlay" onClick={() => setSelectedImage(null)}>
                    <div className="acs-modal acs-modal-large" onClick={(e) => e.stopPropagation()}>
                        <div className="acs-modal-header">
                            <h2>{selectedImage.title}</h2>
                            <button
                                className="acs-modal-close"
                                onClick={() => setSelectedImage(null)}
                            >
                                ×
                            </button>
                        </div>
                        <div className="acs-modal-body">
                            <div className="acs-image-detail">
                                <div className="acs-image-detail-preview">
                                    <img src={selectedImage.url} alt={selectedImage.title} />
                                </div>
                                <div className="acs-image-detail-info">
                                    <div className="acs-detail-group">
                                        <label>Prompt</label>
                                        <p>{selectedImage.prompt}</p>
                                    </div>
                                    <div className="acs-detail-group">
                                        <label>Spécifications</label>
                                        <div className="acs-specs-grid">
                                            <div className="acs-spec">
                                                <span className="acs-spec-label">Taille</span>
                                                <span className="acs-spec-value">{selectedImage.size}</span>
                                            </div>
                                            <div className="acs-spec">
                                                <span className="acs-spec-label">Qualité</span>
                                                <span className="acs-spec-value">{selectedImage.quality}</span>
                                            </div>
                                            <div className="acs-spec">
                                                <span className="acs-spec-label">Style</span>
                                                <span className="acs-spec-value">{selectedImage.style}</span>
                                            </div>
                                            <div className="acs-spec">
                                                <span className="acs-spec-label">Créée le</span>
                                                <span className="acs-spec-value">{formatDate(selectedImage.created_at)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="acs-modal-footer">
                            <button
                                className="acs-button acs-button-danger"
                                onClick={() => {
                                    handleDelete(selectedImage.id);
                                    setSelectedImage(null);
                                }}
                            >
                                <FiTrash2 />
                                Supprimer
                            </button>
                            <button
                                className="acs-button acs-button-secondary"
                                onClick={() => handleDownload(selectedImage)}
                            >
                                <FiDownload />
                                Télécharger
                            </button>
                            <a
                                href={selectedImage.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="acs-button acs-button-primary"
                            >
                                <FiEye />
                                Ouvrir en grand
                            </a>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ImageLibrary;
