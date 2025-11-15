document.addEventListener('DOMContentLoaded', function() {
    const visitorSelect = document.getElementById('visitorSelect');
    const badgeList = document.getElementById('badgeList');
    const registerCardForm = document.getElementById('registerCardForm');
    const badgeForm = document.getElementById('badgeForm');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const terminateBtn = document.getElementById('terminateBtn');

    // --- Event Listeners ---

    visitorSelect.addEventListener('change', function() {
        const visitorId = this.value;
        if (!visitorId) {
            badgeList.innerHTML = '';
            badgeList.style.display = 'none';
            resetForm();
            return;
        }
        fetch(`key_card.php?action=fetch&visitor_id=${visitorId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.badges.length > 0) {
                    let html = '<h5>Existing Key Cards</h5><ul class="list-group key-cards-list">';
                    data.badges.forEach(badge => {
                        html += `<li class="list-group-item">
                            <strong>Key Card #:</strong> ${escapeHtml(badge.key_card_number)} |
                            <strong>Status:</strong> ${escapeHtml(badge.status)}
                            <button class="btn btn-sm btn-link float-end edit-badge-btn" 
                                data-id="${badge.id}" 
                                data-number="${escapeHtml(badge.key_card_number)}" 
                                data-start="${escapeHtml(badge.validity_start)}" 
                                data-end="${escapeHtml(badge.validity_end)}" 
                                data-status="${escapeHtml(badge.status)}">Edit</button>
                        </li>`;
                    });
                    html += '</ul>';
                    badgeList.innerHTML = html;
                    badgeList.style.display = 'block';
                } else {
                    badgeList.innerHTML = '<p>No existing key cards for this visitor.</p>';
                    badgeList.style.display = 'block';
                }
            });
    });

    badgeList.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-badge-btn')) {
            const btn = e.target;
            editBadge(btn.dataset.id, btn.dataset.number, btn.dataset.start, btn.dataset.end, btn.dataset.status);
        }
    });

    registerCardForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const newCardUid = document.getElementById('newCardUid').value;
        if (!newCardUid) {
            alert('Please enter a Card UID to register.');
            return;
        }
        // This part is conceptual. You need a backend endpoint for 'register'.
        // For now, it will just show an alert.
        // To make this work, you would create a case 'register' in key_card.php
        // that inserts the newCardUid into a `registered_cards` table.
        alert(`Simulating registration for Card UID: ${newCardUid}.\nTo implement this, create a 'register' action in key_card.php.`);
        
        // Example of what the fetch would look like:
        /*
        fetch('key_card.php?action=register', { ... body: { card_uid: newCardUid } ... })
            .then(...)
        */
        registerCardForm.reset();
    });


    function editBadge(id, number, start, end, status) {
        document.getElementById('formTitle').textContent = 'Edit Key Card';
        document.getElementById('badgeId').value = id;
        document.getElementById('keyCardNumber').value = number;
        document.getElementById('validityStart').value = formatDateTimeLocal(start);
        document.getElementById('validityEnd').value = formatDateTimeLocal(end);
        document.getElementById('badgeStatus').value = status;
        document.getElementById('statusField').style.display = 'block';
        document.getElementById('terminateBtn').style.display = 'inline-block';
        document.getElementById('submitBtn').textContent = 'Update Key Card';
        document.getElementById('cancelEditBtn').style.display = 'inline-block';
    }

    cancelEditBtn.addEventListener('click', resetForm);

    terminateBtn.addEventListener('click', function() {
        document.getElementById('badgeStatus').value = 'terminated';
        badgeForm.dispatchEvent(new Event('submit'));
    });

    function resetForm() {
        badgeForm.reset();
        document.getElementById('formTitle').textContent = 'Assign Key Card to Visitor';
        document.getElementById('badgeId').value = '';
        document.getElementById('statusField').style.display = 'none';
        document.getElementById('terminateBtn').style.display = 'none';
        document.getElementById('submitBtn').textContent = 'Assign Key Card';
        document.getElementById('cancelEditBtn').style.display = 'none';
    }

    badgeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const badgeId = document.getElementById('badgeId').value;
        const visitorId = visitorSelect.value;
        if (!visitorId) {
            alert('Please select a visitor.');
            return;
        }
        const data = {
            visitor_id: visitorId,
            key_card_number: document.getElementById('keyCardNumber').value,
            validity_start: document.getElementById('validityStart').value.replace('T', ' ') + ':00',
            validity_end: document.getElementById('validityEnd').value.replace('T', ' ') + ':00'
        };
        const action = badgeId ? 'update' : 'issue';
        if (badgeId) {
            data.id = badgeId;
            data.status = document.getElementById('badgeStatus').value;
        }
        fetch(`key_card.php?action=${action}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                resetForm();
                visitorSelect.dispatchEvent(new Event('change'));
            }
        });
    });

    // Helper functions
    function escapeHtml(s) { return s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"); }
    function formatDateTimeLocal(dateTimeStr) {
        if (!dateTimeStr || dateTimeStr === '0000-00-00 00:00:00') return '';
        const dt = new Date(dateTimeStr);
        if (isNaN(dt)) return '';
        return `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}T${String(dt.getHours()).padStart(2, '0')}:${String(dt.getMinutes()).padStart(2, '0')}`;
    }
});