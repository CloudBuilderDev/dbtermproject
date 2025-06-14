<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

$cno = $_SESSION['cno'];
$name = $_SESSION['name'];
$email = $_SESSION['email'];
$tab = $_GET['tab'] ?? 'profile';

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'default';
$orderDir = strtolower($_GET['order'] ?? 'desc');
$orderDir = ($orderDir === 'asc') ? 'ASC' : 'DESC';

$reservations = $cancellations = [];
if ($tab === 'history') {
  if ($filter === 'reserve' || $filter === 'all') {
    $reserveSql = "
      SELECT r.flightNo, r.seatClass, r.payment, 
             TO_CHAR(r.reserveDateTime, 'YYYY-MM-DD HH24:MI') AS reserveDateTime,
             a.airline, a.departureAirport, a.arrivalAirport, 
             TO_CHAR(a.departureDateTime, 'YYYY-MM-DD HH24:MI') AS departureDateTime,
             TO_CHAR(a.arrivalDateTime, 'YYYY-MM-DD HH24:MI') AS arrivalDateTime
      FROM RESERVE r
      JOIN AIRPLAIN a ON r.flightNo = a.flightNo AND r.departureDateTime = a.departureDateTime
      WHERE r.cno = :cno
    ";
    $resParams = [':cno' => $cno];
    if ($startDate !== '') {
      $reserveSql .= " AND r.reserveDateTime >= TO_DATE(:start_res, 'YYYY-MM-DD') ";
      $resParams[':start_res'] = $startDate;
    }
    if ($endDate !== '') {
      $reserveSql .= " AND r.reserveDateTime <= TO_DATE(:end_res, 'YYYY-MM-DD') + 1 ";
      $resParams[':end_res'] = $endDate;
    }
    $order = match($sort) {
      'dep' => 'a.departureDateTime',
      'res' => 'r.reserveDateTime',
      'price' => 'r.payment',
      default => 'r.reserveDateTime'
    };
    $reserveSql .= " ORDER BY $order $orderDir ";
    $stmt = $conn->prepare($reserveSql);
    $stmt->execute($resParams);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  if ($filter === 'cancel' || $filter === 'all') {
    $cancelSql = "
      SELECT c.flightNo, c.seatClass, c.refund, 
             TO_CHAR(c.cancelDateTime, 'YYYY-MM-DD HH24:MI') AS cancelDateTime,
             a.airline, a.departureAirport, a.arrivalAirport, 
             TO_CHAR(a.departureDateTime, 'YYYY-MM-DD HH24:MI') AS departureDateTime,
             TO_CHAR(a.arrivalDateTime, 'YYYY-MM-DD HH24:MI') AS arrivalDateTime
      FROM CANCEL c
      JOIN AIRPLAIN a ON c.flightNo = a.flightNo AND c.departureDateTime = a.departureDateTime
      WHERE c.cno = :cno
    ";
    $canParams = [':cno' => $cno];
    if ($startDate !== '') {
      $cancelSql .= " AND c.cancelDateTime >= TO_DATE(:start_can, 'YYYY-MM-DD') ";
      $canParams[':start_can'] = $startDate;
    }
    if ($endDate !== '') {
      $cancelSql .= " AND c.cancelDateTime <= TO_DATE(:end_can, 'YYYY-MM-DD') + 1 ";
      $canParams[':end_can'] = $endDate;
    }
    $order = match($sort) {
      'dep' => 'a.departureDateTime',
      'res' => 'c.cancelDateTime',
      'price' => 'c.refund',
      default => 'c.cancelDateTime'
    };
    $cancelSql .= " ORDER BY $order $orderDir ";
    $stmt = $conn->prepare($cancelSql);
    $stmt->execute($canParams);
    $cancellations = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
?>



<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>마이페이지</title>
  <style>
    body {
      font-family: sans-serif;
      margin: 0;
      padding: 20px;
      background: #f4f6f9;
      display: flex;
    }
    .sidebar {
      width: 200px;
      padding: 20px;
    }
    .sidebar a {
      display: block;
      margin-bottom: 15px;
      text-decoration: none;
      font-weight: bold;
      color: #007bff;
    }
    .sidebar a.active {
      color: white;
      background: #007bff;
      padding: 8px;
      border-radius: 5px;
    }
    .content {
      flex-grow: 1;
      padding: 20px;
      background: white;
      border-radius: 10px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: center;
      white-space: nowrap;
    }
    th {
      background: #eaeaea;
    }
    .form-inline input, .form-inline select, .form-inline button {
      margin-right: 10px;
    }
    .cancel-btn {
      background-color: #dc3545;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 4px;
      cursor: pointer;
    }
    .cancel-btn:hover {
      background-color: #c82333;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h3><?= htmlspecialchars($name) ?>님의 마이페이지</h3>
    <a href="?tab=profile" class="<?= $tab === 'profile' ? 'active' : '' ?>">회원정보</a>
    <a href="?tab=history" class="<?= $tab === 'history' ? 'active' : '' ?>">예약/취소 내역</a>
    <a href="?tab=payment" class="<?= $tab === 'payment' ? 'active' : '' ?>">결제 정보</a>
    <a href="search.php">메인페이지로</a>
  </div>

  <div class="content">
    <?php if ($tab === 'profile'): ?>
      <h2>회원정보</h2>
      <p><strong>아이디:</strong> <?= htmlspecialchars($_SESSION['id']) ?></p>
      <p><strong>이름:</strong> <?= htmlspecialchars($name) ?></p>
      <p><strong>이메일:</strong> <?= htmlspecialchars($email) ?></p>

    <?php elseif ($tab === 'history'): ?>
      <h2>예약/취소 내역 조회</h2>

        <form method="get" class="form-inline">
          <input type="hidden" name="tab" value="history">
          시작일: <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
          종료일: <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
          <select name="filter">
            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>전체</option>
            <option value="reserve" <?= $filter === 'reserve' ? 'selected' : '' ?>>예약만</option>
            <option value="cancel" <?= $filter === 'cancel' ? 'selected' : '' ?>>취소만</option>
          </select>
          <select name="sort">
            <option value="res" <?= $sort === 'res' ? 'selected' : '' ?>>예약/취소일</option>
            <option value="dep" <?= $sort === 'dep' ? 'selected' : '' ?>>출발일</option>
            <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>가격</option>
          </select>
          <select name="order">
            <option value="desc" <?= ($orderDir === 'DESC') ? 'selected' : '' ?>>내림차순</option>
            <option value="asc" <?= ($orderDir === 'ASC') ? 'selected' : '' ?>>오름차순</option>
          </select>
          <button type="submit">조회</button>
        </form>


      <h3>예약 내역</h3>
      <?php if (count($reservations) > 0): ?>
        <table>
          <tr><th>항공사</th><th>편명</th><th>출발지</th><th>도착지</th><th>출발일시</th><th>도착일시</th><th>좌석등급</th><th>결제금액</th><th>예약일시</th><th>취소</th></tr>
          <?php foreach ($reservations as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['AIRLINE']) ?></td>
              <td><?= htmlspecialchars($r['FLIGHTNO']) ?></td>
              <td><?= htmlspecialchars($r['DEPARTUREAIRPORT']) ?></td>
              <td><?= htmlspecialchars($r['ARRIVALAIRPORT']) ?></td>
              <td><?= $r['DEPARTUREDATETIME'] ?></td>
              <td><?= $r['ARRIVALDATETIME'] ?></td>
              <td><?= $r['SEATCLASS'] ?></td>
              <td><?= number_format($r['PAYMENT']) ?>원</td>
              <td><?= $r['RESERVEDATETIME'] ?></td>
              <td>
                <form action="cancel_confirm.php" method="post" onsubmit="return confirm('정말로 이 예약을 취소하시겠습니까?');">
                  <input type="hidden" name="flightNo" value="<?= $r['FLIGHTNO'] ?>">
                  <input type="hidden" name="departureDateTime" value="<?= $r['DEPARTUREDATETIME'] ?>">
                  <input type="hidden" name="seatClass" value="<?= $r['SEATCLASS'] ?>">
                  <input type="hidden" name="payment" value="<?= $r['PAYMENT'] ?>">
                  <button type="submit" class="cancel-btn">취소하기</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>

      <h3>취소 내역</h3>
      <?php if (count($cancellations) > 0): ?>
        <table>
          <tr><th>항공사</th><th>편명</th><th>출발지</th><th>도착지</th><th>출발일시</th><th>도착일시</th><th>좌석등급</th><th>환불금액</th><th>취소일시</th></tr>
          <?php foreach ($cancellations as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['AIRLINE']) ?></td>
              <td><?= htmlspecialchars($c['FLIGHTNO']) ?></td>
              <td><?= htmlspecialchars($c['DEPARTUREAIRPORT']) ?></td>
              <td><?= htmlspecialchars($c['ARRIVALAIRPORT']) ?></td>
              <td><?= $c['DEPARTUREDATETIME'] ?></td>
              <td><?= $c['ARRIVALDATETIME'] ?></td>
              <td><?= $c['SEATCLASS'] ?></td>
              <td><?= number_format($c['REFUND']) ?>원</td>
              <td><?= $c['CANCELDATETIME'] ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>

    <?php elseif ($tab === 'payment'): ?>
      <h2>결제 정보</h2>
      <p>※ 아직 결제 테이블이 구현되지 않았습니다.</p>
      <p>등록된 결제 수단: <em>(추후 구현)</em></p>
      <p>현재 잔액: <em>(추후 구현)</em></p>
    <?php endif; ?>
  </div>
</body>
</html>