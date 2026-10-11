<?php
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "it_asset_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// ฟังก์ชันดึง Primary Key ของ requisitions
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
$req_id = intval($_GET['id'] ?? 0);

if ($req_id <= 0) {
    die("ไม่พบข้อมูลเอกสาร");
}

// ดึงข้อมูลรายการเบิก-คืน
$sql = "SELECT * FROM requisitions WHERE `$req_pk` = '$req_id'";
$res = $conn->query($sql);

if (!$res || $res->num_rows === 0)  {
    die("ไม่พบรายการที่ต้องการพิมพ์");
}

$req_data =$res->fetch_assoc();
$req_code = "REQ-" . str_pad($req_id, 5, "0", STR_PAD_LEFT);
$status =$req_data['status'];
$is_return = ($status === 'returned');

$date_val =$req_data['req_date'] ?? $req_data['request_date'] ?? $req_data['created_at'] ?? date('Y-m-d H:i:s');
$formatted_date = date("d/m/Y H:i", strtotime($date_val));

$remark = !empty($req_data['remark']) ? $req_data['remark'] : (!empty($req_data['note']) ? $req_data['note'] : '-');$user_name = $req_data['user_name'] ?? $req_data['created_by'] ?? 'พนักงานทั่วไป (User)';

// ดึงรายการอุปกรณ์
$det_cols = [];
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

$sql_items = "SELECT rd.*, a.$name_col AS item_name 
              FROM requisition_details rd 
              LEFT JOIN assets a ON rd.$asset_fk = a.$asset_pk 
              WHERE rd.$req_fk = '$req_id'";
$res_items = $conn->query($sql_items);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จเอกสาร - <?= $req_code ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .receipt-card { background: #fff; max-width: 800px; margin: 30px auto; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
        .doc-title { font-size: 1.6rem; font-weight: 700; color: #0f172a; text-transform: uppercase; }
        .table-items th { background-color: #f1f5f9; color: #334155; font-weight: 600; }
        .signature-box { border-top: 1px dashed #cbd5e1; margin-top: 60px; padding-top: 20px; }

        @media print {
            body { background-color: #fff; }
            .receipt-card { box-shadow: none; border: none; padding: 0; margin: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <!-- ปุ่มสำหรับการพิมพ์หน้าจอ -->
    <div class="text-center my-4 no-print">
        <button onclick="window.print();" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm">
            🖨️ พิมพ์เอกสาร / บันทึกเป็น PDF
        </button>
        <button onclick="window.close();" class="btn btn-outline-secondary btn-lg rounded-pill px-4 ms-2">
            ปิดหน้าต่าง
        </button>
    </div>

    <div class="receipt-card">
        <!-- หัวเอกสาร -->
        <div class="row align-items-center mb-4">
            <div class="col-8">
                <h3 class="fw-bold text-primary mb-1">🏢 IT ASSET & SUPPLY PORTAL</h3>
                <p class="text-muted small mb-0">แผนกเทคโนโลยีสารสนเทศ (IT Support & Operations)</p>
            </div>
            <div class="col-4 text-end">
                <div class="doc-title"><?= $is_return ? 'ใบตรวจรับคืน' : 'ใบอนุมัติเบิก' ?></div>
                <div class="fw-bold text-secondary"><?= $req_code ?></div>
            </div>
        </div>

        <hr class="mb-4">

        <!-- ข้อมูลประกอบเอกสาร -->
        <div class="row mb-4">
            <div class="col-6">
                <p class="mb-1"><strong>ผู้ทำรายการ:</strong> <?= htmlspecialchars($user_name) ?></p>
                <p class="mb-1"><strong>วันที่ทำรายการ:</strong> <?= $formatted_date ?></p>
            </div>
            <div class="col-6 text-end">
                <p class="mb-1"><strong>ประเภทเอกสาร:</strong> <?= $is_return ? 'รับคืนอุปกรณ์เข้าคลัง' : 'เบิกจ่ายอุปกรณ์' ?></p>
                <p class="mb-1"><strong>สถานะรายการ:</strong> <span class="badge bg-secondary"><?= strtoupper($status) ?></span></p>
            </div>
        </div>

        <!-- รายการอุปกรณ์ -->
        <h6 class="fw-bold mb-3">รายการอุปกรณ์ IT (Item Details)</h6>
        <table class="table table-bordered table-items mb-4 align-middle">
            <thead>
                <tr>
                    <th width="10%" class="text-center">ลำดับ</th>
                    <th width="70%">รายการอุปกรณ์</th>
                    <th width="20%" class="text-center">จำนวน (ชิ้น)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($res_items && $res_items->num_rows > 0) {$i = 1;
                    while ($item =$res_items->fetch_assoc()) {
                        $name = !empty($item['item_name']) ? $item['item_name'] : ('อุปกรณ์ ID: ' . $item[$asset_fk]);$qty = $item[$qty_col] ?? 1;
                        echo "<tr>";
                        echo "<td class='text-center'>{$i}</td>";
                        echo "<td>" . htmlspecialchars($name) . "</td>";
                        echo "<td class='text-center fw-bold'>{$qty}</td>";
                        echo "</tr>";
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center text-muted'>ไม่พบรายการอุปกรณ์</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- หมายเหตุและสภาพอุปกรณ์ -->
        <div class="p-3 bg-light rounded-3 mb-4">
            <p class="mb-0 small"><strong>ผลการตรวจสภาพ / หมายเหตุเพิ่มเติม:</strong> <?= htmlspecialchars($remark) ?></p>
        </div>

        <!-- ช่องลงนามเซ็นเอกสาร -->
        <div class="signature-box">
            <div class="row text-center">
                <div class="col-6">
                    <p class="mb-5">ลงชื่อ ...........................................................<br>( ผู้เบิก / ผู้ส่งคืนอุปกรณ์ )</p>
                    <p class="small text-muted">วันที่ ........ / ........ / ................</p>
                </div>
                <div class="col-6">
                    <p class="mb-5">ลงชื่อ ...........................................................<br>( เจ้าหน้าที่ IT / ผู้รับคืนอุปกรณ์ )</p>
                    <p class="small text-muted">วันที่ ........ / ........ / ................</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>