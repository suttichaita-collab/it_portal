<?php
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "it_asset_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// ฟังก์ชันค้นหา Primary Key ของตาราง requisitions
function getReqPrimaryKey($conn) {
    $res =$conn->query("SHOW COLUMNS FROM requisitions");
    if ($res) {
        while ($col =$res->fetch_assoc()) {
            if ($col['Key'] === 'PRI') return$col['Field'];
        }
    }
    return 'req_id';
}

$req_pk = getReqPrimaryKey($conn);

// ส่วนอนุมัติ / ไม่อนุมัติ
if (isset($_GET['action']) && isset($_GET['id'])) {
    $req_id = intval($_GET['id']);
    $action =$_GET['action']; // 'approve' หรือ 'reject'

    if ($action === 'approve') {
        // อัปเดตสถานะเป็น approved
        $conn->query("UPDATE requisitions SET status = 'approved' WHERE `$req_pk` = '$req_id'");

        // ตัดสต็อกสินค้าในตาราง assets
        $det_cols = [];
        $res_dc =$conn->query("SHOW COLUMNS FROM requisition_details");
        if ($res_dc) {
            while ($c =$res_dc->fetch_assoc()) $det_cols[] = strtolower($c['Field']);
        }
        $req_fk = in_array('req_id', $det_cols) ? 'req_id' : (in_array('requisition_id',$det_cols) ? 'requisition_id' : 'id');
        $asset_fk = in_array('asset_id',$det_cols) ? 'asset_id' : (in_array('item_id', $det_cols) ? 'item_id' : 'equipment_id');$qty_col = in_array('quantity', $det_cols) ? 'quantity' : (in_array('qty',$det_cols) ? 'qty' : 'amount');

        $items_res =$conn->query("SELECT * FROM requisition_details WHERE `$req_fk` = '$req_id'");
        if ($items_res &&$items_res->num_rows > 0) {
            while ($item =$items_res->fetch_assoc()) {
                $asset_id =$item[$asset_fk];$qty = intval($item[$qty_col] ?? 1);

                $asset_cols = [];
                $res_ac =$conn->query("SHOW COLUMNS FROM assets");
                if ($res_ac) {
                    while ($c =$res_ac->fetch_assoc()) $asset_cols[] = strtolower($c['Field']);
                }
                $asset_pk = in_array('asset_id', $asset_cols) ? 'asset_id' : 'id';$stock_col = in_array('quantity', $asset_cols) ? 'quantity' : (in_array('stock',$asset_cols) ? 'stock' : 'qty');

                $conn->query("UPDATE assets SET `$stock_col` = `$stock_col` - $qty WHERE `$asset_pk` = '$asset_id' AND `$stock_col` >= $qty");
            }
        }
    } elseif ($action === 'reject') {$conn->query("UPDATE requisitions SET status = 'rejected' WHERE `$req_pk` = '$req_id'");
    }

    header("Location: manage_requests.php?msg=updated");
    exit();
}

