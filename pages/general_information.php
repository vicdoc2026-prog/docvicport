<?php
require_once __DIR__ . '/config/check-session.php';
require_once __DIR__ . '/config/conn.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bindDynamicValues(mysqli_stmt $stmt, string $types, array &$values) {
  $arguments = [$types];
  foreach ($values as &$value) {
    $arguments[] = &$value;
  }
  return call_user_func_array([$stmt, 'bind_param'], $arguments);
}

function validOptionalDate($value) {
  if ($value === '') return true;
  $date = DateTime::createFromFormat('!Y-m-d', $value);
  return $date && $date->format('Y-m-d') === $value;
}

$role = $_SESSION['role'] ?? '';
$canManage = in_array($role, ['president', 'admin', 'belvic_admin'], true);
$profileLabels = [
  'company_name' => 'Company name',
  'contractor_id' => 'Contractor ID',
  'tin' => 'Taxpayer Identification Number',
  'head_office_location' => 'Head office location',
  'telephone' => 'Telephone',
  'fax' => 'Fax',
  'email' => 'Email',
  'manager_name' => 'Manager name',
  'manager_designation' => 'Manager designation',
  'manager_phone' => 'Manager telephone',
  'liaison_name' => 'Liaison officer name',
  'liaison_designation' => 'Liaison officer designation',
  'liaison_phone' => 'Liaison officer telephone',
  'pcab_firm_type' => 'Type of firm',
  'pcab_license_number' => 'PCAB license number',
  'pcab_registration_number' => 'PCAB registration number',
  'pcab_category' => 'PCAB category',
  'pcab_principal_classification' => 'Principal classification',
  'pcab_other_classification' => 'Other classification',
];
$profileDateLabels = [
  'pcab_license_first_issue_date' => 'PCAB license first issue date',
  'pcab_license_valid_from' => 'PCAB license valid from',
  'pcab_license_valid_to' => 'PCAB license valid to',
  'pcab_registration_date' => 'PCAB registration date',
  'pcab_registration_valid_from' => 'PCAB registration valid from',
  'pcab_registration_valid_to' => 'PCAB registration valid to',
];
$errors = [];
$formOpen = '';
$view = ($_GET['view'] ?? 'active') === 'archived' ? 'archived' : 'active';

if (!isset($_SESSION['contractor_info_csrf'])) {
  $_SESSION['contractor_info_csrf'] = bin2hex(random_bytes(32));
}

