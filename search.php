<?php
session_start();
include 'db.php';

// POST로 검색에 필요한 다음 정보들을 받음
$departure = $_POST['departureAirport'] ?? '';
$arrival = $_POST['arrivalAirport'] ?? '';
$departureDate = trim($_POST['departureDate'] ?? '');
$seatClass = $_POST['seatClass'] ?? 'ALL';
$sortOption = $_POST['sortOption'] ?? 'price';

$flights = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $orderBy = ($sortOption === 'time') ? 'a.departureDateTime ASC' : 's.price ASC';

  $where = [];
  $params = [];

  $where[] = "a.departureAirport = :dep";
  $params[':dep'] = $departure;

  $where[] = "a.arrivalAirport = :arr";
  $params[':arr'] = $arrival;

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

  $whereClause = implode(' AND ', $where);

  $sql = "
    SELECT 
      a.airline, a.flightNo, a.departureAirport, a.arrivalAirport,
      TO_CHAR(a.departureDateTime, 'YYYY-MM-DD-HH24:MI') AS DEP_DATETIME,
      TO_CHAR(a.departureDateTime, 'YYYY-MM-DD') AS DEP_DATE,
      TO_CHAR(a.departureDateTime, 'HH24:MI') AS DEP_TIME,
      TO_CHAR(a.arrivalDateTime, 'HH24:MI') AS ARR_TIME,
      s.seatClass, s.price,
      GET_REMAINING_SEATS(a.flightNo, a.departureDateTime, s.seatClass) AS AVAILABLE
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
  echo "</pre>";

  echo "<pre>";
  echo "DEBUG POST 값들:\n";
  print_r($_POST);
  echo "</pre>";
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>CNU Airline - 항공편 검색</title>
  <style>
    body { font-family: sans-serif; background: #f2f2f2; margin: 0; padding: 0; }
    header {
      background: #005bac; color: white;
      padding: 10px 20px; display: flex; justify-content: space-between; align-items: center;
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
  <h1>CNU Airline</h1>
  <div>
    <?php if (isset($_SESSION['id'])): ?>
      <span><?= $_SESSION['name'] ?> 회원님</span>
      <a href="mypage.php" style="color: white; margin-left: 10px;">마이페이지</a>
      
      <?php if (isset($_SESSION['cno']) && $_SESSION['cno'] === 'c0'): ?>
        <a href="admin_stats.php" style="color: yellow; margin-left: 10px;">[관리자페이지]</a>
      <?php endif; ?>

      <a href="logout.php" style="color: white; margin-left: 10px;">로그아웃</a>
    <?php else: ?>
      <a href="login.php" style="color: white;">로그인</a>
    <?php endif; ?>
  </div>
</header>


  <div class="container">
    <h2>항공편 검색</h2>
    <form method="post">
      <label>출발공항:
        <select name="departureAirport" required>
          <option value="ICN" <?= $departure == 'ICN' ? 'selected' : '' ?>>인천</option>
          <option value="PUS" <?= $departure == 'PUS' ? 'selected' : '' ?>>부산</option>
        </select>
      </label>
      <label>도착공항:
        <select name="arrivalAirport" required>
          <option value="NRT" <?= $arrival == 'NRT' ? 'selected' : '' ?>>도쿄</option>
          <option value="JFK" <?= $arrival == 'JFK' ? 'selected' : '' ?>>뉴욕</option>
          <option value="SFO" <?= $arrival == 'SFO' ? 'selected' : '' ?>>샌프란시스코</option>
        </select>
      </label>
      <label>출발일:
        <input type="date" name="departureDate" value="<?= htmlspecialchars($departureDate) ?>">
      </label>
      <label>좌석등급:
        <input type="radio" name="seatClass" value="ALL" <?= $seatClass == 'ALL' ? 'checked' : '' ?>>전체
        <input type="radio" name="seatClass" value="ECONOMY" <?= $seatClass == 'ECONOMY' ? 'checked' : '' ?>>이코노미
        <input type="radio" name="seatClass" value="BUSINESS" <?= $seatClass == 'BUSINESS' ? 'checked' : '' ?>>비즈니스
      </label>
      <label>정렬:
        <select name="sortOption">
          <option value="price" <?= $sortOption == 'price' ? 'selected' : '' ?>>요금순</option>
          <option value="time" <?= $sortOption == 'time' ? 'selected' : '' ?>>출발시간순</option>
        </select>
      </label>
      <input type="submit" value="검색">
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
      <h2>검색 결과</h2>
      <?php if (count($flights) === 0): ?>
        <p>조건에 맞는 항공편이 없습니다.</p>
      <?php else: ?>
        <table>
          <tr>
            <th>항공사</th><th>편명</th><th>출발지</th><th>도착지</th><th>출발일</th>
            <th>시간</th><th>좌석종류</th><th>남은좌석</th><th>요금</th><th>예약</th>
          </tr>
          <?php foreach ($flights as $f): ?>
          <tr>
            <td><?= htmlspecialchars($f['AIRLINE']) ?></td>
            <td><?= htmlspecialchars($f['FLIGHTNO']) ?></td>
            <td><?= htmlspecialchars($f['DEPARTUREAIRPORT']) ?></td>
            <td><?= htmlspecialchars($f['ARRIVALAIRPORT']) ?></td>
            <td><?= htmlspecialchars($f['DEP_DATE']) ?></td>
            <td><?= htmlspecialchars($f['DEP_TIME']) ?> ~ <?= htmlspecialchars($f['ARR_TIME']) ?></td>
            <td><?= htmlspecialchars($f['SEATCLASS']) ?></td>
            <td><?= htmlspecialchars($f['AVAILABLE']) ?></td>
            <td><?= number_format($f['PRICE']) ?>원</td>
            <td>
              <?php if ($f['AVAILABLE'] > 0): ?>
                <form method="post" action="reserve_form.php">
                  <input type="hidden" name="flightNo" value="<?= $f['FLIGHTNO'] ?>">
                  <input type="hidden" name="departureDateTime" value="<?= $f['DEP_DATETIME'] ?>">
                  <input type="hidden" name="seatClass" value="<?= $f['SEATCLASS'] ?>">
                  <input type="hidden" name="price" value="<?= $f['PRICE'] ?>">
                  <input type="hidden" name="departureAirport" value="<?= $f['DEPARTUREAIRPORT'] ?>">
                  <input type="hidden" name="arrivalAirport" value="<?= $f['ARRIVALAIRPORT'] ?>">
                  <input type="submit" value="예약하기">
                </form>
              <?php else: ?>
                <span style="color:gray">예약불가</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
