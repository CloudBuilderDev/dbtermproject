<?php
session_start();
include 'db.php';
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>CNU Airline 메인</title>
  <style>
    body {
      font-family: sans-serif;
      margin: 0; padding: 0;
    }
    header {
      background: #005bac;
      color: white;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    header h1 {
      margin: 0;
    }
    .container {
      padding: 20px;
    }
    .search-box, .flight-list {
      margin-top: 20px;
      border: 1px solid #ccc;
      padding: 15px;
      border-radius: 5px;
    }
    label, select, input[type="date"] {
      margin-right: 10px;
    }
    .right-align {
      display: flex;
      align-items: center;
      gap: 10px;
    }
  </style>
</head>
<body>
  <header>
    <h1>CNU Airline</h1>
    <div class="right-align">
      <?php if (isset($_SESSION['id'])): ?>
        <span><?php echo $_SESSION['name']; ?> 회원님</span>
        <a href="mypage.php" style="color: white;">마이페이지</a>
        <a href="logout.php" style="color: white;">로그아웃</a>
      <?php else: ?>
        <a href="login.php" style="color: white;">로그인</a>
      <?php endif; ?>
    </div>
  </header>

  <div class="container">
    <div class="search-box">
      <h2>항공편 검색</h2>
      <form action="search_result.php" method="post">
        <label>출발공항:
          <select name="departureAirport" required>
            <option value="ICN">인천</option>
            <option value="PUS">부산</option>
          </select>
        </label>

        <label>도착공항:
          <select name="arrivalAirport" required>
            <option value="JFK">뉴욕</option>
            <option value="NRT">도쿄</option>
            <option value="SFO">샌프란시스코</option>
          </select>
        </label>

        <label>출발일:
          <input type="date" name="departureDate">
        </label>

        <label>좌석 등급:
          <input type="radio" name="seatClass" value="ALL" checked> 전체
          <input type="radio" name="seatClass" value="ECONOMY"> 이코노미
          <input type="radio" name="seatClass" value="BUSINESS"> 비즈니스
        </label>

        <input type="submit" value="검색">
      </form>
    </div>

  </div>
</body>
</html>
