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

// ส่วนประมวลผลการยื่นเรื่องแจ้งส่งคืนอุปกรณ์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_submit_return'])) {$req_id = intval($_POST['req_id'] ?? 0);$return_reason = $conn->real_escape_string(trim($_POST['return_reason'] ?? ''));

    if ($req_id > 0) {
        // อัปเดตสถานะเป็น return_pending (รอ Admin ตรวจรับคืน)
        $req_cols = [];
        $res_rc =$conn->query("SHOW COLUMNS FROM requisitions");
        if ($res_rc) {
            while ($c =$res_rc->fetch_assoc()) $req_cols[] = strtolower($c['Field']);
        }

        @$conn->query("ALTER TABLE requisitions MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'");

        $note_text = !empty($return_reason) ? "[แจ้งส่งคืน] " . $return_reason : "[แจ้งส่งคืนอุปกรณ์]";

        if (in_array('remark', $req_cols)) {$update_sql = "UPDATE requisitions SET status = 'return_pending', remark = '$note_text' WHERE `$req_pk` = '$req_id'";
        } elseif (in_array('note', $req_cols)) {$update_sql = "UPDATE requisitions SET status = 'return_pending', note = '$note_text' WHERE `$req_pk` = '$req_id'";
        } else {
            $update_sql = "UPDATE requisitions SET status = 'return_pending' WHERE `$req_pk` = '$req_id'";
        }

        if ($conn->query($update_sql)) {
            header("Location: return_request.php?msg=success");
            exit();
        }
    }
}

// ฟังก์ชันดึงรายการอุปกรณ์สำหรับแสดงผล
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
    <title>IT Asset Portal | อุปกรณ์ที่ต้องส่งคืน</title>
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
        .badge-approved { background-color: #dcfce7; color: #15803d; }
        .badge-pending { background-color: #fef3c7; color: #b45309; }
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

    <div class="container-fluid px-4">

        <?php if (isset($_GET['msg']) &&$_GET['msg'] === 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> ยื่นเรื่องส่งคืนอุปกรณ์เรียบร้อยแล้ว กรุณานำอุปกรณ์ส่งมอบให้เจ้าหน้าที่ IT
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-left text-warning"></i> รายการอุปกรณ์ที่อยู่ในครอบครอง (ต้องส่งคืน)
            </h5>

            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th width="15%">รหัสคำขอ</th>
                            <th width="18%">วันที่อนุมัติเบิก</th>
                            <th width="35%">รายการอุปกรณ์</th>
                            <th width="15%" class="text-center">สถานะ</th>
                            <th width="17%" class="text-center">การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ดึงรายการที่อนุมัติแล้ว หรืออยู่ระหว่างรอ Admin ตรวจรับคืน
                        $sql = "SELECT * FROM requisitions WHERE status IN ('approved', 'return_pending') ORDER BY `$req_pk` DESC";
                        $result = $conn->query($sql);

                        if ($result &&$result->num_rows > 0) {
                            while ($row =$result->fetch_assoc()) {
                                $req_id =$row[$req_pk];$item_text = getRequisitionItems($conn,$req_id);
                                $req_code = "REQ-" . str_pad($req_id, 5, "0", STR_PAD_LEFT);
                                $date_val =$row['req_date'] ?? $row['request_date'] ?? $row['created_at'] ?? '';
                                $req_date = !empty($date_val) ? date("d/m/Y H:i", strtotime($date_val)) : '-';
                                $status =$row['status'];

                                echo "<tr>";
                                echo "<td><span class='fw-bold text-primary'>{$req_code}</span></td>";
                                echo "<td class='text-secondary small'>{$req_date}</td>";
                                echo "<td class='fw-medium text-dark'>{$item_text}</td>";

                                if ($status === 'approved') {
                                    echo "<td class='text-center'><span class='badge-pill badge-approved'><i class='bi bi-box-seam'></i> ถือครองอยู่</span></td>";
                                    echo "<td class='text-center'>
                                        <button class='btn btn-warning btn-sm rounded-2 px-3 fw-medium text-dark' data-bs-toggle='modal' data-bs-target='#returnRequestModal{$req_id}'>
                                            <i class='bi bi-arrow-return-left me-1'></i> แจ้งส่งคืน
                                        </button>
                                    </td>";
                                } else {
                                    echo "<td class='text-center'><span class='badge-pill badge-pending'><i class='bi bi-clock-history'></i> รอเจ้าหน้าที่ตรวจรับ</span></td>";
                                    echo "<td class='text-center'><span class='text-muted small'>ยื่นเรื่องแล้ว</span></td>";
                                }
                                echo "</tr>";

                                // Modal สำหรับกรอกเหตุผลแจ้งส่งคืน
                                echo "
                                <div class='modal fade' id='returnRequestModal{$req_id}' tabindex='-1' aria-hidden='true'>
                                    <div class='modal-dialog modal-dialog-centered'>
                                        <div class='modal-content border-0 rounded-4 shadow'>
                                            <div class='modal-header border-0 pb-0'>
                                                <h5 class='modal-title fw-bold text-dark'>แจ้งส่งคืนอุปกรณ์ {$req_code}</h5>
                                                <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                            </div>
                                            <form method='POST' action='return_request.php'>
                                                <div class='modal-body py-3'>
                                                    <input type='hidden' name='req_id' value='{$req_id}'>
                                                    <input type='hidden' name='action_submit_return' value='1'>
                                                    
                                                    <div class='mb-3'>
                                                        <label class='form-label fw-medium text-secondary'>รายการอุปกรณ์ที่ส่งคืน:</label>
                                                        <div class='p-3 bg-light rounded-3 small'>{$item_text}</div>
                                                    </div>

                                                    <div class='mb-3'>
                                                        <label class='form-label fw-medium text-dark'>เหตุผลในการส่งคืน / หมายเหตุเพิ่มเติม:</label>
                                                        <textarea name='return_reason' class='form-control rounded-3' rows='3' placeholder='เช่น ใช้งานเสร็จสิ้นตามโครงการ, ลาออก, อุปกรณ์มีปัญหา...'></textarea>
                                                    </div>
                                                </div>
                                                <div class='modal-footer border-0 pt-0'>
                                                    <button type='button' class='btn btn-light rounded-3 px-3' data-bs-dismiss='modal'>ยกเลิก</button>
                                                    <button type='submit' class='btn btn-warning text-dark rounded-3 px-4 fw-medium'>ยืนยันส่งคืน</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>
                                    <i class='bi bi-inbox fs-1 d-block mb-2 opacity-50'></i>
                                    คุณไม่มีรายการอุปกรณ์ที่ต้องส่งคืนในขณะนี้
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
