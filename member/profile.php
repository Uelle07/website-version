<?php
/**
 * member/profile.php
 * -------------------------------------------------------
 * MODULE — View Member Profile
 * Displays the member's complete profile information in a 
 * clean, structured, and read-only format with print options.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_member();

$memberId = current_user_id();

// Fetch primary member record with account details
$stmt = $pdo->prepare(
    "SELECT m.*, ma.username AS account_username, ma.last_login 
     FROM member m 
     LEFT JOIN member_account ma ON ma.member_id = m.member_id 
     WHERE m.member_id = ?"
);
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    set_flash('error', 'Member record not found.');
    redirect('member/dashboard.php');
}

// Fetch related records (spouse, parents, children, references)
$stmt = $pdo->prepare("SELECT * FROM spouse WHERE member_id = ?");
$stmt->execute([$memberId]);
$spouse = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM parents WHERE member_id = ?");
$stmt->execute([$memberId]);
$parents = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM children WHERE member_id = ? ORDER BY child_id ASC");
$stmt->execute([$memberId]);
$children = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM member_references WHERE member_id = ?");
$stmt->execute([$memberId]);
$references = $stmt->fetchAll();

$pageTitle = 'My Profile';
require_once __DIR__ . '/../pages/header.php';

$memberAge = !empty($member['birthday']) ? calculate_age($member['birthday']) : null;
$fullName  = trim($member['firstname'] . ' ' . ($member['middlename'] ? $member['middlename'][0] . '. ' : '') . $member['lastname']);
if (!empty($member['extension_name'])) {
    $fullName .= ' ' . $member['extension_name'];
}
$status = $member['account_status'] ?? 'Active';
$sigPhoto = !empty($member['signature']) ? $member['signature'] : (!empty($references[0]['signature']) ? $references[0]['signature'] : null);
?>

<!-- Custom Styling for Profile Alignment, High Contrast & Condensed Print Layout -->
<style>
    .profile-label {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--primary-teal);
        margin-bottom: 0.25rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    html[data-theme="light"] .profile-label {
        color: #0284c7;
    }
    .profile-value {
        font-size: 0.95rem;
        color: var(--text-main);
        font-weight: 600;
        word-break: break-word;
    }
    .profile-section-title {
        color: var(--text-main);
        border-bottom: 1px solid var(--bg-card-border);
        padding-bottom: 0.6rem;
        margin-top: 1.5rem;
        margin-bottom: 1.15rem;
        font-weight: 800;
        font-size: 1.08rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .profile-info-tile {
        background: var(--input-bg);
        border: 1px solid var(--bg-card-border);
        border-radius: 14px;
        padding: 13px 18px;
        transition: border-color 0.25s ease, background 0.25s ease, transform 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .profile-info-tile:hover {
        border-color: var(--bg-card-border-glow);
        background: var(--bg-card-hover);
    }

    .print-doc-header, .print-doc-footer, .print-sig-block {
        display: none;
    }

    @media print {
        *, *::before, *::after {
            color: #000000 !important;
            opacity: 1 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }
        body, html, .dashboard-wrapper {
            background: #ffffff !important;
            color: #000000 !important;
            opacity: 1 !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 10.5px !important;
            line-height: 1.35 !important;
        }
        .no-print {
            display: none !important;
        }
        .print-doc-header {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2.5px solid #0f766e;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .print-doc-footer {
            display: block !important;
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px dashed #cbd5e1;
            font-size: 8.5px;
            color: #374151 !important;
            text-align: center;
        }
        .print-sig-block {
            display: flex !important;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px dashed #cbd5e1;
        }
        .profile-header-card {
            border: 1px solid #9ca3af !important;
            padding: 10px 14px !important;
            margin-bottom: 0 !important;
            box-shadow: none !important;
            background: #ffffff !important;
            border-radius: 4px !important;
        }
        .profile-info-tile {
            background: transparent !important;
            border: 1px solid #cbd5e1 !important;
            padding: 6px 10px !important;
            border-radius: 4px !important;
        }
        .profile-label {
            color: #1f2937 !important;
            font-size: 0.68rem !important;
            font-weight: 700 !important;
            margin-bottom: 1px !important;
            opacity: 1 !important;
        }
        .profile-value {
            color: #000000 !important;
            font-size: 0.84rem !important;
            font-weight: 600 !important;
            opacity: 1 !important;
            margin-bottom: 2px !important;
        }
        .profile-section-title {
            color: #000000 !important;
            border-bottom: 1.5px solid #000000 !important;
            margin-top: 8px !important;
            margin-bottom: 4px !important;
            padding-bottom: 2px !important;
            font-size: 0.88rem !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            opacity: 1 !important;
        }
        .row {
            margin-bottom: 0.15rem !important;
            row-gap: 3px !important;
        }
    }
</style>

<!-- Centered Profile Layout Wrapper -->
<div class="dashboard-wrapper" style="max-width: 1060px; margin: 20px auto 60px auto;">
    
    <!-- Top Navigation & Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print flex-wrap gap-3">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 7px 18px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="update_info.php" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-user-pen"></i> Edit Profile
            </a>
            <button type="button" onclick="window.print()" class="btn btn-accent-purple d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-print"></i> Print Profile
            </button>
            <div class="dropdown position-relative">
                <button class="btn btn-accent-emerald dropdown-toggle d-inline-flex align-items-center gap-2" type="button" id="downloadProfileDropdownBtn" data-bs-toggle="dropdown" data-bs-offset="0,8" aria-expanded="false">
                    <i class="fa-solid fa-download"></i> Download File
                </button>
                <ul class="dropdown-menu dropdown-menu-end glass-dropdown-menu shadow border-0 py-2" aria-labelledby="downloadProfileDropdownBtn" style="position: absolute; top: 100%; right: 0; margin-top: 8px !important; z-index: 1050;">
                    <li>
                        <a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="javascript:void(0);" onclick="openDownloadConfirmModal('pdf')">
                            <i class="fa-solid fa-file-pdf text-danger"></i> <span>Download PDF Document (.pdf)</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="javascript:void(0);" onclick="openDownloadConfirmModal('word')">
                            <i class="fa-solid fa-file-word text-cyan"></i> <span>Download Word Document (.doc)</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Official Printable Document Header (Print View Only) -->
    <div class="print-doc-header">
        <div>
            <h1 style="font-size: 16px; margin: 0; color: #0f766e; font-weight: 800; text-transform: uppercase;">SPACE COOPERATIVE</h1>
            <p style="margin: 0; font-size: 9.5px; color: #374151; font-weight: 600;">Official Member Profile &bull; Confidential Personal Record</p>
        </div>
        <div style="text-align: right;">
            <div style="display: inline-block; padding: 2px 8px; font-weight: bold; border-radius: 4px; font-size: 11px; border: 1px solid #0f766e; background: #f0fdfa; color: #0f766e;">
                ACCOUNT: <?= e($member['account_number']) ?>
            </div>
            <div style="font-size: 9px; color: #374151; margin-top: 2px;">
                Account Status: <strong><?= e($status) ?></strong> &bull; Account No: <strong><?= e($member['account_number']) ?></strong>
            </div>
        </div>
    </div>

    <!-- Main Profile Content Card (Segmented Information Layout) -->
    <div class="card profile-header-card shadow-sm p-4 p-md-5 mb-5">
      <div class="card-body px-0 py-1">

        <!-- Header Section: Photo & Basic Identity -->
        <div class="row align-items-center mb-4 pb-4" style="border-bottom: 1px solid var(--bg-card-border); row-gap: 16px;">
            <div class="col-md-auto text-center text-md-start mb-2 mb-md-0">
                <img src="<?= e(photo_url($member['passport_photo'] ?? '')) ?>" alt="Profile Photo"
                     style="width: 110px; height: 110px; object-fit: cover; border-radius: 18px; border: 3px solid var(--primary-teal); box-shadow: 0 6px 20px var(--accent-glow);">
            </div>
            <div class="col-md">
                <h2 class="mb-1" style="font-weight: 800; font-size: 1.65rem; color: var(--text-main);">
                    <?= e($fullName) ?>
                </h2>
                <p class="mb-3" style="color: var(--text-dim); font-size: 0.90rem;">Account No: <strong style="color: var(--text-main);"><?= e($member['account_number']) ?></strong> &bull; Registered: <strong><?= !empty($member['created_at']) ? date('M d, Y', strtotime($member['created_at'])) : 'Active' ?></strong></p>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-primary" style="font-weight: 700; border-radius: 12px; padding: 6px 14px; font-size: 0.8rem;"><i class="fa-solid fa-id-card me-1"></i> Account: <?= e($member['account_number']) ?></span>
                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; font-weight: 700; font-size: 0.8rem; padding: 6px 14px; border-radius: 12px;"><i class="fa-solid fa-circle-check me-1"></i> Account Status: <?= e($status) ?></span>
                </div>
            </div>
            <?php if (!empty($sigPhoto)): ?>
            <div class="col-md-auto text-center mt-2 mt-md-0">
                <div class="profile-label mb-1" style="font-size: 0.72rem;"><i class="fa-solid fa-signature"></i> Signature</div>
                <img src="<?= e(photo_url($sigPhoto)) ?>" alt="Signature"
                     style="height: 52px; max-width: 160px; object-fit: contain; background: #fff; padding: 4px 8px; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
            </div>
            <?php endif; ?>
        </div>

        <!-- Personal Information Segmented Grid -->
        <h5 class="profile-section-title"><i class="fa-solid fa-id-card me-2 text-cyan"></i> Personal Information</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-sm-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-cake-candles text-cyan"></i> Date of Birth &amp; Age</div>
                    <div class="profile-value">
                        <?= e($member['birthday'] ? date('F d, Y', strtotime($member['birthday'])) . ($memberAge !== null ? ' (' . $memberAge . ' Years Old)' : '') : 'Not Specified') ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-flag text-cyan"></i> Nationality</div>
                    <div class="profile-value"><?= e($member['nationality'] ?? 'Filipino') ?></div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-venus-mars text-cyan"></i> Sex</div>
                    <div class="profile-value"><?= e($member['sex'] ?: 'Not Specified') ?></div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-phone text-cyan"></i> Contact Number</div>
                    <div class="profile-value"><?= e($member['contact_number'] ?: 'Not Specified') ?></div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-user-tag text-cyan"></i> Account Username</div>
                    <div class="profile-value"><?= e($member['account_username'] ?? ($member['email'] ?? 'N/A')) ?></div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-ring text-cyan"></i> Civil Status</div>
                    <div class="profile-value"><?= e($member['civil_status'] ?? 'Single') ?></div>
                </div>
            </div>
            <div class="col-12">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-location-dot text-cyan"></i> Full Home Address</div>
                    <div class="profile-value"><?= e($member['address'] ?: 'Not Specified') ?></div>
                </div>
            </div>
        </div>

        <!-- Employment & Business Section -->
        <h5 class="profile-section-title"><i class="fa-solid fa-briefcase me-2 text-cyan"></i> Employment &amp; Business Information</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-sm-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-user-tie text-cyan"></i> Occupation / Profession</div>
                    <div class="profile-value"><?= e($member['occupation'] ?: 'None') ?></div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-building text-cyan"></i> Employer / Business Name</div>
                    <div class="profile-value"><?= e($member['employer'] ?: 'None') ?></div>
                </div>
            </div>
            <div class="col-md-4 col-sm-12">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-map-location-dot text-cyan"></i> Employer / Business Address</div>
                    <div class="profile-value"><?= e($member['employer_address'] ?: 'None') ?></div>
                </div>
            </div>
        </div>

        <!-- Parents Section -->
        <h5 class="profile-section-title"><i class="fa-solid fa-people-roof me-2 text-cyan"></i> Parents Information</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-person text-cyan"></i> Father's Full Name</div>
                    <div class="profile-value"><?= !empty(trim($parents['father_name'] ?? '')) ? e($parents['father_name']) : 'Not Specified' ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-person-dress text-cyan"></i> Mother's Full Name</div>
                    <div class="profile-value"><?= !empty(trim($parents['mother_name'] ?? '')) ? e($parents['mother_name']) : 'Not Specified' ?></div>
                </div>
            </div>
        </div>

        <!-- Spouse Section -->
        <?php 
            $hasSpouse = !empty(trim($spouse['spouse_name'] ?? '')) && ($member['civil_status'] ?? '') !== 'Single';
        ?>
        <?php if ($hasSpouse): ?>
        <h5 class="profile-section-title"><i class="fa-solid fa-heart me-2 text-cyan"></i> Spouse Information</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-heart text-cyan"></i> Spouse Full Name</div>
                    <div class="profile-value"><?= e($spouse['spouse_name']) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-briefcase text-cyan"></i> Spouse Occupation</div>
                    <div class="profile-value"><?= !empty(trim($spouse['occupation'] ?? '')) ? e($spouse['occupation']) : 'Not Specified' ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-building-user text-cyan"></i> Spouse Employer</div>
                    <div class="profile-value"><?= !empty(trim($spouse['employer'] ?? '')) ? e($spouse['employer']) : 'Not Specified' ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Children Section -->
        <?php 
            $validChildren = array_values(array_filter($children ?? [], function($c) {
                return !empty(trim($c['child_name'] ?? ''));
            }));
        ?>
        <?php if (!empty($validChildren)): ?>
        <h5 class="profile-section-title"><i class="fa-solid fa-children me-2 text-cyan"></i> Children Information</h5>
        <div class="row g-3 mb-4">
            <?php foreach ($validChildren as $index => $child): ?>
                <?php $childAge = !empty($child['birthday']) ? calculate_age($child['birthday']) : null; ?>
                <div class="col-md-6">
                    <div class="profile-info-tile">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="profile-label mb-0"><i class="fa-solid fa-child text-cyan"></i> Child <?= $index + 1 ?></div>
                            <?php if ($childAge !== null): ?>
                                <span class="badge" style="background: rgba(0, 242, 254, 0.12); border: 1px solid var(--bg-card-border-glow); color: var(--primary-teal); font-size: 0.72rem; padding: 3px 8px; border-radius: 8px;">
                                    <?= $childAge ?> Years Old
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="profile-value mb-1" style="font-size: 0.96rem;"><?= e($child['child_name']) ?></div>
                        <div style="color: var(--text-dim); font-size: 0.82rem;">
                            Birthday: <strong style="color: var(--text-main);"><?= e($child['birthday'] ? date('F d, Y', strtotime($child['birthday'])) : 'Not Specified') ?></strong>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Character References Section -->
        <h5 class="profile-section-title"><i class="fa-solid fa-users me-2 text-cyan"></i> Character References</h5>
        <div class="row g-3">
            <?php 
                $ref1Name = !empty($references[0]['reference_member_name']) ? trim($references[0]['reference_member_name']) : '';
                $ref2Name = !empty($references[1]['reference_member_name']) ? trim($references[1]['reference_member_name']) : '';
            ?>
            <div class="col-md-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-user-check text-cyan"></i> Reference Member 1</div>
                    <div class="profile-value"><?= !empty($ref1Name) ? e($ref1Name) : 'Not Specified' ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="profile-info-tile">
                    <div class="profile-label"><i class="fa-solid fa-user-check text-cyan"></i> Reference Member 2</div>
                    <div class="profile-value"><?= !empty($ref2Name) ? e($ref2Name) : 'Not Specified' ?></div>
                </div>
            </div>
            <?php if (count($references) > 2): ?>
                <?php for ($i = 2; $i < count($references); $i++): ?>
                    <div class="col-md-6">
                        <div class="profile-info-tile">
                            <div class="profile-label"><i class="fa-solid fa-user-check text-cyan"></i> Reference Member <?= $i + 1 ?></div>
                            <div class="profile-value"><?= !empty(trim($references[$i]['reference_member_name'])) ? e($references[$i]['reference_member_name']) : 'Not Specified' ?></div>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>

        <!-- Official Print Sign-off Block (Print View Only) -->
        <div class="print-sig-block">
            <div style="text-align: center; width: 190px;">
                <div style="min-height: 38px; display: flex; align-items: flex-end; justify-content: center;">
                    <?php if (!empty($sigPhoto)): ?>
                        <img src="<?= e(photo_url($sigPhoto)) ?>" alt="Signature" style="max-height: 40px; max-width: 150px; object-fit: contain;">
                    <?php else: ?>
                        <span style="font-size: 10px; color: #4b5563;">[ Member Signature on File ]</span>
                    <?php endif; ?>
                </div>
                <div style="border-top: 1px solid #000; margin-top: 3px; padding-top: 2px; font-size: 9.5px; font-weight: bold;">Member's Signature</div>
            </div>
            <div style="text-align: center; width: 190px;">
                <div style="min-height: 38px; display: flex; align-items: flex-end; justify-content: center; font-size: 11px; font-weight: bold; color: #0f766e;">
                    OFFICIAL SYSTEM VERIFIED
                </div>
                <div style="border-top: 1px solid #000; margin-top: 3px; padding-top: 2px; font-size: 9.5px; font-weight: bold;">Cooperative Verification &bull; <?= date('m/d/Y') ?></div>
            </div>
        </div>

      </div>
    </div>

    <!-- Official Printable Document Footer (Print View Only) -->
    <div class="print-doc-footer">
        SPACE Cooperative Member Portal &bull; Official Confidential Profile Record &bull; Printed on <?= date('m/d/Y, h:i A') ?>
    </div>
</div>

<!-- Custom Glassmorphic Download Confirmation Modal -->
<div id="downloadProfileConfirmModal" class="modal-overlay hidden" onclick="if(event.target === this) closeDownloadProfileModal();" aria-hidden="true" role="dialog" style="z-index: 100001 !important;">
  <div class="modal-card" style="max-width: 490px; border: 1px solid var(--bg-card-border-glow, rgba(0, 242, 254, 0.35)); box-shadow: 0 16px 40px rgba(0,0,0,0.6);">
    <div class="modal-header d-flex justify-content-between align-items-center pb-3 border-bottom">
      <h3 class="m-0 d-flex align-items-center gap-2" id="downloadModalTitle" style="font-size: 1.18rem; font-weight: 700; color: var(--text-main);">
        <i class="fa-solid fa-file-pdf text-danger" id="downloadModalIcon"></i> 
        <span id="downloadModalHeading">Confirm PDF Download</span>
      </h3>
      <button type="button" class="btn-close-modal" onclick="closeDownloadProfileModal();" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body py-3">
      <p id="downloadModalDesc" style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1rem;">
        Are you sure you want to download your official member profile record as a PDF Document (.pdf)?
      </p>
      
      <!-- Summary Box -->
      <div class="p-3 rounded mb-2" style="background: var(--input-bg); border: 1px solid var(--bg-card-border); border-radius: 12px;">
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px dashed var(--bg-card-border); font-size: 0.85rem;">
          <span style="color: var(--text-dim);">Member Name:</span>
          <strong style="color: var(--text-main);"><?= e($fullName) ?></strong>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px dashed var(--bg-card-border); font-size: 0.85rem;">
          <span style="color: var(--text-dim);">Account Number:</span>
          <span class="badge" style="background: rgba(0, 194, 168, 0.15); border: 1px solid var(--primary-teal); color: var(--primary-teal); font-weight: 700;"><?= e($member['account_number']) ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center" style="font-size: 0.85rem;">
          <span style="color: var(--text-dim);">Export Format:</span>
          <strong id="downloadModalFormatText" style="color: var(--primary-teal);">PDF Document (.pdf)</strong>
        </div>
      </div>
    </div>
    <div class="modal-actions d-flex justify-content-between align-items-center pt-3 border-top w-100">
      <button type="button" class="btn btn-secondary px-4 py-2" onclick="closeDownloadProfileModal();" style="border-radius: 12px; font-weight: 600;">
        Cancel
      </button>
      <button type="button" id="downloadConfirmBtn" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2" style="border-radius: 12px; font-weight: 700;" onclick="executeProfileDownload();">
        <i class="fa-solid fa-download"></i> <span>Download File</span>
      </button>
    </div>
  </div>
</div>

<script>
let currentProfileDownloadFormat = 'pdf';

function openDownloadConfirmModal(format) {
    currentProfileDownloadFormat = format;
    const modal = document.getElementById('downloadProfileConfirmModal');
    const heading = document.getElementById('downloadModalHeading');
    const icon = document.getElementById('downloadModalIcon');
    const desc = document.getElementById('downloadModalDesc');
    const formatText = document.getElementById('downloadModalFormatText');
    const confirmBtn = document.getElementById('downloadConfirmBtn');

    if (format === 'pdf') {
        if (heading) heading.textContent = 'Confirm PDF Download';
        if (icon) icon.className = 'fa-solid fa-file-pdf text-danger';
        if (desc) desc.textContent = 'Are you sure you want to download your official member profile record as a PDF Document (.pdf)?';
        if (formatText) formatText.innerHTML = '<span class="text-danger"><i class="fa-solid fa-file-pdf me-1"></i> PDF Document (.pdf)</span>';
        if (confirmBtn) {
            confirmBtn.innerHTML = '<i class="fa-solid fa-download"></i> <span>Download PDF</span>';
            confirmBtn.className = 'btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2';
        }
    } else {
        if (heading) heading.textContent = 'Confirm Word Document Download';
        if (icon) icon.className = 'fa-solid fa-file-word text-cyan';
        if (desc) desc.textContent = 'Are you sure you want to download your official member profile record as a Word Document (.doc)?';
        if (formatText) formatText.innerHTML = '<span class="text-cyan"><i class="fa-solid fa-file-word me-1"></i> Word Document (.doc)</span>';
        if (confirmBtn) {
            confirmBtn.innerHTML = '<i class="fa-solid fa-download"></i> <span>Download Word (.doc)</span>';
            confirmBtn.className = 'btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2';
        }
    }

    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeDownloadProfileModal() {
    const modal = document.getElementById('downloadProfileConfirmModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function createProfilePdfBlob(memberData) {
    function escapePdf(str) {
        return (str || '').toString().replace(/\\/g, '\\\\').replace(/\(/g, '\\(').replace(/\)/g, '\\)');
    }

    const title = "SPACE COOPERATIVE";
    const subtitle = "OFFICIAL MEMBER PROFILE RECORD";
    const subSub = "Account: " + memberData.accNo + "  |  Status: " + memberData.status;

    let streamLines = [];
    
    // Header text
    streamLines.push("BT");
    streamLines.push("/F1 16 Tf 50 745 Td (" + escapePdf(title) + ") Tj");
    streamLines.push("/F1 11 Tf 0 -18 Td (" + escapePdf(subtitle) + ") Tj");
    streamLines.push("/F2 9 Tf 0 -14 Td (" + escapePdf(subSub) + ") Tj");
    streamLines.push("ET");

    // Decorative horizontal header bar (Teal RGB: 0.06, 0.46, 0.43)
    streamLines.push("0.06 0.46 0.43 RG 1.5 w 50 700 m 562 700 l S");

    // Table rows
    const rows = [
        ["Account Number", memberData.accNo],
        ["Full Name", memberData.name],
        ["Account Status", memberData.status],
        ["Date of Birth & Age", memberData.birthday],
        ["Sex", memberData.sex],
        ["Civil Status", memberData.civil],
        ["Nationality", memberData.nationality],
        ["Contact Number", memberData.contact],
        ["Account Username", memberData.email],
        ["Full Home Address", memberData.address],
        ["Occupation", memberData.occ],
        ["Employer / Business", memberData.emp],
        ["Employer Address", memberData.empAddr],
        ["Father's Full Name", memberData.father],
        ["Mother's Full Name", memberData.mother]
    ];
    if (memberData.spouse && memberData.spouse !== 'None' && memberData.spouse !== 'Not Specified' && memberData.civil !== 'Single') {
        rows.push(["Spouse Name", memberData.spouse]);
    }
    if (memberData.childrenStr && memberData.childrenStr !== 'None' && memberData.childrenStr !== 'Not Specified') {
        rows.push(["Children Registered", memberData.childrenStr]);
    }
    rows.push(["Character References", memberData.refsStr]);

    let yStart = 680;
    streamLines.push("BT /F2 8.5 Tf");
    streamLines.push("50 " + yStart + " Td");

    for (let i = 0; i < rows.length; i++) {
        const label = escapePdf(rows[i][0] || '');
        const val = escapePdf(rows[i][1] || 'None');
        if (i > 0) {
            streamLines.push("0 -15 Td");
        }
        const formattedLine = label.padEnd(24, ' ') + " :  " + val;
        streamLines.push("(" + formattedLine + ") Tj");
    }

    // Sign-off / Verification metadata line
    const exportTime = "Exported from Space Cooperative Member Portal on " + new Date().toLocaleString();
    streamLines.push("0 -24 Td");
    streamLines.push("/F1 8 Tf");
    streamLines.push("(" + escapePdf(exportTime + " | Official Verified Document") + ") Tj");
    streamLines.push("ET");

    // Bottom divider line
    streamLines.push("0.7 0.7 0.7 RG 0.75 w 50 370 m 562 370 l S");

    const streamContent = streamLines.join("\n");
    const streamLength = streamContent.length;

    const obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    const obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    const obj3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>\nendobj\n";
    const obj4 = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
    const obj5 = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    const obj6 = "6 0 obj\n<< /Length " + streamLength + " >>\nstream\n" + streamContent + "\nendstream\nendobj\n";

    let header = "%PDF-1.4\n";
    let offset1 = header.length;
    let offset2 = offset1 + obj1.length;
    let offset3 = offset2 + obj2.length;
    let offset4 = offset3 + obj3.length;
    let offset5 = offset4 + obj4.length;
    let offset6 = offset5 + obj5.length;
    let xrefOffset = offset6 + obj6.length;

    let xref = "xref\n0 7\n0000000000 65535 f \n" +
        String(offset1).padStart(10, '0') + " 00000 n \n" +
        String(offset2).padStart(10, '0') + " 00000 n \n" +
        String(offset3).padStart(10, '0') + " 00000 n \n" +
        String(offset4).padStart(10, '0') + " 00000 n \n" +
        String(offset5).padStart(10, '0') + " 00000 n \n" +
        String(offset6).padStart(10, '0') + " 00000 n \n";

    let trailer = "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n" + xrefOffset + "\n%%EOF";

    const pdfData = header + obj1 + obj2 + obj3 + obj4 + obj5 + obj6 + xref + trailer;
    return new Blob([pdfData], { type: 'application/pdf' });
}

function executeProfileDownload() {
    closeDownloadProfileModal();
    const hasSpouseData = <?= json_encode(!empty(trim($spouse['spouse_name'] ?? '')) && ($member['civil_status'] ?? '') !== 'Single') ?>;
    const hasChildrenData = <?= json_encode(!empty($validChildren)) ?>;
    const memberData = {
        accNo: <?= json_encode($member['account_number']) ?>,
        name: <?= json_encode($fullName) ?>,
        status: <?= json_encode($status) ?>,
        birthday: <?= json_encode($member['birthday'] ? date('F d, Y', strtotime($member['birthday'])) . ($memberAge !== null ? ' (' . $memberAge . ' Years Old)' : '') : 'Not Specified') ?>,
        civil: <?= json_encode($member['civil_status'] ?? 'Single') ?>,
        sex: <?= json_encode($member['sex'] ?: 'Not Specified') ?>,
        nationality: <?= json_encode($member['nationality'] ?: 'Filipino') ?>,
        contact: <?= json_encode($member['contact_number'] ?: 'Not Specified') ?>,
        email: <?= json_encode($member['account_username'] ?? ($member['email'] ?? 'N/A')) ?>,
        address: <?= json_encode($member['address'] ?: 'Not Specified') ?>,
        occ: <?= json_encode($member['occupation'] ?: 'None') ?>,
        emp: <?= json_encode($member['employer'] ?: 'None') ?>,
        empAddr: <?= json_encode($member['employer_address'] ?: 'None') ?>,
        father: <?= json_encode(!empty(trim($parents['father_name'] ?? '')) ? $parents['father_name'] : 'Not Specified') ?>,
        mother: <?= json_encode(!empty(trim($parents['mother_name'] ?? '')) ? $parents['mother_name'] : 'Not Specified') ?>,
        spouse: hasSpouseData ? <?= json_encode($spouse['spouse_name'] ?? '') ?> : '',
        childrenStr: hasChildrenData ? <?= json_encode(implode(', ', array_map(function($c) { 
            $cAge = !empty($c['birthday']) ? calculate_age($c['birthday']) : null;
            return $c['child_name'] . ' (' . ($c['birthday'] ? date('M d, Y', strtotime($c['birthday'])) . ($cAge !== null ? ' - ' . $cAge . ' Years Old' : '') : 'N/A') . ')'; 
        }, $validChildren ?? []))) ?> : '',
        refsStr: <?= json_encode(!empty($references) ? implode(', ', array_values(array_filter(array_map(function($r) { return trim($r['reference_member_name'] ?? ''); }, $references)))) : 'Not Specified') ?>
    };

    if (currentProfileDownloadFormat === 'word') {
        let spouseRow = (memberData.spouse && memberData.civil !== 'Single')
            ? `<tr><th>Spouse Name</th><td>${memberData.spouse}</td></tr>` : '';
        let childrenRow = (memberData.childrenStr)
            ? `<tr><th>Children Registered</th><td>${memberData.childrenStr}</td></tr>` : '';

        const docContent = `
            <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
            <head><title>Member Record ${memberData.accNo}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 24px; color: #111; }
                h1 { color: #0f766e; border-bottom: 2px solid #0f766e; padding-bottom: 8px; margin-bottom: 4px; }
                .meta { color: #555; font-size: 12px; margin-bottom: 16px; }
                table { width: 100%; border-collapse: collapse; margin-top: 12px; }
                th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; font-size: 13px; }
                th { background: #f0fdfa; font-weight: bold; width: 28%; }
            </style></head>
            <body>
                <h1>SPACE COOPERATIVE - MEMBER PROFILE RECORD</h1>
                <div class="meta">Official System Verification & Record Export &bull; Account: ${memberData.accNo}</div>
                <table>
                    <tr><th>Account Number</th><td>${memberData.accNo}</td></tr>
                    <tr><th>Full Name</th><td>${memberData.name}</td></tr>
                    <tr><th>Account Status</th><td>${memberData.status}</td></tr>
                    <tr><th>Date of Birth</th><td>${memberData.birthday}</td></tr>
                    <tr><th>Sex</th><td>${memberData.sex}</td></tr>
                    <tr><th>Civil Status</th><td>${memberData.civil}</td></tr>
                    <tr><th>Nationality</th><td>${memberData.nationality}</td></tr>
                    <tr><th>Contact Number</th><td>${memberData.contact}</td></tr>
                    <tr><th>Account Username</th><td>${memberData.email}</td></tr>
                    <tr><th>Full Home Address</th><td>${memberData.address}</td></tr>
                    <tr><th>Occupation</th><td>${memberData.occ}</td></tr>
                    <tr><th>Employer / Company</th><td>${memberData.emp}</td></tr>
                    <tr><th>Employer Address</th><td>${memberData.empAddr}</td></tr>
                    <tr><th>Father's Name</th><td>${memberData.father}</td></tr>
                    <tr><th>Mother's Name</th><td>${memberData.mother}</td></tr>
                    ${spouseRow}
                    ${childrenRow}
                    <tr><th>Character References</th><td>${memberData.refsStr}</td></tr>
                </table>
                <p style="margin-top: 24px; font-size: 11px; color: #666;">Exported from Space Cooperative Member Portal on ${new Date().toLocaleString()}</p>
            </body></html>
        `;
        const blob = new Blob([docContent], { type: 'application/msword' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Member_Profile_${memberData.accNo}.doc`;
        link.click();
        if (typeof showToast === 'function') {
            showToast('Word Document (.doc) downloaded successfully.', 'success');
        }
    } else {
        // PDF Direct Export
        const pdfBlob = createProfilePdfBlob(memberData);
        const link = document.createElement('a');
        link.href = URL.createObjectURL(pdfBlob);
        link.download = `Member_Profile_${memberData.accNo}.pdf`;
        link.click();
        if (typeof showToast === 'function') {
            showToast('PDF Document (.pdf) downloaded successfully.', 'success');
        }
    }
}
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>