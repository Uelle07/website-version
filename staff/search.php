<?php
/**
 * staff/search.php
 * -------------------------------------------------------
 * Staff Portal — Search Member Page (Database Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

$query = clean($_GET['q'] ?? '');
$results = [];

if ($query !== '') {
    $stmt = $pdo->prepare(
        "SELECT m.*, s.spouse_name
         FROM member m
         LEFT JOIN spouse s ON s.member_id = m.member_id
         WHERE m.account_number LIKE :q
            OR m.firstname LIKE :q
            OR m.lastname LIKE :q
            OR m.contact_number LIKE :q
         ORDER BY m.created_at DESC"
    );
    $stmt->execute(['q' => "%{$query}%"]);
    $results = $stmt->fetchAll();
}

$pageTitle = 'Search Member';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<div class="section-title flex-title-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
  <h2>Search Cooperative Members</h2>
  <a href="<?= e(BASE_URL) ?>/staff/members.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> View All Members</a>
</div>

<div class="table-card" style="padding: 1.5rem;">
  <form method="get" class="side-by-side-controls mb-4">
    <div class="search-bar-container view-search" style="flex-grow: 1;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" name="q" class="search-input" placeholder="Search by Account Number, Name, or Contact..." value="<?= e($query) ?>">
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
  </form>

  <?php if ($query !== ''): ?>
    <h4 style="color: var(--primary-teal); margin-bottom: 1rem;">
      <i class="fa-solid fa-list-check"></i> Results for: "<?= e($query) ?>" (<?= count($results) ?> found)
    </h4>

    <?php if (!empty($results)): ?>
      <table class="dashboard-table">
        <thead>
          <tr>
            <th>Account No.</th>
            <th>Full Name</th>
            <th>Civil Status</th>
            <th>Spouse Name</th>
            <th>Contact Number</th>
            <th style="text-align: center;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $m): ?>
            <?php
              $fullName = trim($m['firstname'] . ' ' . $m['lastname']);
              $accNo    = $m['account_number'];
            ?>
            <tr>
              <td><strong class="account-tag"><?= e($accNo) ?></strong></td>
              <td><?= e($fullName) ?></td>
              <td><?= e($m['civil_status'] ?? 'Single') ?></td>
              <td><?= e($m['spouse_name'] ?: 'None') ?></td>
              <td><?= e($m['contact_number'] ?: 'N/A') ?></td>
              <td style="text-align: center;">
                <button class="btn btn-secondary btn-sm" onclick="viewMember('<?= e($accNo) ?>')">
                  <i class="fa-solid fa-eye"></i> View
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="p-3 text-muted text-center">No member records match your search criteria.</div>
    <?php endif; ?>
  <?php else: ?>
    <p style="color: var(--text-muted);">Enter an Account Number, Name, or Contact Number above to search member records.</p>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>