// ฟังก์ชันดึงรายการอุปกรณ์
function getRequisitionItems($conn, $req_id) {$det_cols = [];
    $res_dc =$conn->query("SHOW COLUMNS FROM requisition_details");
    if ($res_dc) {
        while ($c =$res_dc->fetch_assoc()) $det_cols[] = strtolower($c['Field']);
    }

    $req_fk = in_array('req_id', $det_cols) ? 'req_id' : (in_array('requisition_id',$det_cols) ? 'requisition_id' : 'id');
    $asset_fk = in_array('asset_id',$det_cols) ? 'asset_id' : (in_array('item_id', $det_cols) ? 'item_id' : 'equipment_id');$qty_col = in_array('quantity', $det_cols) ? 'quantity' : (in_array('qty',$det_cols) ? 'qty' : 'amount');

    $asset_cols = [];
    $res_ac =$conn->query("SHOW COLUMNS FROM assets");
    if ($res_ac) {
        while ($c =$res_ac->fetch_assoc()) $asset_cols[] = strtolower($c['Field']);
    }

    $asset_pk = in_array('asset_id', $asset_cols) ? 'asset_id' : 'id';$name_col = in_array('asset_name', $asset_cols) ? 'asset_name' : (in_array('name',$asset_cols) ? 'name' : 'title');

    $sql = "SELECT rd.*, a.$name_col AS item_name 
            FROM requisition_details rd 
            LEFT JOIN assets a ON rd.$asset_fk = a.$asset_pk 
            WHERE rd.$req_fk = '$req_id'";
            
    $res =$conn->query($sql);$items = [];

    if ($res &&$res->num_rows > 0) {
        while ($d =$res->fetch_assoc()) {
            $name = !empty($d['item_name']) ? $d['item_name'] : ('อุปกรณ์ ID: ' . $d[$asset_fk]);$qty = $d[$qty_col] ?? 1;
            $items[] = "• " . htmlspecialchars($name) . " (<b>" . $qty . "</b> ชิ้น)";
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
    <title>IT Asset Portal | อนุมัติและจ่ายของ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f1f5f9; color: #334155; }
        .navbar-custom { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
        .card-custom { background: #ffffff; border: none; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .table-custom { border-collapse: separate; border-spacing: 0; border-radius: 12px; overflow: hidden; }
        .table-custom thead { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; }
        .table-custom th { font-weight: 500; padding: 16px; font-size: 0.9rem; border: none; }
        .table-custom td { padding: 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .badge-pill { padding: 6px 14px; border-radius: 50px; font-weight: 500; font-size: 0.825rem; display: inline-flex; align-items: center; gap: 6px; }
        .badge-pending { background-color: #fef3c7; color: #b45309; }
        .badge-approved { background-color: #dcfce7; color: #15803d; }
        .badge-rejected { background-color: #fee2e2; color: #991b1b; }
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

        <?php if (isset($_GET['msg']) &&$_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> อัปเดตสถานะคำขอเบิกเรียบร้อยแล้ว
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-4">
            <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-check-square-fill text-primary"></i> รายการคำขอเบิกอุปกรณ์ทั้งหมด
            </h5>

            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th width="12%">รหัสคำขอ</th>
                            <th width="15%">วันที่ยื่นเรื่อง</th>
                            <th width="32%">รายการอุปกรณ์ที่ขอเบิก</th>
                            <th width="15%">เหตุผล</th>
                            <th width="12%" class="text-center">สถานะ</th>
                            <th width="14%" class="text-center">การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM requisitions ORDER BY `$req_pk` DESC";
                        $result = $conn->query($sql);

                        if ($result &&$result->num_rows > 0) {
                            while ($row =$result->fetch_assoc()) {
                                $req_id =$row[$req_pk];$item_text = getRequisitionItems($conn,$req_id);
                                $req_code = "REQ-" . str_pad($req_id, 5, "0", STR_PAD_LEFT);
                                $date_val =$row['req_date'] ?? $row['request_date'] ?? $row['created_at'] ?? '';
                                $req_date = !empty($date_val) ? date("d/m/Y H:i", strtotime($date_val)) : '-';$reason = !empty($row['note']) ? htmlspecialchars($row['note']) : (!empty($row['reason']) ? htmlspecialchars($row['reason']) : '-');
                                $status =$row['status'];

                                echo "<tr>";
                                echo "<td><span class='fw-bold text-primary'>{$req_code}</span></td>";
                                echo "<td class='text-secondary small'>{$req_date}</td>";
                                echo "<td class='fw-medium text-dark'>{$item_text}</td>";
                                echo "<td class='text-secondary small'>{$reason}</td>";

                                if ($status === 'pending') {
                                    echo "<td class='text-center'><span class='badge-pill badge-pending'><i class='bi bi-clock-history'></i> รออนุมัติ</span></td>";
                                    echo "<td class='text-center'>
                                            <a href='manage_requests.php?action=approve&id={$req_id}' class='btn btn-success btn-sm rounded-2 me-1' onclick='return confirm(\"ยืนยันการอนุมัติรายการนี้?\")'>
                                                <i class='bi bi-check-lg'></i> อนุมัติ
                                            </a>
                                            <a href='manage_requests.php?action=reject&id={$req_id}' class='btn btn-outline-danger btn-sm rounded-2' onclick='return confirm(\"ยืนยันปฏิเสธรายการนี้?\")'>
                                                <i class='bi bi-x-lg'></i>
                                            </a>
                                          </td>";
                                } elseif ($status === 'approved') {
                                    echo "<td class='text-center'><span class='badge-pill badge-approved'><i class='bi bi-check-circle-fill'></i> อนุมัติแล้ว</span></td>";
                                    echo "<td class='text-center'>
                                            <a href='print_receipt.php?id={$req_id}' target='_blank' class='btn btn-outline-primary btn-sm rounded-pill px-3'>
                                                <i class='bi bi-printer-fill me-1'></i> พิมพ์ใบเบิก
                                            </a>
                                          </td>";
                                } elseif ($status === 'rejected') {
                                    echo "<td class='text-center'><span class='badge-pill badge-rejected'><i class='bi bi-x-circle-fill'></i> ไม่อนุมัติ</span></td>";
                                    echo "<td class='text-center'><span class='text-muted small'>ดำเนินการแล้ว</span></td>";
                                } else {
                                    echo "<td class='text-center'><span class='badge-pill badge-returned'><i class='bi bi-arrow-return-left'></i> คืนแล้ว</span></td>";
                                    echo "<td class='text-center'>
                                            <a href='print_receipt.php?id={$req_id}' target='_blank' class='btn btn-outline-secondary btn-sm rounded-pill px-3'>
                                                <i class='bi bi-printer-fill me-1'></i> ใบส่งคืน
                                            </a>
                                          </td>";
                                }

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-5 text-muted'>
                                    <i class='bi bi-inbox fs-1 d-block mb-2 opacity-50'></i>
                                    ยังไม่มีรายการคำขอเบิกในขณะนี้
                                  </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>