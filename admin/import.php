<?php
$pageTitle = 'Import Data';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

function validate_import_file_format(string $path, string $name): string
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowedMimes = [
        'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
        'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'pdf' => ['application/pdf'],
    ];
    if (!isset($allowedMimes[$ext])) {
        throw new RuntimeException('Only CSV, Excel, and PDF files are allowed.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $path);
    finfo_close($finfo);
    if ($mime && !in_array($mime, $allowedMimes[$ext], true)) {
        throw new RuntimeException('Uploaded file type does not match the allowed CSV, Excel, or PDF formats.');
    }
    return $ext;
}

function excel_column_index(string $cellRef): int
{
    preg_match('/^[A-Z]+/i', $cellRef, $matches);
    $letters = strtoupper($matches[0] ?? 'A');
    $index = 0;
    for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
        $index = ($index * 26) + (ord($letters[$i]) - 64);
    }
    return max(0, $index - 1);
}

function xlsx_text_nodes(SimpleXMLElement $node): string
{
    $dom = dom_import_simplexml($node);
    if (!$dom) {
        return '';
    }
    $xpath = new DOMXPath($dom->ownerDocument);
    $texts = [];
    foreach ($xpath->query('.//*[local-name()="t"]', $dom) as $textNode) {
        $texts[] = $textNode->nodeValue;
    }
    return $texts ? implode('', $texts) : trim($dom->textContent);
}

function xlsx_cell_value(SimpleXMLElement $cell, array $shared, string $ns): string
{
    $type = (string) $cell->attributes()->t;
    $children = $cell->children($ns);
    if ($type === 'inlineStr') {
        return isset($children->is) ? xlsx_text_nodes($children->is) : '';
    }
    $value = (string) ($children->v ?? '');
    if ($type === 's') {
        return $shared[(int) $value] ?? '';
    }
    if ($type === 'b') {
        return $value === '1' ? '1' : '0';
    }
    return $value;
}

function read_import_rows(string $path, string $name): array
{
    $ext = validate_import_file_format($path, $name);
    if ($ext === 'csv') {
        $handle = fopen($path, 'r');
        $rows = [];
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }
    if ($ext === 'xlsx') {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('XLSX import needs the PHP ZIP extension enabled on the server. Upload CSV instead or enable ZIP in your hosting PHP settings.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not read XLSX file.');
        }
        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            $xml = simplexml_load_string($sharedXml);
            $ns = $xml->getNamespaces(true)[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            foreach ($xml->children($ns)->si as $si) {
                $shared[] = xlsx_text_nodes($si);
            }
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!$sheetXml) {
            throw new RuntimeException('XLSX must contain a first worksheet.');
        }
        $sheet = simplexml_load_string($sheetXml);
        $ns = $sheet->getNamespaces(true)[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $rows = [];
        foreach ($sheet->children($ns)->sheetData->row as $row) {
            $cells = [];
            foreach ($row->children($ns)->c as $cell) {
                $cells[excel_column_index((string) $cell->attributes()->r)] = xlsx_cell_value($cell, $shared, $ns);
            }
            if ($cells) {
                ksort($cells);
                $max = max(array_keys($cells));
                for ($i = 0; $i <= $max; $i++) {
                    $cells[$i] = $cells[$i] ?? '';
                }
                ksort($cells);
            }
            $rows[] = array_values($cells);
        }
        return $rows;
    }
    if ($ext === 'xls') {
        throw new RuntimeException('Legacy XLS files are allowed by format policy, but row import requires CSV or XLSX. Save the Excel file as XLSX or CSV before importing member/partner rows.');
    }
    throw new RuntimeException('PDF files are allowed by format policy, but member/partner row import requires CSV or XLSX data.');
}