$profileResult = $conn->query('SELECT * FROM belvic_contractor_profile WHERE id = 1');
$profile = $profileResult->fetch_assoc();
$profileResult->free();
if (!$profile) {
  http_response_code(500);
  exit('Contractor profile is not configured. Apply the contractor information migration.');
}
$profileForm = $profile;
$certificateForm = ['id' => '', 'name' => '', 'certificate_number' => '', 'details' => '', 'valid_from' => '', 'valid_to' => '', 'sort_order' => ''];
$classificationForm = ['id' => '', 'project_kind' => '', 'size_range' => '', 'sort_order' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$canManage) {
    http_response_code(403);
    exit('You do not have permission to manage contractor information.');
  }

  $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
  $submittedToken = $_POST['csrf_token'] ?? '';
  if (!is_string($submittedToken) || !hash_equals($_SESSION['contractor_info_csrf'], $submittedToken)) {
    $errors[] = 'Your session token is invalid. Refresh the page and try again.';
  }

  if (!$errors && $action === 'update_profile') {
    $formOpen = 'profile';
    foreach (array_keys($profileLabels + $profileDateLabels) as $field) {
      $value = $_POST[$field] ?? '';
      $profileForm[$field] = is_scalar($value) ? trim((string)$value) : '';
    }
    if ($profileForm['company_name'] === '' || $profileForm['contractor_id'] === '') {
      $errors[] = 'Company name and Contractor ID are required.';
    }
    if ($profileForm['email'] !== '' && !filter_var($profileForm['email'], FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Enter a valid email address.';
    }
    foreach ($profileDateLabels as $field => $label) {
      if (!validOptionalDate($profileForm[$field])) $errors[] = $label . ' must be a valid date.';
    }
    foreach ($profileDateLabels as $fromField => $label) {
      if (str_ends_with($fromField, '_valid_from')) {
        $toField = str_replace('_valid_from', '_valid_to', $fromField);
        if ($profileForm[$fromField] !== '' && $profileForm[$toField] !== '' && $profileForm[$toField] < $profileForm[$fromField]) {
          $errors[] = $label . ' cannot be after its validity end date.';
        }
      }
    }
    foreach ($profileLabels as $field => $label) {
      if (strlen($profileForm[$field]) > 500) $errors[] = $label . ' is too long.';
    }
    if (!$errors) {
      $profileColumns = array_keys($profileLabels + $profileDateLabels);
      $profileValues = array_map(static fn($field) => $profileForm[$field] === '' ? null : $profileForm[$field], $profileColumns);
      $setClause = implode(', ', array_map(static fn($field) => '`' . $field . '` = ?', $profileColumns));
      $stmt = $conn->prepare('UPDATE belvic_contractor_profile SET ' . $setClause . ' WHERE id = 1');
      bindDynamicValues($stmt, str_repeat('s', count($profileValues)), $profileValues);
      $stmt->execute();
      $stmt->close();
      header('Location: general_information.php?saved=profile#company-heading');
      exit;
    }
  } elseif (!$errors && in_array($action, ['save_certificate', 'save_classification'], true)) {
    $isCertificate = $action === 'save_certificate';
    $formOpen = $isCertificate ? 'certificate' : 'classification';
    $submitted = $_POST;
    $id = isset($submitted['record_id']) && ctype_digit((string)$submitted['record_id']) ? (int)$submitted['record_id'] : 0;
    $sortOrder = filter_var($submitted['sort_order'] ?? 0, FILTER_VALIDATE_INT);
    if ($sortOrder === false || $sortOrder < 0 || $sortOrder > 65535) $errors[] = 'Sort order must be between 0 and 65535.';

    if ($isCertificate) {
      foreach (array_keys($certificateForm) as $field) {
        $value = $field === 'id' ? ($submitted['record_id'] ?? '') : ($submitted[$field] ?? '');
        $certificateForm[$field] = is_scalar($value) ? trim((string)$value) : '';
      }
      if ($certificateForm['name'] === '') $errors[] = 'Certificate name is required.';
      if (strlen($certificateForm['name']) > 255 || strlen($certificateForm['certificate_number']) > 255 || strlen($certificateForm['details']) > 500) {
        $errors[] = 'Certificate name, number, or details exceed the supported length.';
      }
      foreach (['valid_from', 'valid_to'] as $field) {
        if (!validOptionalDate($certificateForm[$field])) $errors[] = 'Certificate validity dates must be valid dates.';
      }
      if ($certificateForm['valid_from'] !== '' && $certificateForm['valid_to'] !== '' && $certificateForm['valid_to'] < $certificateForm['valid_from']) {
        $errors[] = 'Certificate end date cannot be before its start date.';
      }
      $table = 'belvic_contractor_certificates';
      $fields = ['name', 'certificate_number', 'details', 'valid_from', 'valid_to', 'sort_order'];
      $values = [$certificateForm['name'], $certificateForm['certificate_number'] ?: null, $certificateForm['details'] ?: null, $certificateForm['valid_from'] ?: null, $certificateForm['valid_to'] ?: null, (int)$sortOrder];
    } else {
      foreach (array_keys($classificationForm) as $field) {
        $value = $field === 'id' ? ($submitted['record_id'] ?? '') : ($submitted[$field] ?? '');
        $classificationForm[$field] = is_scalar($value) ? trim((string)$value) : '';
      }
      if ($classificationForm['project_kind'] === '' || $classificationForm['size_range'] === '') $errors[] = 'Project kind and size range are required.';
      if (strlen($classificationForm['project_kind']) > 500 || strlen($classificationForm['size_range']) > 100) $errors[] = 'Classification details exceed the supported length.';
      $table = 'belvic_contractor_classifications';
      $fields = ['project_kind', 'size_range', 'sort_order'];
      $values = [$classificationForm['project_kind'], $classificationForm['size_range'], (int)$sortOrder];
    }

    if (!$errors) {
      if ($isCertificate) {
        $fields = ['name', 'certificate_number', 'details', 'valid_from', 'valid_to', 'sort_order'];
      } else {
        $fields = ['project_kind', 'size_range', 'sort_order'];
      }
      if ($id > 0) {
        $setClause = implode(', ', array_map(static fn($field) => '`' . $field . '` = ?', $fields));
        $stmt = $conn->prepare('UPDATE ' . $table . ' SET ' . $setClause . ' WHERE id = ? AND archived_at IS NULL');
        $values[] = $id;
        bindDynamicValues($stmt, str_repeat('s', count($fields) - 1) . 'i' . 'i', $values);
      } else {
        $columnList = implode(', ', array_map(static fn($field) => '`' . $field . '`', $fields));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $stmt = $conn->prepare('INSERT INTO ' . $table . ' (' . $columnList . ') VALUES (' . $placeholders . ')');
        bindDynamicValues($stmt, $isCertificate ? 'sssssi' : 'ssi', $values);
      }
      $stmt->execute();
      $stmt->close();
      header('Location: general_information.php?saved=' . ($isCertificate ? 'certificate' : 'classification') . '#records');
      exit;
    }
  } elseif (!$errors && in_array($action, ['archive_certificate', 'restore_certificate', 'archive_classification', 'restore_classification'], true)) {
    $id = $_POST['record_id'] ?? '';
    if (!is_scalar($id) || !ctype_digit((string)$id) || (int)$id < 1) {
      $errors[] = 'The selected record is invalid.';
    } else {
      $isCertificate = str_contains($action, 'certificate');
      $table = $isCertificate ? 'belvic_contractor_certificates' : 'belvic_contractor_classifications';
      $isRestore = str_starts_with($action, 'restore_');
      $stmt = $conn->prepare('UPDATE ' . $table . ' SET archived_at = ' . ($isRestore ? 'NULL' : 'CURRENT_TIMESTAMP') . ' WHERE id = ? AND archived_at IS ' . ($isRestore ? 'NOT ' : '') . 'NULL');
      $recordId = (int)$id;
      $stmt->bind_param('i', $recordId);
      $stmt->execute();
      $stmt->close();
      $redirectView = $isRestore ? 'active' : 'archived';
      header('Location: general_information.php?view=' . $redirectView . '&saved=' . ($isRestore ? 'restored' : 'archived') . '#records');
      exit;
    }
  }
}

