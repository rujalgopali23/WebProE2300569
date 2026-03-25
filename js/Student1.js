// js/Student1.js - JavaScript for UC1 (Provider Registration) and UC2 (Officer Review)
// Author: Student 1

document.addEventListener('DOMContentLoaded', function () {

    // ---- UC1: Provider Registration ----

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

    // ---- UC2: Officer Review ----

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
});
