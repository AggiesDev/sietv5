<?php
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sanitize(?string $value): string
{
    return trim(strip_tags((string) $value));
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: uniqid('item-', true));
}

function unique_slug(PDO $pdo, string $table, string $title, ?int $ignoreId = null): string
{
    $base = slugify($title);
    $slug = $base;
    $i = 2;
    do {
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$slug];
        if ($ignoreId) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exists = $stmt->fetch();
        if ($exists) {
            $slug = $base . '-' . $i++;
        }
    } while ($exists);
    return $slug;
}

function paginate(PDO $pdo, string $table, string $where = '1=1', array $params = [], int $perPage = 6): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $count = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $offset = ($page - 1) * $perPage;
    return compact('page', 'pages', 'offset', 'perPage', 'total');
}

function ensure_upload_directory(): void
{
    if (!is_dir(UPLOAD_PATH) && !mkdir(UPLOAD_PATH, 0775, true)) {
        throw new RuntimeException('Upload folder could not be created.');
    }
    if (!is_writable(UPLOAD_PATH)) {
        @chmod(UPLOAD_PATH, 0775);
    }
    if (!is_writable(UPLOAD_PATH)) {
        throw new RuntimeException('Upload folder is not writable. Set write permission for: ' . UPLOAD_PATH);
    }
    $guardFile = UPLOAD_PATH . '.htaccess';
    if (!file_exists($guardFile)) {
        @file_put_contents($guardFile, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\nRequire all denied\n</FilesMatch>\n");
    }
}

function upload_file(string $field, array $allowed, int $maxBytes, string $label): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return null;
    }
    $error = $_FILES[$field]['error'];
    if ($error !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file is larger than the server allows.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file is larger than the form allows.',
            UPLOAD_ERR_PARTIAL => 'The file uploaded only partially. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server upload temp folder is missing.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
        ];
        throw new RuntimeException($messages[$error] ?? 'Upload failed. Please try again.');
    }
    if ($_FILES[$field]['size'] > $maxBytes) {
        throw new RuntimeException($label . ' must be ' . (int) ($maxBytes / 1024 / 1024) . 'MB or smaller.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES[$field]['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Invalid ' . strtolower($label) . ' format.');
    }
    ensure_upload_directory();
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $target = UPLOAD_PATH . $name;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        throw new RuntimeException('Uploaded file could not be stored. Check upload folder permissions.');
    }
    @chmod($target, 0644);
    return upload_public_path($name);
}

function site_base_path(): string
{
    return rtrim((string) (parse_url(SITE_URL, PHP_URL_PATH) ?? ''), '/');
}

function upload_public_path(string $name): string
{
    return site_base_path() . '/assets/uploads/' . ltrim($name, '/');
}

function media_url(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (str_starts_with($value, 'data:') || str_starts_with($value, 'https://placehold.co')) {
        return $value;
    }
    $path = parse_url($value, PHP_URL_PATH);
    if ($path && str_contains($path, '/assets/uploads/')) {
        return site_base_path() . substr($path, strpos($path, '/assets/uploads/'));
    }
    if (str_starts_with($value, 'assets/uploads/')) {
        return site_base_path() . '/' . $value;
    }
    if (str_starts_with($value, '/')) {
        return $value;
    }
    if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
        return $value;
    }
    return site_base_path() . '/' . ltrim($value, '/');
}

function uploaded_file_path(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    $path = parse_url($value, PHP_URL_PATH);
    if (!$path || !str_contains($path, '/assets/uploads/')) {
        return null;
    }
    $fileName = basename($path);
    if ($fileName === '' || $fileName === '.htaccess') {
        return null;
    }
    $uploadRoot = realpath(UPLOAD_PATH);
    if (!$uploadRoot) {
        return null;
    }
    $filePath = realpath(UPLOAD_PATH . $fileName);
    if (!$filePath || !str_starts_with($filePath, $uploadRoot . DIRECTORY_SEPARATOR)) {
        return null;
    }
    return $filePath;
}

function delete_uploaded_file(?string $value): void
{
    $filePath = uploaded_file_path($value);
    if ($filePath && is_file($filePath)) {
        @unlink($filePath);
    }
}

function upload_image(string $field): ?string
{
    return upload_file($field, [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ], 5 * 1024 * 1024, 'Image');
}

function upload_document(string $field): ?string
{
    return upload_file($field, [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'text/csv' => 'csv',
        'text/plain' => 'csv',
        'application/csv' => 'csv',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/zip' => 'xlsx',
    ], 10 * 1024 * 1024, 'Document');
}

