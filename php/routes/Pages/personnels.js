document.addEventListener("DOMContentLoaded", () => {
  const tbody = document.getElementById("personnelsTbody");
  const editModal = new bootstrap.Modal(document.getElementById("editUserModal"));
  const editForm = document.getElementById("editUserForm");

  async function loadUsers() {
    try {
      const res = await fetch("fetch_users.php");
      const users = await res.json();
      tbody.innerHTML = "";

      users.forEach(u => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${u.full_name}</td>
          <td>${u.email}</td>
          <td>${u.rank}</td>
          <td><span class="badge bg-${u.status === 'Active' ? 'success' : 'secondary'}">${u.status}</span></td>
          <td>${u.role}</td>
          <td>${u.joined_date}</td>
          <td>${u.last_active}</td>
          <td>
            <button class="btn btn-sm btn-primary edit-btn" data-id="${u.id}">Edit</button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="${u.id}">Delete</button>
          </td>
        `;
        tbody.appendChild(tr);
      });
    } catch (err) {
      console.error("Error loading users", err);
    }
  }

  // Edit handler
  tbody.addEventListener("click", e => {
    const btn = e.target.closest(".edit-btn");
    if (btn) {
      const id = btn.dataset.id;
      fetch("fetch_users.php")
        .then(res => res.json())
        .then(users => {
          const u = users.find(x => x.id === id);
          if (u) {
            document.getElementById("edit_id").value = u.id;
            document.getElementById("edit_full_name").value = u.full_name;
            document.getElementById("edit_email").value = u.email;
            document.getElementById("edit_rank").value = u.rank;
            document.getElementById("edit_status").value = u.status;
            document.getElementById("edit_role").value = u.role;
            editModal.show();
          }
        });
    }
  });

  // Delete handler
  tbody.addEventListener("click", async e => {
    const btn = e.target.closest(".delete-btn");
    if (btn) {
      const id = btn.dataset.id;
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
        const res = await fetch("delete_user.php", { method: "POST", body: fd });
        const json = await res.json();
        if (json.success) {
          showNotification("Deleted!", "success");
          loadUsers();
        } else {
          showNotification(json.message || "Delete failed", "error");
        }
      };

      confirmNo.onclick = () => {
        confirmModal.classList.remove("show");
      };
    }
  });

  // Save edited user
  editForm.addEventListener("submit", async e => {
    e.preventDefault();
    const fd = new FormData(editForm);
    const res = await fetch("edit_user.php", { method: "POST", body: fd });
    const json = await res.json();
    if (json.success) {
      editModal.hide();
      loadUsers();
    } else {
      showNotification(json.message || "Save failed", "error");
    }
  });

  loadUsers();
});
