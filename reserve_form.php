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
$departureDate = $_POST['departureDateTime'] ?? null;
$seatClass = $_POST['seatClass'] ?? null;
$price = $_POST['price'] ?? null;


if (!$flightNo || !$departureDate || !$seatClass || !$price) {
  echo "필수 예약 정보가 누락되었습니다.";
  exit;
}
echo "$flightNo $departureDate $seatClass $price<br>";

// 항공편 정보 확인
$sql = "
SELECT 
  a.airline, a.flightNo, a.departureAirport, a.arrivalAirport,
  TO_CHAR(a.departureDateTime, 'YYYY-MM-DD') AS dep_date,
  TO_CHAR(a.departureDateTime, 'HH24:MI') AS dep_time,
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
  ':departureDateTime' => $departureDate,
  ':seatClass' => $seatClass
]);

$flight = $stmt->fetch(PDO::FETCH_ASSOC);

echo $flight['AVAILABLE'];
// if (!$flight || $flight['AVAILABLE'] <= 0) {
//   echo "예약할 수 없습니다. 좌석이 부족합니다.";
//   exit;
// }
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>예약 확인</title>
  <style>
    body { font-family: sans-serif; background: #f2f2f2; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { color: #005bac; }
    table { width: 100%; margin-top: 20px; border-collapse: collapse; }
    td { padding: 8px; border-bottom: 1px solid #ccc; }
    .btn { margin-top: 20px; display: inline-block; padding: 10px 20px; background: #005bac; color: white; text-decoration: none; border-radius: 4px; }
    .btn:hover { background: #003e80; }
  </style>
</head>
<body>
  <div class="container">
    <h2>예약 정보 확인</h2>
    <form action="reserve_process.php" method="post">
      <input type="hidden" name="flightNo" value="<?= htmlspecialchars($flightNo) ?>">
      <input type="hidden" name="departureDateTime" value="<?= htmlspecialchars($departureDate) ?>">
      <input type="hidden" name="seatClass" value="<?= htmlspecialchars($seatClass) ?>">
      <input type="hidden" name="price" value="<?= htmlspecialchars($price) ?>">

      <table>
        <tr><td>항공사</td><td><?= htmlspecialchars($flight['AIRLINE']) ?></td></tr>
        <tr><td>편명</td><td><?= htmlspecialchars($flight['FLIGHTNO']) ?></td></tr>
        <tr><td>출발지</td><td><?= htmlspecialchars($flight['DEPARTUREAIRPORT']) ?></td></tr>
        <tr><td>도착지</td><td><?= htmlspecialchars($flight['ARRIVALAIRPORT']) ?></td></tr>
        <tr><td>출발일</td><td><?= htmlspecialchars($flight['DEP_DATE']) ?> <?= htmlspecialchars($flight['DEP_TIME']) ?></td></tr>
        <tr><td>도착시간</td><td><?= htmlspecialchars($flight['ARR_TIME']) ?></td></tr>
        <tr><td>좌석등급</td><td><?= htmlspecialchars($seatClass) ?></td></tr>
        <tr><td>요금</td><td><?= number_format($flight['PRICE']) ?>원</td></tr>
        <tr><td>남은 좌석</td><td><?= $flight['AVAILABLE'] ?>석</td></tr>
      </table>

      <button type="submit" class="btn">결제하고 예약하기</button>
      <a href="search_result.php" class="btn" style="background: gray;">취소</a>
    </form>
  </div>
</body>
</html>