function rows_to_assoc(array $rows): array
{
    if (!$rows) {
        return [];
    }
    $headers = array_map(fn($h) => strtolower(trim((string) $h)), array_shift($rows));
    $assoc = [];
    foreach ($rows as $row) {
        if (!array_filter($row)) {
            continue;
        }
        $item = [];
        foreach ($headers as $index => $header) {
            $item[$header] = trim((string) ($row[$index] ?? ''));
        }
        $assoc[] = $item;
    }
    return $assoc;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['import_type'] ?? '';
    $success = 0;
    $failed = 0;
    $notes = [];
    try {
        if (empty($_FILES['import_file']['name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please upload a valid import file.');
        }
        if ($_FILES['import_file']['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Import file must be 5MB or smaller.');
        }
        $rows = rows_to_assoc(read_import_rows($_FILES['import_file']['tmp_name'], $_FILES['import_file']['name']));
        if (!$rows) {
            throw new RuntimeException('No importable rows were found. Check that the first row contains column headers and at least one data row exists.');
        }
        foreach ($rows as $row) {
            try {
                if ($type === 'members') {
                    $name = sanitize($row['name'] ?? $row['full_name'] ?? '');
                    $email = sanitize($row['email'] ?? '');
                    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new RuntimeException('Missing valid name/email.');
                    }
                    $memberStatus = in_array($row['membership_status'] ?? 'active', ['active', 'pending', 'expired'], true) ? $row['membership_status'] : 'active';
                    $certStatusInput = strtolower(trim((string) ($row['certification_status'] ?? 'Pending Review')));
                    $certStatusMap = [
                        'active' => 'Certified',
                        'certified' => 'Certified',
                        'in progress' => 'In Progress',
                        'pending' => 'Pending Review',
                        'pending review' => 'Pending Review',
                        'not_applied' => 'Pending Review',
                        'not applied' => 'Pending Review',
                        'expired' => 'Expired',
                        'renewal due' => 'Renewal Due',
                        'suspended/withdrawn' => 'Suspended/Withdrawn',
                    ];
                    $certStatus = $certStatusMap[$certStatusInput] ?? 'Pending Review';
                    $visible = isset($row['is_directory_visible']) ? (int) (bool) $row['is_directory_visible'] : 1;
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, profile_image, password, role, membership_grade, membership_status, membership_no, certification_no, certified_grade, membership_title, post_nominal, branch, specialisation, certification_status, is_directory_visible) VALUES (?, ?, ?, ?, ?, 'member', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone), profile_image=COALESCE(NULLIF(VALUES(profile_image), ''), profile_image), role='member', membership_grade=VALUES(membership_grade), membership_status=VALUES(membership_status), membership_no=COALESCE(VALUES(membership_no), membership_no), certification_no=COALESCE(VALUES(certification_no), certification_no), certified_grade=COALESCE(NULLIF(VALUES(certified_grade), ''), certified_grade), membership_title=COALESCE(NULLIF(VALUES(membership_title), ''), membership_title), post_nominal=COALESCE(NULLIF(VALUES(post_nominal), ''), post_nominal), branch=COALESCE(NULLIF(VALUES(branch), ''), branch), specialisation=COALESCE(NULLIF(VALUES(specialisation), ''), specialisation), certification_status=VALUES(certification_status), is_directory_visible=VALUES(is_directory_visible)");
                    $stmt->execute([$name, $email, sanitize($row['phone'] ?? ''), sanitize($row['profile_image'] ?? ''), password_hash('Member@1234', PASSWORD_DEFAULT), sanitize($row['membership_grade'] ?? 'Member'), $memberStatus, sanitize($row['membership_no'] ?? '') ?: null, sanitize($row['certification_no'] ?? '') ?: null, sanitize($row['certified_grade'] ?? ''), sanitize($row['membership_title'] ?? ''), sanitize($row['post_nominal'] ?? ''), sanitize($row['branch'] ?? ''), sanitize($row['specialisation'] ?? ($row['areas'] ?? '')), $certStatus, $visible]);
                    $newId = (int) $pdo->lastInsertId();
                    if ($newId) {
                        $stmt = $pdo->prepare("UPDATE users SET membership_no = COALESCE(membership_no, CONCAT('SIET-', LPAD(id, 5, '0'))), certification_no = COALESCE(certification_no, CONCAT('TPC-', LPAD(id * 17, 5, '0'))) WHERE id = ?");
                        $stmt->execute([$newId]);
                    }
                } elseif ($type === 'partners') {
                    $name = sanitize($row['name'] ?? '');
                    if (!$name) {
                        throw new RuntimeException('Missing partner name.');
                    }
                    $partnerType = in_array($row['type'] ?? 'organisational', ['local', 'international', 'organisational'], true) ? $row['type'] : 'organisational';
                    $status = in_array($row['status'] ?? 'active', ['active', 'inactive'], true) ? $row['status'] : 'active';
                    $stmt = $pdo->prepare('INSERT INTO partners (name, type, description, website, logo, status) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $partnerType, sanitize($row['description'] ?? ''), sanitize($row['website'] ?? ''), sanitize($row['logo'] ?? ''), $status]);
                } else {
                    throw new RuntimeException('Choose an import type.');
                }
                $success++;
            } catch (Throwable $rowError) {
                $failed++;
                $notes[] = $rowError->getMessage();
            }
        }
        $stmt = $pdo->prepare('INSERT INTO import_logs (import_type, file_name, rows_total, rows_success, rows_failed, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$type, $_FILES['import_file']['name'], count($rows), $success, $failed, implode("\n", array_slice($notes, 0, 10)), current_user()['id']]);
        flash($failed ? 'warning' : 'success', "Import complete: {$success} saved, {$failed} failed.");
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }
    header('Location: ' . SITE_URL . '/admin/import.php');
    exit;
}

require_once __DIR__ . '/_header.php';
$q = sanitize($_GET['q'] ?? '');
$typeFilter = in_array($_GET['import_type'] ?? '', ['members', 'partners'], true) ? $_GET['import_type'] : '';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(file_name LIKE ? OR import_type LIKE ? OR notes LIKE ?)';
    array_push($params, ...array_fill(0, 3, '%' . $q . '%'));
}
if ($typeFilter) {
    $where[] = 'import_type=?';
    $params[] = $typeFilter;
}
$stmt = $pdo->prepare('SELECT * FROM import_logs' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC LIMIT 25');
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card card-body">
      <h4>Import Website Data</h4>
      <p class="text-muted">Upload CSV, Excel, or PDF files only. Member and partner row imports read CSV and XLSX data; XLSX support needs the PHP ZIP extension enabled on the server.</p>
      <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
        <div class="mb-3"><label class="form-label">Data Type</label><select name="import_type" class="form-select" required><option value="">Choose...</option><option value="members">Members</option><option value="partners">Organisation Partners</option></select></div>
        <div class="mb-3"><label class="form-label">File</label><input type="file" name="import_file" class="form-control" accept=".csv,.xls,.xlsx,.pdf" required></div>
        <button class="btn btn-primary rounded-pill px-4">Upload & Import</button>
      </form>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card card-body">
      <h5>Accepted Columns</h5>
      <p class="fw-semibold mb-1">Members</p>
      <p class="small text-muted">name,email,phone,profile_image,membership_no,certification_no,membership_grade,certified_grade,membership_title,post_nominal,branch,specialisation,membership_status,certification_status,is_directory_visible</p>
      <p class="fw-semibold mb-1">Partners</p>
      <p class="small text-muted mb-0">name,type,description,website,logo,status</p>
    </div>
  </div>
</div>
<div class="card card-body mt-4">
  <div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><h5 class="mb-0">Recent Imports</h5></div>
  <form class="mb-3" method="get"><div class="row g-2 align-items-end">
    <div class="col-md-7"><label class="form-label">Search import logs</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="File name, type, notes"></div>
    <div class="col-md-2"><label class="form-label">Type</label><select name="import_type" class="form-select"><option value="">All</option><option value="members" <?= $typeFilter==='members'?'selected':'' ?>>members</option><option value="partners" <?= $typeFilter==='partners'?'selected':'' ?>>partners</option></select></div>
    <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="<?= SITE_URL ?>/admin/import.php">Clear</a></div>
  </div></form>
  <div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Date</th><th>Type</th><th>File</th><th>Total</th><th>Saved</th><th>Failed</th></tr></thead><tbody>
  <?php foreach ($logs as $log): ?><tr><td><?= e($log['created_at']) ?></td><td><?= e($log['import_type']) ?></td><td><?= e($log['file_name']) ?></td><td><?= (int) $log['rows_total'] ?></td><td><?= (int) $log['rows_success'] ?></td><td><?= (int) $log['rows_failed'] ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
