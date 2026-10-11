<?php
session_start();

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "it_asset_db");
if ($conn->connect_error) {
    // กรณีมีไฟล์ db.php สามารถเปลี่ยนเป็น require_once('db.php'); ได้ครับ
    die("Connection failed: " . $conn->connect_error);
}

// คำนวณจำนวนการ์ดต่างๆ
// 1. อุปกรณ์ทั้งหมดในคลัง
$total_assets = 0;
$res_a = $conn->query("SELECT COUNT(*) AS total FROM assets");
if ($res_a) $total_assets = $res_a->fetch_assoc()['total'] ?? 0;

// 2. คำขอเบิกรออนุมัติ
$pending_req_count = 0;
$res_p = $conn->query("SELECT COUNT(*) AS total FROM requisitions WHERE status = 'pending'");
if ($res_p) $pending_req_count = $res_p->fetch_assoc()['total'] ?? 0;

// 3. รายการรอตรวจรับคืน (สถานะ approved หรือ return_pending)
$pending_returns_count = 0;
$res_r = $conn->query("SELECT COUNT(*) AS total FROM requisitions WHERE status IN ('approved', 'return_pending')");
if ($res_r) $pending_returns_count = $res_r->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Asset Portal | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f1f5f9; color: #334155; }
        .navbar-custom { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
        .card-custom { background: #ffffff; border: none; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card-custom:hover { transform: translateY(-3px); }
        .section-title { font-size: 1.1rem; font-weight: 600; color: #1e293b; border-left: 4px solid #2563eb; padding-left: 10px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-custom py-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2" href="dashboard.php">
                <i class="bi bi-box-seam-fill"></i> IT Asset Management System
            </a>
            <div class="d-flex gap-2">
                <a href="logout.php" class="btn btn-danger btn-sm fw-medium rounded-3 px-3 align-self-center">
                    ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 mb-5">
        
        <div class="mb-4">
            <h4 class="fw-bold text-dark mb-1">ยินดีต้อนรับเข้าสู่ระบบจัดการคลังและเบิกจ่ายอุปกรณ์</h4>
            <p class="text-secondary small">เลือกเมนูการใช้งานที่ต้องการทำรายการได้จากด้านล่างนี้</p>
        </div>

        <!-- 1. เมนูพนักงาน (User Services) -->
        <div class="mb-4">
            <div class="section-title mb-3">เมนูพนักงาน (User Services)</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-custom p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="p-3 bg-primary-subtle text-primary rounded-3 fs-3"><i class="bi bi-cart-plus-fill"></i></div>
                                <div>
                                    <h5 class="fw-bold mb-0">รายการอุปกรณ์</h5>
                                    <small class="text-muted">Catalog & Request</small>
                                </div>
                            </div>
                            <p class="text-secondary small">ค้นหา เลือกชมรายการอุปกรณ์ IT ทั้งหมดในคลัง และยื่นคำขอเบิกหรือยืมอุปกรณ์</p>
                        </div>
                        <a href="catalog.php" class="btn btn-outline-primary w-100 rounded-3 fw-medium mt-3">
                            เข้าสู่หน้าเบิกอุปกรณ์ <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-custom p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="p-3 bg-info-subtle text-info rounded-3 fs-3"><i class="bi bi-clock-history"></i></div>
                                <div>
                                    <h5 class="fw-bold mb-0">ติดตามคำขอเบิก</h5>
                                    <small class="text-muted">My Requests</small>
                                </div>
                            </div>
                            <p class="text-secondary small">ตรวจสอบสถานะรายการคำขอเบิกอุปกรณ์ของคุณ ว่าอยู่ในขั้นตอนรออนุมัติหรือรับของแล้ว</p>
                        </div>
                        <a href="my_requests.php" class="btn btn-outline-info w-100 rounded-3 fw-medium mt-3">
                            ดูประวัติการเบิก <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-custom p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3 fs-3"><i class="bi bi-box-arrow-left"></i></div>
                                <div>
                                    <h5 class="fw-bold mb-0">อุปกรณ์ที่ต้องส่งคืน</h5>
                                    <small class="text-muted">My Borrowed Returns</small>
                                </div>
                            </div>
                            <p class="text-secondary small">ตรวจสอบรายการอุปกรณ์ประเภทยืม-คืนที่อยู่ในครอบครองของคุณ และยื่นเรื่องแจ้งส่งคืน</p>
                        </div>
                        <a href="return_request.php" class="btn btn-outline-warning text-dark w-100 rounded-3 fw-medium mt-3">
                            รายการอุปกรณ์ที่ยืม <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. เมนูผู้ดูแลระบบ (Admin Controls) -->
        <div class="mb-4">
            <div class="section-title mb-3 text-danger" style="border-color: #dc2626;">เมนูผู้ดูแลระบบ (Admin Controls)</div>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card card-custom p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="p-3 bg-secondary-subtle text-secondary rounded-3 fs-3"><i class="bi bi-boxes"></i></div>
                                <div>
                                    <h5 class="fw-bold mb-0">จัดการคลังอุปกรณ์</h5>
                                    <small class="text-muted">Manage Assets</small>
                                </div>
                            </div>
                            <p class="text-secondary small">เพิ่ม แก้ไข ลบ อุปกรณ์ และปรับจำนวนสต็อกสินค้าในระบบ</p>
                            <div class="mb-3">
                                <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fw-normal">
                                    ทั้งหมด <?= $total_assets ?> รายการ
                                </span>
                            </div>
                        </div>
                        <a href="manage_assets.php" class="btn btn-dark w-100 rounded-3 fw-medium">
                            จัดการคลัง
                        </a>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-custom p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="p-3 bg-primary-subtle text-primary rounded-3 fs-3"><i class="bi bi-check-square-fill"></i></div>
                                <div>
                                    <h5 class="fw-bold mb-0">อนุมัติและจ่ายของ</h5>
                                    <small class="text-muted">Manage Requests</small>
                                </div>
                            </div>
                            <p class="text-secondary small">พิจารณาอนุมัติ/ไม่อนุมัติ คำขอเบิกอุปกรณ์ และตัดสต็อกสินค้า</p>
                            <div class="mb-3">
                                <?php if ($pending_req_count > 0): ?>
                                    <span class="badge bg-danger px-3 py-2 rounded-pill fw-normal">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i> รออนุมัติ <?= $pending_req_count ?> รายการ
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary px-3 py-2 rounded-pill fw-normal">
                                        ไม่มีคำขอค้าง
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="manage_requests.php" class="btn btn-primary w-100 rounded-3 fw-medium">
                            อนุมัติคำขอเบิก
                        </a>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-custom p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3 fs-3"><i class="bi bi-arrow-return-left"></i></div>
                                <div>
                                    <h5 class="fw-bold mb-0">ตรวจรับคืนอุปกรณ์</h5>
                                    <small class="text-muted">Manage Returns</small>
                                </div>
                            </div>
                            <p class="text-secondary small">ยืนยันการรับคืนอุปกรณ์จากพนักงาน และดึงสินค้ากลับเข้าสต็อก</p>
                            <div class="mb-3">
                                <?php if ($pending_returns_count > 0): ?>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-normal">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i> รอรับคืน <?= $pending_returns_count ?> รายการ
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary px-3 py-2 rounded-pill fw-normal">
                                        ไม่มีรายการค้าง
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="manage_returns.php" class="btn btn-warning w-100 rounded-3 fw-medium">
                            จัดการการรับคืน
                        </a>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-custom p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="p-3 bg-success-subtle text-success rounded-3 fs-3"><i class="bi bi-bar-chart-line-fill"></i></div>
                                <div>
                                    <h5 class="fw-bold mb-0">รายงานและสถิติ</h5>
                                    <small class="text-muted">Reports & Analytics</small>
                                </div>
                            </div>
                            <p class="text-secondary small">เรียกดูภาพรวม สถิติการเบิก-คืน และรายงานสรุปย่อทั้งหมด</p>
                            <div class="mb-3">
                                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-normal">
                                    พร้อมใช้งาน
                                </span>
                            </div>
                        </div>
                        <a href="reports.php" class="btn btn-success w-100 rounded-3 fw-medium">
                            ดูรายงาน
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>