/* ---- Logout Modal ---- */
const logoutLink = document.getElementById("logout-link");
if (logoutLink) {
  logoutLink.addEventListener("click", (ev) => {
    ev.preventDefault();
    const modal = document.getElementById("confirmModal");
    const msgEl = document.getElementById("confirmMessage");
    const yes = document.getElementById("confirmYes");
    const no = document.getElementById("confirmNo");

    msgEl.textContent = "Are you sure you want to log out?";
    modal.classList.add("show");

    yes.onclick = () => { window.location.href = logoutLink.href; };
    no.onclick = () => { modal.classList.remove("show"); };
  });
}

/* ---- Headlines Modal ---- */
const openHeadlines = document.getElementById("openHeadlines");
const headlinesModal = document.getElementById("headlinesModal");
const closeHeadlines = document.getElementById("closeHeadlines");

if (openHeadlines && headlinesModal && closeHeadlines) {
  openHeadlines.addEventListener("click", () => {
    headlinesModal.classList.add("show");
  });
  closeHeadlines.addEventListener("click", () => {
    headlinesModal.classList.remove("show");
  });
  // close when clicking outside
  headlinesModal.addEventListener("click", (e) => {
    if (e.target === headlinesModal) {
      headlinesModal.classList.remove("show");
    }
  });
}

/* ---- Carousel Edit ---- */
let selectedCarouselId = null;

function selectCarousel(id, element) {
  // Remove selected class from all
  document.querySelectorAll('.preview-card').forEach(card => card.classList.remove('selected'));
  // If clicking the same, deselect
  if (selectedCarouselId === id) {
    selectedCarouselId = null;
    clearForm();
  } else {
    // Select this one
    element.classList.add('selected');
    selectedCarouselId = id;
    // Extract title and text
    const title = element.querySelector('.preview-caption strong').textContent;
    const text = element.querySelector('.preview-caption').childNodes[2].textContent.trim();
    editCarousel(id, title, text);
  }
}

function editCarousel(id, title, text) {
  document.getElementById('carouselId').value = id;
  document.getElementById('carouselTitle').value = title;
  document.getElementById('carouselText').value = text;
  // Note: Image file input can't be pre-filled for security reasons
  document.getElementById('submitBtn').textContent = 'Update Carousel';
  document.getElementById('deleteBtn').style.display = 'inline-block';
}

function clearForm() {
  document.getElementById('carouselForm').reset();
  document.getElementById('carouselId').value = '';
  document.getElementById('submitBtn').textContent = 'Add Carousel';
  // Deselect all
  document.querySelectorAll('.preview-card').forEach(card => card.classList.remove('selected'));
  selectedCarouselId = null;
  document.getElementById('deleteBtn').style.display = 'none';
}

function deleteCarousel() {
  if (confirm('Are you sure you want to delete this carousel?')) {
    document.getElementById('deleteId').value = selectedCarouselId;
    document.getElementById('deleteForm').submit();
  }
}

/* ---- Crop Modal ---- */
let imgOffsetX = 0, imgOffsetY = 0, isDragging = false, startX, startY, scale, newWidth, newHeight, cropW = 1480, cropH = 500;
let currentImg = null;
let zoomLevel = 1.0;
let canvasScaleX, canvasScaleY; // Scale factors for mouse coordinates

const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));

