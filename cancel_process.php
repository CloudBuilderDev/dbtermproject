<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

// cancel_form.php로부터 POST를 받음
$flightNo = $_POST['flightNo'];
$departureDateTime = $_POST['departureDateTime'];
$seatClass = $_POST['seatClass'];
$payment = $_POST['payment'];
$cno = $_SESSION['cno'];

$now = new DateTime();
$departure = new DateTime($departureDateTime);
$interval = $now->diff($departure);
$daysBefore = (int)$interval->format('%r%a');  // 음수일 수 있음

// 위약금 계산
if ($daysBefore > 15) {
  $penalty = 150000;
} elseif ($daysBefore >= 4) {
  $penalty = 180000;
} elseif ($daysBefore >= 1) {
  $penalty = 250000;
} else {
  $penalty = $payment; // 당일은 전액 위약금
}

$refund = max(0, $payment - $penalty);

// 트랜잭션 처리
try {
  $conn->beginTransaction();

  // 예약 삭제
  $deleteSql = "
    DELETE FROM RESERVE
    WHERE flightNo = :flightNo AND departureDateTime = TO_DATE(:departureDateTime, 'YYYY-MM-DD HH24:MI')
      AND seatClass = :seatClass AND cno = :cno
  ";
  $stmt = $conn->prepare($deleteSql);
  $stmt->execute([
    ':flightNo' => $flightNo,
    ':departureDateTime' => $departureDateTime,
    ':seatClass' => $seatClass,
    ':cno' => $cno
  ]);

  // 취소 테이블에 기록
  $insertSql = "
    INSERT INTO CANCEL (flightNo, departureDateTime, seatClass, refund, cancelDateTime, cno)
    VALUES (:flightNo, TO_DATE(:departureDateTime, 'YYYY-MM-DD HH24:MI'), :seatClass, :refund, SYSDATE, :cno)
  ";
  $stmt = $conn->prepare($insertSql);
  $stmt->execute([
    ':flightNo' => $flightNo,
    ':departureDateTime' => $departureDateTime,
    ':seatClass' => $seatClass,
    ':refund' => $refund,
    ':cno' => $cno
  ]);

  $conn->commit();

  echo "<script>
    alert('예약이 성공적으로 취소되었습니다. 환불 금액: " . number_format($refund) . "원');
    window.location.href = 'mypage.php?tab=history';
  </script>";
} catch (Exception $e) {
  $conn->rollBack();
  echo "오류 발생: " . $e->getMessage();
}
?>
