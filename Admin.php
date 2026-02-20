<?php
session_start();
include "db.php";

// ─── AUTH GUARD ───
if (empty($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// ─── LOGOUT ───
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

// ─── HELPERS: fetch all rows ───
function getAllRows($conn) {
    $res = mysqli_query($conn, "SELECT * FROM registrations ORDER BY id ASC");
    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
    return $rows;
}

function getStats($conn) {
    $total_teams   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM registrations"))[0];
    $total_members = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(team_size) FROM registrations"))[0] ?? 0;
    $veg           = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM registrations WHERE p1_food='Vegetarian' OR p2_food='Vegetarian' OR p3_food='Vegetarian' OR p4_food='Vegetarian'"))[0];
    $nonveg        = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM registrations WHERE p1_food='Non-Vegetarian' OR p2_food='Non-Vegetarian' OR p3_food='Non-Vegetarian' OR p4_food='Non-Vegetarian'"))[0];
    return compact('total_teams','total_members','veg','nonveg');
}

// ─── EXPORT CSV ───
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="techblaze3_registrations_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Ref ID','Team','College','Size','P1 Name','P1 Phone','P1 Email','P1 Food','P2 Name','P2 Phone','P2 Email','P2 Food','P3 Name','P3 Phone','P3 Email','P3 Food','P4 Name','P4 Phone','P4 Email','P4 Food','Medical','Registered At']);
    foreach (getAllRows($conn) as $row) {
        fputcsv($out, [
            $row['ref_id']??'', $row['team']??'', $row['college']??'', $row['team_size']??'',
            $row['p1']??'', $row['p1_phone']??'', $row['p1_email']??'', $row['p1_food']??'',
            $row['p2']??'', $row['p2_phone']??'', $row['p2_email']??'', $row['p2_food']??'',
            $row['p3']??'', $row['p3_phone']??'', $row['p3_email']??'', $row['p3_food']??'',
            $row['p4']??'', $row['p4_phone']??'', $row['p4_email']??'', $row['p4_food']??'',
            $row['medical']??'', $row['created_at']??''
        ]);
    }
    fclose($out);
    exit();
}

// ─── EXPORT XLSX ───
if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    $rows  = getAllRows($conn);
    $stats = getStats($conn);
    $payload = json_encode([
        'rows'        => $rows,
        'stats'       => $stats,
        'exported_at' => date('Y-m-d H:i:s')
    ]);
    $tmpInput  = sys_get_temp_dir() . '/tb3_xlsx_input.json';
    $tmpOutput = sys_get_temp_dir() . '/techblaze_export.xlsx';
    file_put_contents($tmpInput, $payload);
    $script = __DIR__ . '/export_xlsx.py';
    $out = shell_exec("python \"$script\" \"$tmpInput\" 2>&1");
    if (file_exists($tmpOutput)) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="techblaze3_registrations_' . date('Ymd_His') . '.xlsx"');
        readfile($tmpOutput);
        unlink($tmpOutput);
        unlink($tmpInput);
        exit();
    } else {
        die("Excel export failed. Error: " . $out);
    }
}

// ─── EXPORT DOCX ───
if (isset($_GET['export']) && $_GET['export'] === 'docx') {
    $rows  = getAllRows($conn);
    $stats = getStats($conn);
    $payload = json_encode([
        'rows'        => $rows,
        'stats'       => $stats,
        'exported_at' => date('Y-m-d H:i:s')
    ]);
    $tmpInput  = sys_get_temp_dir() . '/tb3_docx_input.json';
    $tmpOutput = sys_get_temp_dir() . '/techblaze_export.docx';
    file_put_contents($tmpInput, $payload);
    $script = __DIR__ . '/export_docx.js';
    $out = shell_exec("\"C:\\Program Files\\nodejs\\node.exe\" \"$script\" \"$tmpInput\" 2>&1");
    if (file_exists($tmpOutput)) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="techblaze3_registrations_' . date('Ymd_His') . '.docx"');
        readfile($tmpOutput);
        unlink($tmpOutput);
        unlink($tmpInput);
        exit();
    } else {
        die("Word export failed. Error: " . $out);
    }
}

