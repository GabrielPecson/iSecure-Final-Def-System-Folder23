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
        const cardUid = document.getElementById('card_uid').value;
        const cardName = document.getElementById('card_name').value;

        if (!cardUid || !cardName) {
            alert('Please provide both a Card UID and a Card Name.');
            return;
        }

        fetch('key_card.php?action=register', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ card_uid: cardUid, card_name: cardName })
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                // Reload the page to show the new card in the dropdown and table
                window.location.reload();
            }
        });
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
        const editingBadgeId = document.getElementById('badgeId').value; // This is for editing, not assigning
        const visitorId = visitorSelect.value;

        if (!visitorId) {
            alert('Please select a visitor.');
            return;
        }

        const data = {
            visitor_id: visitorId,
            validity_start: document.getElementById('validityStart').value.replace('T', ' ') + ':00',
            validity_end: document.getElementById('validityEnd').value.replace('T', ' ') + ':00'
        };

        if (editingBadgeId) { // This block is for editing an already assigned card
            data.id = editingBadgeId;
            data.status = document.getElementById('badgeStatus').value;
        } else { // This block is for assigning a new card
            data.id = document.getElementById('keyCardId').value; // The ID of the badge record to update
        }

        fetch(`key_card.php?action=update`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                resetForm();
                window.location.reload(); // Reload to see the updated table and dropdown
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