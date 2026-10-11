<?php //จัดการหมวดหมู่และทะเบียนอุปกรณ์1
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "it_asset_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ระบบเพิ่มข้อมูลอุปกรณ์เข้าคลัง
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_asset'])) {
    $asset_code = $_POST['asset_code'];
    $asset_name = $_POST['asset_name'];
    $category_id = $_POST['category_id'];
    $is_returnable = $_POST['is_returnable'];
    $stock_qty = $_POST['stock_qty'];
    $min_alert_qty = $_POST['min_alert_qty'];
    $unit = $_POST['unit'];

    $sql = "INSERT INTO assets (asset_code, asset_name, category_id, is_returnable, stock_qty, min_alert_qty, unit) 
            VALUES ('$asset_code', '$asset_name', '$category_id', '$is_returnable', '$stock_qty', '$min_alert_qty', '$unit')";
    $conn->query($sql);
    header("Location: manage_assets.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Asset Portal | จัดการทะเบียนอุปกรณ์</title>
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

        /* Navbar Style */
        .navbar-custom {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        /* Card Container Style */
        .card-custom {
            background: #ffffff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            transition: transform 0.2s ease;
        }

        /* Form Inputs */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
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
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-custom tbody tr {
            transition: background-color 0.2s ease;
        }
        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Status Badges */
        .badge-pill {
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.825rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .badge-normal {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-warning-custom {
            background-color: #fee2e2;
            color: #b91c1c;
            animation: pulse 2s infinite;
        }
        .badge-category {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .badge-type-borrow {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        .badge-type-consume {
            background-color: #f3e8ff;
            color: #6b21a8;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <nav class="navbar navbar-dark navbar-custom py-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2" href="#">
                <i class="bi bi-box-seam-fill"></i> IT Asset Portal | จัดการทะเบียนอุปกรณ์
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
        
        <!-- Form Add Asset -->
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle-fill"></i> เพิ่มอุปกรณ์ใหม่เข้าคลังสินค้า
            </h5>
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="add_asset" value="1">
                
                <div class="col-md-2">
                    <label class="form-label small fw-medium text-secondary">รหัสอุปกรณ์ (Asset Code)</label>
                    <input type="text" name="asset_code" class="form-control" placeholder="เช่น AST-001" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium text-secondary">ชื่ออุปกรณ์ *</label>
                    <input type="text" name="asset_name" class="form-control" placeholder="เช่น Wireless Mouse" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-medium text-secondary">หมวดหมู่ *</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <option value="1">คอมพิวเตอร์และโน้ตบุ๊ก</option>
                        <option value="2">อุปกรณ์สิ้นเปลือง/เครื่องเขียน</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-medium text-secondary">ประเภทการเบิก</label>
                    <select name="is_returnable" class="form-select">
                        <option value="0">ใช้แล้วหมดไป (ไม่ต้องคืน)</option>
                        <option value="1">ยืม-คืน</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium text-secondary">หน่วยนับ</label>
                    <input type="text" name="unit" class="form-control" placeholder="ชิ้น / เครื่อง / ตัว" value="ชิ้น">
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-medium text-secondary">จำนวนสต็อกแรกรับ</label>
                    <input type="number" name="stock_qty" class="form-control" value="10" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-medium text-secondary">เกณฑ์แจ้งเตือนสต็อกต่ำ (Min)</label>
                    <input type="number" name="min_alert_qty" class="form-control" value="5" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-medium rounded-3 shadow-sm">
                        <i class="bi bi-save me-1"></i> บันทึกอุปกรณ์
                    </button>
                </div>
            </form>
        </div>

        <!-- Table Display Assets -->
        <div class="card card-custom p-4 mb-5">
            <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-list-stars text-primary"></i> รายการอุปกรณ์ในคลังปัจจุบัน
            </h5>
            
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="12%">รหัสอุปกรณ์</th>
                            <th width="25%">ชื่ออุปกรณ์</th>
                            <th width="15%">หมวดหมู่</th>
                            <th width="12%">ประเภท</th>
                            <th width="10%" class="text-center">จำนวนเหลือ</th>
                            <th width="10%" class="text-center">เกณฑ์ต่ำสุด</th>
                            <th width="11%" class="text-center">สถานะสต็อก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT a.*, c.category_name 
                                FROM assets a 
                                LEFT JOIN categories c ON a.category_id = c.category_id 
                                ORDER BY a.asset_id DESC";
                        $result = $conn->query($sql);

                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                // ตรวจสอบสถานะสต็อก
                                $is_low = $row['stock_qty'] <= $row['min_alert_qty'];
                                $status_badge = $is_low 
                                    ? '<span class="badge-pill badge-warning-custom"><i class="bi bi-exclamation-triangle-fill"></i> สต็อกต่ำกว่าเกณฑ์</span>' 
                                    : '<span class="badge-pill badge-normal"><i class="bi bi-check-circle-fill"></i> Normally</span>';
                                
                                // ตรวจสอบหมวดหมู่
                                $cat_name = !empty($row['category_name']) ? $row['category_name'] : 'ทั่วไป';
                                
                                // ประเภทการคืน
                                $type_badge = ($row['is_returnable'] == 1) 
                                    ? '<span class="badge-pill badge-type-borrow"><i class="bi bi-arrow-repeat"></i> ยืม-คืน</span>' 
                                    : '<span class="badge-pill badge-type-consume"><i class="bi bi-box-arrow-right"></i> ใช้แล้วหมดไป</span>';

                                echo "<tr>";
                                echo "<td class='text-secondary fw-medium'>#{$row['asset_id']}</td>";
                                echo "<td><span class='fw-bold text-primary'>{$row['asset_code']}</span></td>";
                                echo "<td class='fw-medium text-dark'>{$row['asset_name']}</td>";
                                echo "<td><span class='badge-pill badge-category'>{$cat_name}</span></td>";
                                echo "<td>{$type_badge}</td>";
                                echo "<td class='text-center fs-6 fw-bold " . ($is_low ? "text-danger" : "text-dark") . "'>{$row['stock_qty']} {$row['unit']}</td>";
                                echo "<td class='text-center text-secondary'>{$row['min_alert_qty']} {$row['unit']}</td>";
                                echo "<td class='text-center'>{$status_badge}</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center py-4 text-muted'>ยังไม่มีข้อมูลอุปกรณ์ในคลัง</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>