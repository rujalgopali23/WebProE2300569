// js/Student3.js - JavaScript for UC4 (Enrolment/Payment) and UC5 (Reviews)
// Author: Student 3

document.addEventListener('DOMContentLoaded', function () {

    // ---- UC4: Enrolment / Payment ----

    // CVV - numbers only, max 3 digits
    const cvvField = document.getElementById('cvv');
    if (cvvField) {
        cvvField.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').substring(0, 3);
        });
    }

    // Validate expiry format MM/YY and not expired
    const expiryField = document.getElementById('expiry');
    if (expiryField) {
        expiryField.addEventListener('blur', function () {
            const val = this.value;
            if (!val.match(/^\d{2}\/\d{2}$/)) return;
            const [mm, yy] = val.split('/').map(Number);
            const now = new Date();
            const expDate = new Date(2000 + yy, mm - 1);
            if (expDate < now) {
                this.classList.add('is-invalid');
                let err = this.nextElementSibling;
                if (!err || !err.classList.contains('invalid-feedback')) {
                    err = document.createElement('div');
                    err.className = 'invalid-feedback';
                    this.parentNode.appendChild(err);
                }
                err.textContent = 'This card has expired.';
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }

    // ---- UC5: Reviews ----

    // Star hover effect label
    const starLabels = { 1: '⭐ Poor', 2: '⭐⭐ Fair', 3: '⭐⭐⭐ Good', 4: '⭐⭐⭐⭐ Very Good', 5: '⭐⭐⭐⭐⭐ Excellent' };
    document.querySelectorAll('.star-rating label').forEach(label => {
        label.addEventListener('mouseenter', function () {
            const input = document.getElementById(this.getAttribute('for'));
            if (input) {
                const textEl = document.getElementById('ratingText');
                if (textEl) textEl.textContent = starLabels[input.value] || '';
            }
        });
    });

    // Prevent review form double-submit
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function () {
            const btn = this.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            }
        });
    }
});
