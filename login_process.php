<?php
session_start();
include 'db.php';

$id = trim($_POST['id']);
$passwd = trim($_POST['passwd']);

$sql = "SELECT * FROM CUSTOMER WHERE LOWER(id) = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => strtolower($id)]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['PASSWD'] !== $passwd) {
    echo "<script>alert('아이디 또는 비밀번호가 틀렸습니다.'); history.back();</script>";
    exit;
}

// 세션 저장
$_SESSION['id'] = $user['ID'];
$_SESSION['name'] = $user['NAME'];
$_SESSION['cno'] = $user['CNO'];
$_SESSION['email'] = $user['EMAIL'];

// 관리자 계정일 경우 관리자 페이지로 이동
if ($user['CNO'] === 'c0') {
    header("Location: admin_stats.php");
    exit;
}

// 일반 회원은 메인 페이지로 이동
header("Location: search.php");
exit;
?>
