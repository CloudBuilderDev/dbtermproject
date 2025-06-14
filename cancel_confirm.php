<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

$flightNo = $_POST['flightNo'];
$departureDateTime = $_POST['departureDateTime'];
$seatClass = $_POST['seatClass'];
$payment = $_POST['payment'];

$now = new DateTime();
$departure = new DateTime($departureDateTime);
$daysBefore = (int)$now->diff($departure)->format('%r%a');

if ($daysBefore > 15) {
  $penalty = 150000;
} elseif ($daysBefore >= 4) {
  $penalty = 180000;
} elseif ($daysBefore >= 1) {
  $penalty = 250000;
} else {
  $penalty = $payment;
}

$refund = max(0, $payment - $penalty);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>예약 취소 확인</title>
  <style>
    body { font-family: sans-serif; padding: 30px; background: #f4f4f4; }
    .box { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 0 8px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
    h2 { color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    td { padding: 8px; border-bottom: 1px solid #ddd; }
    .btn { padding: 10px 15px; background: #c00; color: white; border: none; border-radius: 4px; cursor: pointer; }
    .btn:hover { background: #900; }
    .back { margin-top: 15px; display: inline-block; }
  </style>
</head>
<body>
  <div class="box">
    <h2>예약 취소 확인</h2>
    <table>
      <tr><td>편명</td><td><?= htmlspecialchars($flightNo) ?></td></tr>
      <tr><td>출발일시</td><td><?= htmlspecialchars($departureDateTime) ?></td></tr>
      <tr><td>좌석 등급</td><td><?= htmlspecialchars($seatClass) ?></td></tr>
      <tr><td>결제 금액</td><td><?= number_format($payment) ?>원</td></tr>
      <tr><td>취소 위약금</td><td><?= number_format($penalty) ?>원</td></tr>
      <tr><td>환불 금액</td><td><strong><?= number_format($refund) ?>원</strong></td></tr>
    </table>

    <form action="cancel_process.php" method="post" style="margin-top:20px;">
      <input type="hidden" name="flightNo" value="<?= $flightNo ?>">
      <input type="hidden" name="departureDateTime" value="<?= $departureDateTime ?>">
      <input type="hidden" name="seatClass" value="<?= $seatClass ?>">
      <input type="hidden" name="payment" value="<?= $payment ?>">
      <input type="hidden" name="penalty" value="<?= $penalty ?>">
      <input type="hidden" name="refund" value="<?= $refund ?>">
      <button type="submit" class="btn">확정 취소</button>
    </form>

    <a href="javascript:history.back()" class="back">← 돌아가기</a>
  </div>
</body>
</html>
