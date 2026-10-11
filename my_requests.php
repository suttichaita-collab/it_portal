<?php //ติดตามสถานะคำขอเบิกสำหรับพนักงาน3
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "it_asset_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// ฟังก์ชันดึงรายละเอียดอุปกรณ์แบบสมบูรณ์
function getRequisitionItems($conn, $req_id) {
    // 1. ค้นหาชื่อคอลัมน์ใน requisition_details
    $det_cols = [];
    $res_dc = $conn->query("SHOW COLUMNS FROM requisition_details");
    if ($res_dc) {
        while ($c = $res_dc->fetch_assoc()) $det_cols[] = strtolower($c['Field']);
    }

    $req_fk = in_array('req_id', $det_cols) ? 'req_id' : (in_array('requisition_id', $det_cols) ? 'requisition_id' : 'request_id');
    $asset_fk = in_array('asset_id', $det_cols) ? 'asset_id' : (in_array('item_id', $det_cols) ? 'item_id' : (in_array('equipment_id', $det_cols) ? 'equipment_id' : 'product_id'));
    $qty_col = in_array('quantity', $det_cols) ? 'quantity' : (in_array('qty', $det_cols) ? 'qty' : 'amount');

    // 2. ค้นหาชื่อคอลัมน์ Primary Key และ ชื่อสินค้า ในตาราง assets
    $asset_cols = [];
    $res_ac = $conn->query("SHOW COLUMNS FROM assets");
    if ($res_ac) {
        while ($c = $res_ac->fetch_assoc()) $asset_cols[] = strtolower($c['Field']);
    }

    $asset_pk = in_array('asset_id', $asset_cols) ? 'asset_id' : (in_array('id', $asset_cols) ? 'id' : 'item_id');
    $name_col = in_array('asset_name', $asset_cols) ? 'asset_name' : (in_array('name', $asset_cols) ? 'name' : (in_array('title', $asset_cols) ? 'title' : ''));

    // 3. ดึงข้อมูล
    if (!empty($name_col)) {
        $sql = "SELECT rd.*, a.$name_col AS item_name 
                FROM requisition_details rd 
                LEFT JOIN assets a ON rd.$asset_fk = a.$asset_pk 
                WHERE rd.$req_fk = '$req_id'";
    } else {
        $sql = "SELECT rd.* FROM requisition_details rd WHERE rd.$req_fk = '$req_id'";
    }
            
    $res = $conn->query($sql);
    $items = [];

    if ($res && $res->num_rows > 0) {
        while ($d = $res->fetch_assoc()) {
            $name = !empty($d['item_name']) ? $d['item_name'] : ('อุปกรณ์ ID: ' . ($d[$asset_fk] ?? 'N/A'));
            $qty = $d[$qty_col] ?? 1;
            $items[] = htmlspecialchars($name) . " (" . $qty . " ชิ้น)";
        }
    }
    
    return !empty($items) ? implode('<br>', $items) : '<span class="text-muted">ไม่พบรายการ</span>';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Asset Portal | ติดตามสถานะคำขอเบิก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f1f5f9; color: #334155; }
        .navbar-custom { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .card-custom { background: #ffffff; border: none; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
        .table-custom { border-collapse: separate; border-spacing: 0; border-radius: 12px; overflow: hidden; }
        .table-custom thead { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; }
        .table-custom th { font-weight: 500; padding: 16px; font-size: 0.9rem; border: none; }
        .table-custom td { padding: 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .table-custom tbody tr:hover { background-color: #f8fafc; }
        .badge-pill { padding: 6px 14px; border-radius: 50px; font-weight: 500; font-size: 0.825rem; display: inline-flex; align-items: center; gap: 6px; }
        .badge-pending { background-color: #fef3c7; color: #d97706; }
        .badge-approved { background-color: #dcfce7; color: #15803d; }
        .badge-rejected { background-color: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-custom py-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2" href="dashboard.php">
                <i class="bi bi-box-seam-fill"></i> IT Asset Portal | ติดตามสถานะคำขอเบิก
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

    <div class="container-fluid px-4">
        <div class="card card-custom p-4 mb-5">
            <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary"></i> ประวัติและติดตามสถานะคำขอเบิกอุปกรณ์
            </h5>

            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th width="12%">รหัสคำขอ</th>
                            <th width="15%">วันที่ยื่นเรื่อง</th>
                            <th width="30%">รายการที่ขอเบิก</th>
                            <th width="20%">เหตุผลในการเบิก</th>
                            <th width="13%" class="text-center">สถานะปัจจุบัน</th>
                            <th width="10%">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM requisitions ORDER BY req_id DESC";
                        $result = $conn->query($sql);

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $req_id = $row['req_id'];
                                $item_text = getRequisitionItems($conn, $req_id);

                                $status = $row['status'] ?? 'pending';
                                if ($status == 'pending') {
                                    $status_badge = '<span class="badge-pill badge-pending"><i class="bi bi-hourglass-split"></i> รออนุมัติ</span>';
                                } elseif ($status == 'approved') {
                                    $status_badge = '<span class="badge-pill badge-approved"><i class="bi bi-check-circle-fill"></i> อนุมัติแล้ว</span>';
                                } else {
                                    $status_badge = '<span class="badge-pill badge-rejected"><i class="bi bi-x-circle-fill"></i> ไม่อนุมัติ</span>';
                                }

                                $req_code = "REQ-" . str_pad($req_id, 5, "0", STR_PAD_LEFT);
                                $date_val = $row['req_date'] ?? $row['request_date'] ?? $row['created_at'] ?? '';
                                $req_date = !empty($date_val) ? date("d/m/Y H:i", strtotime($date_val)) : '-';
                                $reason = !empty($row['note']) ? htmlspecialchars($row['note']) : (!empty($row['reason']) ? htmlspecialchars($row['reason']) : '-');
                                $remark = !empty($row['remark']) ? htmlspecialchars($row['remark']) : '-';

                                echo "<tr>";
                                echo "<td><span class='fw-bold text-primary'>{$req_code}</span></td>";
                                echo "<td class='text-secondary small'>{$req_date}</td>";
                                echo "<td class='fw-medium text-dark'>{$item_text}</td>";
                                echo "<td class='text-secondary small'>{$reason}</td>";
                                echo "<td class='text-center'>{$status_badge}</td>";
                                echo "<td class='text-secondary small'>{$remark}</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-5 text-muted'>
                                    <i class='bi bi-inbox fs-1 d-block mb-2 opacity-50'></i>
                                    ยังไม่มีข้อมูลคำขอเบิกในระบบ
                                  </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>