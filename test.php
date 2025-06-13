$sql = "SELECT * FROM CUSTOMER WHERE id = 'happy7656'";
$stmt = $conn->query($sql);

if ($stmt->rowCount() == 0) {
    echo "❌ 하드코딩된 ID도 조회 안됨";
} else {
    echo "✅ 하드코딩 ID 조회 성공<br>";
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "ID: " . $user['id'] . "<br>";
    echo "PASSWD: " . $user['passwd'] . "<br>";
}
