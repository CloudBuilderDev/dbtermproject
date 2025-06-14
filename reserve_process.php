<?php
session_start();
include 'db.php';

// 로그인 여부 체크 
if (!isset($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

// 예약 정보 받기
$flightNo = $_POST['flightNo'];
$departureDateTime = $_POST['departureDateTime'];
$seatClass = $_POST['seatClass'];
$price = $_POST['price'];

// cno와 email은 세션에 저장된 값을 가져온다.
$cno = $_SESSION['cno']; 
$email = $_SESSION['email'];
echo "DEBUG: $flightNo $departureDateTime $seatClass $price $cno $email";


// 중복 예약 확인 로직
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
  echo "<script>
    alert('⚠️ 이미 동일한 조건으로 예약이 되어 있습니다.');
    window.location.href = 'search.php';
  </script>";
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


// 이메일 보내기 로직
// PHPMailer를 사용
require 'PHPMailer-master/src/PHPMailer.php'; 
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true); // PHPMailer를 이용하여 메일 생성

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    $mail->Username = 'happy765696@gmail.com';       // 실제 Gmail 주소
    $mail->Password = 'zrhzbfzfdashfnyt';         // 16자리 앱 비밀번호
    $mail->SMTPSecure = 'tls';                     // 또는 'ssl'
    $mail->Port = 587;

    $mail->setFrom('happy765696@gmail.com', 'CNU Airline');
    $mail->addAddress($email);

    $mail->Subject = 'CNU Airline';
    $mail->Body =
"안녕하세요 고객님,

예약이 성공적으로 완료되었습니다.

- 항공편: $flightNo
- 출발일시: $departureDateTime
- 좌석 등급: $seatClass
- 결제 금액: " . number_format($price) . "원

즐거운 여행 되시기 바랍니다.

CNU Airline 드림.";

    $mail->send();
    // echo "이메일 전송 성공!";
} catch (Exception $e) {
    echo "이메일 전송 실패: {$mail->ErrorInfo}";
}

echo "<script>
  alert('예약이 완료되었습니다!\\n탑승권이 이메일로 발송되었습니다.');
  window.location.href = 'mypage.php?tab=history';
</script>";
exit;
?>
