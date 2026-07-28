<?php
$pageTitle = 'Membership & Certification Search';
require_once __DIR__ . '/includes/header.php';

function directory_match(array $member, array $filters): bool
{
    foreach (['name', 'membership_no', 'certification_no', 'post_nominal'] as $field) {
        if ($filters[$field] !== '' && stripos($member[$field], $filters[$field]) === false) {
            return false;
        }
    }
    foreach (['membership_grade', 'certified_grade', 'membership_title', 'branch', 'specialisation', 'membership_status', 'certification_status'] as $field) {
        if ($filters[$field] !== '' && $member[$field] !== $filters[$field]) {
            return false;
        }
    }
    if ($filters['az'] !== '' && strtoupper($member['name'][0] ?? '') !== $filters['az']) {
        return false;
    }
    return true;
}

function option_values(array $members, string $field): array
{
    $values = array_unique(array_filter(array_column($members, $field)));
    sort($values, SORT_NATURAL | SORT_FLAG_CASE);
    return $values;
}

function directory_badge(string $type, string $status): string
{
    if ($type === 'membership') {
        $display = $status === 'active' ? 'Paid' : ucfirst($status);
        $class = $status === 'active' ? 'ms-paid' : ($status === 'expired' ? 'ms-overdue' : 'ms-unknown');
        return '<span class="ms-badge ' . $class . '">' . e($display) . '</span>';
    }
    $classes = [
        'Certified' => 'cs-ok',
        'In Progress' => 'cs-prog',
        'Pending Review' => 'cs-pend',
        'Expired' => 'cs-exp',
        'Renewal Due' => 'cs-due',
        'Suspended/Withdrawn' => 'cs-stop',
    ];
    return '<span class="cs-badge ' . ($classes[$status] ?? 'cs-unknown') . '">' . e($status) . '</span>';
}

$filters = [
    'name' => sanitize($_GET['name'] ?? ($_GET['q'] ?? '')),
    'membership_no' => sanitize($_GET['membership_no'] ?? ''),
    'certification_no' => sanitize($_GET['certification_no'] ?? ''),
    'membership_grade' => sanitize($_GET['membership_grade'] ?? ''),
    'certified_grade' => sanitize($_GET['certified_grade'] ?? ''),
    'membership_title' => sanitize($_GET['membership_title'] ?? ''),
    'post_nominal' => sanitize($_GET['post_nominal'] ?? ''),
    'branch' => sanitize($_GET['branch'] ?? ''),
    'specialisation' => sanitize($_GET['specialisation'] ?? ($_GET['areas'] ?? '')),
    'membership_status' => sanitize($_GET['membership_status'] ?? ''),
    'certification_status' => sanitize($_GET['certification_status'] ?? ''),
    'az' => strtoupper(substr(sanitize($_GET['az'] ?? ''), 0, 1)),
    'sort' => sanitize($_GET['sort'] ?? 'name_az'),
    'view' => sanitize($_GET['view'] ?? ''),
];

$stmt = $pdo->prepare("SELECT id, name, email, phone, profile_image, membership_grade, membership_status, membership_no, certification_no, certified_grade, membership_title, post_nominal, branch, specialisation, certification_status FROM users WHERE role = 'member' AND is_directory_visible = 1 ORDER BY name");
$stmt->execute();
$members = $stmt->fetchAll();
$filtered = array_values(array_filter($members, fn($member) => directory_match($member, $filters)));
usort($filtered, fn($a, $b) => $filters['sort'] === 'name_za' ? strcasecmp($b['name'], $a['name']) : strcasecmp($a['name'], $b['name']));
$selected = null;
foreach ($filtered as $member) {
    if ($filters['view'] === $member['membership_no'] || (!$selected && count($filtered) === 1)) {
        $selected = $member;
        break;
    }
}
$actionUrl = SITE_URL . '/' . basename($_SERVER['SCRIPT_NAME']);
$banner = page_banner($pdo, basename($_SERVER['SCRIPT_NAME']));
$heroTitle = banner_text($banner, 'title', 'Search SIET Members');
$heroSubtitle = banner_text($banner, 'subtitle', 'Search and view placeholder SIET membership and certification records.');
$heroStyle = $banner ? ' style="background:linear-gradient(135deg, rgba(13,110,253,.70), rgba(168,85,247,.40)), url(' . e(media_url($banner['image'])) . ') center/cover; color:#fff;"' : '';
$heroMuted = $banner ? '' : ' text-muted';
?>
<section class="page-hero"<?= $heroStyle ?>>
  <div class="container py-5">
    <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
      <div>
        <h1 class="mb-2"><?= e($heroTitle) ?></h1>
        <p class="<?= $heroMuted ?> mb-0"><?= e($heroSubtitle) ?></p>
      </div>
      <a href="<?= SITE_URL ?>/admin/members.php" class="btn btn-outline-primary rounded-pill">Edit Member List</a>
    </div>
  </div>
