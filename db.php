<?php
$tns = "
(DESCRIPTION =
  (ADDRESS_LIST =
    (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
  )
  (CONNECT_DATA = (SERVICE_NAME = XE))
)";
$dsn = "oci:dbname=" . $tns . ";charset=utf8";
$username = "d202102675";
$password = "1111";

try {
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->query("SELECT * FROM dual"); // Oracle의 테스트용 가상 테이블
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // echo "✅ Oracle 연결 성공!<br>";
    // echo "쿼리 결과: " . htmlspecialchars(print_r($row, true));
} catch (PDOException $e) {
    echo "❌ 연결 실패: " . $e->getMessage();
}
?>
