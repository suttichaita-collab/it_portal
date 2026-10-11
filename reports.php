<?php
// เชื่อมต่อฐานข้อมูล5
$conn = new mysqli("localhost", "root", "", "it_asset_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// --- 1. ค้นหาคอลัมน์ของตาราง assets แบบละเอียด ---
$asset_cols = [];
$res_ac =$conn->query("SHOW COLUMNS FROM assets");
if ($res_ac) {
    while ($c =$res_ac->fetch_assoc()) {
        $asset_cols[] = strtolower($c['Field']);
    }
}

// หา Primary Key และ Asset Name
$asset_pk = in_array('asset_id', $asset_cols) ? 'asset_id' : (in_array('id',$asset_cols) ? 'id' : $asset_cols[0]);$name_col = in_array('asset_name', $asset_cols) ? 'asset_name' : (in_array('name',$asset_cols) ? 'name' : (in_array('title', $asset_cols) ? 'title' :$asset_cols[1]));

// ไล่หาคอลัมน์ที่เป็นจำนวนสต็อก
$possible_stock_cols = ['quantity', 'stock', 'qty', 'amount', 'asset_qty', 'total_qty', 'item_qty', 'balance'];$stock_col = '';
foreach ($possible_stock_cols as$p_col) {
    if (in_array($p_col,$asset_cols)) {
        $stock_col =$p_col;
        break;
    }
}

// ถ้ายังหาไม่เจอ ให้เลือกคอลัมน์ที่เป็นตัวเลข (INT/BIGINT) คอลัมน์แรกที่ไม่ใช่ PK
if (empty($stock_col)) {
    $res_type =$conn->query("SHOW FULL COLUMNS FROM assets");
    if ($res_type) {
        while ($fc =$res_type->fetch_assoc()) {
            $f_name = strtolower($fc['Field']);
            $f_type = strtolower($fc['Type']);
            if ($f_name !== $asset_pk && (strpos($f_type, 'int') !== false)) {
                $stock_col =$f_name;
                break;
            }
        }
    }
}
if (empty($stock_col))$stock_col = 'quantity'; // Default fallback

// --- 2. คำนวณข้อมูลสถิติ ---

// ชนิดอุปกรณ์ทั้งหมด
$total_types = 0;
$res_types =$conn->query("SELECT COUNT(*) AS total FROM assets");
if ($res_types) $total_types =$res_types->fetch_assoc()['total'] ?? 0;

// สต็อกรวมในคลัง (นำค่า SUM มาเป็น INT)
$total_stock = 0;
$res_stock =$conn->query("SELECT SUM(CAST(`$stock_col` AS SIGNED)) AS total FROM assets");
if ($res_stock) {
    $row_s =$res_stock->fetch_assoc();
    $total_stock = intval($row_s['total'] ?? 0);
}

// สินค้าสต็อกต่ำกว่าเกณฑ์ (ตั้งเกณฑ์ไว้ที่ <= 5 ชิ้น)
$low_stock_limit = 5;
$low_stock_count = 0;
$res_low =$conn->query("SELECT COUNT(*) AS total FROM assets WHERE CAST(`$stock_col` AS SIGNED) <= $low_stock_limit");
if ($res_low) {
    $low_stock_count = intval($res_low->fetch_assoc()['total'] ?? 0);
}

// --- 3. ค้นหาคอลัมน์ของตาราง requisition_details ---
$det_cols = [];
$res_dc =$conn->query("SHOW COLUMNS FROM requisition_details");
if ($res_dc) {
    while ($c =$res_dc->fetch_assoc()) {
        $det_cols[] = strtolower($c['Field']);
    }
}
$asset_fk = in_array('asset_id', $det_cols) ? 'asset_id' : (in_array('item_id',$det_cols) ? 'item_id' : (in_array('equipment_id', $det_cols) ? 'equipment_id' :$det_cols[0]));
$qty_col = in_array('quantity',$det_cols) ? 'quantity' : (in_array('qty', $det_cols) ? 'qty' : (in_array('amount',$det_cols) ? 'amount' : 'quantity'));
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Asset Portal | รายงานและสถิติ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f1f5f9; color: #334155; }
        .navbar-custom { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
        .card-custom { background: #ffffff; border: none; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .icon-box { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .rank-badge { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.85rem; }
        .rank-1 { background-color: #ef4444; color: #fff; }
        .rank-2 { background-color: #f59e0b; color: #fff; }
        .rank-3 { background-color: #06b6d4; color: #fff; }
        .rank-other { background-color: #e2e8f0; color: #475569; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-custom py-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2" href="dashboard.php">
                <i class="bi bi-box-seam-fill"></i> IT Asset Management System
            </a>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-light btn-sm fw-medium rounded-3 px-3 align-self-center">
                    <i class="bi bi-arrow-left me-1"></i> กลับ Dashboard
                </a>
                <a href="logout.php" class="btn btn-danger btn-sm fw-medium rounded-3 px-3 align-self-center">
                    ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 mb-5">

        <!-- สรุปตัวเลข 3 การ์ดหลัก -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-custom p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-medium">ชนิดอุปกรณ์ทั้งหมด</span>
                            <h2 class="fw-bold text-dark my-1"><?= number_format($total_types) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></h2>
                        </div>
                        <div class="icon-box bg-primary-subtle text-primary">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-medium">สต็อกรวมในคลัง</span>
                            <h2 class="fw-bold text-dark my-1"><?= number_format($total_stock) ?> <span class="fs-6 fw-normal text-muted">ชิ้น</span></h2>
                        </div>
                        <div class="icon-box bg-success-subtle text-success">
                            <i class="bi bi-layers-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-custom p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-medium">สินค้าสต็อกต่ำกว่าเกณฑ์ (≤ 5 ชิ้น)</span>
                            <h2 class="fw-bold <?= $low_stock_count > 0 ? 'text-danger' : 'text-dark' ?> my-1"><?= number_format($low_stock_count) ?> <span class="fs-6 fw-normal text-muted">รายการ</span></h2>
                        </div>
                        <div class="icon-box bg-danger-subtle text-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section สถิติ และ Stock Alert -->
        <div class="row g-4">
            <!-- Top 5 อุปกรณ์ถูกเบิกบ่อยที่สุด -->
            <div class="col-md-6">
                <div class="card card-custom p-4 h-100">
                    <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-fire text-danger"></i> Top 5 อุปกรณ์ที่ถูกเบิกบ่อยที่สุด
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead>
                                <tr class="text-secondary border-bottom small">
                                    <th width="15%">อันดับ</th>
                                    <th width="60%">ชื่ออุปกรณ์</th>
                                    <th width="25%" class="text-end">จำนวนที่ถูกเบิก</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_top = "SELECT a.$name_col AS item_name, SUM(CAST(rd.$qty_col AS SIGNED)) AS total_borrowed 
                                            FROM requisition_details rd 
                                            LEFT JOIN assets a ON rd.$asset_fk = a.$asset_pk 
                                            GROUP BY rd.$asset_fk 
                                            ORDER BY total_borrowed DESC 
                                            LIMIT 5";
                                $res_top = $conn->query($sql_top);

                                if ($res_top && $res_top->num_rows > 0) {$rank = 1;
                                    while ($row = $res_top->fetch_assoc()) {$rank_class = "rank-other";
                                        if ($rank === 1)$rank_class = "rank-1";
                                        if ($rank === 2)$rank_class = "rank-2";
                                        if ($rank === 3)$rank_class = "rank-3";

                                        echo "<tr class='border-bottom'>";
                                        echo "<td><span class='rank-badge {$rank_class}'>{$rank}</span></td>";
                                        echo "<td class='fw-medium text-dark'>" . htmlspecialchars($row['item_name'] ?? 'ไม่ระบุชื่อ') . "</td>";
                                        echo "<td class='text-end fw-bold text-primary'>" . number_format($row['total_borrowed']) . " ชิ้น</td>";
                                        echo "</tr>";
                                        $rank++;
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center py-4 text-muted'>ยังไม่มีประวัติการเบิกอุปกรณ์</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Stock Alert อุปกรณ์ใกล้หมด -->
            <div class="col-md-6">
                <div class="card card-custom p-4 h-100">
                    <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-circle-fill text-warning"></i> รายการสินค้าใกล้หมด (Stock Alert)
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead>
                                <tr class="text-secondary border-bottom small">
                                    <th width="45%">ชื่ออุปกรณ์</th>
                                    <th width="25%" class="text-center">คงเหลือ</th>
                                    <th width="30%" class="text-center">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_alert = "SELECT * FROM assets WHERE CAST(`$stock_col` AS SIGNED) <= $low_stock_limit ORDER BY CAST(`$stock_col` AS SIGNED) ASC";
                                $res_alert = $conn->query($sql_alert);

                                if ($res_alert &&$res_alert->num_rows > 0) {
                                    while ($row = $res_alert->fetch_assoc()) {$qty = intval($row[$stock_col]);
                                        $badge =$qty == 0 
                                            ? "<span class='badge bg-danger px-3 py-2 rounded-pill'>หมดสต็อก</span>" 
                                            : "<span class='badge bg-warning text-dark px-3 py-2 rounded-pill'>ใกล้หมด</span>";

                                        echo "<tr class='border-bottom'>";
                                        echo "<td class='fw-medium text-dark'>" . htmlspecialchars($row[$name_col] ?? '-') . "</td>";
                                        echo "<td class='text-center fw-bold " . ($qty == 0 ? 'text-danger' : 'text-warning-emphasis') . "'>{$qty} ชิ้น</td>";
                                        echo "<td class='text-center'>{$badge}</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center py-5 text-muted'>
                                            <i class='bi bi-check-circle fs-1 text-success d-block mb-2 opacity-75'></i>
                                            สินค้าทุกรายการอยู่ในเกณฑ์ปกติ (ยังไม่มีรายการสต็อกต่ำกว่า 5 ชิ้น)
                                          </td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>