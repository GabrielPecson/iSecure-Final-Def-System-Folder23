document.addEventListener('DOMContentLoaded', function() {

    const alertModalEl = document.getElementById('alertModal');
    const alertModal = alertModalEl ? new bootstrap.Modal(alertModalEl) : null;

    function showAlertModal(message, title = 'Alert') {
      if (!alertModal) {
        console.error('Alert modal is not available in the DOM.');
        alert(message); // Fallback to old alert
        return;
      }
      const alertModalLabel = document.getElementById('alertModalLabel');
      const alertModalBody = document.getElementById('alertModalBody');
      if (alertModalLabel) alertModalLabel.textContent = title;
      if (alertModalBody) alertModalBody.textContent = message;
      alertModal.show();
    }
    
    // ----- get elements -----
    const visitorSelect = document.getElementById('visitorSelect');
    const badgeList = document.getElementById('badgeList');

    const registerCardForm = document.getElementById('registerCardForm');
    const badgeForm = document.getElementById('badgeForm');

    const badgeIdField = document.getElementById('badgeId');
    const keyCardNumber = document.getElementById('keyCardNumber');

    const validityStartField = document.getElementById('validityStart');
    const validityEndField = document.getElementById('validityEnd');

    const doorSelect = document.getElementById('doorAccess');
    const statusField = document.getElementById('statusField');
    const badgeStatus = document.getElementById('badgeStatus');

    const submitBtn = document.getElementById('submitBtn');
    const terminateBtn = document.getElementById('terminateBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');

    const assignCardField = document.getElementById('assignCardField');
    const keyCardIdSelect = document.getElementById('keyCardId');
    const formTitle = document.getElementById('formTitle');

    // THIS WAS MISSING BEFORE — declare the UID field container
    const uidField = document.getElementById('uidField');

    // ----- defensive check: make sure required elements exist -----
    const required = {
        visitorSelect, badgeList, registerCardForm, badgeForm,
        badgeIdField, validityStartField, validityEndField,
        doorSelect, submitBtn, assignCardField, keyCardIdSelect, formTitle
    };

    for (const [name, el] of Object.entries(required)) {
        if (!el) {
            console.warn(`key_card_manager.js: required element "${name}" is missing in the DOM. Aborting script to avoid errors.`);
            return;
        }
    }

    // statusField, badgeStatus, keyCardNumber, terminateBtn, cancelEditBtn, uidField are optional for graceful degradation
    // If some are missing, we still continue but will hide related functionality:
    if (!statusField) console.warn('statusField not found — edit/status features disabled.');
    if (!badgeStatus) console.warn('badgeStatus dropdown not found — status will not be editable.');
    if (!keyCardNumber) console.warn('keyCardNumber input not found — UID display disabled.');
    if (!terminateBtn) console.warn('terminateBtn not found — terminate feature disabled.');
    if (!cancelEditBtn) console.warn('cancelEditBtn not found — cancel edit disabled.');
    if (!uidField) console.warn('uidField container not found — UID field will not be shown in edit mode.');

    // ============================================================
    // FETCH BADGES FOR VISITOR
    // ============================================================
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
                if (data.success && data.badges && data.badges.length > 0) {

                    let html = `<h5>Existing Key Cards</h5><ul class="list-group key-cards-list">`;

                    data.badges.forEach(badge => {
                        html += `
                            <li class="list-group-item">
                                <strong>UID:</strong> ${escapeHtml(badge.key_card_number)} <br>
                                <strong>Status:</strong> ${escapeHtml(badge.status)} <br>
                                <strong>Validity:</strong> ${badge.validity_start || 'N/A'} → ${badge.validity_end || 'N/A'}
                                <button class="btn btn-sm btn-link float-end edit-badge-btn"
                                    data-id="${badge.id}"
                                    data-number="${escapeHtml(badge.key_card_number)}"
                                    data-start="${badge.validity_start || ''}"
                                    data-end="${badge.validity_end || ''}"
                                    data-status="${badge.status || ''}"
                                    data-door="${badge.door || 'ALL'}"
                                >Edit</button>
                            </li>
                        `;
                    });

                    html += `</ul>`;
                    badgeList.innerHTML = html;
                    badgeList.style.display = 'block';

                } else {
                    badgeList.innerHTML = '<p>No existing key cards for this visitor.</p>';
                    badgeList.style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Error fetching badges:', err);
                badgeList.innerHTML = '<p class="text-danger">Failed to fetch badges.</p>';
            });
    });


    // ============================================================
    // CLICK EVENT FOR EDIT BUTTON (delegated)
    // ============================================================
    badgeList.addEventListener('click', function(e) {
        const target = e.target;
        if (target && target.classList.contains('edit-badge-btn')) {
            editBadge(
                target.dataset.id,
                target.dataset.number,
                target.dataset.start,
                target.dataset.end,
                target.dataset.status,
                target.dataset.door
            );
        }
    });


    // ============================================================
    // REGISTER NEW CARD
    // ============================================================
    registerCardForm.addEventListener('submit', function(e) {
        const cardUidEl = document.getElementById('card_uid');
        const cardNameEl = document.getElementById('card_name');

        if (!cardUidEl || !cardNameEl) {
            showAlertModal('Registration fields missing from the page.');
            e.preventDefault(); // Prevent submission if critical elements are missing
            return;
        }

        const cardUid = cardUidEl.value.trim();
        const cardName = cardNameEl.value.trim();

        if (!cardUid || !cardName) {
            showAlertModal('Please provide both a Card UID and a Card Name.');
            e.preventDefault(); // Prevent submission if validation fails client-side
            return;
        }
        // If client-side validation passes, allow form to submit normally
    });


    // ============================================================
    // EDIT MODE SETUP
    // ============================================================
    function editBadge(id, number, start, end, status, door) {

        if (formTitle) formTitle.textContent = "Edit Key Card";
        if (badgeIdField) badgeIdField.value = id;
        if (keyCardNumber) keyCardNumber.value = number || '';

        if (validityStartField) validityStartField.value = formatDateTimeLocal(start);
        if (validityEndField) validityEndField.value = formatDateTimeLocal(end);

        if (badgeStatus) badgeStatus.value = status || 'active';
        if (doorSelect) doorSelect.value = door || 'ALL';

        // Show/hide UI elements depending on availability
        if (statusField) statusField.style.display = 'block';
        if (terminateBtn) terminateBtn.style.display = 'inline-block';
        if (cancelEditBtn) cancelEditBtn.style.display = 'inline-block';
        if (submitBtn) submitBtn.textContent = 'Update Key Card';

        if (assignCardField) assignCardField.style.display = 'none';
        if (uidField) uidField.style.display = 'block';
    }


    // ============================================================
    // RESET TO ASSIGN MODE
    // ============================================================
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', resetForm);
    }

    function resetForm() {
        try {
            badgeForm.reset();
        } catch (err) { /* ignore */ }

        if (formTitle) formTitle.textContent = "Assign Key Card to Visitor";
        if (badgeIdField) badgeIdField.value = "";
        if (submitBtn) submitBtn.textContent = "Assign Key Card";

        if (statusField) statusField.style.display = 'none';
        if (terminateBtn) terminateBtn.style.display = 'none';
        if (cancelEditBtn) cancelEditBtn.style.display = 'none';

        if (assignCardField) assignCardField.style.display = 'block';
        if (uidField) uidField.style.display = 'none';
    }


    // ============================================================
    // TERMINATE CARD
    // ============================================================
    if (terminateBtn) {
        terminateBtn.addEventListener('click', function() {
            if (badgeStatus) badgeStatus.value = 'terminated';
            // trigger submit
            badgeForm.dispatchEvent(new Event('submit'));
        });
    }


    // ============================================================
    // ASSIGN or UPDATE SUBMIT HANDLER
    // ============================================================
    badgeForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const editingBadgeId = (badgeIdField && badgeIdField.value) ? badgeIdField.value : '';
        const visitorId = (visitorSelect && visitorSelect.value) ? visitorSelect.value : '';

        if (!visitorId) {
            showAlertModal('Please select a visitor.');
            return;
        }

        const vsVal = validityStartField ? validityStartField.value : '';
        const veVal = validityEndField ? validityEndField.value : '';

        if (!vsVal || !veVal) {
            showAlertModal('Please fill validity start and end.');
            return;
        }

        const validityStart = vsVal.replace('T', ' ') + ':00';
        const validityEnd = veVal.replace('T', ' ') + ':00';

        const data = {
            visitor_id: visitorId,
            validity_start: validityStart,
            validity_end: validityEnd,
            door: doorSelect ? doorSelect.value : 'ALL'
        };

        if (editingBadgeId) {
            data.id = editingBadgeId;
            if (badgeStatus) data.status = badgeStatus.value;
        } else {
            data.id = keyCardIdSelect ? keyCardIdSelect.value : '';
            if (!data.id) {
                showAlertModal('Please select a card to assign.');
                return;
            }
            // Check if selected option is disabled (already assigned)
            const selectedOption = keyCardIdSelect.options[keyCardIdSelect.selectedIndex];
            if (selectedOption.disabled) {
                showAlertModal('This card is already assigned to a visitor. Please select an available card.');
                return;
            }
        }

        // If client-side validation passes, allow form to submit normally
        // (no e.preventDefault() here, and no fetch call)
    });


    // ============================================================
    // HELPER FUNCTIONS
    // ============================================================
    function escapeHtml(s) {
        if (!s && s !== '') return '';
        return String(s).replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;");
    }

    function formatDateTimeLocal(dateTimeStr) {
        if (!dateTimeStr || dateTimeStr === '0000-00-00 00:00:00') return '';
        const dt = new Date(dateTimeStr);
        if (isNaN(dt)) return '';
        return (
            dt.getFullYear() + "-" +
            String(dt.getMonth() + 1).padStart(2, '0') + "-" +
            String(dt.getDate()).padStart(2, '0') + "T" +
            String(dt.getHours()).padStart(2, '0') + ":" +
            String(dt.getMinutes()).padStart(2, '0')
        );
    }

});
