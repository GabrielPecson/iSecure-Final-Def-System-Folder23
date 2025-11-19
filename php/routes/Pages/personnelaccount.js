document.addEventListener("DOMContentLoaded", () => {
  const usersTbody = document.getElementById("usersTbody");
  const addForm = document.getElementById("addUserForm");
  const editForm = document.getElementById("editUserForm");
  const searchInput = document.getElementById("search");
  const roleDropdown = document.getElementById("roleDropdown"); // .dropdown-content
  const mainDropdown = document.querySelector('.dropdown'); // .dropdown container

  let allUsers = []; // Store all users for filtering

  // Set initial selected role to "All Roles"
  const allRolesLink = roleDropdown.querySelector('a[data-role="all"]');
  if (allRolesLink) {
    allRolesLink.classList.add('selected');
    const roleBtn = document.querySelector('.role-btn');
    if (roleBtn) {
      roleBtn.innerHTML = `<i class="fa-solid fa-user"></i> ${allRolesLink.textContent} <i class="fa-solid fa-caret-down"></i>`;
    }
  }

  function escapeHtml(s) {
  if (s === null || s === undefined) return "";
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

  // Fetch users and populate table
  async function fetchUsers() {
    try {
      const res = await fetch("fetch_users.php");
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const users = await res.json();
      if (!Array.isArray(users)) return console.error("Invalid response", users);

      allUsers = users; // Store for filtering
      renderUsers(allUsers);
    } catch (err) {
      console.error(err);
      showNotification("Error fetching users. Check console.", "error");
    }
  }

  // Render users based on filtered list
  function renderUsers(users) {
    usersTbody.innerHTML = "";
    users.forEach(u => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${escapeHtml(u.full_name)}</td>
        <td>${escapeHtml(u.email)}</td>
        <td>${escapeHtml(u.rank)}</td>
        <td>${escapeHtml(u.status)}</td>
        <td>${escapeHtml(u.role)}</td>
        <td>${escapeHtml(u.joined_date)}</td>
        <td>${escapeHtml(u.last_active)}</td>
        <td>
          <button class="btn btn-sm btn-primary edit-btn" data-id="${u.id}">Edit</button>
          <button class="btn btn-sm btn-danger delete-btn" data-id="${u.id}">Delete</button>
        </td>
      `;
      usersTbody.appendChild(tr);
    });
  }

  // Filter users based on search and role
  function filterUsers() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedRole = document.querySelector('.dropdown-content a.selected')?.getAttribute('data-role') || 'all';

    let filtered = allUsers.filter(u => {
      const userRole = u.role ? u.role.trim() : '';
      const matchesSearch = u.full_name.toLowerCase().includes(searchTerm) || u.email.toLowerCase().includes(searchTerm);
      const matchesRole = selectedRole === 'all' || userRole === selectedRole;
      return matchesSearch && matchesRole;
    });

    renderUsers(filtered);
  }

  // Search input event
  searchInput.addEventListener('input', filterUsers);

  // Role dropdown toggle
  window.toggleDropdown = function() {
    const dropdown = document.querySelector('.dropdown');
    const roleBtn = document.querySelector('.role-btn');
    const dropdownContent = document.getElementById('roleDropdown');

    if (dropdown.classList.contains('show')) {
      dropdown.classList.remove('show');
    } else {
      // Close any other open dropdowns
      document.querySelectorAll('.dropdown.show').forEach(d => d.classList.remove('show'));
      dropdown.classList.add('show');

      // Calculate position for fixed dropdown content
      const rect = roleBtn.getBoundingClientRect();
      dropdownContent.style.top = `${rect.bottom + 5}px`; // 5px for a little spacing
      dropdownContent.style.left = `${rect.left}px`;
    }
  };

  // Fix dropdown button click to toggle dropdown-content visibility
  document.querySelector('.role-btn').addEventListener('click', (e) => {
    window.toggleDropdown();
  });

  // Role selection
  document.querySelectorAll('.dropdown-content a').forEach(a => {
    a.addEventListener('click', function(e) {
      e.preventDefault();
      const selectedRole = this.getAttribute('data-role');

      // Remove selected class from all
      document.querySelectorAll('.dropdown-content a').forEach(a => a.classList.remove('selected'));
      // Add to clicked
      this.classList.add('selected');

      // Update button text
      const roleBtn = document.querySelector('.role-btn');
      roleBtn.innerHTML = `<i class="fa-solid fa-user"></i> ${this.textContent} <i class="fa-solid fa-caret-down"></i>`;

      // Close dropdown
      document.querySelector('.dropdown').classList.remove('show');

      // Filter
      filterUsers();
    });
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    const dropdown = document.querySelector('.dropdown');
    if (!dropdown.contains(e.target)) {
      dropdown.classList.remove('show');
    }
  });

  // Add Personnel button click to open modal
  const addPersonnelBtn = document.getElementById('addPersonnelBtn');
  const addUserModal = document.getElementById('addUserModal');
  const closeAddModalBtn = document.getElementById('closeAddModal');
  const cancelAddBtn = document.getElementById('cancelAddBtn');
  const addUserForm = document.getElementById('addUserForm');

  if (addPersonnelBtn && addUserModal) {
    addPersonnelBtn.addEventListener('click', () => {
      addUserModal.classList.add('show');
    });
  }

  if (closeAddModalBtn) {
    closeAddModalBtn.addEventListener('click', () => {
      addUserModal.classList.remove('show');
    });
  }

  if (cancelAddBtn) {
    cancelAddBtn.addEventListener('click', () => {
      addUserModal.classList.remove('show');
    });
  }

  // Add User form submit handler
  if (addUserForm) {
    addUserForm.addEventListener('submit', (e) => {
      const passwordInput = document.getElementById('password');
      const confirmPasswordInput = document.getElementById('password_confirm');
      const errorMessageDiv = document.getElementById('add-user-error-message');

      // Clear previous error
      errorMessageDiv.textContent = '';

      if (passwordInput.value === '') {
        e.preventDefault();
        errorMessageDiv.textContent = 'Password cannot be empty.';
        return;
      }

      if (passwordInput.value !== confirmPasswordInput.value) {
        e.preventDefault(); // Prevent form submission
        errorMessageDiv.textContent = 'Password and Confirm Password do not match.';
      }
      // If they match, the form will submit normally
    });
  }

  

  // Edit & Delete Delegation
  usersTbody.addEventListener("click", async e => {
    const editBtn = e.target.closest(".edit-btn");
    const deleteBtn = e.target.closest(".delete-btn");

    if (editBtn) {
      const id = editBtn.dataset.id;
      try {
        const res = await fetch(`get_user.php?id=${encodeURIComponent(id)}`);
        const data = await res.json();
        if (!data.success) return showNotification(data.message || "Cannot load user", "error");

        const user = data.user;

        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_full_name').value = user.full_name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_rank').value = user.rank;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_status').value = user.status;

        document.getElementById('editUserModal').classList.add('show');
      } catch (err) {
        console.error(err);
        showNotification("Network error while fetching user", "error");
      }
    }

    if (deleteBtn) {
      const id = deleteBtn.dataset.id;
      const confirmModal = document.getElementById("confirmModal");
      const confirmMessage = document.getElementById("confirmMessage");
      const confirmYes = document.getElementById("confirmYes");
      const confirmNo = document.getElementById("confirmNo");

      confirmMessage.textContent = "Delete this user?";
      confirmModal.classList.add("show");

      confirmYes.onclick = () => {
        confirmModal.classList.remove("show");

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_user.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
      };

      confirmNo.onclick = () => {
        confirmModal.classList.remove("show");
      };
    }
  });



  // Password toggle for edit modal
  const toggleEditPassword = document.getElementById('toggleEditPassword');
  const editPasswordInput = document.getElementById('edit_password');

  if (toggleEditPassword && editPasswordInput) {
    toggleEditPassword.addEventListener('click', () => {
      const type = editPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      editPasswordInput.setAttribute('type', type);
      toggleEditPassword.classList.toggle('fa-eye');
      toggleEditPassword.classList.toggle('fa-eye-slash');
    });
  }

  

  const closeAddModalBtnEl = document.getElementById('closeAddModal');
  const cancelAddBtnEl = document.getElementById('cancelAddBtn');
  const closeEditModalBtnEl = document.getElementById('closeEditModal');

  if (closeAddModalBtnEl) closeAddModalBtnEl.onclick = () => document.getElementById('addUserModal').classList.remove('show');
  if (cancelAddBtnEl) cancelAddBtnEl.onclick = () => document.getElementById('addUserModal').classList.remove('show');
  if (closeEditModalBtnEl) closeEditModalBtnEl.onclick = () => document.getElementById('editUserModal').classList.remove('show');

  const cancelEditBtn = document.getElementById('cancelEditBtn');
  if (cancelEditBtn) cancelEditBtn.onclick = () => document.getElementById('editUserModal').classList.remove('show');

  fetchUsers();

  
});