<?php //สรุปรายการคำขอและยืนยันการเบิก2
session_start();

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "it_asset_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตรวจสอบตะกร้าสินค้า
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: catalog.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['user']['id'] ?? 1;
    $note_input = $conn->real_escape_string($_POST['note'] ?? '');
    $now = date("Y-m-d H:i:s");

    // 1. ตรวจสอบคอลัมน์ตาราง requisitions
    $req_cols = [];
    $res_rc = $conn->query("SHOW COLUMNS FROM requisitions");
    if ($res_rc) {
        while ($c = $res_rc->fetch_assoc()) $req_cols[] = strtolower($c['Field']);
    }

    $date_col = in_array('req_date', $req_cols) ? 'req_date' : (in_array('request_date', $req_cols) ? 'request_date' : 'created_at');
    $note_col = in_array('note', $req_cols) ? 'note' : (in_array('reason', $req_cols) ? 'reason' : (in_array('remark', $req_cols) ? 'remark' : ''));

    // 2. บันทึกเข้า requisitions
    if (!empty($note_col)) {
        $sql = "INSERT INTO requisitions (user_id, $date_col, status, $note_col) VALUES ('$user_id', '$now', 'pending', '$note_input')";
    } else {
        $sql = "INSERT INTO requisitions (user_id, $date_col, status) VALUES ('$user_id', '$now', 'pending')";
    }

    if ($conn->query($sql)) {
        $req_id = $conn->insert_id;

        // 3. ตรวจสอบคอลัมน์ตาราง requisition_details
        $det_cols = [];
        $res_dc = $conn->query("SHOW COLUMNS FROM requisition_details");
        if ($res_dc) {
            while ($c = $res_dc->fetch_assoc()) $det_cols[] = strtolower($c['Field']);
        }

        $req_fk = in_array('req_id', $det_cols) ? 'req_id' : (in_array('requisition_id', $det_cols) ? 'requisition_id' : 'request_id');
        $asset_fk = in_array('asset_id', $det_cols) ? 'asset_id' : (in_array('item_id', $det_cols) ? 'item_id' : (in_array('equipment_id', $det_cols) ? 'equipment_id' : 'product_id'));
        $qty_col = in_array('quantity', $det_cols) ? 'quantity' : (in_array('qty', $det_cols) ? 'qty' : 'amount');

        // 4. วนลูปบันทึกรายการสินค้า
        foreach ($_SESSION['cart'] as $key => $item) {
            // ดึง ID อุปกรณ์
            $real_asset_id = 0;
            if (is_array($item)) {
                $real_asset_id = $item['asset_id'] ?? $item['id'] ?? $item['item_id'] ?? $item['equipment_id'] ?? $key;
                $qty = (int)($item['qty'] ?? $item['quantity'] ?? $item['amount'] ?? 1);
            } else {
                $real_asset_id = $key;
                $qty = (int)$item;
            }

            if (!empty($real_asset_id)) {
                $insert_detail = "INSERT INTO requisition_details ($req_fk, $asset_fk, $qty_col) VALUES ('$req_id', '$real_asset_id', '$qty')";
                $conn->query($insert_detail);
            }
        }

        unset($_SESSION['cart']);

        echo "<script>alert('ยื่นคำขอเบิกอุปกรณ์เรียบร้อยแล้ว!'); window.location.href='my_requests.php';</script>";
        exit();
    } else {
        $message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันการเบิกอุปกรณ์ | IT Asset Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f1f5f9; color: #334155; }
        .navbar-custom { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
        .card-custom { background: #ffffff; border: none; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-custom py-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2" href="dashboard.php">
                <i class="bi bi-box-seam-fill"></i> IT Asset Management System
            </a>
            <a href="catalog.php" class="btn btn-light btn-sm fw-medium rounded-3 px-3">
                <i class="bi bi-arrow-left me-1"></i> กลับหน้าคลังอุปกรณ์
            </a>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-12 col-md-8 mx-auto">
                <div class="card card-custom p-4">
                    <h4 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-cart-check-fill text-primary"></i> สรุปรายการคำขอเบิกอุปกรณ์
                    </h4>

                    <?php if ($message): ?>
                        <div class="alert alert-danger"><?=$message?></div>
                    <?php endif; ?>

                    <div class="table-responsive mb-4">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>รายการอุปกรณ์</th>
                                    <th class="text-center" width="25%">จำนวนที่ขอเบิก</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $key => $item): ?>
                                    <?php 
                                        $name = is_array($item) ? ($item['name'] ?? $item['asset_name'] ?? 'อุปกรณ์ #'.$key) : 'อุปกรณ์ #'.$key;
                                        $qty = is_array($item) ? ($item['qty'] ?? $item['quantity'] ?? 1) : $item;
                                    ?>
                                    <tr>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($name) ?></td>
                                        <td class="text-center fw-bold text-primary"><?= $qty ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <form method="POST">
                        <div class="mb-4">
                            <label for="note" class="form-label fw-medium">หมายเหตุ / เหตุผลในการเบิกใช้งาน</label>
                            <textarea class="form-control rounded-3" id="note" name="note" rows="3" placeholder="ระบุวัตถุประสงค์ในการเบิกอุปกรณ์..."></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="catalog.php" class="btn btn-secondary rounded-3 px-4">ยกเลิก</a>
                            <button type="submit" class="btn btn-primary rounded-3 px-4 fw-medium">
                                <i class="bi bi-send-fill me-1"></i> ยืนยันคำขอเบิก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>