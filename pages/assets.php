<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

$canManageAssets = in_array($_SESSION['role'] ?? '', ['admin', 'belvic_admin'], true);
$csrfToken = $_SESSION['assets_csrf_token'] ?? '';
if ($csrfToken === '') {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['assets_csrf_token'] = $csrfToken;
}

$assetSearch = trim((string) ($_GET['search'] ?? ''));
$assetCategory = trim((string) ($_GET['category'] ?? 'all'));
$requestedAction = $_GET['asset_action'] ?? '';
$pageQuery = array_filter([
    'search' => $assetSearch,
    'category' => $assetCategory !== 'all' ? $assetCategory : null,
], static fn($value) => $value !== null && $value !== '');
$pageUrl = 'assets.php' . ($pageQuery ? '?' . http_build_query($pageQuery) : '');
$formValues = [
    'name' => '',
    'category' => '',
    'serial_number' => '',
    'status' => 'Active',
];
$formErrors = [];
$successMessages = [
    'created' => 'Asset added successfully.',
    'updated' => 'Asset details updated successfully.',
    'deleted' => 'Asset deleted successfully.',
];
$formSuccess = is_string($requestedAction) ? ($successMessages[$requestedAction] ?? null) : null;
$uploadedImagePath = null;
$storedImageAbsolutePath = null;
$assetToEdit = null;
$formOperation = 'create';

