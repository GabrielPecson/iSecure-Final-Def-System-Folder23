document.addEventListener('DOMContentLoaded', () => {
  fetch('sidebar_dashboard.php')
    .then(r => {
      if (!r.ok) throw new Error("Sidebar not found: " + r.status);
      return r.text();
    })
    .then(html => {
      const container = document.getElementById('sidebar-container');
      if (container) {
        container.innerHTML = html;
      } else {
        console.error('Sidebar container not found');
      }
    })
    .catch(err => console.error(err));
});