function showCropModal() {
  cropModal.show();
  zoomLevel = 1.0; // Reset zoom
  const fileInput = document.getElementById('carouselImage');
  if (fileInput.files[0]) {
    const file = fileInput.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = new Image();
      img.onload = function() {
        currentImg = img;
        scale = Math.max(cropW / img.width, cropH / img.height);
        updateImageSize();
        imgOffsetX = (cropW - newWidth) / 2;
        imgOffsetY = (cropH - newHeight) / 2;

        const canvas = document.getElementById('cropCanvas');
        canvas.width = cropW;
        canvas.height = cropH;

        draw(); // Draw immediately with current scales
        // Delay for modal resize
        setTimeout(() => {
          const canvas = document.getElementById('cropCanvas');
          if (canvas) {
            const rect = canvas.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
              canvasScaleX = canvas.width / rect.width;
              canvasScaleY = canvas.height / rect.height;
              draw();
            }
          }
        }, 300);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
}

function updateImageSize() {
  if (!currentImg) return;
  const effectiveScale = scale * zoomLevel;
  newWidth = currentImg.width * effectiveScale;
  newHeight = currentImg.height * effectiveScale;
}

function draw() {
  const canvas = document.getElementById('cropCanvas');
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  if (currentImg) {
    ctx.drawImage(currentImg, imgOffsetX, imgOffsetY, newWidth, newHeight);
  }
  ctx.strokeStyle = 'red';
  ctx.lineWidth = 2;
  ctx.strokeRect(0, 0, canvas.width, canvas.height);
}

function zoomIn() {
  if (!currentImg) return;
  const centerImageX = -imgOffsetX + cropW / 2;
  const centerImageY = -imgOffsetY + cropH / 2;
  zoomLevel *= 1.1;
  if (zoomLevel > 5) zoomLevel = 5; // Max zoom limit
  updateImageSize();
  imgOffsetX = -centerImageX + cropW / 2;
  imgOffsetY = -centerImageY + cropH / 2;
  draw();
}

function zoomOut() {
  if (!currentImg) return;
  const centerImageX = -imgOffsetX + cropW / 2;
  const centerImageY = -imgOffsetY + cropH / 2;
  zoomLevel /= 1.1;
  if (zoomLevel < 0.5) zoomLevel = 0.5; // Min zoom limit
  updateImageSize();
  imgOffsetX = -centerImageX + cropW / 2;
  imgOffsetY = -centerImageY + cropH / 2;
  draw();
}

document.getElementById('cropCanvas').addEventListener('mousedown', (e) => {
  isDragging = true;
  const canvas = e.target;
  const rect = canvas.getBoundingClientRect();
  startX = (e.clientX - rect.left) * canvasScaleX;
  startY = (e.clientY - rect.top) * canvasScaleY;
});

document.getElementById('cropCanvas').addEventListener('mousemove', (e) => {
  if (isDragging) {
    const canvas = e.target;
    const rect = canvas.getBoundingClientRect();
    const currentX = (e.clientX - rect.left) * canvasScaleX;
    const currentY = (e.clientY - rect.top) * canvasScaleY;
    imgOffsetX += currentX - startX;
    imgOffsetY += currentY - startY;
    startX = currentX;
    startY = currentY;
    draw();
  }
});

document.getElementById('cropCanvas').addEventListener('mouseup', () => {
  isDragging = false;
});

document.getElementById('cropCanvas').addEventListener('mouseleave', () => {
  isDragging = false;
});

document.getElementById('cropOk').onclick = function() {
  const canvas = document.getElementById('cropCanvas');
  const cropX = Math.max(0, Math.min(1, (-imgOffsetX + canvas.width / 2) / newWidth));
  const cropY = Math.max(0, Math.min(1, (-imgOffsetY + canvas.height / 2) / newHeight));
  // Set to current active crop inputs
  if (document.getElementById('carouselImage').files[0]) {
    document.getElementById('cropX').value = cropX;
    document.getElementById('cropY').value = cropY;
    document.getElementById('zoomLevel').value = zoomLevel;
  } else if (document.getElementById('visionImage').files[0]) {
    document.getElementById('visionCropX').value = cropX;
    document.getElementById('visionCropY').value = cropY;
    document.getElementById('visionZoomLevel').value = zoomLevel;
  } else if (document.getElementById('newsCarouselImage').files[0]) {
    document.getElementById('newsCarouselCropX').value = cropX;
    document.getElementById('newsCarouselCropY').value = cropY;
    document.getElementById('newsCarouselZoomLevel').value = zoomLevel;
  } else if (document.getElementById('headlinesImage').files[0]) {
    document.getElementById('headlinesCropX').value = cropX;
    document.getElementById('headlinesCropY').value = cropY;
    document.getElementById('headlinesZoomLevel').value = zoomLevel;
  } else if (document.getElementById('sidebarImage').files[0]) {
    document.getElementById('sidebarCropX').value = cropX;
    document.getElementById('sidebarCropY').value = cropY;
    document.getElementById('sidebarZoomLevel').value = zoomLevel;
  }
  cropModal.hide();
};

document.getElementById('carouselImage').addEventListener('change', function() {
  if (this.files[0]) {
    showCropModal();
  }
});

/* ---- Vision Edit ---- */
let selectedVisionId = null;

function selectVision(id, element) {
  // Remove selected class from all
  document.querySelectorAll('#vision .preview-card').forEach(card => card.classList.remove('selected'));
  // If clicking the same, deselect
  if (selectedVisionId === id) {
    selectedVisionId = null;
    clearVisionForm();
  } else {
    // Select this one
    element.classList.add('selected');
    selectedVisionId = id;
    // Extract title and link
    const title = element.querySelector('.preview-caption strong').textContent;
    const link = element.querySelector('.preview-caption a').textContent;
    editVision(id, title, link);
  }
}

function editVision(id, title, link) {
  document.getElementById('visionId').value = id;
  document.getElementById('visionTitle').value = title;
  document.getElementById('visionLink').value = link;
  document.getElementById('visionSubmitBtn').textContent = 'Update Vision/Mission';
  document.getElementById('visionDeleteBtn').style.display = 'inline-block';
}

function clearVisionForm() {
  document.getElementById('visionForm').reset();
  document.getElementById('visionId').value = '';
  document.getElementById('visionSubmitBtn').textContent = 'Add Vision/Mission';
  // Deselect all
  document.querySelectorAll('#vision .preview-card').forEach(card => card.classList.remove('selected'));
  selectedVisionId = null;
  document.getElementById('visionDeleteBtn').style.display = 'none';
}

function deleteVision() {
  if (confirm('Are you sure you want to delete this vision/mission?')) {
    document.getElementById('visionDeleteId').value = selectedVisionId;
    document.getElementById('visionDeleteForm').submit();
  }
}

document.getElementById('visionImage').addEventListener('change', function() {
  if (this.files[0]) {
    showCropModalVision();
  }
});

/* ---- News Carousel Edit ---- */
let selectedNewsCarouselId = null;

function selectNewsCarousel(id, element) {
  // Remove selected class from all
  document.querySelectorAll('#newsCarousel .preview-card').forEach(card => card.classList.remove('selected'));
  // If clicking the same, deselect
  if (selectedNewsCarouselId === id) {
    selectedNewsCarouselId = null;
    clearNewsCarouselForm();
  } else {
    // Select this one
    element.classList.add('selected');
    selectedNewsCarouselId = id;
    editNewsCarousel(id);
  }
}

function editNewsCarousel(id) {
  document.getElementById('newsCarouselId').value = id;
  document.getElementById('newsCarouselSubmitBtn').textContent = 'Update News Carousel';
  document.getElementById('newsCarouselDeleteBtn').style.display = 'inline-block';
}

function clearNewsCarouselForm() {
  document.getElementById('newsCarouselForm').reset();
  document.getElementById('newsCarouselId').value = '';
  document.getElementById('newsCarouselSubmitBtn').textContent = 'Add News Carousel';
  // Deselect all
  document.querySelectorAll('#newsCarousel .preview-card').forEach(card => card.classList.remove('selected'));
  selectedNewsCarouselId = null;
  document.getElementById('newsCarouselDeleteBtn').style.display = 'none';
}

function deleteNewsCarousel() {
  if (confirm('Are you sure you want to delete this news carousel?')) {
    document.getElementById('newsCarouselDeleteId').value = selectedNewsCarouselId;
    document.getElementById('newsCarouselDeleteForm').submit();
  }
}

document.getElementById('newsCarouselImage').addEventListener('change', function() {
  if (this.files[0]) {
    showCropModalNewsCarousel();
  }
});

/* ---- Headlines Edit ---- */
let selectedHeadlinesId = null;

function selectHeadline(id, element) {
  // Remove selected class from all
  document.querySelectorAll('.headline-item').forEach(item => item.classList.remove('selected'));
  // If clicking the same, deselect
  if (selectedHeadlinesId === id) {
    selectedHeadlinesId = null;
    clearHeadlinesForm();
  } else {
    // Select this one
    element.classList.add('selected');
    selectedHeadlinesId = id;
    // Fetch data via AJAX or assume we have it, but since it's PHP rendered, we need to fetch
    // For simplicity, since modal has data, but to avoid, let's assume we fetch or use modal
    // Actually, since the modal is there, but to populate form, we need to fetch the data.
    // For now, I'll add a simple fetch
    fetchHeadlineData(id);
  }
}

function fetchHeadlineData(id) {
  // Simple fetch, assuming we have a script to get data
  fetch(`fetch_headline.php?id=${id}`)
    .then(response => response.json())
    .then(data => {
      document.getElementById('headlinesId').value = data.id;
      document.getElementById('headlinesTitle').value = data.title;
      document.getElementById('headlinesDescription').value = data.description;
      document.getElementById('headlinesSubmitBtn').textContent = 'Update Headline';
      document.getElementById('headlinesDeleteBtn').style.display = 'inline-block';
    });
}

function clearHeadlinesForm() {
  document.getElementById('headlinesForm').reset();
  document.getElementById('headlinesId').value = '';
  document.getElementById('headlinesSubmitBtn').textContent = 'Add Headline';
  // Deselect all
  document.querySelectorAll('.headline-item').forEach(item => item.classList.remove('selected'));
  selectedHeadlinesId = null;
  document.getElementById('headlinesDeleteBtn').style.display = 'none';
}

function deleteHeadline() {
  if (confirm('Are you sure you want to delete this headline?')) {
    document.getElementById('headlinesDeleteId').value = selectedHeadlinesId;
    document.getElementById('headlinesDeleteForm').submit();
  }
}

document.getElementById('headlinesImage').addEventListener('change', function() {
  if (this.files[0]) {
    showCropModalHeadlines();
  }
});

/* ---- Crop Modal for Vision ---- */
function showCropModalVision() {
  cropModal.show();
  zoomLevel = 1.0; // Reset zoom
  cropW = 560; // Set for vision card dimensions
  cropH = 250;
  const fileInput = document.getElementById('visionImage');
  if (fileInput.files[0]) {
    const file = fileInput.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = new Image();
      img.onload = function() {
        currentImg = img;
        scale = Math.max(cropW / img.width, cropH / img.height);
        updateImageSize();
        imgOffsetX = (cropW - newWidth) / 2;
        imgOffsetY = (cropH - newHeight) / 2;

        const canvas = document.getElementById('cropCanvas');
        canvas.width = cropW;
        canvas.height = cropH;

        draw(); // Draw immediately with current scales
        // Delay for modal resize
        setTimeout(() => {
          const canvas = document.getElementById('cropCanvas');
          if (canvas) {
            const rect = canvas.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
              canvasScaleX = canvas.width / rect.width;
              canvasScaleY = canvas.height / rect.height;
              draw();
            }
          }
        }, 300);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
}

/* ---- Crop Modal for News Carousel ---- */
function showCropModalNewsCarousel() {
  cropModal.show();
  zoomLevel = 1.0; // Reset zoom
  cropW = 800; // Set for news carousel
  cropH = 450;
  const fileInput = document.getElementById('newsCarouselImage');
  if (fileInput.files[0]) {
    const file = fileInput.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = new Image();
      img.onload = function() {
        currentImg = img;
        scale = Math.max(cropW / img.width, cropH / img.height);
        updateImageSize();
        imgOffsetX = (cropW - newWidth) / 2;
        imgOffsetY = (cropH - newHeight) / 2;

        const canvas = document.getElementById('cropCanvas');
        canvas.width = cropW;
        canvas.height = cropH;

        draw(); // Draw immediately with current scales
        // Delay for modal resize
        setTimeout(() => {
          const canvas = document.getElementById('cropCanvas');
          if (canvas) {
            const rect = canvas.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
              canvasScaleX = canvas.width / rect.width;
              canvasScaleY = canvas.height / rect.height;
              draw();
            }
          }
        }, 300);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
}

/* ---- Crop Modal for Headlines ---- */
function showCropModalHeadlines() {
  cropModal.show();
  zoomLevel = 1.0; // Reset zoom
  cropW = 200; // Set for headlines
  cropH = 150;
  const fileInput = document.getElementById('headlinesImage');
  if (fileInput.files[0]) {
    const file = fileInput.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = new Image();
      img.onload = function() {
        currentImg = img;
        scale = Math.max(cropW / img.width, cropH / img.height);
        updateImageSize();
        imgOffsetX = (cropW - newWidth) / 2;
        imgOffsetY = (cropH - newHeight) / 2;

        const canvas = document.getElementById('cropCanvas');
        canvas.width = cropW;
        canvas.height = cropH;

        draw(); // Draw immediately with current scales
        // Delay for modal resize
        setTimeout(() => {
          const canvas = document.getElementById('cropCanvas');
          if (canvas) {
            const rect = canvas.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
              canvasScaleX = canvas.width / rect.width;
              canvasScaleY = canvas.height / rect.height;
              draw();
            }
          }
        }, 300);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
}

/* ---- Sidebar Edit ---- */
function selectSidebar(sectionType, elem) {
  // Remove selected class from all
  document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('selected'));
  // Select this one
  elem.classList.add('selected');
  // Populate form
  document.getElementById('sidebarSectionType').value = sectionType;
  document.getElementById('sidebarSectionTypeSelect').value = sectionType;
  document.getElementById('sidebarTitle').value = elem.dataset.title;
  document.getElementById('sidebarContent').value = elem.dataset.content;
  document.getElementById('sidebarSubmitBtn').textContent = 'Update Section';
  document.getElementById('sidebarDeleteBtn').style.display = 'inline-block';
}

function clearSidebarForm() {
  document.getElementById('sidebarForm').reset();
  document.getElementById('sidebarSectionType').value = '';
  document.getElementById('sidebarSubmitBtn').textContent = 'Update Section';
  // Deselect all
  document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('selected'));
  document.getElementById('sidebarDeleteBtn').style.display = 'none';
}

function deleteSidebar() {
  if (confirm('Are you sure you want to reset this section?')) {
    document.getElementById('sidebarDeleteType').value = document.getElementById('sidebarSectionType').value;
    document.getElementById('sidebarDeleteForm').submit();
  }
}

function updateSidebarType() {
  const select = document.getElementById('sidebarSectionTypeSelect');
  document.getElementById('sidebarSectionType').value = select.value;
  // Clear form if changed
  if (select.value !== document.getElementById('sidebarSectionType').value) {
    clearSidebarForm();
    document.getElementById('sidebarSectionType').value = select.value;
  }
}

document.getElementById('sidebarImage').addEventListener('change', function() {
  if (this.files[0]) {
    showCropModalSidebar();
  }
});

/* ---- Crop Modal for Sidebar ---- */
function showCropModalSidebar() {
  cropModal.show();
  zoomLevel = 1.0; // Reset zoom
  cropW = 300; // Set for sidebar
  cropH = 200;
  const fileInput = document.getElementById('sidebarImage');
  if (fileInput.files[0]) {
    const file = fileInput.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = new Image();
      img.onload = function() {
        currentImg = img;
        scale = Math.max(cropW / img.width, cropH / img.height);
        updateImageSize();
        imgOffsetX = (cropW - newWidth) / 2;
        imgOffsetY = (cropH - newHeight) / 2;

        const canvas = document.getElementById('cropCanvas');
        canvas.width = cropW;
        canvas.height = cropH;

        draw(); // Draw immediately with current scales
        // Delay for modal resize
        setTimeout(() => {
          const canvas = document.getElementById('cropCanvas');
          if (canvas) {
            const rect = canvas.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) {
              canvasScaleX = canvas.width / rect.width;
              canvasScaleY = canvas.height / rect.height;
              draw();
            }
          }
        }, 300);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
}

/* ---- Tab Activation on Load ---- */
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const activeTab = urlParams.get('active_tab');
  if (activeTab) {
    // Remove active class from all tab buttons
    document.querySelectorAll('.nav-link').forEach(tab => tab.classList.remove('active'));
    // Remove show and active from all tab panes
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));
    // Activate the target tab
    const tabElement = document.getElementById(activeTab + '-tab');
    if (tabElement) {
      tabElement.classList.add('active');
      const targetPane = document.querySelector(tabElement.getAttribute('data-bs-target'));
      if (targetPane) {
        targetPane.classList.add('show', 'active');
      }
    }
  }
});