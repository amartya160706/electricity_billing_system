document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('role');
    const label = document.getElementById('identifier-label');

    if (role && label) {
        role.addEventListener('change', function () {
            label.textContent =
                role.value === 'consumer'
                ? 'Service Number'
                : 'Username / Mobile';
        });
    }

    const serviceSelect = document.getElementById('service_number_select');
    if (serviceSelect) {
        serviceSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('c_name').value = selectedOption.dataset.name || '';
            document.getElementById('c_address').value = selectedOption.dataset.address || '';
            document.getElementById('c_meter').value = selectedOption.dataset.meter || '';
            document.getElementById('c_category').value = selectedOption.dataset.category || '';
            document.getElementById('c_load').value = selectedOption.dataset.load || '';
            document.getElementById('c_start').value = selectedOption.dataset.start || '';
        });
    }

    // Set default due date to 15 days from today
    const dueDateInput = document.getElementById('due_date');
    if (dueDateInput && !dueDateInput.value) {
        const today = new Date();
        today.setDate(today.getDate() + 15);
        dueDateInput.value = today.toISOString().split('T')[0];
    }
});

