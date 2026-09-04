<?php

/**
 * =========================================================
 * SSRINI HANDCRAFTS
 * ADMIN ABOUT DETAILS
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

$pageTitle = 'About Details';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getTableColumns(PDO $pdo, string $tableName): array
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`', '``', $tableName) . "`");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }
        return $columns;
    } catch (Throwable $e) {
        return [];
    }
}

function findColumn(array $columns, array $candidates): ?string
{
    $lowerColumns = [];
    foreach ($columns as $column) {
        $lowerColumns[strtolower($column)] = $column;
    }
    foreach ($candidates as $candidate) {
        if (isset($lowerColumns[strtolower($candidate)])) {
            return $lowerColumns[strtolower($candidate)];
        }
    }
    return null;
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

$defaultAbout = [
    'title' => 'About Ssrini',
    'subtitle' => 'Creative handcrafted products from Bengal.',
    'content' => "Welcome to Ssrini, the brand of Adrish Creative. Our story began in Kolkata in 2004 with an institute dedicated to the art of handicrafts...",
    'image' => '',
    'instagram' => '',
    'twitter' => '',
    'facebook' => '',
    'updated_at' => ''
];

$aboutData = $defaultAbout;
$aboutError = null;
$successMessage = null;
$recordId = null;

if (isset($_SESSION['about_success'])) {
    $successMessage = (string) $_SESSION['about_success'];
    unset($_SESSION['about_success']);
}

try {
    $aboutColumns = getTableColumns($pdo, 'about_content');

    $idColumn        = findColumn($aboutColumns, ['id']);
    $titleColumn     = findColumn($aboutColumns, ['title', 'heading', 'about_title']);
    $subtitleColumn  = findColumn($aboutColumns, ['subtitle', 'sub_title', 'tagline']);
    $contentColumn   = findColumn($aboutColumns, ['company_description', 'content', 'description']);
    $imageColumn     = findColumn($aboutColumns, ['image', 'image_path', 'about_image']);
    $instagramColumn = findColumn($aboutColumns, ['instagram_url', 'instagram']);
    $twitterColumn   = findColumn($aboutColumns, ['twitter_url', 'twitter']);
    $facebookColumn  = findColumn($aboutColumns, ['facebook_url', 'facebook']);
    $updatedAtColumn = findColumn($aboutColumns, ['updated_at']);

    $stmt = $pdo->query("SELECT * FROM about_content ORDER BY id ASC LIMIT 1");
    $existingData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingData !== false) {
        $recordId = $existingData['id'] ?? null;
        if ($titleColumn && !empty($existingData[$titleColumn])) $aboutData['title'] = (string) $existingData[$titleColumn];
        if ($subtitleColumn && !empty($existingData[$subtitleColumn])) $aboutData['subtitle'] = (string) $existingData[$subtitleColumn];
        if ($contentColumn && !empty($existingData[$contentColumn])) $aboutData['content'] = (string) $existingData[$contentColumn];
        if ($imageColumn && !empty($existingData[$imageColumn])) $aboutData['image'] = (string) $existingData[$imageColumn];
        if ($instagramColumn && !empty($existingData[$instagramColumn])) $aboutData['instagram'] = (string) $existingData[$instagramColumn];
        if ($twitterColumn && !empty($existingData[$twitterColumn])) $aboutData['twitter'] = (string) $existingData[$twitterColumn];
        if ($facebookColumn && !empty($existingData[$facebookColumn])) $aboutData['facebook'] = (string) $existingData[$facebookColumn];
        if ($updatedAtColumn && !empty($existingData[$updatedAtColumn])) $aboutData['updated_at'] = (string) $existingData[$updatedAtColumn];
    }
} catch (Throwable $e) {
    $aboutError = 'Database load error: ' . $e->getMessage();
}

/**
 * FORM SUBMISSION
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $newTitle     = trim($_POST['title'] ?? '');
        $newSubtitle  = trim($_POST['subtitle'] ?? '');
        $newContent   = trim($_POST['content'] ?? '');
        $newInstagram = trim($_POST['instagram'] ?? '');
        $newTwitter   = trim($_POST['twitter'] ?? '');
        $newFacebook  = trim($_POST['facebook'] ?? '');

        // Retain current image unless a new one is uploaded
        $uploadedImagePath = $aboutData['image'];

        if (isset($_FILES['about_image']) && $_FILES['about_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['about_image'];
            $uploadDirectory = __DIR__ . '/../uploads/about/';
            
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($ext, $allowedExtensions)) {
                $fileName = 'about-' . date('YmdHis') . '.' . $ext;
                $targetFile = $uploadDirectory . $fileName;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $uploadedImagePath = 'uploads/about/' . $fileName;
                }
            }
        }

        $updateData = [];
        if ($titleColumn) $updateData[$titleColumn] = $newTitle;
        if ($subtitleColumn) $updateData[$subtitleColumn] = $newSubtitle;
        if ($contentColumn) $updateData[$contentColumn] = $newContent;
        if ($imageColumn) $updateData[$imageColumn] = $uploadedImagePath;
        if ($instagramColumn) $updateData[$instagramColumn] = $newInstagram;
        if ($twitterColumn) $updateData[$twitterColumn] = $newTwitter;
        if ($facebookColumn) $updateData[$facebookColumn] = $newFacebook;
        if ($updatedAtColumn) $updateData[$updatedAtColumn] = date('Y-m-d H:i:s');

        if ($recordId !== null) {
            $setParts = [];
            $params   = [];
            foreach ($updateData as $col => $val) {
                $param = ':val_' . $col;
                $setParts[] = quoteIdentifier($col) . ' = ' . $param;
                $params[$param] = $val;
            }
            $params[':id'] = $recordId;
            $sql = "UPDATE about_content SET " . implode(', ', $setParts) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $cols = array_keys($updateData);
            $placeholders = array_map(fn($c) => ':val_' . $c, $cols);
            $params = [];
            foreach ($updateData as $col => $val) {
                $params[':val_' . $col] = $val;
            }
            $sql = "INSERT INTO about_content (" . implode(', ', array_map('quoteIdentifier', $cols)) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        $_SESSION['about_success'] = 'Changes saved successfully!';
        header('Location: about-details.php');
        exit;

    } catch (Throwable $e) {
        $aboutError = $e->getMessage();
    }
}

// Format Saved Image URL
$displayImageUrl = '';
if (!empty($aboutData['image'])) {
    $displayImageUrl = (strpos($aboutData['image'], 'http') === 0) ? $aboutData['image'] : '../' . ltrim($aboutData['image'], '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Ssrini Handcrafts</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .about-main-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-top: 20px; }
        .about-card { background: #fff; border: 1px solid #eeeaf2; border-radius: 12px; padding: 20px; }
        .about-form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 15px; }
        .about-input, .about-textarea { width: 100%; border: 1px solid #e3dfea; border-radius: 8px; padding: 10px; font-size: 13px; box-sizing: border-box; }
        .about-textarea { min-height: 180px; resize: vertical; }
        .about-save-button { background: #7627c9; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .about-alert { padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; }
        .about-alert-success { background: #edf9f1; color: #267346; border: 1px solid #ccebd6; }
        .about-alert-error { background: #fff2f2; color: #c53030; border: 1px solid #ffd3d3; }
        .preview-box { background: #faf9fc; border: 1px solid #eeeaf2; border-radius: 10px; padding: 15px; }
        .preview-img-container { width: 100%; max-height: 220px; overflow: hidden; border-radius: 8px; margin-bottom: 15px; border: 1px solid #e3dfea; background: #eee; }
        .preview-img-container img { width: 100%; height: 220px; object-fit: cover; display: block; }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-area">
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <div class="page-content" style="padding:20px;">
            <h1 style="font-size:20px; margin:0;">About Details</h1>

            <?php if ($successMessage): ?>
                <div class="about-alert about-alert-success">✓ <?= e($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($aboutError): ?>
                <div class="about-alert about-alert-error">⚠ <?= e($aboutError) ?></div>
            <?php endif; ?>

            <div class="about-main-grid">
                <!-- FORM -->
                <section class="about-card">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="about-form-group">
                            <label style="font-weight:bold; font-size:12px;">About Title</label>
                            <input type="text" id="inputTitle" name="title" class="about-input" value="<?= e($aboutData['title']) ?>">
                        </div>

                        <div class="about-form-group">
                            <label style="font-weight:bold; font-size:12px;">Subtitle / Tagline</label>
                            <input type="text" id="inputSubtitle" name="subtitle" class="about-input" value="<?= e($aboutData['subtitle']) ?>">
                        </div>

                        <div class="about-form-group">
                            <label style="font-weight:bold; font-size:12px;">Brand Story / Description *</label>
                            <textarea id="inputContent" name="content" class="about-textarea" required><?= e($aboutData['content']) ?></textarea>
                        </div>

                        <div class="about-form-group">
                            <label style="font-weight:bold; font-size:12px;">About Image</label>
                            <input type="file" id="inputImage" name="about_image" class="about-input" accept="image/*">
                            <?php if (!empty($displayImageUrl)): ?>
                                <div style="margin-top:8px; display:flex; align-items:center; gap:8px;">
                                    <span style="font-size:11px; color:#666;">Current Saved Image:</span>
                                    <img src="<?= e($displayImageUrl) ?>" style="height:35px; width:35px; object-fit:cover; border-radius:4px; border:1px solid #ccc;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <h4 style="margin:20px 0 10px; font-size:14px;">Social Links</h4>

                        <div class="about-form-group">
                            <label style="font-size:11px;">Instagram URL</label>
                            <input type="url" name="instagram" class="about-input" value="<?= e($aboutData['instagram']) ?>">
                        </div>

                        <div class="about-form-group">
                            <label style="font-size:11px;">Twitter (X) URL</label>
                            <input type="url" name="twitter" class="about-input" value="<?= e($aboutData['twitter']) ?>">
                        </div>

                        <div class="about-form-group">
                            <label style="font-size:11px;">Facebook URL</label>
                            <input type="url" name="facebook" class="about-input" value="<?= e($aboutData['facebook']) ?>">
                        </div>

                        <button type="submit" class="about-save-button">💾 Save Changes</button>
                    </form>
                </section>

                <!-- LIVE PREVIEW -->
                <aside class="about-card">
                    <h3 style="font-size:15px; margin-top:0;">Live Preview</h3>
                    <div class="preview-box">
                        <div id="imagePreviewContainer" class="preview-img-container" style="<?= empty($displayImageUrl) ? 'display:none;' : '' ?>">
                            <img id="previewImage" src="<?= e($displayImageUrl) ?>" alt="About Preview Image">
                        </div>

                        <h2 id="previewTitle" style="font-size:18px; margin:0 0 5px; color:#25212d;"><?= e($aboutData['title']) ?></h2>
                        <p id="previewSubtitle" style="color:#7627c9; font-size:12px; margin:0 0 12px; font-weight:600;"><?= e($aboutData['subtitle']) ?></p>
                        <p id="previewContent" style="font-size:12px; color:#555; white-space:pre-line; line-height:1.6;"><?= e($aboutData['content']) ?></p>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</div>

<!-- REALTIME PREVIEW JS -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputTitle = document.getElementById('inputTitle');
        const inputSubtitle = document.getElementById('inputSubtitle');
        const inputContent = document.getElementById('inputContent');
        const inputImage = document.getElementById('inputImage');

        const previewTitle = document.getElementById('previewTitle');
        const previewSubtitle = document.getElementById('previewSubtitle');
        const previewContent = document.getElementById('previewContent');
        const previewImage = document.getElementById('previewImage');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');

        if (inputTitle && previewTitle) {
            inputTitle.addEventListener('input', function() {
                previewTitle.textContent = this.value || 'About Ssrini';
            });
        }

        if (inputSubtitle && previewSubtitle) {
            inputSubtitle.addEventListener('input', function() {
                previewSubtitle.textContent = this.value || 'Creative handcrafted products from Bengal.';
            });
        }

        if (inputContent && previewContent) {
            inputContent.addEventListener('input', function() {
                previewContent.textContent = this.value || 'Your story description...';
            });
        }

        if (inputImage && previewImage && imagePreviewContainer) {
            inputImage.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        previewImage.src = event.target.result;
                        imagePreviewContainer.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>

</body>
</html>