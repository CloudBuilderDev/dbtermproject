<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id']) || $_SESSION['cno'] !== 'c0') {
  echo "<script>alert('접근 권한이 없습니다.'); location.href='login.php';</script>";
  exit;
}

// 항공사별 예약 통계 필터링
$airlineFilter = $_GET['airline'] ?? 'all';
$airlineWhere = ($airlineFilter !== 'all') ? "WHERE a.airline = :airline" : "";

$sql1 = "
  SELECT a.airline AS 항공사, COUNT(*) AS 예약건수, SUM(r.payment) AS 총결제금액
  FROM RESERVE r
  JOIN AIRPLAIN a ON r.flightNo = a.flightNo AND r.departureDateTime = a.departureDateTime
  $airlineWhere
  GROUP BY a.airline
  ORDER BY 총결제금액 DESC
";
$stmt1 = $conn->prepare($sql1);
if ($airlineFilter !== 'all') {
  $stmt1->execute([':airline' => $airlineFilter]);
} else {
  $stmt1->execute();
}
$stats1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

// 고객별 누적 예약 금액 순위 (정렬 필터)
$sort = $_GET['sort'] ?? 'amount';
$order = strtoupper($_GET['order'] ?? 'DESC');
$orderBy = match ($sort) {
  'name' => 'c.name',
  default => 'SUM(r.payment)'
};

$sql2 = "
  SELECT c.name AS 고객명, c.id AS 아이디, SUM(r.payment) AS 총결제금액,
         RANK() OVER (ORDER BY $orderBy $order) AS 결제순위
  FROM CUSTOMER c
  JOIN RESERVE r ON c.cno = r.cno
  GROUP BY c.name, c.id
";
$stmt2 = $conn->query($sql2);
$stats2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// 항공사 목록
$airlines = $conn->query("SELECT DISTINCT airline FROM AIRPLAIN ORDER BY airline")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>관리자 통계 페이지</title>
  <style>
    body { font-family: sans-serif; margin: 20px; background: #f0f2f5; }
    .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h1, h2 { color: #333; }
    form.filter { background: #f9fafc; padding: 20px; margin-bottom: 30px; border: 1px solid #ddd; border-radius: 8px; }
    .form-row { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
    .form-row label { font-weight: bold; min-width: 100px; }
    select, input[type="date"], button { padding: 6px 10px; border-radius: 5px; border: 1px solid #ccc; }
    button { background-color: #007bff; color: white; border: none; }
    button:hover { background-color: #0056b3; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
    th { background-color: #f1f1f1; }
  </style>
</head>
<body>
<div class="container">
  <h1>관리자 통계 정보</h1>

  <h2>1. 항공사별 예약 건수 및 총 결제금액</h2>
  <form method="get" class="filter">
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
    <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
    <div class="form-row">
      <label>항공사:
        <select name="airline">
          <option value="all" <?= $airlineFilter === 'all' ? 'selected' : '' ?>>전체</option>
          <?php foreach ($airlines as $al): ?>
            <option value="<?= $al ?>" <?= $airlineFilter === $al ? 'selected' : '' ?>><?= $al ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit">필터 적용</button>
    </div>
  </form>

  <table>
    <tr><th>항공사</th><th>예약건수</th><th>총 결제금액</th></tr>
    <?php foreach ($stats1 as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['항공사']) ?></td>
        <td><?= $row['예약건수'] ?></td>
        <td><?= number_format($row['총결제금액']) ?>원</td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h2>2. 고객별 누적 결제금액 순위</h2>
  <form method="get" class="filter">
    <input type="hidden" name="airline" value="<?= htmlspecialchars($airlineFilter) ?>">
    <div class="form-row">
      <label>정렬 기준:
        <select name="sort">
          <option value="amount" <?= $sort === 'amount' ? 'selected' : '' ?>>결제금액</option>
          <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>이름순</option>
        </select>
      </label>
      <label>정렬 방식:
        <select name="order">
          <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>내림차순</option>
          <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>오름차순</option>
        </select>
      </label>
      <button type="submit">정렬 적용</button>
    </div>
  </form>

  <table>
    <tr><th>순위</th><th>고객명</th><th>아이디</th><th>총 결제금액</th></tr>
    <?php foreach ($stats2 as $row): ?>
      <tr>
        <td><?= $row['결제순위'] ?></td>
        <td><?= htmlspecialchars($row['고객명']) ?></td>
        <td><?= htmlspecialchars($row['아이디']) ?></td>
        <td><?= number_format($row['총결제금액']) ?>원</td>
      </tr>
    <?php endforeach; ?>
  </table>

  <a href="search.php">메인페이지로 돌아가기</a>
</div>
</body>
</html>
