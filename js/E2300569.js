

document.addEventListener('DOMContentLoaded', function () {


    // Character counter for org_profile textarea
    const profileField = document.querySelector('[name="org_profile"]');
    if (profileField) {
        const counter = document.createElement('div');
        counter.className = 'form-text';
        counter.id = 'profileCounter';
        profileField.parentNode.appendChild(counter);

        function updateCounter() {
            const len = profileField.value.trim().length;
            counter.textContent = len + ' characters (minimum 50)';
            counter.style.color = len < 50 ? '#dc3545' : '#198754';
        }
        profileField.addEventListener('input', updateCounter);
        updateCounter();
    }

    // File upload label update
    const fileInput = document.querySelector('input[type="file"][name="document"]');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            const label = this.nextElementSibling;
            if (label && this.files.length > 0) {
                const size = (this.files[0].size / 1024 / 1024).toFixed(2);
                console.log('File selected: ' + this.files[0].name + ' (' + size + ' MB)');
            }
        });
    }

    // Confirm before approval action
    const approveForms = document.querySelectorAll('form [value="approve"]');
    approveForms.forEach(btn => {
        btn.closest('form').addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to APPROVE this training provider?')) {
                e.preventDefault();
            }
        });
    });

    // Require rejection reason before submitting reject form
    const rejectForm = document.querySelector('#rejectModal form');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function (e) {
            const reason = this.querySelector('[name="rejection_reason"]');
            if (!reason || reason.value.trim().length < 10) {
                e.preventDefault();
                reason.classList.add('is-invalid');
                let err = reason.nextElementSibling;
                if (!err || !err.classList.contains('invalid-feedback')) {
                    err = document.createElement('div');
                    err.className = 'invalid-feedback';
                    reason.parentNode.appendChild(err);
                }
                err.textContent = 'Please provide a reason of at least 10 characters.';
            }
        });
    }

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