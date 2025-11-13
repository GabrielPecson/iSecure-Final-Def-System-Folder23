<?php
require 'auth_check.php';
require '../database/db_connect.php';

// Default fallbacks
$fullName = 'Unknown User';
$role = 'Unknown Role';

// Check session token
if (!isset($_SESSION['token'])) {
    header("Location: loginpage.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM personnel_sessions WHERE token = :token AND expires_at > NOW()");
$stmt->execute([':token' => $_SESSION['token']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    session_unset();
    session_destroy();
    header("Location: loginpage.php");
    exit;
}

if (!empty($session['user_id'])) {
    $stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $session['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $fullName = htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');
        $role = htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8');
    } else {
        session_unset();
        session_destroy();
        header("Location: loginpage.php");
        exit;
    }
} else {
    session_unset();
    session_destroy();
    header("Location: loginpage.php");
    exit;
}

/*
 * --- NEW: Fetch data for each section so the previews work ---
 * Use the real table names from your schema:
 *  - landing_carousel
 *  - landing_about_us
 *  - landing_vision_mission
 *  - news_carousel
 *  - news_headlines
 */
$carouselItems = $pdo->query("SELECT * FROM landing_carousel ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$about = $pdo->query("SELECT * FROM landing_about_us WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$visionItems = $pdo->query("SELECT * FROM landing_vision_mission ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$newsCarousel = $pdo->query("SELECT * FROM news_carousel ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$headlines = $pdo->query("SELECT * FROM news_headlines ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$sidebarItems = $pdo->query("SELECT * FROM landing_sidebar_sections ORDER BY FIELD(section_type, 'basa_announcements', 'west_philippine_sea', 'government_links')")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../../images/logo/5thFighterWing-logo.png">
  <link rel="stylesheet" href="../../stylesheet/customizelanding.css">
  <link rel="stylesheet" href="../../stylesheet/sidebar.css">
  <title>Main Dashboard</title>
  <style>
    /* small inline preview styles (keeps original CSS file unchanged) */
    .current-images { display:flex; flex-wrap:wrap; gap:15px; margin-bottom:10px; }
    .preview-card { background:#fff; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.08); padding:10px; width:220px; text-align:center; }
    .preview-card.selected { border: 3px solid #007bff; box-shadow: 0 4px 12px rgba(0,123,255,0.3); }
    .preview-img { width:100%; height:120px; object-fit:cover; border-radius:8px; margin-bottom:8px; }
    .preview-caption { font-size:14px; color:#333; word-break:break-word; }
  </style>
</head>
<body>

<div class="body">

<div class="left-panel">
  <div id="sidebar-container"></div>
</div>

<div class="right-panel">
<div class="main-content">
    
      <div class="main-content">
  <div class="main-header">
    <div class="header-left">
      <i class="fa-solid fa-home"></i> 
      <h6 class="path"> / Dashboard /</h6>
      <h6 class="current-loc">Customize Landing Page</h6>
    </div>
    <div class="header-right">
      <i class="fa-regular fa-bell me-3"></i>
      <i class="fa-regular fa-message me-3"></i>
      <div class="user-info">
        <i class="fa-solid fa-user-circle fa-lg me-2"></i>
        <div class="user-text">
          <span class="username"><?php echo $fullName; ?></span>
          <a id="logout-link" class="logout-link" href="logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>

      <!-- Confirm Modal -->
      <div id="confirmModal" class="modal">
        <div class="modal-content">
          <p id="confirmMessage"></p>
          <div class="modal-actions">
            <button id="confirmYes" class="btn btn-danger">Yes</button>
            <button id="confirmNo" class="btn btn-secondary">No</button>
          </div>
        </div>
      </div>

      <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
          <?= htmlspecialchars($_GET['msg']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php endif; ?>

      <div class="container-fluid mt-4">
        <h3>Customize Landing Page</h3>

        <!-- Tabs -->
        <ul class="nav nav-tabs mt-4" id="adminTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="carousel-tab" data-bs-toggle="tab" data-bs-target="#carousel" type="button" role="tab" aria-controls="carousel" aria-selected="true">Carousel</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="about-tab" data-bs-toggle="tab" data-bs-target="#about" type="button" role="tab" aria-controls="about" aria-selected="false">About Us</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="vision-tab" data-bs-toggle="tab" data-bs-target="#vision" type="button" role="tab" aria-controls="vision" aria-selected="false">Vision/Mission</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="newsCarousel-tab" data-bs-toggle="tab" data-bs-target="#newsCarousel" type="button" role="tab" aria-controls="newsCarousel" aria-selected="false">News Carousel</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="headlines-tab" data-bs-toggle="tab" data-bs-target="#headlines" type="button" role="tab" aria-controls="headlines" aria-selected="false">News Headlines</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="sidebar-tab" data-bs-toggle="tab" data-bs-target="#sidebar" type="button" role="tab" aria-controls="sidebar" aria-selected="false">Sidebar Sections</button>
          </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content mt-3" id="adminTabsContent">

          <!-- Carousel -->
          <div class="tab-pane fade show active" id="carousel" role="tabpanel" aria-labelledby="carousel-tab">
            <div class="card p-4 shadow-sm">
              <h5 class="card-title mb-3">Current Carousel Images</h5>
              <p>Please Choose an Image to update</p>
              <div class="current-images">
                <?php if (!empty($carouselItems)): ?>
                  <?php foreach ($carouselItems as $row): ?>
                    <div class="preview-card" onclick="selectCarousel(<?= $row['id'] ?>, this)">
                      <img src="<?= htmlspecialchars($row['image_path'] ?? '') ?>" class="preview-img" alt="carousel" style="cursor:pointer;">
                      <p class="preview-caption">
                        <strong><?= htmlspecialchars($row['caption_title'] ?? '') ?></strong><br>
                        <?= htmlspecialchars($row['caption_text'] ?? '') ?>
                      </p>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p>No carousel images yet.</p>
                <?php endif; ?>
              </div>

              <h5 class="card-title mt-4">Add/Update Carousel</h5>
              <form action="update_carousel.php" method="POST" enctype="multipart/form-data" id="carouselForm">
                <input type="hidden" name="active_tab" value="carousel">
                <input type="hidden" name="id" id="carouselId">
                <input type="hidden" name="crop_x" id="cropX" value="0.5">
                <input type="hidden" name="crop_y" id="cropY" value="0.5">
                <input type="hidden" name="zoom_level" id="zoomLevel" value="1.0">
                <div class="mb-3"><label>Image</label><input type="file" name="image" class="form-control" id="carouselImage"></div>
                <div class="mb-3"><label>Caption Title</label><input type="text" name="caption_title" class="form-control" id="carouselTitle"></div>
                <div class="mb-3"><label>Caption Text</label><textarea name="caption_text" class="form-control" id="carouselText"></textarea></div>
                <button type="submit" class="btn btn-primary" id="submitBtn">Add Carousel</button>
                <button type="button" class="btn btn-secondary" onclick="clearForm()">Clear</button>
                <button type="button" class="btn btn-danger" id="deleteBtn" onclick="deleteCarousel()" style="display: none;">Delete Carousel</button>
              </form>

              <form action="delete_carousel.php" method="POST" id="deleteForm" style="display:none;">
                <input type="hidden" name="active_tab" value="carousel">
                <input type="hidden" name="id" id="deleteId">
              </form>
            </div>
          </div>

          <!-- About Us -->
          <div class="tab-pane fade" id="about" role="tabpanel" aria-labelledby="about-tab">
            <div class="card p-4 shadow-sm">
              <h5 class="card-title mb-3">Current About Us</h5>
              <p><strong><?= htmlspecialchars($about['title'] ?? '') ?></strong></p>
              <p><?= nl2br(htmlspecialchars($about['content'] ?? '')) ?></p>

              <h5 class="card-title mt-4">Update About Us</h5>
              <form action="update_about.php" method="POST">
                <input type="hidden" name="active_tab" value="about">
                <div class="mb-3"><label>Title</label><input type="text" name="title" value="<?= htmlspecialchars($about['title'] ?? '') ?>" class="form-control"></div>
                <div class="mb-3"><label>Content</label><textarea name="content" class="form-control"><?= htmlspecialchars($about['content'] ?? '') ?></textarea></div>
                <button type="submit" class="btn btn-primary">Update About</button>
              </form>
            </div>
          </div>

          <!-- Vision/Mission -->
          <div class="tab-pane fade" id="vision" role="tabpanel" aria-labelledby="vision-tab">
            <div class="card p-4 shadow-sm">
              <h5 class="card-title mb-3">Current Vision/Mission</h5>
              <p>Please Choose an Image to update</p>
              <div class="current-images">
                <?php if (!empty($visionItems)): ?>
                  <?php foreach ($visionItems as $row): ?>
                    <div class="preview-card" onclick="selectVision(<?= $row['id'] ?>, this)">
                      <img src="<?= htmlspecialchars($row['image_path'] ?? '') ?>" class="preview-img" alt="vision" style="cursor:pointer;">
                      <p class="preview-caption">
                        <strong><?= htmlspecialchars($row['title'] ?? '') ?></strong><br>
                        <a href="<?= htmlspecialchars($row['link_url'] ?? '#') ?>" target="_blank"><?= htmlspecialchars($row['link_url'] ?? '') ?></a>
                      </p>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p>No vision/mission content yet.</p>
                <?php endif; ?>
              </div>

              <h5 class="card-title mt-4">Add/Update Vision/Mission</h5>
              <form action="update_vision.php" method="POST" enctype="multipart/form-data" id="visionForm">
                <input type="hidden" name="active_tab" value="vision">
                <input type="hidden" name="id" id="visionId">
                <input type="hidden" name="crop_x" id="visionCropX" value="0.5">
                <input type="hidden" name="crop_y" id="visionCropY" value="0.5">
                <input type="hidden" name="zoom_level" id="visionZoomLevel" value="1.0">
                <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" id="visionTitle"></div>
                <div class="mb-3"><label>Image</label><input type="file" name="image" class="form-control" id="visionImage"></div>
                <div class="mb-3"><label>Link URL</label><input type="text" name="link_url" class="form-control" id="visionLink"></div>
                <button type="submit" class="btn btn-primary" id="visionSubmitBtn">Add Vision/Mission</button>
                <button type="button" class="btn btn-secondary" onclick="clearVisionForm()">Clear</button>
                <button type="button" class="btn btn-danger" id="visionDeleteBtn" onclick="deleteVision()" style="display: none;">Delete Vision/Mission</button>
              </form>

              <form action="delete_vision.php" method="POST" id="visionDeleteForm" style="display:none;">
                <input type="hidden" name="active_tab" value="vision">
                <input type="hidden" name="id" id="visionDeleteId">
              </form>
            </div>
          </div>

          <!-- News Carousel -->
          <div class="tab-pane fade" id="newsCarousel" role="tabpanel" aria-labelledby="newsCarousel-tab">
            <div class="card p-4 shadow-sm">
              <h5 class="card-title mb-3">Current News Carousel</h5>
              <p>Please Choose an Image to update</p>
              <div class="current-images">
                <?php if (!empty($newsCarousel)): ?>
                  <?php foreach ($newsCarousel as $row): ?>
                    <div class="preview-card" onclick="selectNewsCarousel(<?= $row['id'] ?>, this)">
                      <img src="<?= htmlspecialchars($row['image_path'] ?? '') ?>" class="preview-img" alt="news carousel" style="cursor:pointer;">
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p>No news carousel images yet.</p>
                <?php endif; ?>
              </div>

              <h5 class="card-title mt-4">Add/Update News Carousel</h5>
              <form action="update_news_carousel.php" method="POST" enctype="multipart/form-data" id="newsCarouselForm">
                <input type="hidden" name="active_tab" value="newsCarousel">
                <input type="hidden" name="id" id="newsCarouselId">
                <input type="hidden" name="crop_x" id="newsCarouselCropX" value="0.5">
                <input type="hidden" name="crop_y" id="newsCarouselCropY" value="0.5">
                <input type="hidden" name="zoom_level" id="newsCarouselZoomLevel" value="1.0">
                <div class="mb-3"><label>Image</label><input type="file" name="image" class="form-control" id="newsCarouselImage"></div>
                <button type="submit" class="btn btn-primary" id="newsCarouselSubmitBtn">Add News Carousel</button>
                <button type="button" class="btn btn-secondary" onclick="clearNewsCarouselForm()">Clear</button>
                <button type="button" class="btn btn-danger" id="newsCarouselDeleteBtn" onclick="deleteNewsCarousel()" style="display: none;">Delete News Carousel</button>
              </form>

              <form action="delete_news_carousel.php" method="POST" id="newsCarouselDeleteForm" style="display:none;">
                <input type="hidden" name="active_tab" value="newsCarousel">
                <input type="hidden" name="id" id="newsCarouselDeleteId">
              </form>
            </div>
          </div>

        <!-- News Headlines -->
<div class="tab-pane fade" id="headlines" role="tabpanel" aria-labelledby="headlines-tab">
  <div class="card p-4 shadow-sm">
    <h5 class="card-title mb-3">Current News Headlines</h5>
    <p>Please Choose a Headline to update</p>

    <div class="list-group">
      <?php if (!empty($headlines)): ?>
        <?php foreach ($headlines as $row): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center headline-item" onclick="selectHeadline(<?= $row['id'] ?>, this)" style="cursor:pointer;">
            <span><strong><?= htmlspecialchars($row['title'] ?? '') ?></strong></span>
            <button class="btn btn-sm btn-outline-primary" 
                    data-bs-toggle="modal" 
                    data-bs-target="#headlineModal<?= $row['id'] ?>">
              View
            </button>
          </div>

          <!-- Modal -->
          <div class="modal fade" id="headlineModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title"><?= htmlspecialchars($row['title'] ?? '') ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                  <?php if (!empty($row['image_path'])): ?>
                    <img src="<?= htmlspecialchars($row['image_path']) ?>" class="img-fluid mb-3 rounded" alt="headline">
                  <?php endif; ?>
                  <p><?= nl2br(htmlspecialchars($row['description'] ?? '')) ?></p>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No headlines yet.</p>
      <?php endif; ?>
    </div>

    <h5 class="card-title mt-4">Add/Update Headline</h5>
    <form action="update_news_headlines.php" method="POST" enctype="multipart/form-data" id="headlinesForm">
      <input type="hidden" name="active_tab" value="headlines">
      <input type="hidden" name="id" id="headlinesId">
      <input type="hidden" name="crop_x" id="headlinesCropX" value="0.5">
      <input type="hidden" name="crop_y" id="headlinesCropY" value="0.5">
      <input type="hidden" name="zoom_level" id="headlinesZoomLevel" value="1.0">
      <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" id="headlinesTitle"></div>
      <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" id="headlinesDescription"></textarea></div>
      <div class="mb-3"><label>Image</label><input type="file" name="image" class="form-control" id="headlinesImage"></div>
      <button type="submit" class="btn btn-primary" id="headlinesSubmitBtn">Add Headline</button>
      <button type="button" class="btn btn-secondary" onclick="clearHeadlinesForm()">Clear</button>
      <button type="button" class="btn btn-danger" id="headlinesDeleteBtn" onclick="deleteHeadline()" style="display: none;">Delete Headline</button>
    </form>

    <form action="delete_news_headlines.php" method="POST" id="headlinesDeleteForm" style="display:none;">
      <input type="hidden" name="active_tab" value="headlines">
      <input type="hidden" name="id" id="headlinesDeleteId">
    </form>
  </div>
</div>

          <!-- Sidebar Sections -->
          <div class="tab-pane fade" id="sidebar" role="tabpanel" aria-labelledby="sidebar-tab">
    <div class="card p-4 shadow-sm">
      <h5 class="card-title mb-3">Current Sidebar Sections</h5>
      <p>Please Choose a Section to update</p>

      <div class="list-group">
        <?php if (!empty($sidebarItems)): ?>
          <?php foreach ($sidebarItems as $index => $row): 
            $typeLabels = ['basa_announcements' => 'Basa Airbase Announcements', 'west_philippine_sea' => 'West Philippine Sea Happenings', 'government_links' => 'Government Links'];
            $label = $typeLabels[$row['section_type']] ?? $row['section_type'];
          ?>
            <div class="list-group-item d-flex justify-content-between align-items-center sidebar-item" onclick="selectSidebar('<?= $row['section_type'] ?>', this)" style="cursor:pointer;" data-title="<?= htmlspecialchars($row['title']) ?>" data-content="<?= htmlspecialchars($row['content']) ?>" data-image="<?= htmlspecialchars($row['image_path']) ?>">
              <span><strong><?= htmlspecialchars($label) ?></strong> - <?= htmlspecialchars($row['title']) ?></span>
              <button class="btn btn-sm btn-outline-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#sidebarModal<?= $index ?>">
                View
              </button>
            </div>

            <!-- Modal for preview -->
            <div class="modal fade" id="sidebarModal<?= $index ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title"><?= htmlspecialchars($label) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body text-center">
                    <?php if (!empty($row['image_path'])): ?>
                      <img src="<?= htmlspecialchars($row['image_path']) ?>" class="img-fluid mb-3 rounded" alt="sidebar">
                    <?php endif; ?>
                    <p><?= nl2br(htmlspecialchars($row['content'] ?? '')) ?></p>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No sidebar sections yet.</p>
        <?php endif; ?>
      </div>

      <h5 class="card-title mt-4">Add/Update Sidebar Section</h5>
      <form action="update_sidebar.php" method="POST" enctype="multipart/form-data" id="sidebarForm">
        <input type="hidden" name="active_tab" value="sidebar">
        <input type="hidden" name="section_type" id="sidebarSectionType">
        <input type="hidden" name="crop_x" id="sidebarCropX" value="0.5">
        <input type="hidden" name="crop_y" id="sidebarCropY" value="0.5">
        <input type="hidden" name="zoom_level" id="sidebarZoomLevel" value="1.0">
        <div class="mb-3"><label>Section Type</label><select name="section_type_select" class="form-control" id="sidebarSectionTypeSelect" onchange="updateSidebarType()">
          <option value="">Select Section</option>
          <option value="basa_announcements">Basa Airbase Announcements</option>
          <option value="west_philippine_sea">West Philippine Sea Happenings</option>
          <option value="government_links">Government Links</option>
        </select></div>
        <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" id="sidebarTitle"></div>
        <div class="mb-3"><label>Content</label><textarea name="content" class="form-control" id="sidebarContent"></textarea></div>
        <div class="mb-3"><label>Image</label><input type="file" name="image" class="form-control" id="sidebarImage"></div>
        <button type="submit" class="btn btn-primary" id="sidebarSubmitBtn">Update Section</button>
        <button type="button" class="btn btn-secondary" onclick="clearSidebarForm()">Clear</button>
        <button type="button" class="btn btn-danger" id="sidebarDeleteBtn" onclick="deleteSidebar()" style="display: none;">Reset Section</button>
      </form>

      <form action="delete_sidebar.php" method="POST" id="sidebarDeleteForm" style="display:none;">
        <input type="hidden" name="active_tab" value="sidebar">
        <input type="hidden" name="section_type" id="sidebarDeleteType">
      </form>
    </div>
  </div>

        </div> <!-- end tab-content -->
      </div> <!-- end container -->
    </div> <!-- end main-content -->
  </div> <!-- end right-panel -->
</div> <!-- end body -->

<!-- Crop Preview Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" style="max-width: 95vw; width: 95vw;">
    <div class="modal-content" style="height: 90vh; border-radius: 0;">
      <div class="modal-header">
        <h5 class="modal-title" id="cropModalLabel">Preview Crop Position</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex flex-column justify-content-center align-items-center p-2" style="flex: 1; overflow: hidden;">
        <canvas id="cropCanvas" width="1480" height="500" style="border:1px solid #ccc; max-width: calc(100% - 10px); height: auto;"></canvas>
        <div class="mt-3">
          <button class="btn btn-outline-secondary me-2" onclick="zoomIn()">Zoom In (+)</button>
          <button class="btn btn-outline-secondary me-2" onclick="zoomOut()">Zoom Out (-)</button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="cropOk">OK</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../scripts/sidebar.js"></script>
<script src="../../scripts/customizelanding.js"></script>
<script>
<?php if (isset($_GET['active_tab'])): ?>
    var tabId = '<?= htmlspecialchars($_GET['active_tab']) ?>-tab';
    var tabElement = document.getElementById(tabId);
    if (tabElement) {
        var tab = new bootstrap.Tab(tabElement);
        tab.show();
    }
<?php endif; ?>
</script>
</body>
</html>
