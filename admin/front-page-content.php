<?php

/**
 * =========================================================
 * SSRINI HANDCRAFTS
 * ADMIN FRONT PAGE CONTENT
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

$pageTitle = 'Front Page Content';

// HELPER FUNCTIONS
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirectToPage(): void
{
    header('Location: front-page-content.php');
    exit;
}

function tableExists(PDO $pdo, string $tableName): bool
{
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = :table_name
        ");
        $stmt->execute([':table_name' => $tableName]);
        return (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

// DEFAULT VALUES
$frontPageContent = [
    'id' => '',
    'hero_image' => '',
    'catchy_headline' => '',
    'store_headline' => '',
    'introduction_headline' => '',
    'introduction_description' => '',
    'updated_at' => ''
];

$successMessage = '';
$errorMessage = '';
$tableExists = false;
$formSubmitted = false;

// INITIAL FORM VALUES
$catchyHeadline = '';
$storeHeadline = '';
$introductionHeadline = '';
$introductionDescription = '';
$existingHeroImage = '';

// CSRF TOKEN INITIALIZATION
if (empty($_SESSION['front_page_content_token'])) {
    try {
        $_SESSION['front_page_content_token'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        $_SESSION['front_page_content_token'] = hash('sha256', uniqid((string) mt_rand(), true));
    }
}
$csrfToken = (string) $_SESSION['front_page_content_token'];

// DATABASE LOAD
try {
    $tableExists = tableExists($pdo, 'front_page_content');

    if (!$tableExists) {
        $errorMessage = 'The front_page_content table was not found in the current database.';
    } else {
        $stmt = $pdo->query("
            SELECT id, hero_image, catchy_headline, store_headline, introduction_headline, introduction_description, updated_at
            FROM front_page_content
            ORDER BY id ASC
            LIMIT 1
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row !== false) {
            $frontPageContent = array_merge($frontPageContent, $row);
            $catchyHeadline = (string) ($row['catchy_headline'] ?? '');
            $storeHeadline = (string) ($row['store_headline'] ?? '');
            $introductionHeadline = (string) ($row['introduction_headline'] ?? '');
            $introductionDescription = (string) ($row['introduction_description'] ?? '');
            $existingHeroImage = (string) ($row['hero_image'] ?? '');
        }
    }
} catch (Throwable $e) {
    $errorMessage = 'Front page content could not be loaded. Please check your database configuration.';
}

// FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tableExists) {
    $formSubmitted = true;

    $catchyHeadline = trim((string) ($_POST['catchy_headline'] ?? ''));
    $storeHeadline = trim((string) ($_POST['store_headline'] ?? ''));
    $introductionHeadline = trim((string) ($_POST['introduction_headline'] ?? ''));
    $introductionDescription = trim((string) ($_POST['introduction_description'] ?? ''));
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    // CSRF TOKEN CHECK
    if (!hash_equals($csrfToken, $submittedToken)) {
        $errorMessage = 'Invalid form request. Please refresh the page and try again.';
    }

    // VALIDATION
    if ($errorMessage === '') {
        if ($catchyHeadline === '') {
            $errorMessage = 'Please enter the catchy headline.';
        } elseif (mb_strlen($catchyHeadline) > 255) {
            $errorMessage = 'Catchy headline must not exceed 255 characters.';
        } elseif ($storeHeadline === '') {
            $errorMessage = 'Please enter the store headline.';
        } elseif (mb_strlen($storeHeadline) > 255) {
            $errorMessage = 'Store headline must not exceed 255 characters.';
        } elseif ($introductionHeadline === '') {
            $errorMessage = 'Please enter the introduction headline.';
        } elseif (mb_strlen($introductionHeadline) > 255) {
            $errorMessage = 'Introduction headline must not exceed 255 characters.';
        } elseif ($introductionDescription === '') {
            $errorMessage = 'Please enter the introduction description.';
        }
    }

    // IMAGE PROCESSING
    $newHeroImage = $existingHeroImage;

    if ($errorMessage === '' && isset($_FILES['hero_image']) && is_array($_FILES['hero_image']) && (($_FILES['hero_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
        $imageFile = $_FILES['hero_image'];

        if ($imageFile['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = 'Hero image upload failed. Please try again.';
        } elseif (($imageFile['size'] ?? 0) > 5 * 1024 * 1024) {
            $errorMessage = 'Hero image must be smaller than 5 MB.';
        } else {
            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo ? finfo_file($fileInfo, $imageFile['tmp_name']) : false;
            if ($fileInfo) finfo_close($fileInfo);

            if ($mimeType === false || !isset($allowedMimeTypes[$mimeType])) {
                $errorMessage = 'Invalid hero image format. Please upload JPG, PNG or WEBP.';
            } elseif (!@getimagesize($imageFile['tmp_name'])) {
                $errorMessage = 'The uploaded hero image is not a valid image.';
            } else {
                $uploadDirectory = __DIR__ . '/../assets/uploads/';

                if (!is_dir($uploadDirectory)) {
                    if (!mkdir($uploadDirectory, 0755, true)) {
                        $errorMessage = 'Upload directory could not be created.';
                    }
                }

                if ($errorMessage === '') {
                    $extension = $allowedMimeTypes[$mimeType];
                    try {
                        $randomName = bin2hex(random_bytes(16));
                    } catch (Throwable $e) {
                        $randomName = uniqid('hero_', true);
                    }

                    $fileName = 'hero_' . $randomName . '.' . $extension;
                    $destination = $uploadDirectory . $fileName;

                    if (move_uploaded_file($imageFile['tmp_name'], $destination)) {
                        $newHeroImage = $fileName;
                    } else {
                        $errorMessage = 'Hero image could not be saved.';
                    }
                }
            }
        }
    }

    // SAVE DATABASE CONTENT
    if ($errorMessage === '') {
        try {
            $existingStmt = $pdo->query("
                SELECT id, hero_image
                FROM front_page_content
                ORDER BY id ASC
                LIMIT 1
            ");

            $existingRecord = $existingStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRecord !== false) {
                $recordId = (int) ($existingRecord['id'] ?? 0);

                $updateStmt = $pdo->prepare("
                    UPDATE front_page_content
                    SET
                        hero_image = :hero_image,
                        catchy_headline = :catchy_headline,
                        store_headline = :store_headline,
                        introduction_headline = :introduction_headline,
                        introduction_description = :introduction_description,
                        updated_at = NOW()
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    ':hero_image' => $newHeroImage,
                    ':catchy_headline' => $catchyHeadline,
                    ':store_headline' => $storeHeadline,
                    ':introduction_headline' => $introductionHeadline,
                    ':introduction_description' => $introductionDescription,
                    ':id' => $recordId
                ]);

                // DELETE OLD IMAGE
                $oldImage = trim((string) ($existingRecord['hero_image'] ?? ''));
                if ($newHeroImage !== $oldImage && $oldImage !== '') {
                    $oldImagePath = __DIR__ . '/../assets/uploads/' . basename($oldImage);
                    if (is_file($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }
            } else {
                $insertStmt = $pdo->prepare("
                    INSERT INTO front_page_content
                    (hero_image, catchy_headline, store_headline, introduction_headline, introduction_description, updated_at)
                    VALUES
                    (:hero_image, :catchy_headline, :store_headline, :introduction_headline, :introduction_description, NOW())
                ");

                $insertStmt->execute([
                    ':hero_image' => $newHeroImage,
                    ':catchy_headline' => $catchyHeadline,
                    ':store_headline' => $storeHeadline,
                    ':introduction_headline' => $introductionHeadline,
                    ':introduction_description' => $introductionDescription
                ]);
            }

            $_SESSION['front_page_content_success'] = 'Front page content saved successfully.';

            // Refresh CSRF token for next session
            unset($_SESSION['front_page_content_token']);

            redirectToPage();
        } catch (Throwable $e) {
            if ($newHeroImage !== $existingHeroImage && $newHeroImage !== '') {
                $failedImagePath = __DIR__ . '/../assets/uploads/' . basename($newHeroImage);
                if (is_file($failedImagePath)) {
                    @unlink($failedImagePath);
                }
            }
            $errorMessage = 'Front page content could not be saved. Please try again.';
        }
    }
}

// SESSION SUCCESS MESSAGE
if (isset($_SESSION['front_page_content_success'])) {
    $successMessage = (string) $_SESSION['front_page_content_success'];
    unset($_SESSION['front_page_content_success']);
}

// HERO IMAGE URL FORMATTING
$heroImageUrl = '';
if (trim($existingHeroImage) !== '') {
    $heroImageUrl = '../assets/uploads/' . rawurlencode(basename($existingHeroImage));
}

$updatedAt = $frontPageContent['updated_at'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ssrini Handcrafts Admin Front Page Content">
    <title><?= e($pageTitle) ?> | Ssrini Handcrafts</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .front-content-page { display: flex; flex-direction: column; gap: 20px; }
        .front-alert { padding: 13px 16px; border-radius: 10px; font-size: 12px; line-height: 1.5; }
        .front-alert-success { background: #eaf8ef; border: 1px solid #c9ecd5; color: #247545; }
        .front-alert-error { background: #fff3f3; border: 1px solid #ffd4d4; color: #c53030; }
        .front-content-card { background: #ffffff; border: 1px solid #eeeaf2; border-radius: 16px; box-shadow: 0 7px 22px rgba(30, 20, 50, 0.05); overflow: hidden; }
        .front-content-card-header { padding: 20px 22px; border-bottom: 1px solid #eeeaf2; display: flex; align-items: center; justify-content: space-between; gap: 15px; }
        .front-content-card-title { color: #25212d; font-size: 15px; font-weight: 700; }
        .front-content-card-description { margin-top: 5px; color: #9a94a2; font-size: 11px; line-height: 1.5; }
        .front-content-status { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 8px; background: #f5effb; color: #7627c9; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .front-content-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #7627c9; }
        .front-content-form { padding: 22px; }
        .front-content-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; }
        .front-content-full { grid-column: 1 / -1; }
        .front-form-group { display: flex; flex-direction: column; gap: 8px; }
        .front-form-label { color: #4f4857; font-size: 11px; font-weight: 700; }
        .front-form-label span { color: #c53030; }
        .front-form-help { color: #9a94a2; font-size: 10px; line-height: 1.45; }
        .front-form-input, .front-form-textarea { width: 100%; border: 1px solid #e3dfea; border-radius: 10px; background: #ffffff; color: #25212d; outline: none; font-family: inherit; font-size: 12px; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
        .front-form-input { height: 44px; padding: 0 13px; }
        .front-form-textarea { min-height: 135px; padding: 12px 13px; resize: vertical; line-height: 1.55; }
        .front-form-input:focus, .front-form-textarea:focus { border-color: #9b48d1; box-shadow: 0 0 0 3px rgba(155, 72, 209, 0.10); }
        .front-image-section { grid-column: 1 / -1; display: grid; grid-template-columns: minmax(260px, 340px) minmax(0, 1fr); gap: 22px; align-items: start; padding: 18px; border: 1px solid #eeeaf2; border-radius: 14px; background: #fcfbfd; }
        .front-image-preview { width: 100%; height: 205px; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, #f5effb, #faf7fc); border: 1px solid #e7dff0; display: flex; align-items: center; justify-content: center; }
        .front-image-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .front-image-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: #9a94a2; text-align: center; padding: 20px; }
        .front-image-placeholder-icon { width: 48px; height: 48px; border-radius: 13px; display: flex; align-items: center; justify-content: center; background: #eee4fb; color: #7627c9; font-size: 22px; }
        .front-image-placeholder-title { color: #514a59; font-size: 11px; font-weight: 700; }
        .front-image-placeholder-text { color: #a19aa8; font-size: 9px; }
        .front-image-controls { display: flex; flex-direction: column; gap: 13px; }
        .front-image-title { color: #403a47; font-size: 12px; font-weight: 700; }
        .front-image-description { color: #9a94a2; font-size: 10px; line-height: 1.55; }
        .front-file-input { width: 100%; padding: 10px; border: 1px dashed #d8cbe3; border-radius: 10px; background: #ffffff; color: #6e6676; font-size: 10px; }
        .front-file-input::file-selector-button { border: none; border-radius: 7px; background: linear-gradient(135deg, #7627c9, #c52b9f); color: #ffffff; padding: 7px 11px; margin-right: 9px; cursor: pointer; font-size: 10px; font-weight: 600; }
        .front-image-current { padding: 9px 11px; border-radius: 8px; background: #f5f2f7; color: #77717f; font-size: 9px; word-break: break-all; }
        .front-character-info { display: flex; align-items: center; justify-content: space-between; gap: 10px; color: #aaa3b0; font-size: 9px; }
        .front-character-count { white-space: nowrap; }
        .front-content-footer { margin-top: 22px; padding-top: 18px; border-top: 1px solid #eeeaf2; display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; }
        .front-updated-text { color: #9a94a2; font-size: 10px; }
        .front-updated-text strong { color: #6e6676; }
        .front-form-actions { display: flex; align-items: center; gap: 9px; }
        .front-reset-button { height: 42px; display: inline-flex; align-items: center; justify-content: center; padding: 0 16px; border: 1px solid #e3dfea; border-radius: 10px; background: #ffffff; color: #625b6b; text-decoration: none; font-size: 11px; font-weight: 600; }
        .front-reset-button:hover { background: #faf8fc; }
        .front-save-button { height: 42px; border: none; border-radius: 10px; padding: 0 20px; background: linear-gradient(135deg, #7627c9, #c52b9f); color: #ffffff; cursor: pointer; font-size: 11px; font-weight: 700; box-shadow: 0 7px 16px rgba(118, 39, 201, 0.20); }
        .front-save-button:hover { transform: translateY(-1px); }
        .front-info-card { padding: 17px 20px; border: 1px solid #eeeaf2; border-radius: 14px; background: #ffffff; box-shadow: 0 7px 22px rgba(30, 20, 50, 0.04); }
        .front-info-title { color: #403a47; font-size: 12px; font-weight: 700; }
        .front-info-list { margin: 10px 0 0; padding-left: 17px; color: #8a8392; font-size: 10px; line-height: 1.7; }
        .front-empty { padding: 65px 20px; text-align: center; }
        .front-empty-icon { width: 58px; height: 58px; margin: 0 auto 13px; border-radius: 16px; background: #f1e8ff; display: flex; align-items: center; justify-content: center; font-size: 25px; }
        .front-empty-title { color: #25212d; font-size: 14px; font-weight: 700; }
        .front-empty-description { max-width: 500px; margin: 7px auto 0; color: #9a94a2; font-size: 11px; line-height: 1.55; }

        @media (max-width: 900px) {
            .front-content-grid { grid-template-columns: 1fr; }
            .front-content-full { grid-column: auto; }
            .front-image-section { grid-column: auto; grid-template-columns: 1fr; }
        }
        @media (max-width: 650px) {
            .front-content-card-header { align-items: flex-start; flex-direction: column; }
            .front-content-form { padding: 17px; }
            .front-form-actions { width: 100%; }
            .front-reset-button, .front-save-button { flex: 1; }
            .front-content-footer { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-area">
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <div class="page-content">
            <section class="page-header">
                <div>
                    <h1 style="color: #e8eded; font-size: 15px; font-style: italic; font-weight: bold; background-color: #ca06ad; font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif; border-radius: 7px; padding: 10px 18px; display: inline-block;">
                        Front Page Content
                    </h1>
                    <p class="page-description" style="color: #db03db; font-size: 15px; font-style: italic; background-color: #f1f3f1; font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif; border-radius: 8px; padding: 12px 20px; display: inline-block; box-shadow: 0 4px 12px rgba(3, 22, 144, 0.3); letter-spacing: 0.5px;">
                        Manage the main content displayed on the Ssrini Handcrafts homepage.
                    </p>
                </div>
                <div>
                    <span class="front-content-status">
                        <span class="front-content-status-dot"></span>
                        Content Manager
                    </span>
                </div>
            </section>

            <div class="front-content-page">
                <?php if ($successMessage !== ''): ?>
                    <div class="front-alert front-alert-success"><?= e($successMessage) ?></div>
                <?php endif; ?>

                <?php if ($errorMessage !== ''): ?>
                    <div class="front-alert front-alert-error"><?= e($errorMessage) ?></div>
                <?php endif; ?>

                <?php if ($tableExists): ?>
                    <section class="front-content-card">
                        <div class="front-content-card-header">
                            <div>
                                <div class="front-content-card-title">Homepage Content</div>
                                <div class="front-content-card-description">Update the text and hero image used on the store homepage.</div>
                            </div>
                            <div class="front-content-status">
                                <span class="front-content-status-dot"></span>
                                <?= $existingHeroImage !== '' ? 'Content Available' : 'New Content' ?>
                            </div>
                        </div>

                        <form method="POST" action="front-page-content.php" enctype="multipart/form-data" class="front-content-form" id="frontPageContentForm">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                            <div class="front-content-grid">
                                <!-- HERO IMAGE -->
                                <div class="front-image-section">
                                    <div class="front-image-preview" id="heroImagePreview">
                                        <?php if ($heroImageUrl !== ''): ?>
                                            <img src="<?= e($heroImageUrl) ?>" alt="Current hero image" id="heroPreviewImage">
                                        <?php else: ?>
                                            <div class="front-image-placeholder" id="heroImagePlaceholder">
                                                <div class="front-image-placeholder-icon">🖼️</div>
                                                <div class="front-image-placeholder-title">No Hero Image</div>
                                                <div class="front-image-placeholder-text">Upload an image to display it here.</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="front-image-controls">
                                        <div class="front-image-title">Hero Image</div>
                                        <div class="front-image-description">
                                            Upload the main image that will appear in the homepage hero section.<br>
                                            Recommended formats: JPG, PNG or WEBP.<br>
                                            Maximum size: 5 MB.
                                        </div>
                                        <input type="file" name="hero_image" id="heroImageInput" class="front-file-input" accept="image/jpeg,image/png,image/webp">
                                        <?php if ($existingHeroImage !== ''): ?>
                                            <div class="front-image-current">Current file: <?= e($existingHeroImage) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- CATCHY HEADLINE -->
                                <div class="front-form-group">
                                    <label class="front-form-label" for="catchyHeadline">Catchy Headline <span>*</span></label>
                                    <input type="text" name="catchy_headline" id="catchyHeadline" class="front-form-input" maxlength="255" value="<?= e($catchyHeadline) ?>" placeholder="Enter your catchy headline" required>
                                    <div class="front-character-info">
                                        <span>Main promotional headline.</span>
                                        <span class="front-character-count" id="catchyHeadlineCount">0 / 255</span>
                                    </div>
                                </div>

                                <!-- STORE HEADLINE -->
                                <div class="front-form-group">
                                    <label class="front-form-label" for="storeHeadline">Store Headline <span>*</span></label>
                                    <input type="text" name="store_headline" id="storeHeadline" class="front-form-input" maxlength="255" value="<?= e($storeHeadline) ?>" placeholder="Enter your store headline" required>
                                    <div class="front-character-info">
                                        <span>Main store heading.</span>
                                        <span class="front-character-count" id="storeHeadlineCount">0 / 255</span>
                                    </div>
                                </div>

                                <!-- INTRODUCTION HEADLINE -->
                                <div class="front-form-group">
                                    <label class="front-form-label" for="introductionHeadline">Introduction Headline <span>*</span></label>
                                    <input type="text" name="introduction_headline" id="introductionHeadline" class="front-form-input" maxlength="255" value="<?= e($introductionHeadline) ?>" placeholder="Enter introduction headline" required>
                                    <div class="front-character-info">
                                        <span>Heading for the introduction section.</span>
                                        <span class="front-character-count" id="introductionHeadlineCount">0 / 255</span>
                                    </div>
                                </div>

                                <!-- INTRODUCTION DESCRIPTION -->
                                <div class="front-form-group front-content-full">
                                    <label class="front-form-label" for="introductionDescription">Introduction Description <span>*</span></label>
                                    <textarea name="introduction_description" id="introductionDescription" class="front-form-textarea" placeholder="Write a short introduction about Ssrini Handcrafts..." required><?= e($introductionDescription) ?></textarea>
                                    <div class="front-form-help">Write a clear and concise introduction about the store, products or brand.</div>
                                </div>
                            </div>

                            <div class="front-content-footer">
                                <div class="front-updated-text">
                                    <?php if (trim((string)$updatedAt) !== ''): ?>
                                        Last updated: <strong><?= e(date('d M Y, h:i A', strtotime((string)$updatedAt))) ?></strong>
                                    <?php else: ?>
                                        No content has been saved yet.
                                    <?php endif; ?>
                                </div>

                                <div class="front-form-actions">
                                    <a href="front-page-content.php" class="front-reset-button">Reset</a>
                                    <button type="submit" class="front-save-button" id="saveFrontContentButton">💾 Save Front Page Content</button>
                                </div>
                            </div>
                        </form>
                    </section>

                    <section class="front-info-card">
                        <div class="front-info-title">Content Management Notes</div>
                        <ul class="front-info-list">
                            <li>The first record in <strong>front_page_content</strong> is used as the active homepage content.</li>
                            <li>If the table is empty, saving this form will automatically create the first record.</li>
                            <li>If a record already exists, saving the form will update that record instead of creating duplicates.</li>
                            <li>Hero images are stored inside <strong>assets/uploads/</strong>.</li>
                            <li>Existing hero images are replaced when a new image is uploaded.</li>
                        </ul>
                    </section>
                <?php else: ?>
                    <section class="front-content-card">
                        <div class="front-empty">
                            <div class="front-empty-icon">📋</div>
                            <div class="front-empty-title">Front Page Content Table Not Available</div>
                            <div class="front-empty-description">
                                The admin page is ready, but the <strong>front_page_content</strong> table was not found in the current database. Please check the <strong>ssrini_handcrafts</strong> database.
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/admin.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // HERO IMAGE PREVIEW
        const heroImageInput = document.getElementById('heroImageInput');
        const heroImagePreview = document.getElementById('heroImagePreview');

        if (heroImageInput && heroImagePreview) {
            heroImageInput.addEventListener('change', function () {
                const file = this.files && this.files[0] ? this.files[0] : null;
                if (!file) return;

                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a JPG, PNG or WEBP image.');
                    this.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size must be smaller than 5 MB.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    heroImagePreview.innerHTML = '';
                    const image = document.createElement('img');
                    image.src = event.target.result;
                    image.alt = 'Hero image preview';
                    heroImagePreview.appendChild(image);
                };
                reader.readAsDataURL(file);
            });
        }

        // CHARACTER COUNTERS
        function setupCharacterCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);

            if (!input || !counter) return;

            function updateCounter() {
                counter.textContent = input.value.length + ' / ' + maxLength;
            }

            input.addEventListener('input', updateCounter);
            updateCounter();
        }

        setupCharacterCounter('catchyHeadline', 'catchyHeadlineCount', 255);
        setupCharacterCounter('storeHeadline', 'storeHeadlineCount', 255);
        setupCharacterCounter('introductionHeadline', 'introductionHeadlineCount', 255);

        // FORM SUBMIT PROTECTION
        const frontPageContentForm = document.getElementById('frontPageContentForm');
        const saveFrontContentButton = document.getElementById('saveFrontContentButton');

        if (frontPageContentForm && saveFrontContentButton) {
            frontPageContentForm.addEventListener('submit', function () {
                saveFrontContentButton.disabled = true;
                saveFrontContentButton.textContent = 'Saving...';
            });
        }
    });
</script>

</body>
</html>