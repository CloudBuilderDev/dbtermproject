<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

$cno = $_SESSION['cno'];
$tab = $_GET['tab'] ?? 'reservation';

$start = $_GET['startDate'] ?? date('Y-m-01'); // 이번달 1일
$end = $_GET['endDate'] ?? date('Y-m-d');      // 오늘
$type = $_GET['type'] ?? 'all'; // all / reserve / cancel

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>마이페이지 - 예약 내역</title>
  <style>
    body { font-family: sans-serif; background: #f4f6f8; margin: 0; padding: 0; }
    .container { max-width: 1200px; margin: auto; padding: 30px; background: white; }
    h2 { color: #005bac; }
    form { margin: 20px 0; }
    label { margin-right: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
    th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: center; }
    th { background-color: #e8f1fa; }
    .filter-row { margin-bottom: 15px; }
    .btn { padding: 6px 14px; background: #005bac; color: white; border: none; border-radius: 4px; cursor: pointer; }
  </style>
</head>
<body>
  <div class="container">
    <h2>예약/취소 내역 조회</h2>

    <form method="get" action="mypage.php">
      <input type="hidden" name="tab" value="reservation">
      <label>기간:
        <input type="date" name="startDate" value="<?= htmlspecialchars($start) ?>">
        ~
        <input type="date" name="endDate" value="<?= htmlspecialchars($end) ?>">
      </label>
      <label>
        <select name="type">
          <option value="all" <?= $type == 'all' ? 'selected' : '' ?>>전체</option>
          <option value="reserve" <?= $type == 'reserve' ? 'selected' : '' ?>>예약만</option>
          <option value="cancel" <?= $type == 'cancel' ? 'selected' : '' ?>>취소만</option>
        </select>
      </label>
      <button type="submit" class="btn">조회</button>
    </form>

    <table>
      <tr>
        <th>항공사</th><th>편명</th><th>출발</th><th>도착</th>
        <th>출발시간</th><th>도착시간</th>
        <th>좌석등급</th><th>금액</th><th>처리일시</th><th>구분</th>
      </tr>
      <?php
        $query = "
          SELECT 
            a.airline, r.flightNo, a.departureAirport, a.arrivalAirport,
            TO_CHAR(a.departureDateTime, 'YYYY-MM-DD HH24:MI') AS dep_time,
            TO_CHAR(a.arrivalDateTime, 'YYYY-MM-DD HH24:MI') AS arr_time,
            r.seatClass, r.payment AS amount,
            TO_CHAR(r.reserveDateTime, 'YYYY-MM-DD HH24:MI') AS action_time,
            '예약' AS type
          FROM RESERVE r
          JOIN AIRPLAIN a ON r.flightNo = a.flightNo AND r.departureDateTime = a.departureDateTime
          WHERE r.cno = :cno
            AND r.reserveDateTime BETWEEN TO_DATE(:start, 'YYYY-MM-DD') AND TO_DATE(:end || ' 23:59:59', 'YYYY-MM-DD HH24:MI:SS')
        ";

        if ($type === 'cancel') {
          $query = "
            SELECT 
              a.airline, c.flightNo, a.departureAirport, a.arrivalAirport,
              TO_CHAR(a.departureDateTime, 'YYYY-MM-DD HH24:MI') AS dep_time,
              TO_CHAR(a.arrivalDateTime, 'YYYY-MM-DD HH24:MI') AS arr_time,
              c.seatClass, c.refundAmount AS amount,
              TO_CHAR(c.cancelDateTime, 'YYYY-MM-DD HH24:MI') AS action_time,
              '취소' AS type
            FROM CANCEL c
            JOIN AIRPLAIN a ON c.flightNo = a.flightNo AND c.departureDateTime = a.departureDateTime
            WHERE c.cno = :cno
              AND c.cancelDateTime BETWEEN TO_DATE(:start, 'YYYY-MM-DD') AND TO_DATE(:end || ' 23:59:59', 'YYYY-MM-DD HH24:MI:SS')
          ";
        } else if ($type === 'all') {
          $query = "
            SELECT * FROM (
              SELECT 
                a.airline, r.flightNo, a.departureAirport, a.arrivalAirport,
                TO_CHAR(a.departureDateTime, 'YYYY-MM-DD HH24:MI') AS dep_time,
                TO_CHAR(a.arrivalDateTime, 'YYYY-MM-DD HH24:MI') AS arr_time,
                r.seatClass, r.payment AS amount,
                TO_CHAR(r.reserveDateTime, 'YYYY-MM-DD HH24:MI') AS action_time,
                '예약' AS type
              FROM RESERVE r
              JOIN AIRPLAIN a ON r.flightNo = a.flightNo AND r.departureDateTime = a.departureDateTime
              WHERE r.cno = :cno
                AND r.reserveDateTime BETWEEN TO_DATE(:start, 'YYYY-MM-DD') AND TO_DATE(:end || ' 23:59:59', 'YYYY-MM-DD HH24:MI:SS')
              UNION ALL
              SELECT 
                a.airline, c.flightNo, a.departureAirport, a.arrivalAirport,
                TO_CHAR(a.departureDateTime, 'YYYY-MM-DD HH24:MI') AS dep_time,
                TO_CHAR(a.arrivalDateTime, 'YYYY-MM-DD HH24:MI') AS arr_time,
                c.seatClass, c.refundAmount AS amount,
                TO_CHAR(c.cancelDateTime, 'YYYY-MM-DD HH24:MI') AS action_time,
                '취소' AS type
              FROM CANCEL c
              JOIN AIRPLAIN a ON c.flightNo = a.flightNo AND c.departureDateTime = a.departureDateTime
              WHERE c.cno = :cno
                AND c.cancelDateTime BETWEEN TO_DATE(:start, 'YYYY-MM-DD') AND TO_DATE(:end || ' 23:59:59', 'YYYY-MM-DD HH24:MI:SS')
            ) ORDER BY action_time DESC
          ";
        }

        $stmt = $conn->prepare($query);
        $stmt->execute([':cno' => $cno, ':start' => $start, ':end' => $end]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) === 0) {
          echo "<tr><td colspan='10'>조회된 내역이 없습니다.</td></tr>";
        } else {
          foreach ($results as $r) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['AIRLINE']) . "</td>";
            echo "<td>" . htmlspecialchars($r['FLIGHTNO']) . "</td>";
            echo "<td>" . htmlspecialchars($r['DEPARTUREAIRPORT']) . "</td>";
            echo "<td>" . htmlspecialchars($r['ARRIVALAIRPORT']) . "</td>";
            echo "<td>" . $r['DEP_TIME'] . "</td>";
            echo "<td>" . $r['ARR_TIME'] . "</td>";
            echo "<td>" . $r['SEATCLASS'] . "</td>";
            echo "<td>" . number_format($r['AMOUNT']) . "원</td>";
            echo "<td>" . $r['ACTION_TIME'] . "</td>";
            echo "<td>" . $r['TYPE'] . "</td>";
            echo "</tr>";
          }
        }
      ?>
    </table>
  </div>
</body>
</html>