// ─── FETCH STATS ───
$total_teams   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM registrations"))[0];
$total_members = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(team_size) FROM registrations"))[0] ?? 0;
$veg_count     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM registrations WHERE p1_food='Vegetarian' OR p2_food='Vegetarian' OR p3_food='Vegetarian' OR p4_food='Vegetarian'"))[0];
$nonveg_count  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM registrations WHERE p1_food='Non-Vegetarian' OR p2_food='Non-Vegetarian' OR p3_food='Non-Vegetarian' OR p4_food='Non-Vegetarian'"))[0];

// ─── SEARCH ───
$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$where  = $search ? "WHERE team LIKE '%$search%' OR college LIKE '%$search%' OR p1 LIKE '%$search%' OR ref_id LIKE '%$search%'" : '';
$registrations = mysqli_query($conn, "SELECT * FROM registrations $where ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Tech Blaze 3.0</title>
  <link rel="stylesheet" href="style.css">
  <style>
    * { box-sizing: border-box; }
    body { font-family: 'DM Sans', sans-serif; }
    .admin-wrap { max-width: 1400px; margin: 0 auto; padding: 28px 24px 60px; }

    .admin-topbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 32px; }
    .admin-brand { display: flex; align-items: center; gap: 12px; }
    .admin-logo { width: 40px; height: 40px; background: linear-gradient(135deg,#6c63ff,#a78bfa); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; color: #fff; }
    .admin-title { font-size: 18px; font-weight: 700; color: #fff; }
    .admin-sub { font-size: 12px; color: rgba(255,255,255,0.4); }
    .admin-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

    .export-group { display: flex; gap: 8px; align-items: center; }
    .export-label { font-size: 11px; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 0.07em; margin-right: 2px; }

    .btn-export-csv {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 9px 16px;
      background: rgba(102,187,106,0.15);
      border: 1px solid rgba(102,187,106,0.3);
      color: #66bb6a; border-radius: 10px;
      font-size: 13px; font-weight: 600; text-decoration: none;
      transition: background 0.2s;
    }
    .btn-export-csv:hover { background: rgba(102,187,106,0.28); }

    .btn-export-xlsx {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 9px 16px;
      background: rgba(33,150,83,0.15);
      border: 1px solid rgba(33,150,83,0.35);
      color: #2ecc71; border-radius: 10px;
      font-size: 13px; font-weight: 600; text-decoration: none;
      transition: background 0.2s;
    }
    .btn-export-xlsx:hover { background: rgba(33,150,83,0.28); }

    .btn-export-docx {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 9px 16px;
      background: rgba(41,128,185,0.15);
      border: 1px solid rgba(41,128,185,0.35);
      color: #5dade2; border-radius: 10px;
      font-size: 13px; font-weight: 600; text-decoration: none;
      transition: background 0.2s;
    }
    .btn-export-docx:hover { background: rgba(41,128,185,0.28); }

    .btn-logout { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; background: rgba(239,83,80,0.12); border: 1px solid rgba(239,83,80,0.25); color: #ef5350; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
    .btn-logout:hover { background: rgba(239,83,80,0.22); }

    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 28px; }
    .stat-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 18px 20px; }
    .stat-card-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.35); margin-bottom: 6px; }
    .stat-card-value { font-size: 28px; font-weight: 800; color: #fff; line-height: 1; }
    .stat-card-value.green { color: #66bb6a; }
    .stat-card-value.blue  { color: #42a5f5; }
    .stat-card-value.orange{ color: #ffa726; }
    .stat-card-value.purple{ color: #ab47bc; }

    .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
    .search-bar input { flex: 1; padding: 10px 16px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; color: #fff; font-size: 14px; font-family: inherit; outline: none; }
    .search-bar input:focus { border-color: #6c63ff; }
    .search-bar button { padding: 10px 18px; background: #6c63ff; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .search-bar a { padding: 10px 14px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.5); border-radius: 10px; text-decoration: none; font-size: 13px; display: flex; align-items: center; }

    .table-wrap { overflow-x: auto; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; }
    table { width: 100%; border-collapse: collapse; min-width: 900px; }
    thead th { padding: 13px 16px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: rgba(255,255,255,0.35); border-bottom: 1px solid rgba(255,255,255,0.07); white-space: nowrap; }
    tbody td { padding: 13px 16px; font-size: 13px; color: rgba(255,255,255,0.8); border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: top; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: rgba(255,255,255,0.03); }
    .ref-badge { display: inline-block; padding: 3px 10px; background: rgba(108,99,255,0.2); border: 1px solid rgba(108,99,255,0.35); color: #a78bfa; border-radius: 6px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .team-name { font-weight: 600; color: #fff; }
    .member-list { line-height: 1.8; }
    .member-chip { display: inline-block; background: rgba(255,255,255,0.07); border-radius: 5px; padding: 1px 7px; font-size: 12px; margin-bottom: 2px; }
    .food-veg { color: #66bb6a; }
    .food-nonveg { color: #ffa726; }
    .no-results { text-align: center; padding: 50px 20px; color: rgba(255,255,255,0.3); font-size: 15px; }
  </style>
</head>
<body>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>

  <div class="admin-wrap">

    <!-- Top Bar -->
    <div class="admin-topbar">
      <div class="admin-brand">
        <div class="admin-logo">CT</div>
        <div>
          <div class="admin-title">Admin Panel</div>
          <div class="admin-sub">Tech Blaze 3.0 — Registrations</div>
        </div>
      </div>
      <div class="admin-actions">
        <div class="export-group">
          <span class="export-label">Export:</span>
          <a href="?export=csv"  class="btn-export-csv">  📄 CSV</a>
          <a href="?export=xlsx" class="btn-export-xlsx"> 🟢 Excel</a>
          <a href="?export=docx" class="btn-export-docx"> 🔵 Word</a>
        </div>
        <a href="?logout=1" class="btn-logout">⎋ Logout</a>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card-label">Total Teams</div>
        <div class="stat-card-value blue"><?= $total_teams ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">Total Participants</div>
        <div class="stat-card-value purple"><?= $total_members ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">Vegetarian</div>
        <div class="stat-card-value green"><?= $veg_count ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-card-label">Non-Vegetarian</div>
        <div class="stat-card-value orange"><?= $nonveg_count ?></div>
      </div>
    </div>

    <!-- Search -->
    <form class="search-bar" method="GET">
      <input type="text" name="q" placeholder="Search by team name, college, leader name, or ref ID…" value="<?= htmlspecialchars($search) ?>">
      <button type="submit">Search</button>
      <?php if ($search): ?><a href="Admin.php">✕ Clear</a><?php endif; ?>
    </form>

    <!-- Table -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Ref ID</th>
            <th>Team Name</th>
            <th>College</th>
            <th>Size</th>
            <th>Members</th>
            <th>Medical Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $count = 0;
          while ($row = mysqli_fetch_assoc($registrations)):
            $count++;
            $members = [];
            for ($i = 1; $i <= 4; $i++) {
              if (!empty($row["p$i"])) {
                $food = $row["p{$i}_food"];
                $foodClass = ($food === 'Vegetarian') ? 'food-veg' : 'food-nonveg';
                $foodIcon  = ($food === 'Vegetarian') ? '🥦' : '🍗';
                $members[] = "<span class='member-chip'>{$row["p$i"]} <span class='$foodClass'>$foodIcon</span></span>";
              }
            }
          ?>
          <tr>
            <td><span class="ref-badge"><?= htmlspecialchars($row['ref_id'] ?? 'N/A') ?></span></td>
            <td class="team-name"><?= htmlspecialchars($row['team']) ?></td>
            <td><?= htmlspecialchars($row['college']) ?></td>
            <td style="text-align:center; font-weight:700;"><?= (int)$row['team_size'] ?></td>
            <td class="member-list"><?= implode('<br>', $members) ?></td>
            <td style="font-size:12px; color:rgba(255,255,255,0.5); max-width:180px;">
              <?= $row['medical'] ? htmlspecialchars($row['medical']) : '<span style="opacity:0.3">—</span>' ?>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php if ($count === 0): ?>
          <tr><td colspan="6" class="no-results"><?= $search ? "No results for \"" . htmlspecialchars($search) . "\"" : "No registrations yet." ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($count > 0): ?>
    <p style="text-align:right; margin-top:12px; font-size:12px; color:rgba(255,255,255,0.3);">
      Showing <?= $count ?> registration<?= $count !== 1 ? 's' : '' ?><?= $search ? " for \"" . htmlspecialchars($search) . "\"" : '' ?>
    </p>
    <?php endif; ?>
  </div>
</body>
</html>