$archiveCondition = $view === 'archived' ? 'IS NOT NULL' : 'IS NULL';
$certificateResult = $conn->query('SELECT * FROM belvic_contractor_certificates WHERE archived_at ' . $archiveCondition . ' ORDER BY sort_order, id');
$certificates = $certificateResult->fetch_all(MYSQLI_ASSOC);
$certificateResult->free();
$classificationResult = $conn->query('SELECT * FROM belvic_contractor_classifications WHERE archived_at ' . $archiveCondition . ' ORDER BY sort_order, id');
$classifications = $classificationResult->fetch_all(MYSQLI_ASSOC);
$classificationResult->free();

function certificateStatus($validFrom, $validTo) {
  $today = date('Y-m-d');
  if (!$validFrom && !$validTo) return ['Dates not provided', 'neutral'];
  if ($validFrom && $validFrom > $today) return ['Upcoming', 'upcoming'];
  if ($validTo && $validTo < $today) return ['Expired', 'amber'];
  return ['Valid', 'emerald'];
}
?>
<?php include __DIR__ . '/../bar/navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contractor General Information | Belvic</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: Inter, system-ui, sans-serif; }
    .status-valid { color: #166534; background: #f0fdf4; border-color: #bbf7d0; }
    .status-expired { color: #92400e; background: #fffbeb; border-color: #fde68a; }
    .status-upcoming { color: #075985; background: #f0f9ff; border-color: #bae6fd; }
    .status-neutral { color: #475569; background: #f8fafc; border-color: #cbd5e1; }
    dialog::backdrop { background: rgba(15, 23, 42, .48); backdrop-filter: blur(2px); }
    dialog[open] { animation: dialog-in .16s ease-out; }
    @keyframes dialog-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
  <main class="mx-auto w-full max-w-7xl px-4 py-7 sm:px-6 lg:px-8 lg:py-10">
    <header class="mb-6 flex flex-col gap-5 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
      <div class="min-w-0">
        <p class="text-xs font-extrabold uppercase text-sky-800">Department of Public Works and Highways · Manila</p>
        <h1 class="mt-2 break-words text-2xl font-extrabold sm:text-3xl">Contractor General Information</h1>
        <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-600">Contractor profile, active certificates, and PCAB project classifications.</p>
      </div>
      <?php if ($canManage): ?>
        <button type="button" id="openProfileDialog" class="inline-flex w-fit items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 font-bold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300">
          <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit Profile
        </button>
      <?php endif; ?>
    </header>

    <?php if (!empty($_GET['saved'])): ?>
      <div role="status" class="mb-5 rounded-lg border border-slate-200 bg-white px-4 py-3 font-semibold text-slate-800">
        <?php echo $_GET['saved'] === 'profile' ? 'Contractor profile updated.' : ($_GET['saved'] === 'restored' ? 'Record restored.' : ($_GET['saved'] === 'archived' ? 'Record archived.' : 'Contractor information saved.')); ?>
      </div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
        <ul class="list-disc space-y-1 pl-5"><?php foreach ($errors as $error): ?><li><?php echo e($error); ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <nav aria-label="Contractor record view" class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
        <a href="general_information.php#records" class="rounded-md px-3 py-2 text-sm font-bold <?php echo $view === 'active' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">Active records</a>
        <a href="?view=archived#records" class="rounded-md px-3 py-2 text-sm font-bold <?php echo $view === 'archived' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">Archived records</a>
      </div>
      <?php if ($canManage && $view === 'active'): ?>
        <div class="flex flex-wrap gap-2">
          <button type="button" data-open-certificate class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 font-bold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-plus" aria-hidden="true"></i> Certificate</button>
          <button type="button" data-open-classification class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 font-bold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-plus" aria-hidden="true"></i> Classification</button>
        </div>
      <?php endif; ?>
    </nav>

    <section aria-labelledby="company-heading" class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 bg-slate-900 px-5 py-5 text-white sm:px-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="min-w-0">
            <p class="text-xs font-bold uppercase text-slate-300">Firm / Company</p>
            <h2 id="company-heading" class="mt-1 break-words text-xl font-extrabold sm:text-2xl"><?php echo e($profile['company_name']); ?></h2>
          </div>
          <div class="shrink-0 border-l-2 border-sky-400 pl-4 sm:text-right">
            <p class="text-xs font-semibold text-slate-300">Contractor ID</p>
            <p class="text-2xl font-extrabold tabular-nums"><?php echo e($profile['contractor_id']); ?></p>
          </div>
        </div>
      </div>
      <dl class="grid grid-cols-1 gap-x-8 gap-y-5 p-5 sm:grid-cols-2 sm:p-7 lg:grid-cols-3">
        <div><dt class="text-xs font-bold uppercase text-slate-500">Taxpayer Identification Number</dt><dd class="mt-1 break-words font-semibold"><?php echo e($profile['tin']); ?></dd></div>
        <div class="sm:col-span-2 lg:col-span-2"><dt class="text-xs font-bold uppercase text-slate-500">Head Office Location</dt><dd class="mt-1 break-words font-semibold"><?php echo e($profile['head_office_location']); ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">Telephone Number</dt><dd class="mt-1 font-semibold"><a class="hover:text-sky-800" href="tel:<?php echo e($profile['telephone']); ?>"><?php echo e($profile['telephone']); ?></a></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">Fax Number</dt><dd class="mt-1 font-semibold"><?php echo e($profile['fax']); ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">Email Address</dt><dd class="mt-1 break-all font-semibold"><a class="hover:text-sky-800" href="mailto:<?php echo e($profile['email']); ?>"><?php echo e($profile['email']); ?></a></dd></div>
      </dl>
    </section>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <section aria-labelledby="management-heading" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex items-center gap-3">
          <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-800"><i class="fa-solid fa-user-tie" aria-hidden="true"></i></span>
          <div><h2 id="management-heading" class="font-extrabold">Person Managing Affairs</h2><p class="text-sm font-medium text-slate-500">Firm management contact</p></div>
        </div>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div><dt class="text-xs font-bold uppercase text-slate-500">Name</dt><dd class="mt-1 font-semibold"><?php echo e($profile['manager_name']); ?></dd></div>
          <div><dt class="text-xs font-bold uppercase text-slate-500">Designation</dt><dd class="mt-1 font-semibold"><?php echo e($profile['manager_designation']); ?></dd></div>
          <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase text-slate-500">Telephone Number</dt><dd class="mt-1 font-semibold"><?php echo e($profile['manager_phone']); ?></dd></div>
        </dl>
      </section>

      <section aria-labelledby="liaison-heading" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex items-center gap-3">
          <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50 text-teal-800"><i class="fa-solid fa-address-card" aria-hidden="true"></i></span>
          <div><h2 id="liaison-heading" class="font-extrabold">Authorized Liaison Officer</h2><p class="text-sm font-medium text-slate-500">Authorized contractor representative</p></div>
        </div>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div><dt class="text-xs font-bold uppercase text-slate-500">Name</dt><dd class="mt-1 font-semibold"><?php echo e($profile['liaison_name']); ?></dd></div>
          <div><dt class="text-xs font-bold uppercase text-slate-500">Designation</dt><dd class="mt-1 font-semibold"><?php echo e($profile['liaison_designation']); ?></dd></div>
          <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase text-slate-500">Telephone Number</dt><dd class="mt-1 font-semibold"><?php echo e($profile['liaison_phone']); ?></dd></div>
        </dl>
      </section>
    </div>

    <section id="records" aria-labelledby="certificates-heading" class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
        <h2 id="certificates-heading" class="font-extrabold">Licenses &amp; Certificates</h2>
        <p class="mt-1 text-sm font-medium text-slate-500"><?php echo $view === 'archived' ? 'Archived records can be restored.' : 'Validity states are calculated from the dates entered.'; ?></p>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-[800px] w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3 font-bold">Document</th><th class="px-5 py-3 font-bold">Number / Details</th><th class="px-5 py-3 font-bold">Validity Period</th><th class="px-5 py-3 font-bold">Status</th><?php if ($canManage): ?><th class="px-5 py-3 text-right font-bold">Actions</th><?php endif; ?></tr></thead>
          <tbody>
            <?php if (!$certificates): ?>
              <tr><td colspan="<?php echo $canManage ? '5' : '4'; ?>" class="border-t border-slate-100 px-5 py-8 text-center font-semibold text-slate-500">No <?php echo $view === 'archived' ? 'archived' : 'active'; ?> certificates.</td></tr>
            <?php endif; ?>
            <?php foreach ($certificates as $certificate): ?>
              <?php [$certificateState, $certificateTone] = certificateStatus($certificate['valid_from'], $certificate['valid_to']); ?>
              <tr class="border-t border-slate-100 align-top hover:bg-slate-50/70">
                <th scope="row" class="px-5 py-4 font-bold text-slate-800"><?php echo e($certificate['name']); ?></th>
                <td class="px-5 py-4"><span class="block break-words font-semibold text-slate-800"><?php echo e($certificate['certificate_number'] ?: 'Not provided'); ?></span><span class="mt-1 block text-xs font-medium text-slate-500"><?php echo e($certificate['details'] ?: ''); ?></span></td>
                <td class="px-5 py-4 text-slate-700"><?php echo $certificate['valid_from'] ? date('M j, Y', strtotime($certificate['valid_from'])) : 'Date not provided'; ?><?php if ($certificate['valid_to']): ?> – <?php echo date('M j, Y', strtotime($certificate['valid_to'])); ?><?php endif; ?></td>
                <td class="px-5 py-4"><span class="inline-flex whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-bold status-<?php echo e($certificateTone); ?>"><?php echo e($certificateState); ?></span></td>
                <?php if ($canManage): ?>
                  <td class="px-5 py-4"><div class="flex justify-end gap-2">
                    <?php if ($view === 'archived'): ?>
                      <form method="POST"><input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['contractor_info_csrf']); ?>"><input type="hidden" name="action" value="restore_certificate"><input type="hidden" name="record_id" value="<?php echo (int)$certificate['id']; ?>"><button class="rounded-md border border-slate-300 px-3 py-2 font-bold text-slate-700 hover:bg-white" type="submit">Restore</button></form>
                    <?php else: ?>
                      <button type="button" data-edit-certificate data-id="<?php echo (int)$certificate['id']; ?>" data-name="<?php echo e($certificate['name']); ?>" data-certificate-number="<?php echo e($certificate['certificate_number']); ?>" data-details="<?php echo e($certificate['details']); ?>" data-valid-from="<?php echo e($certificate['valid_from']); ?>" data-valid-to="<?php echo e($certificate['valid_to']); ?>" data-sort-order="<?php echo (int)$certificate['sort_order']; ?>" class="rounded-md border border-slate-300 px-3 py-2 font-bold text-slate-700 hover:bg-white">Edit</button>
                      <form method="POST" class="archive-form"><input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['contractor_info_csrf']); ?>"><input type="hidden" name="action" value="archive_certificate"><input type="hidden" name="record_id" value="<?php echo (int)$certificate['id']; ?>"><button class="rounded-md border border-amber-200 px-3 py-2 font-bold text-amber-800 hover:bg-amber-50" type="submit">Archive</button></form>
                    <?php endif; ?>
                  </div></td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section aria-labelledby="pcab-heading" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
        <p class="text-xs font-bold uppercase text-sky-800">PCAB Contractor’s License</p>
        <h2 id="pcab-heading" class="mt-1 font-extrabold">Classification &amp; Project Size Ranges</h2>
      </div>
      <dl class="grid grid-cols-2 gap-4 border-b border-slate-100 p-5 sm:grid-cols-4 sm:px-6">
        <div><dt class="text-xs font-bold uppercase text-slate-500">Type of Firm</dt><dd class="mt-1 font-semibold"><?php echo e($profile['pcab_firm_type']); ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">License Number</dt><dd class="mt-1 font-semibold"><?php echo e($profile['pcab_license_number']); ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">License First Issue</dt><dd class="mt-1 font-semibold"><?php echo $profile['pcab_license_first_issue_date'] ? date('M j, Y', strtotime($profile['pcab_license_first_issue_date'])) : 'Not provided'; ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">License Validity</dt><dd class="mt-1 font-semibold"><?php echo $profile['pcab_license_valid_from'] ? date('M j, Y', strtotime($profile['pcab_license_valid_from'])) : 'Not provided'; ?><?php if ($profile['pcab_license_valid_to']): ?> – <?php echo date('M j, Y', strtotime($profile['pcab_license_valid_to'])); ?><?php endif; ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">Registration Number</dt><dd class="mt-1 font-semibold"><?php echo e($profile['pcab_registration_number']); ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">Category</dt><dd class="mt-1 font-semibold"><?php echo e($profile['pcab_category']); ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">Registration Date</dt><dd class="mt-1 font-semibold"><?php echo $profile['pcab_registration_date'] ? date('M j, Y', strtotime($profile['pcab_registration_date'])) : 'Not provided'; ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">Registration Validity</dt><dd class="mt-1 font-semibold"><?php echo $profile['pcab_registration_valid_from'] ? date('M j, Y', strtotime($profile['pcab_registration_valid_from'])) : 'Not provided'; ?><?php if ($profile['pcab_registration_valid_to']): ?> – <?php echo date('M j, Y', strtotime($profile['pcab_registration_valid_to'])); ?><?php endif; ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">Principal Classification</dt><dd class="mt-1 font-semibold"><?php echo e($profile['pcab_principal_classification']); ?></dd></div>
        <div><dt class="text-xs font-bold uppercase text-slate-500">Other Classification</dt><dd class="mt-1 font-semibold"><?php echo e($profile['pcab_other_classification']); ?></dd></div>
      </dl>
      <div class="overflow-x-auto">
        <table class="min-w-[560px] w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3 font-bold">Kind of Project</th><th class="px-5 py-3 text-right font-bold">Size Range</th><?php if ($canManage): ?><th class="px-5 py-3 text-right font-bold">Actions</th><?php endif; ?></tr></thead>
          <tbody>
            <?php if (!$classifications): ?>
              <tr><td colspan="<?php echo $canManage ? '3' : '2'; ?>" class="border-t border-slate-100 px-5 py-8 text-center font-semibold text-slate-500">No <?php echo $view === 'archived' ? 'archived' : 'active'; ?> classifications.</td></tr>
            <?php endif; ?>
            <?php foreach ($classifications as $classification): ?>
              <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                <td class="px-5 py-3 font-semibold text-slate-800"><?php echo e($classification['project_kind']); ?></td>
                <td class="px-5 py-3 text-right font-bold text-slate-700"><?php echo e($classification['size_range']); ?></td>
                <?php if ($canManage): ?><td class="px-5 py-3"><div class="flex justify-end gap-2">
                  <?php if ($view === 'archived'): ?>
                    <form method="POST"><input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['contractor_info_csrf']); ?>"><input type="hidden" name="action" value="restore_classification"><input type="hidden" name="record_id" value="<?php echo (int)$classification['id']; ?>"><button class="rounded-md border border-slate-300 px-3 py-2 font-bold text-slate-700 hover:bg-white" type="submit">Restore</button></form>
                  <?php else: ?>
                    <button type="button" data-edit-classification data-id="<?php echo (int)$classification['id']; ?>" data-project-kind="<?php echo e($classification['project_kind']); ?>" data-size-range="<?php echo e($classification['size_range']); ?>" data-sort-order="<?php echo (int)$classification['sort_order']; ?>" class="rounded-md border border-slate-300 px-3 py-2 font-bold text-slate-700 hover:bg-white">Edit</button>
                    <form method="POST" class="archive-form"><input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['contractor_info_csrf']); ?>"><input type="hidden" name="action" value="archive_classification"><input type="hidden" name="record_id" value="<?php echo (int)$classification['id']; ?>"><button class="rounded-md border border-amber-200 px-3 py-2 font-bold text-amber-800 hover:bg-amber-50" type="submit">Archive</button></form>
                  <?php endif; ?>
                </div></td><?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <?php if ($canManage): ?>
    <dialog id="profileDialog" class="w-[min(94vw,920px)] max-w-none rounded-xl border border-slate-200 bg-white p-0 shadow-2xl">
      <form method="POST" class="max-h-[90vh] overflow-y-auto p-5 sm:p-7">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['contractor_info_csrf']); ?>">
        <div class="mb-5 flex items-start justify-between gap-4">
          <div><h2 class="text-xl font-extrabold">Edit Contractor Profile</h2><p class="mt-1 text-sm font-medium text-slate-500">Update company, contact, and PCAB registration information.</p></div>
          <button type="button" data-close-dialog="profileDialog" aria-label="Close profile editor" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
        <?php
          $profileGroups = [
            'Company and contacts' => ['company_name', 'contractor_id', 'tin', 'head_office_location', 'telephone', 'fax', 'email'],
            'Person managing affairs' => ['manager_name', 'manager_designation', 'manager_phone'],
            'Authorized liaison officer' => ['liaison_name', 'liaison_designation', 'liaison_phone'],
            'PCAB details' => ['pcab_firm_type', 'pcab_license_number', 'pcab_license_first_issue_date', 'pcab_license_valid_from', 'pcab_license_valid_to', 'pcab_registration_number', 'pcab_category', 'pcab_registration_date', 'pcab_registration_valid_from', 'pcab_registration_valid_to', 'pcab_principal_classification', 'pcab_other_classification'],
          ];
          $allProfileLabels = $profileLabels + $profileDateLabels;
        ?>
        <?php foreach ($profileGroups as $groupLabel => $groupFields): ?>
          <fieldset class="mb-5 rounded-lg border border-slate-200 p-4">
            <legend class="px-2 text-sm font-extrabold text-slate-800"><?php echo e($groupLabel); ?></legend>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <?php foreach ($groupFields as $field): ?>
                <?php $inputType = isset($profileDateLabels[$field]) ? 'date' : ($field === 'email' ? 'email' : 'text'); ?>
                <label class="text-sm font-bold text-slate-700 <?php echo in_array($field, ['company_name', 'head_office_location', 'pcab_principal_classification', 'pcab_other_classification'], true) ? 'sm:col-span-2' : ''; ?>">
                  <?php echo e($allProfileLabels[$field]); ?>
                  <?php if ($field === 'head_office_location'): ?>
                    <textarea name="<?php echo e($field); ?>" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-medium focus:outline-none focus:ring-2 focus:ring-sky-200"><?php echo e($profileForm[$field]); ?></textarea>
                  <?php else: ?>
                    <input name="<?php echo e($field); ?>" type="<?php echo $inputType; ?>" value="<?php echo e($profileForm[$field]); ?>" <?php echo in_array($field, ['company_name', 'contractor_id'], true) ? 'required' : ''; ?> class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-medium focus:outline-none focus:ring-2 focus:ring-sky-200">
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>
            </div>
          </fieldset>
        <?php endforeach; ?>
        <div class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
          <button type="button" data-close-dialog="profileDialog" class="rounded-lg border border-slate-300 px-4 py-2.5 font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
          <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 font-bold text-white hover:bg-slate-700">Save Profile</button>
        </div>
      </form>
    </dialog>

    <dialog id="certificateDialog" class="w-[min(94vw,680px)] max-w-none rounded-xl border border-slate-200 bg-white p-0 shadow-2xl">
      <form method="POST" class="p-5 sm:p-7">
        <input type="hidden" name="action" id="certificateAction" value="save_certificate">
        <input type="hidden" name="record_id" id="certificateId" value="<?php echo e($certificateForm['id']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['contractor_info_csrf']); ?>">
        <div class="mb-5 flex items-start justify-between gap-4"><div><h2 id="certificateDialogTitle" class="text-xl font-extrabold">Add Certificate</h2><p class="mt-1 text-sm font-medium text-slate-500">Leave dates blank when they are not listed.</p></div><button type="button" data-close-dialog="certificateDialog" aria-label="Close certificate editor" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <label class="text-sm font-bold text-slate-700 sm:col-span-2">Certificate name<input id="certificateName" name="name" required maxlength="255" value="<?php echo e($certificateForm['name']); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
          <label class="text-sm font-bold text-slate-700 sm:col-span-2">Certificate number<input id="certificateNumber" name="certificate_number" maxlength="255" value="<?php echo e($certificateForm['certificate_number']); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
          <label class="text-sm font-bold text-slate-700 sm:col-span-2">Details<input id="certificateDetails" name="details" maxlength="500" value="<?php echo e($certificateForm['details']); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
          <label class="text-sm font-bold text-slate-700">Valid from<input id="certificateValidFrom" name="valid_from" type="date" value="<?php echo e($certificateForm['valid_from']); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
          <label class="text-sm font-bold text-slate-700">Valid to<input id="certificateValidTo" name="valid_to" type="date" value="<?php echo e($certificateForm['valid_to']); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
          <label class="text-sm font-bold text-slate-700">Display order<input id="certificateSortOrder" name="sort_order" type="number" min="0" max="65535" value="<?php echo e($certificateForm['sort_order'] ?: '100'); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
        </div>
        <div class="mt-6 flex flex-col-reverse justify-end gap-2 sm:flex-row"><button type="button" data-close-dialog="certificateDialog" class="rounded-lg border border-slate-300 px-4 py-2.5 font-bold text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 font-bold text-white hover:bg-slate-700">Save Certificate</button></div>
      </form>
    </dialog>

    <dialog id="classificationDialog" class="w-[min(94vw,600px)] max-w-none rounded-xl border border-slate-200 bg-white p-0 shadow-2xl">
      <form method="POST" class="p-5 sm:p-7">
        <input type="hidden" name="action" id="classificationAction" value="save_classification">
        <input type="hidden" name="record_id" id="classificationId" value="<?php echo e($classificationForm['id']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['contractor_info_csrf']); ?>">
        <div class="mb-5 flex items-start justify-between gap-4"><div><h2 id="classificationDialogTitle" class="text-xl font-extrabold">Add Project Classification</h2><p class="mt-1 text-sm font-medium text-slate-500">Set a licensed kind of project and its size range.</p></div><button type="button" data-close-dialog="classificationDialog" aria-label="Close classification editor" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
        <div class="grid grid-cols-1 gap-4">
          <label class="text-sm font-bold text-slate-700">Kind of project<textarea id="classificationProjectKind" name="project_kind" rows="3" required maxlength="500" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"><?php echo e($classificationForm['project_kind']); ?></textarea></label>
          <label class="text-sm font-bold text-slate-700">Size range<input id="classificationSizeRange" name="size_range" required maxlength="100" value="<?php echo e($classificationForm['size_range']); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
          <label class="text-sm font-bold text-slate-700">Display order<input id="classificationSortOrder" name="sort_order" type="number" min="0" max="65535" value="<?php echo e($classificationForm['sort_order'] ?: '100'); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
        </div>
        <div class="mt-6 flex flex-col-reverse justify-end gap-2 sm:flex-row"><button type="button" data-close-dialog="classificationDialog" class="rounded-lg border border-slate-300 px-4 py-2.5 font-bold text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 font-bold text-white hover:bg-slate-700">Save Classification</button></div>
      </form>
    </dialog>

    <script>
      (() => {
        const dialogs = ['profileDialog', 'certificateDialog', 'classificationDialog'];
        const openDialog = id => document.getElementById(id)?.showModal();
        document.getElementById('openProfileDialog')?.addEventListener('click', () => openDialog('profileDialog'));
        document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => document.getElementById(button.dataset.closeDialog)?.close()));
        document.querySelectorAll('[data-open-certificate]').forEach(button => button.addEventListener('click', () => {
          document.getElementById('certificateDialog').querySelector('form').reset();
          document.getElementById('certificateAction').value = 'save_certificate';
          document.getElementById('certificateId').value = '';
          document.getElementById('certificateDialogTitle').textContent = 'Add Certificate';
          openDialog('certificateDialog');
        }));
        document.querySelectorAll('[data-edit-certificate]').forEach(button => button.addEventListener('click', () => {
          const data = button.dataset;
          document.getElementById('certificateAction').value = 'save_certificate';
          document.getElementById('certificateId').value = data.id;
          document.getElementById('certificateName').value = data.name;
          document.getElementById('certificateNumber').value = data.certificateNumber;
          document.getElementById('certificateDetails').value = data.details;
          document.getElementById('certificateValidFrom').value = data.validFrom;
          document.getElementById('certificateValidTo').value = data.validTo;
          document.getElementById('certificateSortOrder').value = data.sortOrder;
          document.getElementById('certificateDialogTitle').textContent = 'Edit Certificate';
          openDialog('certificateDialog');
        }));
        document.querySelectorAll('[data-open-classification]').forEach(button => button.addEventListener('click', () => {
          document.getElementById('classificationDialog').querySelector('form').reset();
          document.getElementById('classificationAction').value = 'save_classification';
          document.getElementById('classificationId').value = '';
          document.getElementById('classificationDialogTitle').textContent = 'Add Project Classification';
          openDialog('classificationDialog');
        }));
        document.querySelectorAll('[data-edit-classification]').forEach(button => button.addEventListener('click', () => {
          const data = button.dataset;
          document.getElementById('classificationAction').value = 'save_classification';
          document.getElementById('classificationId').value = data.id;
          document.getElementById('classificationProjectKind').value = data.projectKind;
          document.getElementById('classificationSizeRange').value = data.sizeRange;
          document.getElementById('classificationSortOrder').value = data.sortOrder;
          document.getElementById('classificationDialogTitle').textContent = 'Edit Project Classification';
          openDialog('classificationDialog');
        }));
        document.querySelectorAll('.archive-form').forEach(form => form.addEventListener('submit', event => {
          if (!window.confirm('Archive this record? It can be restored later.')) event.preventDefault();
        }));
        document.addEventListener('keydown', event => {
          if (event.key === 'Escape') dialogs.forEach(id => document.getElementById(id)?.close());
        });
        <?php if ($errors && $formOpen !== ''): ?>
          openDialog('<?php echo $formOpen === 'profile' ? 'profileDialog' : ($formOpen === 'certificate' ? 'certificateDialog' : 'classificationDialog'); ?>');
        <?php endif; ?>
      })();
    </script>
  <?php endif; ?>
</body>
</html>