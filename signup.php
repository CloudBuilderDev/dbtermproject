<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>회원가입 - CNU Airline</title>
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

    .signup-box {
      background-color: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      width: 400px;
    }

    .signup-box h2 {
      margin-bottom: 25px;
      color: #005bac;
      text-align: center;
    }

    .signup-box input[type="text"],
    .signup-box input[type="password"],
    .signup-box input[type="email"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }

    .signup-box input[type="submit"] {
      width: 100%;
      padding: 12px;
      background-color: #005bac;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      cursor: pointer;
    }

    .signup-box input[type="submit"]:hover {
      background-color: #003e80;
    }

    .signup-box .footer-link {
      margin-top: 15px;
      text-align: center;
      font-size: 13px;
    }

    .signup-box .footer-link a {
      color: #005bac;
      text-decoration: none;
    }
  </style>
</head>
<body>

<div class="signup-box">
  <h2>회원가입</h2>
  <form action="signup_process.php" method="post">
    <!-- 아이디: 공백, 한글 모두 금지 -->
    <input type="text" name="id" placeholder="아이디 (영문/숫자/기호)" maxlength="20" 
      required pattern="^[a-zA-Z0-9!@#$%^&*()_+=\-`~\[\]{}|\\:;'<>.,?/]+$" 
      oninput="this.value = this.value.replace(/[ㄱ-ㅎㅏ-ㅣ가-힣\s]/g, '')"
      title="공백이나 한글은 사용할 수 없습니다.">

    <!-- 비밀번호: 공백, 한글 모두 금지 -->
    <input type="password" name="passwd" placeholder="비밀번호" maxlength="20" 
      required pattern="^[a-zA-Z0-9!@#$%^&*()_+=\-`~\[\]{}|\\:;'<>.,?/]+$" 
      oninput="this.value = this.value.replace(/[ㄱ-ㅎㅏ-ㅣ가-힣\s]/g, '')"
      title="공백이나 한글은 사용할 수 없습니다.">

    <input type="text" name="name" placeholder="이름" maxlength="20" required>
    <input type="email" name="email" placeholder="이메일" maxlength="50" required>

    <!-- 여권번호: 영문/숫자 9자리 정확히 -->
    <input type="text" name="passportNumber" placeholder="여권번호 (9자리)" maxlength="9" minlength="9"
      required pattern="^[a-zA-Z0-9]{9}$"
      title="여권번호는 영문 또는 숫자로 9자리 입력해야 합니다.">

    <input type="submit" value="회원가입">
  </form>
  <div class="footer-link">
    이미 계정이 있으신가요? <a href="login.php">로그인</a>
  </div>
</div>


</body>
</html>
