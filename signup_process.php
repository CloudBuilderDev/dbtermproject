<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = trim($_POST['id']);
  $name = trim($_POST['name']);
  $passwd = trim($_POST['passwd']);
  $email = trim($_POST['email']);
  $passportNumber = trim($_POST['passportNumber']);

  // 추가 유효성 검사 (서버 측)
  if (preg_match('/[\sㄱ-ㅎㅏ-ㅣ가-힣]/u', $id) ||
      preg_match('/[\sㄱ-ㅎㅏ-ㅣ가-힣]/u', $passwd) ||
      strlen($passportNumber) !== 9 ||
      !preg_match('/^[a-zA-Z0-9]{9}$/', $passportNumber)) {
    die("❌ 잘못된 입력입니다. 아이디/비밀번호는 공백 및 한글을 포함할 수 없으며, 여권번호는 영문 또는 숫자 9자리여야 합니다.");
  }

  try {
    // CNO 생성
    $stmt = $conn->query("SELECT LPAD(NVL(MAX(TO_NUMBER(SUBSTR(cno, 2))), 0) + 1, 3, '0') AS next_cno
                          FROM CUSTOMER
                          WHERE REGEXP_LIKE(SUBSTR(cno, 2), '^[0-9]+')");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $newCno = 'C' . $row['NEXT_CNO'];

    // INSERT 실행
    $sql = "INSERT INTO CUSTOMER (cno, id, name, passwd, email, passportNumber)
            VALUES (:cno, :id, :name, :passwd, :email, :passportNumber)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
      ':cno' => $newCno,
      ':id' => $id,
      ':name' => $name,
      ':passwd' => $passwd,
      ':email' => $email,
      ':passportNumber' => $passportNumber
    ]);

  } catch (PDOException $e) {
    die("회원가입 실패: " . $e->getMessage());
  }
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>회원가입 완료</title>
  <style>
    body {
      font-family: sans-serif;
      background-color: #f2f2f2;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .success-box {
      background-color: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      text-align: center;
      width: 400px;
    }
    .success-box h2 {
      color: #2e8b57;
      margin-bottom: 20px;
    }
    .success-box p {
      font-size: 15px;
      margin-bottom: 25px;
    }
    .success-box a {
      display: inline-block;
      padding: 10px 20px;
      background-color: #005bac;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-weight: bold;
    }
    .success-box a:hover {
      background-color: #003e80;
    }
  </style>
</head>
<body>
<div class="success-box">
  <h2>✅ 회원가입이 완료되었습니다!</h2>
  <p><?= htmlspecialchars($name) ?>님, 이제 로그인하여 서비스를 이용하실 수 있습니다.</p>
  <a href="login.php">로그인하러 가기</a>
</div>
</body>
</html>
<?php
} else {
  header("Location: signup.php");
  exit;
}
?>
