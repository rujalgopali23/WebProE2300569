

document.addEventListener('DOMContentLoaded', function () {

    // ---- Manage Courses (UC3) ----

    // Auto-set end_date minimum to start_date
    const startDate = document.querySelector('[name="start_date"]');
    const endDate   = document.querySelector('[name="end_date"]');
    if (startDate && endDate) {
        startDate.addEventListener('change', function () {
            endDate.min = this.value;
            if (endDate.value && endDate.value < this.value) {
                endDate.value = '';
            }
        });
    }

    // Live price formatting
    const priceField = document.querySelector('[name="price"]');
    if (priceField) {
        priceField.addEventListener('blur', function () {
            if (this.value !== '') {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    }

    // Confirm delete
    document.querySelectorAll('a[href*="delete="]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ---- Reports (UC6) ----

    // Highlight current month in table
    const currentMonth = new Date().getMonth(); // 0-indexed
    const monthRows = document.querySelectorAll('table tbody tr');
    if (monthRows.length === 12) {
        monthRows[currentMonth].classList.add('table-primary');
    }
});