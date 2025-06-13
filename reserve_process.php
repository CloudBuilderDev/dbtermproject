<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

// 예약 정보 받기
$flightNo = $_POST['flightNo'];
$departureDateTime = $_POST['departureDateTime'];
$seatClass = $_POST['seatClass'];
$price = $_POST['price'];
$cno = $_SESSION['cno']; // 회원 번호
$email = $_SESSION['email'];
echo "DEBUG: $flightNo $departureDateTime $seatClass $price $cno $email";


// 중복 예약 확인
$checkSql = "
  SELECT COUNT(*) AS cnt
  FROM RESERVE
  WHERE flightNo = :flightNo
    AND departureDateTime = TO_DATE(:depDateTime, 'YYYY-MM-DD HH24:MI:SS')
    AND seatClass = :seatClass
    AND cno = :cno
";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->execute([
  ':flightNo' => $flightNo,
  ':depDateTime' => $departureDateTime,
  ':seatClass' => $seatClass,
  ':cno' => $cno
]);
$row = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($row['CNT'] > 0) {
  echo "⚠️ 이미 동일한 조건으로 예약이 되어 있습니다.";
  exit;
}


// 예약 정보 INSERT
$insertSql = "
  INSERT INTO RESERVE (flightNo, departureDateTime, seatClass, payment, reserveDateTime,cno)
  VALUES (:flightNo, TO_DATE(:depDateTime, 'YYYY-MM-DD HH24:MI:SS'), :seatClass, :price, SYSDATE, :cno)
";
$stmt = $conn->prepare($insertSql);
$stmt->execute([
  ':flightNo' => $flightNo,
  ':depDateTime' => $departureDateTime,
  ':seatClass' => $seatClass,
  ':price' => $price,
  ':cno' => $cno
]);




// $userEmail = $_SESSION['email']; // 세션에 이메일 저장되어 있다고 가정
// $subject = "CNU Airline 탑승권 확인서";
// $message = "예약이 완료되었습니다.\n편명: $flightNo\n출발일: $departureDateTime\n좌석: $seatClass\n요금: $price 원";
// $headers = "From: no-reply@cnu-airline.com";

// 실제 서버에서는 메일 서버 설정 필요
// mail($userEmail, $subject, $message, $headers);

// echo "<h2>예약이 성공적으로 완료되었습니다.</h2>";
// echo "<p>이메일로 탑승권이 전송되었습니다.</p>";
// echo "<a href='main.php'>메인으로 이동</a>";

echo "<script>
  alert('예약이 완료되었습니다!\\n탑승권이 이메일로 발송되었습니다.');
  window.location.href = 'mypage.php?tab=reservation';
</script>";
exit;
?>
