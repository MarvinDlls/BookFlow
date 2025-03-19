import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

async function addBookToDatabase(book) {
    console.log("Données envoyées :", book);

    try {
        const response = await fetch('/book/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: book.title,
                author: book.authors,
                description: book.description,
                cover: book.cover,
                popularity: book.popularity,
                slug: book.title.toLowerCase().replace(/\s+/g, '-').substring(0, 50),
            }),
        });

        const result = await response.json();
        console.log("Réponse serveur :", result);

        if (result.success) {
            window.location.href = `/books/${book.id}`;
        } else {
            console.error("Erreur lors de l'ajout du livre :", result.message);
        }
    } catch (error) {
        console.error("Erreur réseau :", error);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('#bookTabs button');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => {
                t.classList.remove('text-red-600', 'border-red-600', 'active');
                t.classList.add('text-gray-500', 'border-transparent');
                t.setAttribute('aria-selected', 'false');
            });

            tabPanes.forEach(pane => {
                pane.classList.remove('show', 'active');
            });

            this.classList.remove('text-gray-500', 'border-transparent');
            this.classList.add('text-red-600', 'border-red-600', 'active');
            this.setAttribute('aria-selected', 'true');

            const targetId = this.getAttribute('data-bs-target').substring(1);
            const targetPane = document.getElementById(targetId);
            targetPane.classList.add('show', 'active');
        });
    });

    const ratingStars = document.querySelectorAll('form button svg');
    ratingStars.forEach((star, index) => {
        star.parentElement.addEventListener('click', function() {
            ratingStars.forEach((s, i) => {
                if (i <= index) {
                    s.parentElement.classList.remove('text-gray-300');
                    s.parentElement.classList.add('text-yellow-400');
                } else {
                    s.parentElement.classList.remove('text-yellow-400');
                    s.parentElement.classList.add('text-gray-300');
                }
            });
        });
    });
});