function assetImageAbsolutePath(?string $relativePath): ?string
{
    if ($relativePath === null || !preg_match('/^\.\.\/uploads\/assets\/[a-f0-9]{32}\.(jpg|png|webp)$/', $relativePath)) {
        return null;
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . basename($relativePath);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canManageAssets) {
        http_response_code(403);
        exit('You do not have permission to manage construction assets.');
    }

    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        http_response_code(400);
        exit('Your session verification failed. Reload the page and try again.');
    }

    $requestedOperation = $_POST['operation'] ?? '';
    $formOperation = is_string($requestedOperation) ? $requestedOperation : '';
    $assetIdValue = $_POST['asset_id'] ?? '';
    $assetId = is_string($assetIdValue) && ctype_digit($assetIdValue) ? (int) $assetIdValue : 0;

    if ($formOperation === 'delete') {
        if ($assetId < 1) {
            http_response_code(400);
            exit('Invalid asset record.');
        }

        try {
            $findAsset = $conn->prepare('SELECT image_path FROM belvic_construction_assets WHERE id = ?');
            $findAsset->bind_param('i', $assetId);
            $findAsset->execute();
            $assetResult = $findAsset->get_result();
            $existingAsset = $assetResult->fetch_assoc();
            $findAsset->close();

            if (!$existingAsset) {
                http_response_code(404);
                exit('Asset record not found.');
            }

            $deleteAsset = $conn->prepare('DELETE FROM belvic_construction_assets WHERE id = ?');
            $deleteAsset->bind_param('i', $assetId);
            $deleteAsset->execute();
            $deleteAsset->close();

            $oldImagePath = assetImageAbsolutePath($existingAsset['image_path']);
            if ($oldImagePath !== null && is_file($oldImagePath) && !unlink($oldImagePath)) {
                error_log('Unable to remove deleted Belvic asset image: ' . $oldImagePath);
            }

            header('Location: ' . $pageUrl . ($pageQuery ? '&' : '?') . 'asset_action=deleted');
            exit;
        } catch (mysqli_sql_exception $exception) {
            error_log('Unable to delete Belvic construction asset: ' . $exception->getMessage());
            http_response_code(500);
            exit('The asset could not be deleted due to a database error.');
        }
    }

    if (!in_array($formOperation, ['create', 'update'], true)) {
        http_response_code(400);
        exit('Invalid asset operation.');
    }

    if ($formOperation === 'update') {
        if ($assetId < 1) {
            http_response_code(400);
            exit('Invalid asset record.');
        }

        $findAsset = $conn->prepare('SELECT id, name, category, serial_number, status, image_path FROM belvic_construction_assets WHERE id = ?');
        $findAsset->bind_param('i', $assetId);
        $findAsset->execute();
        $assetResult = $findAsset->get_result();
        $assetToEdit = $assetResult->fetch_assoc();
        $findAsset->close();

        if (!$assetToEdit) {
            http_response_code(404);
            exit('Asset record not found.');
        }

        $formValues = [
            'name' => $assetToEdit['name'],
            'category' => $assetToEdit['category'],
            'serial_number' => $assetToEdit['serial_number'],
            'status' => $assetToEdit['status'],
        ];
    }

    foreach ($formValues as $field => $defaultValue) {
        $postedValue = $_POST[$field] ?? $defaultValue;
        $formValues[$field] = is_string($postedValue) ? trim($postedValue) : '';
    }

    $requiredLengths = ['name' => 255, 'category' => 100, 'serial_number' => 100, 'status' => 50];
    foreach ($requiredLengths as $field => $maxLength) {
        if (strlen($formValues[$field]) > $maxLength) {
            $formErrors[] = ucfirst(str_replace('_', ' ', $field)) . " must be {$maxLength} characters or fewer.";
        }
    }
    foreach (['name', 'category', 'serial_number', 'status'] as $requiredField) {
        if ($formValues[$requiredField] === '') {
            $formErrors[] = ucfirst(str_replace('_', ' ', $requiredField)) . ' is required.';
        }
    }

    $validStatuses = ['Active', 'Operational', 'Maintenance', 'Repair Needed', 'Out of Service'];
    if (!in_array($formValues['status'], $validStatuses, true)) {
        $formErrors[] = 'Select a valid asset status.';
    }

    $imageUpload = $_FILES['image'] ?? null;
    $hasImageUpload = is_array($imageUpload) && ($imageUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($hasImageUpload) {
        $uploadError = $imageUpload['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $formErrors[] = $uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
                ? 'The image exceeds the upload size limit.'
                : 'The image upload failed. Please choose the image again.';
        } elseif (
            !isset($imageUpload['tmp_name'], $imageUpload['size']) ||
            !is_string($imageUpload['tmp_name']) ||
            !is_uploaded_file($imageUpload['tmp_name'])
        ) {
            $formErrors[] = 'The uploaded image could not be verified.';
        } elseif (!is_numeric($imageUpload['size']) || (int) $imageUpload['size'] > 5 * 1024 * 1024) {
            $formErrors[] = 'The image must be 5 MB or smaller.';
        } else {
            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($imageUpload['tmp_name']);
            $imageExtensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($imageExtensions[$mimeType])) {
                $formErrors[] = 'Upload a JPG, PNG, or WebP image.';
            } else {
                $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assets';
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    $formErrors[] = 'The image storage directory could not be created.';
                } else {
                    $fileName = bin2hex(random_bytes(16)) . '.' . $imageExtensions[$mimeType];
                    $storedImageAbsolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
                    $uploadedImagePath = '../uploads/assets/' . $fileName;
                }
            }
        }
    } elseif ($imageUpload !== null && !is_array($imageUpload)) {
        $formErrors[] = 'Invalid image upload data.';
    }

    $removeExistingImage = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';
    $oldImagePath = $assetToEdit ? $assetToEdit['image_path'] : null;
    $imagePathToSave = $hasImageUpload ? $uploadedImagePath : ($removeExistingImage ? null : $oldImagePath);

    if (!$formErrors && $storedImageAbsolutePath !== null && !move_uploaded_file($imageUpload['tmp_name'], $storedImageAbsolutePath)) {
        $formErrors[] = 'The uploaded image could not be saved. Please try again.';
        $uploadedImagePath = null;
        $storedImageAbsolutePath = null;
    }

    if (!$formErrors) {
        try {
            if ($formOperation === 'create') {
                $saveAsset = $conn->prepare(
                    'INSERT INTO belvic_construction_assets (name, category, serial_number, status, image_path)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $saveAsset->bind_param(
                    'sssss',
                    $formValues['name'],
                    $formValues['category'],
                    $formValues['serial_number'],
                    $formValues['status'],
                    $imagePathToSave
                );
                $successKey = 'created';
            } else {
                $saveAsset = $conn->prepare(
                    'UPDATE belvic_construction_assets
                     SET name = ?, category = ?, serial_number = ?, status = ?, image_path = ?
                     WHERE id = ?'
                );
                $saveAsset->bind_param(
                    'sssssi',
                    $formValues['name'],
                    $formValues['category'],
                    $formValues['serial_number'],
                    $formValues['status'],
                    $imagePathToSave,
                    $assetId
                );
                $successKey = 'updated';
            }
            $saveAsset->execute();
            $saveAsset->close();

            if (($hasImageUpload || $removeExistingImage) && $oldImagePath !== $imagePathToSave) {
                $oldImageAbsolutePath = assetImageAbsolutePath($oldImagePath);
                if ($oldImageAbsolutePath !== null && is_file($oldImageAbsolutePath) && !unlink($oldImageAbsolutePath)) {
                    error_log('Unable to remove replaced Belvic asset image: ' . $oldImageAbsolutePath);
                }
            }

            header('Location: ' . $pageUrl . ($pageQuery ? '&' : '?') . 'asset_action=' . $successKey);
            exit;
        } catch (mysqli_sql_exception $exception) {
            if ($storedImageAbsolutePath !== null && is_file($storedImageAbsolutePath)) {
                unlink($storedImageAbsolutePath);
            }
            if ((int) $exception->getCode() === 1062) {
                $formErrors[] = 'An asset with this serial number is already registered.';
            } else {
                error_log('Unable to save Belvic construction asset: ' . $exception->getMessage());
                $formErrors[] = 'The asset could not be saved because of a database error. Please try again.';
            }
        }
    }
}