</section>

<section class="section-pad py-5">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="card vm-card">
          <div class="card-body">
            <div class="vm-title">Search Membership &amp; Certification</div>
            <div class="text-muted small mb-2">Use keywords and filters below.</div>
            <div class="vm-azbar">
              <?php foreach (range('A', 'Z') as $letter): ?>
                <?php $query = array_merge($filters, ['az' => $letter, 'view' => '']); ?>
                <a class="vm-az <?= $filters['az'] === $letter ? 'is-active' : '' ?>" href="<?= e($actionUrl . '?' . http_build_query($query)) ?>"><?= $letter ?></a>
              <?php endforeach; ?>
              <a class="vm-az vm-az-clear <?= $filters['az'] === '' ? 'is-active' : '' ?>" href="<?= e($actionUrl) ?>">Reset</a>
            </div>

            <form method="get" action="<?= e($actionUrl) ?>">
              <input type="hidden" name="az" value="<?= e($filters['az']) ?>">
              <div class="row g-2">
                <div class="col-12"><label class="form-label fw-semibold">Name</label><input class="form-control" name="name" value="<?= e($filters['name']) ?>" placeholder="Search by name"></div>
                <div class="col-12"><label class="form-label fw-semibold">Membership No.</label><input class="form-control" name="membership_no" value="<?= e($filters['membership_no']) ?>" placeholder="Search by member no"></div>
                <div class="col-12"><label class="form-label fw-semibold">Certification No.</label><input class="form-control" name="certification_no" value="<?= e($filters['certification_no']) ?>" placeholder="Search by certification no"></div>

                <?php foreach ([
                    'membership_grade' => 'Membership Grade',
                    'certified_grade' => 'Certification Level',
                    'membership_title' => 'Membership Title',
                    'branch' => 'Branch of Engineering',
                    'specialisation' => 'Specialisation (Areas of Certification)',
                    'membership_status' => 'Membership Status',
                    'certification_status' => 'Certification Status',
                ] as $field => $label): ?>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold"><?= e($label) ?></label>
                    <select class="form-select" name="<?= e($field) ?>">
                      <option value="">Select <?= e($label) ?></option>
                      <?php foreach (option_values($members, $field) as $value): ?>
                        <option value="<?= e($value) ?>" <?= $filters[$field] === $value ? 'selected' : '' ?>><?= e($value) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                <?php endforeach; ?>

                <div class="col-md-6"><label class="form-label fw-semibold">Certification Post-nominal</label><input class="form-control" name="post_nominal" value="<?= e($filters['post_nominal']) ?>" placeholder="e.g., MSIET"></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Sort</label><select class="form-select" name="sort"><option value="name_az" <?= $filters['sort'] === 'name_az' ? 'selected' : '' ?>>Name (A-Z)</option><option value="name_za" <?= $filters['sort'] === 'name_za' ? 'selected' : '' ?>>Name (Z-A)</option></select></div>
                <div class="col-12 d-flex gap-2 mt-2">
                  <button class="btn btn-primary rounded-pill px-4" type="submit">Search</button>
                  <a class="btn btn-outline-danger rounded-pill px-4" href="<?= e($actionUrl) ?>">Reset</a>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card vm-card">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="vm-title">Search Results</div>
              <div class="text-muted small"><?= count($filtered) ?> result(s)</div>
            </div>

            <div class="mt-3 vm-results">
              <?php if ($filtered): ?>
                <div class="list-group">
                  <?php foreach ($filtered as $member): ?>
                    <?php $query = array_merge($filters, ['view' => $member['membership_no']]); ?>
                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= e($actionUrl . '?' . http_build_query($query)) ?>">
                      <div class="d-flex align-items-center gap-3 me-2">
                        <img src="<?= e(media_url($member['profile_image']) ?: 'https://placehold.co/64x64?text=Member') ?>" class="rounded-circle object-fit-cover" style="width:48px;height:48px" alt="">
                        <div>
                        <div class="fw-semibold">
                          <?= e($member['name']) ?>
                          <span class="ms-2"><?= directory_badge('membership', $member['membership_status']) ?></span>
                          <span class="ms-2"><?= directory_badge('certification', $member['certification_status']) ?></span>
                        </div>
                        <div class="small text-muted">Member No: <?= e($member['membership_no']) ?> &bull; Cert No: <?= e($member['certification_no']) ?></div>
                        </div>
                      </div>
                      <span class="badge text-bg-light border">View</span>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="alert alert-light border mb-0">No placeholder member records matched the filters.</div>
              <?php endif; ?>
            </div>

            <?php if ($selected): ?>
              <div class="vm-selected mt-4">
                <div class="vm-title-sm">Member Information</div>
                <div class="table-responsive">
                  <table class="table table-bordered table-info mb-0 vm-info-table">
                    <tbody>
                      <tr><th>Name:</th><td><?= e($selected['name']) ?></td></tr>
                      <tr><th>Membership No.</th><td><?= e($selected['membership_no']) ?></td></tr>
                      <tr><th>Certification No.</th><td><?= e($selected['certification_no']) ?></td></tr>
                      <tr><th>Membership Grade:</th><td><?= e($selected['membership_grade']) ?></td></tr>
                      <tr><th>Certification Level:</th><td><?= e($selected['certified_grade']) ?></td></tr>
                      <tr><th>Membership Title:</th><td><?= e($selected['membership_title']) ?></td></tr>
                      <tr><th>Certification Post-nominal:</th><td><?= e($selected['post_nominal']) ?></td></tr>
                      <tr><th>Branch of Engineering:</th><td><?= e($selected['branch']) ?></td></tr>
                      <tr><th>Specialisation:</th><td><?= e($selected['specialisation']) ?></td></tr>
                      <tr><th>Membership Status:</th><td><?= directory_badge('membership', $selected['membership_status']) ?></td></tr>
                      <tr><th>Certification Status:</th><td><?= directory_badge('certification', $selected['certification_status']) ?></td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card vm-card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
          <div class="vm-title">Members List</div>
          <div class="text-muted small">Total: <?= count($filtered) ?> filtered</div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle vm-table mb-0">
            <thead><tr><th>No</th><th>Name</th><th>Member No</th><th>Certification No</th><th>Membership Grade</th><th>Certification Level</th><th>Membership Title</th><th>Post-nominal</th><th>Branch</th><th>Specialisation</th><th>Membership Status</th><th>Certification Status</th></tr></thead>
            <tbody>
              <?php foreach ($filtered as $index => $member): ?>
                <tr><td><?= $index + 1 ?></td><td class="fw-semibold"><img src="<?= e(media_url($member['profile_image']) ?: 'https://placehold.co/48x48?text=M') ?>" class="rounded-circle object-fit-cover me-2" style="width:34px;height:34px" alt=""><?= e($member['name']) ?></td><td><?= e($member['membership_no']) ?></td><td><?= e($member['certification_no']) ?></td><td><?= e($member['membership_grade']) ?></td><td><?= e($member['certified_grade']) ?></td><td><?= e($member['membership_title']) ?></td><td><?= e($member['post_nominal']) ?></td><td><?= e($member['branch']) ?></td><td><?= e($member['specialisation']) ?></td><td><?= directory_badge('membership', $member['membership_status']) ?></td><td><?= directory_badge('certification', $member['certification_status']) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
