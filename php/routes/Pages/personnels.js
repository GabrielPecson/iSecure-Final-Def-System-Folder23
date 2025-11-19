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
          } else {
            showNotification("User not found for editing.", "error");
          }
        })
        .catch(err => {
          console.error("Error fetching users for edit", err);
          showNotification("Failed to fetch user details due to network error", "error");
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

        const redirectInput = document.createElement('input');
        redirectInput.type = 'hidden';
        redirectInput.name = 'redirect_to';
        redirectInput.value = 'personnels.php';
        form.appendChild(redirectInput);

        document.body.appendChild(form);
        form.submit();
      };

      confirmNo.onclick = () => {
        confirmModal.classList.remove("show");
      };
    }
  });

  // Save edited user
  editForm.addEventListener("submit", e => {
    e.preventDefault();
    const fd = new FormData(editForm);

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'edit_user.php';

    for (let [name, value] of fd.entries()) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      form.appendChild(input);
    }

    const redirectInput = document.createElement('input');
    redirectInput.type = 'hidden';
    redirectInput.name = 'redirect_to';
    redirectInput.value = 'personnels.php';
    form.appendChild(redirectInput);

    document.body.appendChild(form);
    form.submit();
  });

  loadUsers();
});
