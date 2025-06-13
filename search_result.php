<?php
session_start();
include 'db.php';

//POST 요청 받기
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: main.php");
  exit;
}

//POST 요청으로 출발 공항, 도착 공항, 출발 날짜, 좌석등급, 정렬 옵션을 받음
$departure = $_POST['departureAirport'];
$arrival = $_POST['arrivalAirport'];
$departureDate = trim($_POST['departureDate'] ?? '');
$seatClass = $_POST['seatClass'] ?? 'ALL';
$sortOption = $_POST['sortOption'] ?? 'price'; // 이때, 정렬 옵션은 첫 검색 시, price를 기준으로 정렬, 이후 새 정렬옵션으로 다시 POST요청을 보낼 경우 처리 
echo "DEBUG: $departure $arrival $departureDate $seatClass $sortOption<b>";
var_dump($departureDate);

// 정렬 조건 선택
$orderBy = ($sortOption === 'time') ? 'a.departureDateTime ASC' : 's.price ASC';

$where = [];
$params = [];

$where[] = "a.departureAirport = :dep";
$params[':dep'] = $departure;

$where[] = "a.arrivalAirport = :arr";
$params[':arr'] = $arrival;

// 출발 날짜가 있다면, 다음과 같이 값을 바인딩하고 없다면 SYSDATE, 현재 시간 이후의 값들로 where조건을 형성
if ($departureDate !== '') {
  $where[] = "TRUNC(a.departureDateTime) = TO_DATE(:dep_date, 'YYYY-MM-DD')";
  $params[':dep_date'] = $departureDate;
} else {
  $where[] = "a.departureDateTime >= SYSDATE";
}

if ($seatClass !== 'ALL') {
  $where[] = "s.seatClass = :seatClass";
  $params[':seatClass'] = $seatClass;
}

// where 조건을 AND로 모두 연결
$whereClause = implode(' AND ', $where);

$sql = "
SELECT 
  a.airline, a.flightNo, a.departureAirport, a.arrivalAirport,
  TO_CHAR(a.departureDateTime, 'YYYY-MM-DD-HH24:MI') AS dep_dateTime,
  TO_CHAR(a.departureDateTime, 'YYYY-MM-DD') AS dep_date,
  TO_CHAR(a.departureDateTime, 'HH24:MI') AS dep_time,
  TO_CHAR(a.arrivalDateTime, 'HH24:MI') AS arr_time,
  s.seatClass,
  s.price,
  GET_REMAINING_SEATS(a.flightNo, a.departureDateTime, s.seatClass) AS available
FROM AIRPLAIN a
JOIN SEATS s
  ON a.flightNo = s.flightNo AND a.departureDateTime = s.departureDateTime
WHERE $whereClause
ORDER BY $orderBy
";

$stmt = $conn->prepare($sql);
$stmt->execute($params); 
$flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
echo "[DEBUG] 최종 SQL 쿼리문:\n$sql\n\n";
echo "[DEBUG] 바인딩 파라미터:\n";
print_r($params);
var_dump($departureDate);
echo "</pre>";

?>


<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>항공편 검색 결과</title>
  <style>
    body { font-family: sans-serif; background: #f2f2f2; margin: 0; padding: 0; }
    header {
      background: #005bac;
      color: white;
      padding: 10px 20px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .container { max-width: 1000px; margin: auto; background: white; padding: 30px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border-bottom: 1px solid #ccc; text-align: center; }
    th { background: #e8f1fa; }
    .btn { margin-top: 20px; display: inline-block; padding: 10px 20px; background: #005bac; color: white; text-decoration: none; border-radius: 4px; }
    .btn:hover { background: #003e80; }
  </style>
</head>
<body>
  <header>
    <h1>CNU Airline - 검색 결과</h1>
    <div>
      <?php if (isset($_SESSION['id'])): ?>
        <span><?= $_SESSION['name'] ?> 회원님</span>
        <a href="mypage.php" style="color: white; margin-left: 10px;">마이페이지</a>
        <a href="logout.php" style="color: white; margin-left: 10px;">로그아웃</a>
      <?php else: ?>
        <a href="login.php" style="color: white;">로그인</a>
      <?php endif; ?>
    </div>
  </header>

  <div class="container">
    <h2>검색 결과</h2>

    <form method="post" action="search_result.php">
      <!-- 정렬 옵션을 유지하며 다시 검색할 수 있도록 -->
      <input type="hidden" name="departureAirport" value="<?= htmlspecialchars($departure) ?>">
      <input type="hidden" name="arrivalAirport" value="<?= htmlspecialchars($arrival) ?>">
      <input type="hidden" name="departureDate" value="<?= htmlspecialchars($departureDate) ?>">
      <input type="hidden" name="seatClass" value="<?= htmlspecialchars($seatClass) ?>">
      <label>정렬 기준:
        <select name="sortOption" onchange="this.form.submit()">
          <option value="price" <?= $sortOption === 'price' ? 'selected' : '' ?>>요금순</option>
          <option value="time" <?= $sortOption === 'time' ? 'selected' : '' ?>>출발시간순</option>
        </select>
      </label>
    </form>

    <?php if (count($flights) === 0): ?>
      <p>조건에 맞는 항공편이 없습니다.</p>
    <?php else: ?>
      <table>
        <tr>
          <th>항공사</th>
          <th>편명</th>
          <th>출발지</th>
          <th>도착지</th>
          <th>출발일</th>
          <th>시간</th>
          <th>좌석종류</th>
          <th>남은좌석</th>
          <th>요금</th>
        </tr>
        <?php foreach ($flights as $f): ?>
        <tr>
          <td><?= htmlspecialchars($f['AIRLINE'] ?? '') ?></td>
          <td><?= htmlspecialchars($f['FLIGHTNO'] ?? '') ?></td>
          <td><?= htmlspecialchars($f['DEPARTUREAIRPORT'] ?? '') ?></td>
          <td><?= htmlspecialchars($f['ARRIVALAIRPORT'] ?? '') ?></td>
          <td><?= htmlspecialchars($f['DEP_DATE'] ?? '') ?></td>
          <td><?= htmlspecialchars($f['DEP_TIME'] ?? '') ?> ~ <?= htmlspecialchars($f['ARR_TIME'] ?? '') ?></td>
          <td><?= htmlspecialchars($f['SEATCLASS'] ?? '') ?></td>
          <td><?= htmlspecialchars($f['AVAILABLE'] ?? '0') ?></td>
          <td><?= number_format($f['PRICE'] ?? 0) ?>원</td>
          <td>
            <?php if ($f['AVAILABLE'] > 0): ?>
              <form class="inline" action="reserve_form.php" method="post">
                <input type="hidden" name="flightNo" value="<?= $f['FLIGHTNO'] ?>">
                <input type="hidden" name="departureDateTime" value="<?= $f['DEP_DATETIME'] ?>">
                <input type="hidden" name="seatClass" value="<?= $f['SEATCLASS'] ?>">
                <input type="hidden" name="price" value="<?= $f['PRICE'] ?>">
                <input type="submit" value="예약하기">
              </form>
            <?php else: ?>
              <span style="color:gray">예약 불가</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
    <a href="main.php" class="btn">← 메인으로 돌아가기</a>
  </div>
</body>
</html>
