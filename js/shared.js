// js/shared.js - Shared JavaScript for EduSkill Marketplace System

// Course search by title
function searchCourses() {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.course-card-wrapper');
    let found = 0;

    cards.forEach(card => {
        const title = card.querySelector('.card-title').textContent.toLowerCase();
        const provider = card.querySelector('.text-muted.small').textContent.toLowerCase();
        if (title.includes(query) || provider.includes(query)) {
            card.style.display = 'block';
            found++;
        } else {
            card.style.display = 'none';
        }
    });

    // Show no results message
    const noResults = document.getElementById('noResults');
    if (noResults) noResults.remove();
    if (found === 0) {
        const msg = document.createElement('p');
        msg.id = 'noResults';
        msg.className = 'text-muted text-center w-100 py-4';
        msg.innerHTML = '<i class="fas fa-search fa-2x mb-2 d-block"></i>No courses found for "' + query + '"';
        document.getElementById('courseGrid').appendChild(msg);
    }
}

// Search on Enter key
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') searchCourses();
        });
    }

    // Category filter buttons
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const category = this.dataset.category;
            document.querySelectorAll('.course-card-wrapper').forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Auto-dismiss alerts after 4 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });
});