$assets = [];
$categories = [];
$distribution = [];
$totalAssets = 0;
$loadError = false;

try {
    $result = $conn->query(
        'SELECT id, name, category, serial_number, status, image_path
         FROM belvic_construction_assets
         ORDER BY category, name, id'
    );
} catch (mysqli_sql_exception $exception) {
    error_log('Unable to load Belvic construction assets: ' . $exception->getMessage());
    $loadError = true;
}

if (!$loadError && $result === false) {
    error_log('Unable to load Belvic construction assets: query returned no result.');
    $loadError = true;
}

if (!$loadError) {
    while ($asset = $result->fetch_assoc()) {
        $category = (string) $asset['category'];
        $categories[$category] = $category;
        $distribution[$category] = ($distribution[$category] ?? 0) + 1;
        $totalAssets++;

        $searchableText = implode(' ', [
            $asset['name'],
            $category,
            $asset['serial_number'],
            $asset['status'],
        ]);

        if (
            ($assetSearch === '' || stripos($searchableText, $assetSearch) !== false) &&
            ($assetCategory === 'all' || $category === $assetCategory)
        ) {
            $assets[] = $asset;
        }
    }

    $result->free();
    ksort($categories, SORT_NATURAL | SORT_FLAG_CASE);
}

$chartColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444', '#4F46E5', '#EC4899', '#14B8A6'];
$statusStyles = [
    'active' => 'bg-green-100 text-green-800',
    'operational' => 'bg-green-100 text-green-800',
    'maintenance' => 'bg-yellow-100 text-yellow-800',
    'repair needed' => 'bg-red-100 text-red-800',
    'out of service' => 'bg-red-100 text-red-800',
];

