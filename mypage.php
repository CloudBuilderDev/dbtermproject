<?php
session_start();
include 'db.php';
if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}
$cno = $_SESSION['cno'];
$activeTab = $_GET['tab'] ?? 'profile'; // 기본은 프로필
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>마이페이지</title>
  <style>
    .tab-menu a { margin-right: 10px; text-decoration: none; }
    .tab-menu a.active { font-weight: bold; color: #005bac; }
    .section { margin-top: 20px; }
  </style>
</head>
<body>
  <h2>마이페이지</h2>
  <div class="tab-menu">
    <a href="?tab=profile" class="<?= $activeTab == 'profile' ? 'active' : '' ?>">회원 정보</a>
    <a href="?tab=reservation" class="<?= $activeTab == 'reservation' ? 'active' : '' ?>">예약 내역</a>
    <a href="?tab=cancel" class="<?= $activeTab == 'cancel' ? 'active' : '' ?>">취소 내역</a>
  </div>

  <div class="section">
    <?php if ($activeTab === 'reservation'): ?>
      <h3>예약 내역</h3>
      <table border="1" cellpadding="8">
        <tr><th>편명</th><th>출발일</th><th>좌석등급</th><th>요금</th><th>예약일시</th></tr>
        <?php
        $sql = "SELECT flightNo, TO_CHAR(departureDateTime, 'YYYY-MM-DD HH24:MI') as dep_time, seatClass, payment, TO_CHAR(reserveDateTime, 'YYYY-MM-DD HH24:MI') as res_time FROM RESERVE WHERE cno = :cno ORDER BY reserveDateTime DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':cno' => $cno]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r):
        ?>
          <tr>
            <td><?= htmlspecialchars($r['FLIGHTNO']) ?></td>
            <td><?= $r['DEP_TIME'] ?></td>
            <td><?= $r['SEATCLASS'] ?></td>
            <td><?= number_format($r['PAYMENT']) ?>원</td>
            <td><?= $r['RES_TIME'] ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p><?= $activeTab === 'profile' ? "회원정보 탭 준비 중" : "취소 내역 탭 준비 중" ?></p>
    <?php endif; ?>
  </div>
</body>
</html>
