import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
{{ parent() }}
    document.addEventListener('DOMContentLoaded', function() {
        // Fonction pour charger les données du livre dans la modale
        function loadBookPreview(bookId) {
            fetch('/api/books/' + bookId)
                .then(response => response.json())
                .then(book => {
                    // Remplir les champs de la modale avec les données du livre
                    document.getElementById('modalBookTitle').textContent = book.title;
                    document.getElementById('modalBookAuthors').textContent = book.authors ? book.authors.join(', ') : 'Auteur inconnu';
                    
                    // Image
                    const imgElement = document.getElementById('modalBookImage');
                    if (book.thumbnail) {
                        imgElement.src = book.thumbnail;
                        imgElement.classList.remove('d-none');
                        document.getElementById('modalBookPlaceholder').classList.add('d-none');
                    } else {
                        imgElement.classList.add('d-none');
                        document.getElementById('modalBookPlaceholder').classList.remove('d-none');
                    }
                    
                    // Description
                    document.getElementById('modalBookDescription').textContent = book.description || 'Aucune description disponible';
                    
                    // Badges d'informations
                    const infoContainer = document.getElementById('modalBookInfo');
                    infoContainer.innerHTML = '';
                    
                    if (book.publishedDate) {
                        const dateBadge = document.createElement('span');
                        dateBadge.className = 'badge bg-secondary me-1';
                        dateBadge.innerHTML = `<i class="bi bi-calendar me-1"></i> ${book.publishedDate}`;
                        infoContainer.appendChild(dateBadge);
                    }
                    
                    if (book.pageCount) {
                        const pagesBadge = document.createElement('span');
                        pagesBadge.className = 'badge bg-secondary me-1';
                        pagesBadge.innerHTML = `<i class="bi bi-file-text me-1"></i> ${book.pageCount} pages`;
                        infoContainer.appendChild(pagesBadge);
                    }
                    
                    if (book.language) {
                        const langBadge = document.createElement('span');
                        langBadge.className = 'badge bg-secondary me-1';
                        langBadge.innerHTML = `<i class="bi bi-globe me-1"></i> ${book.language.toUpperCase()}`;
                        infoContainer.appendChild(langBadge);
                    }
                    
                    // Liens
                    const detailsLink = document.getElementById('modalBookDetails');
                    detailsLink.href = `/books/${book.id}`;
                    
                    const previewLink = document.getElementById('modalBookPreview');
                    if (book.previewLink) {
                        previewLink.href = book.previewLink;
                        previewLink.classList.remove('d-none');
                    } else {
                        previewLink.classList.add('d-none');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des données du livre:', error);
                });
        }
        
        // Ajouter des écouteurs d'événements aux boutons "Aperçu rapide"
        document.querySelectorAll('.btn-quick-preview').forEach(button => {
            button.addEventListener('click', function(event) {
                const bookId = this.dataset.bookId;
                loadBookPreview(bookId);
            });
        });
    });