include '../bar/navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belvic Construction Assets</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="min-h-screen bg-gray-100">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-blue-700">Belvic Construction</p>
                <h1 class="text-3xl font-bold text-gray-900">Assets</h1>
                <p class="mt-2 text-gray-600">Registered construction equipment and vehicles.</p>
            </div>
            <div class="flex flex-col gap-3 sm:items-end">
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <p class="rounded-full bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800">
                        <i class="fas fa-hard-hat mr-2" aria-hidden="true"></i>
                        <?= $totalAssets ?> registered <?= $totalAssets === 1 ? 'asset' : 'assets' ?>
                    </p>
                    <?php if ($canManageAssets): ?>
                        <button
                            id="addAssetButton"
                            type="button"
                            class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            <i class="fas fa-plus mr-1" aria-hidden="true"></i> Add Asset
                        </button>
                    <?php endif; ?>
                </div>
                <form action="assets.php" method="get" class="flex w-full gap-2 sm:w-auto">
                    <input
                        type="search"
                        name="search"
                        value="<?= htmlspecialchars($assetSearch, ENT_QUOTES, 'UTF-8') ?>"
                        aria-label="Search assets"
                        placeholder="Search assets..."
                        class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 sm:w-64"
                    >
                    <?php if ($assetCategory !== 'all'): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($assetCategory, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-search sm:mr-1" aria-hidden="true"></i>
                        <span class="hidden sm:inline">Search</span>
                    </button>
                </form>
            </div>
        </header>

        <?php if ($formSuccess !== null): ?>
            <div class="mb-6 rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-800" role="status">
                <?= htmlspecialchars($formSuccess, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($formErrors): ?>
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-semibold">The asset could not be saved:</p>
                <ul class="mt-2 list-inside list-disc">
                    <?php foreach ($formErrors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($canManageAssets): ?>
            <dialog id="addAssetDialog" class="max-h-[90vh] w-[min(42rem,calc(100%-2rem))] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/50" aria-labelledby="add-asset-title">
                <form action="<?= htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                        <div>
                            <h2 id="add-asset-title" class="text-xl font-bold text-gray-900">Add Construction Asset</h2>
                            <p id="assetDialogDescription" class="mt-1 text-sm text-gray-600">Enter the vehicle or equipment details to register it.</p>
                        </div>
                        <button type="button" onclick="document.getElementById('addAssetDialog').close()" aria-label="Close dialog" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="grid gap-4 px-6 py-5 sm:grid-cols-2">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input id="assetOperation" type="hidden" name="operation" value="create">
                        <input id="assetId" type="hidden" name="asset_id" value="">
                        <div class="sm:col-span-2">
                            <label for="assetName" class="mb-1 block text-sm font-medium text-gray-700">Asset name <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="assetName" name="name" required maxlength="255" value="<?= htmlspecialchars($formValues['name'], ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="e.g. 10 WHEELER DUMP TRUCK (DT-5)">
                        </div>
                        <div>
                            <label for="assetCategory" class="mb-1 block text-sm font-medium text-gray-700">Category <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="assetCategory" name="category" required maxlength="100" value="<?= htmlspecialchars($formValues['category'], ENT_QUOTES, 'UTF-8') ?>" list="assetCategories" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="e.g. Dump Truck">
                            <datalist id="assetCategories">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div>
                            <label for="assetSerial" class="mb-1 block text-sm font-medium text-gray-700">Serial number <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="assetSerial" name="serial_number" required maxlength="100" value="<?= htmlspecialchars($formValues['serial_number'], ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        </div>
                        <div>
                            <label for="assetStatus" class="mb-1 block text-sm font-medium text-gray-700">Status <span class="text-red-600" aria-hidden="true">*</span></label>
                            <select id="assetStatus" name="status" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                <?php foreach (['Active', 'Operational', 'Maintenance', 'Repair Needed', 'Out of Service'] as $statusOption): ?>
                                    <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>" <?= $formValues['status'] === $statusOption ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="assetImage" class="mb-1 block text-sm font-medium text-gray-700">Asset image <span class="text-gray-400">(optional, JPG/PNG/WebP, max 5 MB)</span></label>
                            <input id="assetImage" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2.5 file:font-medium file:text-gray-700 hover:file:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            <p id="assetImageFileName" class="mt-1 text-xs text-gray-500" aria-live="polite">No image selected.</p>
                            <div id="currentAssetImage" class="mt-2 hidden">
                                <img id="currentAssetImagePreview" src="" alt="Current asset image" class="h-20 w-28 rounded-md object-cover">
                                <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                                    Remove current image
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button type="button" onclick="document.getElementById('addAssetDialog').close()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-400">
                            Cancel
                        </button>
                        <button id="saveAssetButton" type="submit" class="rounded-lg bg-blue-700 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Save Asset
                        </button>
                    </div>
                </form>
            </dialog>
            <?php if ($formErrors): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => openAssetDialog(<?= json_encode($formOperation) ?>, <?= json_encode($formValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= (int) ($assetToEdit['id'] ?? 0) ?>, <?= json_encode($assetToEdit['image_path'] ?? '') ?>));
                </script>
            <?php endif; ?>
                    <script>
                        document.getElementById('assetImage').addEventListener('change', function () {
                            document.getElementById('assetImageFileName').textContent =
                                this.files.length ? this.files[0].name : 'No image selected.';
                        });
                    </script>
        <?php endif; ?>

        <?php if ($loadError): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-5 text-red-800" role="alert">
                <h2 class="font-semibold">Asset records could not be loaded</h2>
                <p class="mt-1 text-sm">Check that the construction assets migration has been applied, then reload this page.</p>
            </div>
        <?php else: ?>
            <section class="mb-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]" aria-labelledby="asset-summary-heading">
                <div class="rounded-xl bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 id="asset-summary-heading" class="text-xl font-bold text-gray-900">Asset Distribution</h2>
                            <p class="mt-1 text-sm text-gray-500">Counts by category from registered records.</p>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Total: <?= $totalAssets ?></p>
                    </div>
                    <?php if ($distribution): ?>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <?php $colorIndex = 0; ?>
                            <?php foreach ($distribution as $category => $count): ?>
                                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                                    <span class="flex min-w-0 items-center gap-2 text-sm text-gray-700">
                                        <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: <?= $chartColors[$colorIndex % count($chartColors)] ?>"></span>
                                        <span class="truncate"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                    <span class="ml-3 font-semibold text-gray-900"><?= $count ?></span>
                                </div>
                                <?php $colorIndex++; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">No registered assets to summarize yet.</p>
                    <?php endif; ?>
                </div>

                <div class="flex h-64 items-center justify-center rounded-xl bg-white p-5 shadow-sm">
                    <?php if ($distribution): ?>
                        <canvas id="assetChart" aria-label="Registered asset distribution chart" role="img"></canvas>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">The chart will appear when assets are recorded.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section aria-labelledby="asset-list-heading">
                <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 id="asset-list-heading" class="text-xl font-bold text-gray-900">Registered Assets</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Showing <?= count($assets) ?> of <?= $totalAssets ?> <?= $totalAssets === 1 ? 'asset' : 'assets' ?>.
                        </p>
                    </div>
                    <form action="assets.php" method="get" class="flex items-center gap-2">
                        <?php if ($assetSearch !== ''): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($assetSearch, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                        <label for="categoryFilter" class="text-sm font-medium text-gray-700">Category</label>
                        <select
                            id="categoryFilter"
                            name="category"
                            onchange="this.form.submit()"
                            class="max-w-[16rem] rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                            <option value="all" <?= $assetCategory === 'all' ? 'selected' : '' ?>>All categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>" <?= $assetCategory === $category ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <?php if (empty($assets)): ?>
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
                        <i class="fas fa-truck mb-3 text-3xl text-gray-300" aria-hidden="true"></i>
                        <p class="font-medium text-gray-700">
                            <?= $assetSearch !== '' || $assetCategory !== 'all'
                                ? 'No assets match the selected search or category.'
                                : 'No construction assets have been recorded yet.' ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($assets as $asset):
                            $status = (string) $asset['status'];
                            $statusClass = $statusStyles[strtolower(trim($status))] ?? 'bg-gray-100 text-gray-700';
                            $name = (string) $asset['name'];
                            $imagePath = trim((string) ($asset['image_path'] ?? ''));
                            $assetDetails = [
                                'id' => (int) $asset['id'],
                                'name' => $name,
                                'category' => (string) $asset['category'],
                                'serial_number' => (string) $asset['serial_number'],
                                'status' => $status,
                                'image_path' => $imagePath,
                            ];
                        ?>
                            <article class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm transition-shadow hover:shadow-md">
                                <div class="p-5">
                                    <div class="mb-4 flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="break-words text-lg font-bold text-gray-900">
                                                <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                            </h3>
                                            <p class="mt-1 break-all text-sm text-gray-500">
                                                <?= htmlspecialchars((string) $asset['serial_number'], ENT_QUOTES, 'UTF-8') ?>
                                            </p>
                                        </div>
                                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium <?= $statusClass ?>">
                                            <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                    <div class="group/image flex h-44 items-center justify-center overflow-hidden rounded-lg bg-gray-100">
                                        <?php if ($imagePath !== ''): ?>
                                            <img
                                                src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>"
                                                alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                                                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover/image:scale-110"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <i class="fas fa-truck text-4xl text-gray-300" aria-hidden="true"></i>
                                            <span class="sr-only">No asset image available</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-4 text-xs font-medium uppercase tracking-wide text-gray-500">
                                        <?= htmlspecialchars((string) $asset['category'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                    <?php if ($canManageAssets): ?>
                                        <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4">
                                            <button
                                                type="button"
                                                class="edit-asset-button flex-1 rounded-lg border border-blue-200 px-3 py-2 text-sm font-medium text-blue-800 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                data-asset="<?= htmlspecialchars(json_encode($assetDetails, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                <i class="fas fa-pen mr-1" aria-hidden="true"></i> Edit
                                            </button>
                                            <form action="<?= htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') ?>" method="post" onsubmit="return confirm('Delete this asset record? This cannot be undone.');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="operation" value="delete">
                                                <input type="hidden" name="asset_id" value="<?= (int) $asset['id'] ?>">
                                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500" aria-label="Delete <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <?php if (!$loadError && $distribution): ?>
        <script>
            const chartCanvas = document.getElementById('assetChart');
            if (chartCanvas) {
                new Chart(chartCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode(array_keys($distribution), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
                        datasets: [{
                            data: <?= json_encode(array_values($distribution)) ?>,
                            backgroundColor: <?= json_encode(array_slice($chartColors, 0, count($distribution))) ?>,
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        </script>
    <?php endif; ?>
    <?php if ($canManageAssets): ?>
        <script>
            const assetDialog = document.getElementById('addAssetDialog');

            function openAssetDialog(operation, values, id = 0, imagePath = '') {
                document.getElementById('assetOperation').value = operation;
                document.getElementById('assetId').value = id || '';
                document.getElementById('add-asset-title').textContent =
                    operation === 'update' ? 'Edit Construction Asset' : 'Add Construction Asset';
                document.getElementById('assetDialogDescription').textContent =
                    operation === 'update' ? 'Update this vehicle or equipment record.' : 'Enter the vehicle or equipment details to register it.';
                document.getElementById('saveAssetButton').textContent =
                    operation === 'update' ? 'Save Changes' : 'Save Asset';

                document.getElementById('assetName').value = values.name || '';
                document.getElementById('assetCategory').value = values.category || '';
                document.getElementById('assetSerial').value = values.serial_number || '';
                document.getElementById('assetStatus').value = values.status || 'Active';
                document.getElementById('assetImage').value = '';
                document.getElementById('assetImageFileName').textContent = 'No image selected.';

                const currentImage = document.getElementById('currentAssetImage');
                const imagePreview = document.getElementById('currentAssetImagePreview');
                currentImage.classList.toggle('hidden', !imagePath);
                imagePreview.src = imagePath || '';
                const removeImageCheckbox = assetDialog.querySelector('input[name="remove_image"]');
                removeImageCheckbox.checked = false;
                assetDialog.showModal();
            }

            document.getElementById('addAssetButton')?.addEventListener('click', () => {
                openAssetDialog('create', {name: '', category: '', serial_number: '', status: 'Active'});
            });

            document.querySelectorAll('.edit-asset-button').forEach(button => {
                button.addEventListener('click', () => {
                    const asset = JSON.parse(button.dataset.asset);
                    openAssetDialog('update', asset, asset.id, asset.image_path);
                });
            });
        </script>
    <?php endif; ?>
</body>
</html>