function count_rows(PDO $pdo, string $table, string $where = '1=1'): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM {$table} WHERE {$where}")->fetchColumn();
}

function site_page_catalog(PDO $pdo = null): array
{
    $pages = [
        'index.php' => 'Home',
        'introduction.php' => 'Introduction',
        'organisation.php' => 'Organisation Structure',
        'fellows.php' => 'Our Honorary Patron & Fellows',
        'council.php' => 'Executive Council',
        'founders.php' => 'Our Founders and Past Councils',
        'history.php' => 'History and Milestones',
        'why-member.php' => 'Why Become SIET Member',
        'membership-pathways.php' => 'Membership Pathways',
        'mature-candidate.php' => 'Mature Candidate Scheme',
        'students-members.php' => 'Students as Members',
        'member-directory.php' => 'Membership & Certification Search',
        'membership-fees.php' => 'Membership Fees',
        'membership-apply.php' => 'Membership Application',
        'professionalexaminations.php' => 'Professional Examinations',
        'cert-vs-membership.php' => 'Certification & Progression',
        'siet-tpc.php' => 'SIET - TPC',
        'cert-application.php' => 'Certification Application',
        'cert-fees.php' => 'Certification Fees',
        'accreditation.php' => 'Accreditation Introduction',
        'accreditation-international.php' => 'International Accreditation',
        'accreditation-local.php' => 'Local Accreditation',
        'accredited-courses.php' => 'Accredited Courses',
        'cpd-renewal.php' => 'Renewal of Professional Registration',
        'cpd-types.php' => 'Types of CPD',
        'global-founding.php' => 'Founding Member & Member',
        'global-recognitions.php' => 'International Recognitions',
        'global-affiliations.php' => 'Global Recognitions & Affiliations',
        'organisational-partner.php' => 'Why & How to become SIET Partner',
        'organisational-partnership.php' => 'Our Organisation Partners',
        'sponsorship.php' => 'Sponsorship',
        'awards.php' => 'Awards',
        'resources.php' => 'Expert Services',
        'events.php' => 'Events',
        'news.php' => 'News',
        'publications.php' => 'Publications',
        'contact.php' => 'Contact',
        'page.php' => 'All Custom Pages (Fallback)',
    ];
    if ($pdo && table_exists($pdo, 'site_pages')) {
        $rows = $pdo->query("SELECT title, slug FROM site_pages WHERE status='published' ORDER BY title")->fetchAll();
        foreach ($rows as $row) {
            $pages['page:' . $row['slug']] = 'Custom Page: ' . $row['title'];
        }
    }
    return $pages;
}

function default_nav_blueprint(): array
{
    return [
        ['Home', 'index.php', []],
        ['About SIET', '', [
            ['Introduction', 'introduction.php'],
            ['Organisation Structure', 'organisation.php'],
            ['Our Honorary Patron & Fellows', 'fellows.php'],
            ['Executive Council', 'council.php'],
            ['Our Founders and Past Councils', 'founders.php'],
            ['History and Milestones', 'history.php'],
        ]],
        ['Membership', '', [
            ['Why Become SIET Member', 'why-member.php'],
            ['Membership Pathways', 'membership-pathways.php'],
            ['Mature Candidate Scheme', 'mature-candidate.php'],
            ['Students as Members', 'students-members.php'],
            ['Membership & Certification Search', 'member-directory.php'],
            ['Membership Fees', 'membership-fees.php'],
        ]],
        ['Certification', '', [
            ['Professional Examinations', 'professionalexaminations.php'],
            ['Certification & Progression', 'cert-vs-membership.php'],
            ['SIET - TPC', 'siet-tpc.php'],
            ['Certification Application', 'cert-application.php'],
            ['Certification Fees', 'cert-fees.php'],
        ]],
        ['Accreditation', '', [
            ['Introduction', 'accreditation.php'],
            ['International Accreditation', 'accreditation-international.php'],
            ['Local Accreditation', 'accreditation-local.php'],
            ['Accredited Courses', 'accredited-courses.php'],
        ]],
        ['Renewal & CPD', '', [
            ['Renewal of Professional Registration', 'cpd-renewal.php'],
            ['Types of CPD', 'cpd-types.php'],
        ]],
        ['Global Network', '', [
            ['Founding Member & Member', 'global-founding.php'],
            ['International Recognitions', 'global-recognitions.php'],
            ['Global Recognitions & Affiliations', 'global-affiliations.php'],
        ]],
        ['Partnerships & Recognition', '', [
            ['Organisational Partnership', '', 1],
            ['Why & How to become SIET Partner', 'organisational-partner.php'],
            ['Our Organisation Partners', 'organisational-partnership.php'],
            ['Recognition', '', 1],
            ['Sponsorship', 'sponsorship.php'],
            ['Awards', 'awards.php'],
            ['Resources', '', 1],
            ['Expert Services', 'resources.php'],
            ['Events', 'events.php'],
            ['News', 'news.php'],
            ['Publications', 'publications.php'],
        ]],
    ];
}

