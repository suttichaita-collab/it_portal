<?php //ติดตามประวัติการส่งคืนของพนักงาน5
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "it_asset_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
$user_id = $_SESSION['user_id'] ?? 1;

// จัดการการแจ้งส่งคืนอุปกรณ์
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_item'])) {
    $detail_id = $_POST['detail_id'];
    $return_note = $_POST['return_note'];
    
    // อัปเดตสถานะการคืนในฐานข้อมูล
    $update_sql = "UPDATE requisition_details SET return_status = 'pending_return', return_note = '$return_note' WHERE detail_id = '$detail_id'";
    $conn->query($update_sql);
    
    header("Location: my_returns.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Asset Portal | อุปกรณ์ที่ต้องส่งคืน</title>
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts (Kanit) -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f1f5f9;
            color: #334155;
        }

        /* Navbar Style สไตล์เดียวกับ Dashboard และ Manage Assets */
        .navbar-custom {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        /* Card Custom Style */
        .card-custom {
            background: #ffffff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        /* Table Style */
        .table-custom {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
        }
        .table-custom thead {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
        }
        .table-custom th {
            font-weight: 500;
            padding: 16px;
            font-size: 0.9rem;
            border: none;
        }
        .table-custom td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }

        .badge-pill {
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.825rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .badge-borrowed {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        .badge-pending-return {
            background-color: #fef3c7;
            color: #d97706;
        }
        .badge-returned {
            background-color: #dcfce7;
            color: #15803d;
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <nav class="navbar navbar-dark navbar-custom py-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2" href="dashboard.php">
                <i class="bi bi-box-seam-fill"></i> IT Asset Portal | รายการอุปกรณ์ที่ต้องส่งคืน
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
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> ส่งคำขอคืนอุปกรณ์เรียบร้อยแล้ว รอเจ้าหน้าที่ตรวจสอบ
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-4 mb-5">
            <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-in-left text-primary"></i> รายการอุปกรณ์ประเภทยืม-คืน ในครอบครอง
            </h5>

            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th width="15%">รหัสอุปกรณ์</th>
                            <th width="25%">ชื่ออุปกรณ์</th>
                            <th width="12%" class="text-center">จำนวนที่ยืม</th>
                            <th width="15%">วันที่รับอุปกรณ์</th>
                            <th width="18%" class="text-center">สถานะการคืน</th>
                            <th width="15%" class="text-center">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ดึงรายการอุปกรณ์ประเภทยืม-คืนที่อนุมัติแล้ว
                        $sql = "SELECT rd.*, a.asset_code, a.asset_name, a.unit, r.created_at as borrow_date
                                FROM requisition_details rd
                                JOIN requisitions r ON rd.req_id = r.req_id
                                JOIN assets a ON rd.asset_id = a.asset_id
                                WHERE r.user_id = '$user_id' AND a.is_returnable = 1 AND r.status = 'approved'
                                ORDER BY rd.detail_id DESC";
                        $result = $conn->query($sql);

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $return_status = $row['return_status'] ?? 'borrowed';
                                $borrow_date = date("d/m/Y", strtotime($row['borrow_date']));

                                echo "<tr>";
                                echo "<td><span class='fw-bold text-primary'>{$row['asset_code']}</span></td>";
                                echo "<td class='fw-medium text-dark'>{$row['asset_name']}</td>";
                                echo "<td class='text-center fw-bold'>{$row['quantity']} {$row['unit']}</td>";
                                echo "<td class='text-secondary small'>{$borrow_date}</td>";
                                
                                echo "<td class='text-center'>";
                                if ($return_status == 'borrowed') {
                                    echo '<span class="badge-pill badge-borrowed"><i class="bi bi-laptop"></i> กำลังใช้งาน</span>';
                                } elseif ($return_status == 'pending_return') {
                                    echo '<span class="badge-pill badge-pending-return"><i class="bi bi-hourglass-split"></i> รอยืนยันการคืน</span>';
                                } else {
                                    echo '<span class="badge-pill badge-returned"><i class="bi bi-check-circle-fill"></i> คืนเรียบร้อย</span>';
                                }
                                echo "</td>";

                                echo "<td class='text-center'>";
                                if ($return_status == 'borrowed') {
                                    echo "<button type='button' class='btn btn-warning btn-sm fw-medium rounded-3' data-bs-toggle='modal' data-bs-target='#returnModal{$row['detail_id']}'>
                                            <i class='bi bi-arrow-counterclockwise me-1'></i> แจ้งส่งคืน
                                          </button>";
                                } else {
                                    echo "<span class='text-muted small'>-</span>";
                                }
                                echo "</td>";
                                echo "</tr>";

                                // Modal แจ้งคืนอุปกรณ์
                                echo "
                                <div class='modal fade' id='returnModal{$row['detail_id']}' tabindex='-1'>
                                    <div class='modal-dialog modal-dialog-centered'>
                                        <div class='modal-content border-0 shadow-lg style='border-radius: 16px;'>
                                            <form action='' method='POST'>
                                                <div class='modal-header border-0 pb-0'>
                                                    <h5 class='modal-heading fw-bold text-dark'>แจ้งส่งคืนอุปกรณ์</h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body py-3'>
                                                    <input type='hidden' name='detail_id' value='{$row['detail_id']}'>
                                                    <p class='mb-2'><strong>อุปกรณ์:</strong> {$row['asset_name']}</p>
                                                    <div class='mb-3'>
                                                        <label class='form-label small fw-medium text-secondary'>หมายเหตุ/สภาพอุปกรณ์ตอนคืน</label>
                                                        <textarea name='return_note' class='form-control rounded-3' rows='3' placeholder='ระบุสภาพสินค้า เช่น สมบูรณ์ดี, ชำรุดบางส่วน...'></textarea>
                                                    </div>
                                                </div>
                                                <div class='modal-footer border-0 pt-0'>
                                                    <button type='button' class='btn btn-light rounded-3' data-bs-dismiss='modal'>ยกเลิก</button>
                                                    <button type='submit' name='return_item' class='btn btn-primary rounded-3 px-4'>ยืนยันส่งคืน</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-5 text-muted'>
                                    <i class='bi bi-box-seam fs-1 d-block mb-2 opacity-50'></i>
                                    ไม่มีรายการอุปกรณ์ยืม-คืนในครอบครองของคุณ
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