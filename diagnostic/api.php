<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  exit(0);
}

define('DB_SERVER','localhost');
define('DB_USER','admin_news');
define('DB_PASS','ZnyEGn5VItB.[a@o');
define('DB_NAME','data_shrimpvet');

$connect = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

if (mysqli_connect_errno()) {
  echo json_encode(['success' => false, 'message' => 'Failed to connect: ' . mysqli_connect_error()]);
  exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
} else {
  $data = $_REQUEST;
}

switch($action) {
  case 'login':
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    if (!$username || !$password) {
      echo json_encode(['success' => false, 'message' => 'Username and password required']);
      break;
    }
    
    $stmt = $connect->prepare('SELECT * FROM user_infor WHERE username = ? AND password = ?');
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();
      echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => $user]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
    break;
    
  case 'users':
    $query = 'SELECT * FROM user_infor';
    $result = $connect->query($query);
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
      $users[] = $row;
    }
    
    echo json_encode($users);
    break;
    
  default:
    echo json_encode(['message' => 'API is working!']);
}

mysqli_close($connect);
?>