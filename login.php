<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>CNU Airline 로그인</title>
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

    .login-box {
      background-color: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      width: 350px;
    }

    .login-box h2 {
      margin-bottom: 25px;
      color: #005bac;
      text-align: center;
    }

    .login-box input[type="text"],
    .login-box input[type="password"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }

    .login-box input[type="submit"] {
      width: 100%;
      padding: 12px;
      background-color: #005bac;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      cursor: pointer;
    }

    .login-box input[type="submit"]:hover {
      background-color: #003e80;
    }

    .login-box .footer-link {
      margin-top: 15px;
      text-align: center;
      font-size: 13px;
    }

    .login-box .footer-link a {
      color: #005bac;
      text-decoration: none;
    }
  </style>
</head>
<body>

<div class="login-box">
  <h2>로그인</h2>
  <form action="login_process.php" method="post">
    <input type="text" name="id" placeholder="아이디" required>
    <input type="password" name="passwd" placeholder="비밀번호" required>
    <input type="submit" value="로그인">
  </form>
  <div class="footer-link">
    아직 계정이 없으신가요? <a href="signup.php">회원가입</a>
  </div>
</div>

</body>
</html>
