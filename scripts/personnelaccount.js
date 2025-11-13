document.addEventListener("DOMContentLoaded", () => {
  const usersTbody = document.getElementById("usersTbody");
  const addModal = document.getElementById("addUserModal");
  const addForm = document.getElementById("addUserForm");
  const editModal = document.getElementById("editUserModal");
  const editForm = document.getElementById("editUserForm");
  const searchInput = document.getElementById("search");
  const roleDropdown = document.getElementById("roleDropdown");

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
    console.log("toggleDropdown called");
    const dropdown = document.querySelector('.dropdown');
    if (dropdown.classList.contains('show')) {
      dropdown.classList.remove('show');
      console.log("Dropdown hidden");
    } else {
      // Close any other open dropdowns
      document.querySelectorAll('.dropdown.show').forEach(d => d.classList.remove('show'));
      dropdown.classList.add('show');
      console.log("Dropdown shown");
    }
  };

  // Fix dropdown button click to toggle dropdown-content visibility
  document.querySelector('.role-btn').addEventListener('click', (e) => {
    console.log("Role button clicked");
    e.stopPropagation();
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
    addUserForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(addUserForm);

      try {
        const response = await fetch('add_user.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        showNotification(result.message, result.success ? "success" : "error");

        if (result.success) {
          addUserModal.classList.remove('show');
          addUserForm.reset();
          fetchUsers();
        }
      } catch (error) {
        console.error('Error adding user:', error);
        showNotification('Failed to add user. Please try again.', "error");
      }
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
        const user = await res.json();
        if (!user.id) return showNotification(user.message || "Cannot load user", "error");

        // Safely populate fields if they exist
        ['id','full_name','email','rank','role','status'].forEach(f => {
          if (editForm[f]) editForm[f].value = user[f] || '';
        });

        editModal.classList.add("show");
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

      confirmYes.onclick = async () => {
        confirmModal.classList.remove("show");
        const fd = new FormData();
        fd.append("id", id);
        try {
          const res = await fetch("delete_user.php", { method: "POST", body: fd });
          const json = await res.json();
          showNotification(json.message || (json.success ? "Deleted!" : "Delete failed"), json.success ? "success" : "error");
          if (json.success) fetchUsers();
        } catch (err) {
          console.error(err);
          showNotification("Network error while deleting user", "error");
        }
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

  // Edit form submit
  editForm.addEventListener("submit", async e => {
    e.preventDefault();
    try {
      const formData = new FormData(editForm);
      const res = await fetch("edit_user.php", { method: "POST", body: formData });
      const json = await res.json();
      showNotification(json.message, json.status === "success" ? "success" : "error");
      if (json.status === "success") {
        editModal.classList.remove("show");
        fetchUsers();
      }
    } catch (err) {
      console.error(err);
      showNotification("Network error while updating user", "error");
    }
  });

  // Modal close buttons
  ['closeEditModal','cancelEditBtn','closeAddModal','cancelAddBtn'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.onclick = () => {
      if (id.includes('Edit')) editModal.classList.remove("show");
      else addModal.classList.remove("show");
    };
  });

  fetchUsers();
});