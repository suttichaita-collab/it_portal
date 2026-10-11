<?php //แคตตาล็อกอุปกรณ์สำหรับพนักงาน2
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "it_asset_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// จัดการการเพิ่มลงตะกร้า (Session)
session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $asset_id = $_POST['asset_id'];
    $qty = (int)$_POST['quantity'];
    
    if (isset($_SESSION['cart'][$asset_id])) {
        $_SESSION['cart'][$asset_id] += $qty;
    } else {
        $_SESSION['cart'][$asset_id] = $qty;
    }
    header("Location: catalog.php");
    exit();
}

// ลบสินค้าออกจากตะกร้า
if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $remove_id = $_GET['id'];
    unset($_SESSION['cart'][$remove_id]);
    header("Location: catalog.php");
    exit();
}

// ล้างตะกร้าทั้งหมด
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    $_SESSION['cart'] = array();
    header("Location: catalog.php");
    exit();
}

// ค้นหาอุปกรณ์
$search = $_GET['search'] ?? '';
$search_query = $search ? "WHERE a.asset_name LIKE '%$search%' OR a.asset_code LIKE '%$search%'" : "";
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Asset Portal | แคตตาล็อกเบิกอุปกรณ์</title>
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
            padding: 14px 16px;
            font-size: 0.9rem;
            border: none;
        }
        .table-custom td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Badges */
        .badge-pill {
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
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

        .input-qty {
            width: 70px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #cbd5e1;
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <nav class="navbar navbar-dark navbar-custom py-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2" href="dashboard.php">
                <i class="bi bi-box-seam-fill"></i> IT Asset Portal | แคตตาล็อกเบิกอุปกรณ์
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
        <div class="row g-4 mb-5">
            
            <!-- รายการอุปกรณ์ฝั่งซ้าย -->
            <div class="col-lg-8">
                <div class="card card-custom p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-grid-3x3-gap-fill text-primary"></i> รายการอุปกรณ์พร้อมเบิก
                        </h5>
                    </div>

                    <!-- ฟอร์มค้นหา -->
                    <form action="" method="GET" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control rounded-start-3" placeholder="ค้นหาชื่ออุปกรณ์ หรือรหัสอุปกรณ์..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary px-4 rounded-end-3">
                                <i class="bi bi-search me-1"></i> ค้นหา
                            </button>
                        </div>
                    </form>

                    <!-- ตารางรายการสินค้า -->
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th width="20%">หมวดหมู่</th>
                                    <th width="30%">ชื่ออุปกรณ์</th>
                                    <th width="18%">ประเภท</th>
                                    <th width="12%" class="text-center">คงเหลือ</th>
                                    <th width="20%" class="text-center">เลือกเบิก</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT a.*, c.category_name 
                                        FROM assets a 
                                        LEFT JOIN categories c ON a.category_id = c.category_id 
                                        $search_query
                                        ORDER BY a.asset_id DESC";
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $cat_name = !empty($row['category_name']) ? $row['category_name'] : 'ทั่วไป';
                                        $type_badge = ($row['is_returnable'] == 1) 
                                            ? '<span class="badge-pill badge-type-borrow"><i class="bi bi-arrow-repeat"></i> ยืม-คืน</span>' 
                                            : '<span class="badge-pill badge-type-consume"><i class="bi bi-box-arrow-right"></i> ใช้แล้วหมดไป</span>';

                                        echo "<tr>";
                                        echo "<td><span class='badge-pill badge-category'>{$cat_name}</span></td>";
                                        echo "<td>
                                                <div class='fw-bold text-dark'>{$row['asset_name']}</div>
                                                <small class='text-muted'>{$row['asset_code']}</small>
                                              </td>";
                                        echo "<td>{$type_badge}</td>";
                                        echo "<td class='text-center fw-bold text-primary'>{$row['stock_qty']} {$row['unit']}</td>";
                                        echo "<td class='text-center'>";
                                        
                                        if ($row['stock_qty'] > 0) {
                                            echo "<form action='' method='POST' class='d-flex align-items-center justify-content-center gap-2'>
                                                    <input type='hidden' name='asset_id' value='{$row['asset_id']}'>
                                                    <input type='number' name='quantity' value='1' min='1' max='{$row['stock_qty']}' class='form-control form-control-sm input-qty' required>
                                                    <button type='submit' name='add_to_cart' class='btn btn-success btn-sm px-3 rounded-2 fw-medium'>
                                                        <i class='bi bi-cart-plus me-1'></i> ใส่ตะกร้า
                                                    </button>
                                                  </form>";
                                        } else {
                                            echo "<span class='badge bg-secondary rounded-pill'>ของหมดสต็อก</span>";
                                        }
                                        
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center py-4 text-muted'>ไม่พบรายการอุปกรณ์</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ตะกร้าคำขอเบิกฝั่งขวา -->
            <div class="col-lg-4">
                <div class="card card-custom p-4 sticky-top" style="top: 20px;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-cart-fill text-primary"></i> ตะกร้าคำขอเบิก
                        </h5>
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <a href="catalog.php?action=clear" class="btn btn-link text-danger text-decoration-none btn-sm p-0">ล้างตะกร้า</a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($_SESSION['cart'])): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-cart-x fs-1 opacity-50 d-block mb-2"></i>
                            ยังไม่มีรายการในตะกร้า
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush mb-3">
                            <?php
                            $total_items = 0;
                            foreach ($_SESSION['cart'] as $cart_asset_id => $cart_qty) {
                                $cart_item = $conn->query("SELECT asset_name, unit FROM assets WHERE asset_id = $cart_asset_id")->fetch_assoc();
                                if ($cart_item) {
                                    $total_items += $cart_qty;
                                    echo "<li class='list-group-item d-flex justify-content-between align-items-center px-0 py-3'>
                                            <div>
                                                <div class='fw-medium text-dark'>{$cart_item['asset_name']}</div>
                                                <small class='text-primary fw-bold'>จำนวน: {$cart_qty} {$cart_item['unit']}</small>
                                            </div>
                                            <a href='catalog.php?action=remove&id={$cart_asset_id}' class='btn btn-outline-danger btn-sm border-0'>
                                                <i class='bi bi-trash3-fill'></i>
                                            </a>
                                          </li>";
                                }
                            }
                            ?>
                        </ul>
                        
                        <div class="border-top pt-3 mb-3 d-flex justify-content-between align-items-center">
                            <span class="fw-medium text-secondary">รวมทั้งหมด:</span>
                            <span class="fs-5 fw-bold text-primary"><?= $total_items ?> รายการ</span>
                        </div>

                        <a href="checkout.php" class="btn btn-primary w-100 py-2 fw-medium rounded-3 shadow-sm">
                            <i class="bi bi-send-check me-1"></i> ยืนยันส่งคำขอเบิก
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>
</html>