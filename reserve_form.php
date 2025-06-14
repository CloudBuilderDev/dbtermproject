<?php
session_start();
include 'db.php';

// 로그인 확인
if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

// POST로 넘어온 예약 정보 받기
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo "잘못된 접근입니다.";
  exit;
}

$flightNo = $_POST['flightNo'] ?? null;
$departureDateTime = $_POST['departureDateTime'] ?? null;
$seatClass = $_POST['seatClass'] ?? null;
$price = $_POST['price'] ?? null;
$departureAirport = $_POST['departureAirport'] ?? '';
$arrivalAirport = $_POST['arrivalAirport'] ?? '';


echo "DEBUG: $flightNo $departureDateTime $seatClass $price $departureAirport $arrivalAirport<br>";

// 항공편 정보 확인
$sql = "
SELECT 
  a.airline, a.flightNo, a.departureAirport, a.arrivalAirport,
  TO_CHAR(a.departureDateTime, 'YYYY-MM-DD') AS dep_date,
  TO_CHAR(a.departureDateTime, 'HH24:MI') AS dep_time,
  TO_CHAR(a.arrivalDateTime, 'YYYY-MM-DD') AS arr_date,
  TO_CHAR(a.arrivalDateTime, 'HH24:MI') AS arr_time,
  s.price,
  GET_REMAINING_SEATS(a.flightNo, a.departureDateTime, s.seatClass) AS available
FROM AIRPLAIN a
JOIN SEATS s ON a.flightNo = s.flightNo AND a.departureDateTime = s.departureDateTime 
WHERE a.flightNo = :flightNo
  AND a.departureDateTime = TO_DATE(:departureDateTime, 'YYYY-MM-DD HH24:MI')
  AND s.seatClass = :seatClass
";


$stmt = $conn->prepare($sql);
$stmt->execute([
  ':flightNo' => $flightNo,
  ':departureDateTime' => $departureDateTime,
  ':seatClass' => $seatClass
]);

$flight = $stmt->fetch(PDO::FETCH_ASSOC);

// 만약 다른 사용자가 같은 항공편 좌석을 예약하여 예약 가능한 좌석의 수가 줄어든다면 이를 예외처리
echo $flight['AVAILABLE'];
if (!$flight || $flight['AVAILABLE'] <= 0) {
    echo "<script>
    alert('예약 가능한 좌석이 부족합니다.');
    window.location.href = 'search.php';
  </script>";
  exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>예약 확인</title>
  <style>
    body {
      font-family: sans-serif;
      background: #f2f2f2;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 600px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      color: #005bac;
    }
    table {
      width: 100%;
      margin-top: 20px;
      border-collapse: collapse;
    }
    td {
      padding: 8px;
      border-bottom: 1px solid #ccc;
    }
    .button-row {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 30px;
    }
    .btn {
      padding: 10px 20px;
      background: #005bac;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      border: none;
      cursor: pointer;
    }
    .btn:hover {
      background: #003e80;
    }
    .btn.cancel {
      background: gray;
    }
    .btn.cancel:hover {
      background: #555;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>예약 정보 확인</h2>
    <table>
      <tr><td>항공사</td><td><?= htmlspecialchars($flight['AIRLINE']) ?></td></tr>
      <tr><td>편명</td><td><?= htmlspecialchars($flight['FLIGHTNO']) ?></td></tr>
      <tr><td>출발지</td><td><?= htmlspecialchars($flight['DEPARTUREAIRPORT']) ?></td></tr>
      <tr><td>도착지</td><td><?= htmlspecialchars($flight['ARRIVALAIRPORT']) ?></td></tr>
      <tr><td>출발일</td><td><?= htmlspecialchars($flight['DEP_DATE']) ?> <?= htmlspecialchars($flight['DEP_TIME']) ?></td></tr>
      <tr><td>도착일</td><td><?= htmlspecialchars($flight['ARR_DATE']) ?> <?= htmlspecialchars($flight['ARR_TIME']) ?></td></tr>
      <tr><td>좌석등급</td><td><?= htmlspecialchars($seatClass) ?></td></tr>
      <tr><td>요금</td><td><?= number_format($flight['PRICE']) ?>원</td></tr>
      <tr><td>남은 좌석</td><td><?= $flight['AVAILABLE'] ?>석</td></tr>
    </table>

    <div class="button-row">
      <!-- 예약하기 버튼 -->
      <form action="reserve_process.php" method="post">
        <input type="hidden" name="flightNo" value="<?= htmlspecialchars($flightNo) ?>">
        <input type="hidden" name="departureDateTime" value="<?= htmlspecialchars($departureDateTime) ?>">
        <input type="hidden" name="seatClass" value="<?= htmlspecialchars($seatClass) ?>">
        <input type="hidden" name="price" value="<?= htmlspecialchars($price) ?>">
        <button type="submit" class="btn">결제하고 예약하기</button>
      </form>

      <!-- 취소 버튼 -->
      <form action="search.php" method="post">
        <input type="hidden" name="departureAirport" value="<?= htmlspecialchars($departureAirport) ?>">
        <input type="hidden" name="arrivalAirport" value="<?= htmlspecialchars($arrivalAirport) ?>">
        <input type="hidden" name="departureDate" value="<?= htmlspecialchars(substr($departureDateTime,0,10)) ?>">
        <input type="hidden" name="seatClass" value="<?= htmlspecialchars($seatClass) ?>">
        <input type="hidden" name="sortOption" value="<?= htmlspecialchars($sortOption) ?>">
        <button type="submit" class="btn cancel">취소</button>
      </form>
    </div>
  </div>
</body>
</html>
