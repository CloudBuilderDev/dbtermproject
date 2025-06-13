<?php
session_start();
include 'db.php';

$id = trim($_POST['id']);
$passwd = trim($_POST['passwd']);

$sql = "SELECT * FROM CUSTOMER WHERE LOWER(id) = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['PASSWD'] !== $passwd) {
    echo "아이디 또는 비밀번호가 틀렸습니다.";
} else {
    $_SESSION['id'] = $user['ID'];
    $_SESSION['name'] = $user['NAME'];
    $_SESSION['cno'] = $user['CNO'];
    $_SESSION['email'] = $user['EMAIL'];
    echo "로그인 성공!";
    header("Location: main.php");
}
?>