function seed_default_navigation(PDO $pdo, bool $replace = false): void
{
    if ($replace) {
        $pdo->exec('DELETE FROM nav_items');
    }
    $parentStmt = $pdo->prepare('INSERT INTO nav_items (label, url, sort_order, is_active, is_header) VALUES (?, ?, ?, 1, 0)');
    $childStmt = $pdo->prepare('INSERT INTO nav_items (label, url, parent_id, sort_order, is_active, is_header) VALUES (?, ?, ?, ?, 1, ?)');
    foreach (default_nav_blueprint() as $topIndex => $top) {
        [$label, $url, $children] = $top;
        $parentStmt->execute([$label, $url, $topIndex]);
        $parentId = (int) $pdo->lastInsertId();
        foreach ($children as $childIndex => $child) {
            $childStmt->execute([$child[0], $child[1], $parentId, $childIndex, (int) ($child[2] ?? 0)]);
        }
    }
}

function active_class(string $file): string
{
    return basename($_SERVER['SCRIPT_NAME']) === $file ? 'active' : '';
}

function table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function public_nav_tree(PDO $pdo): array
{
    if (!table_exists($pdo, 'nav_items')) {
        return [];
    }
    $rows = $pdo->query('SELECT n.*, p.slug AS page_slug FROM nav_items n LEFT JOIN site_pages p ON p.id = n.page_id WHERE n.is_active = 1 ORDER BY n.parent_id IS NOT NULL, n.sort_order, n.label')->fetchAll();
    if (!$rows) {
        return [];
    }
    $tree = [];
    foreach ($rows as $row) {
        $row['children'] = [];
        if (!$row['parent_id']) {
            $tree[$row['id']] = $row;
        }
    }
    foreach ($rows as $row) {
        if ($row['parent_id'] && isset($tree[$row['parent_id']])) {
            $tree[$row['parent_id']]['children'][] = $row;
        }
    }
    return array_values($tree);
}

function published_nav_pages(PDO $pdo, array $excludePageIds = []): array
{
    if (!table_exists($pdo, 'site_pages')) {
        return [];
    }
    $sql = "SELECT id, title, slug FROM site_pages WHERE status='published' AND show_in_nav=1";
    $params = [];
    if ($excludePageIds) {
        $placeholders = implode(',', array_fill(0, count($excludePageIds), '?'));
        $sql .= " AND id NOT IN ({$placeholders})";
        $params = array_values($excludePageIds);
    }
    $sql .= ' ORDER BY title';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function nav_url(array $item): string
{
    if (!empty($item['page_slug'])) {
        return SITE_URL . '/page.php?slug=' . rawurlencode($item['page_slug']);
    }
    $url = $item['url'] ?: '#';
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || $url === '#') {
        return $url;
    }
    return SITE_URL . '/' . ltrim($url, '/');
}

function page_banner(PDO $pdo, string $pageKey): ?array
{
    if (!table_exists($pdo, 'site_banners')) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM site_banners WHERE scope='page' AND page_key=? AND is_active=1 ORDER BY sort_order, id DESC LIMIT 1");
    $stmt->execute([$pageKey]);
    $banner = $stmt->fetch();
    return $banner ?: null;
}

function banner_text(?array $banner, string $field, string $fallback): string
{
    $value = trim((string) ($banner[$field] ?? ''));
    return $value !== '' ? $value : $fallback;
}

function page_intro(string $title, string $subtitle): void
{
    global $pdo;
    $key = basename($_SERVER['SCRIPT_NAME']);
    $banner = isset($pdo) ? page_banner($pdo, $key) : null;
    $style = $banner ? ' style="background:linear-gradient(135deg, rgba(13,110,253,.70), rgba(168,85,247,.40)), url(' . e(media_url($banner['image'])) . ') center/cover; color:#fff;"' : '';
    $heroTitle = banner_text($banner, 'title', $title);
    $heroSubtitle = banner_text($banner, 'subtitle', $subtitle);
    $muted = $banner ? '' : ' text-muted';
    echo '<section class="page-hero"' . $style . '><div class="container py-5"><h1 class="display-6 fw-bold">' . e($heroTitle) . '</h1><p class="lead mb-0' . $muted . '">' . e($heroSubtitle) . '</p></div></section>';
}
?>
