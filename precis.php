<?php
session_start();
if (!isset($_SESSION['user'])) $_SESSION['user'] = 0;
$is_admin = ($_SESSION['user'] == 474652);

$db = new SQLite3('books.db');
$db->exec("CREATE TABLE IF NOT EXISTS books (
    id INTEGER PRIMARY KEY, topic TEXT, title TEXT,
    description TEXT, file TEXT, cover TEXT)");

function slugify($filename) {
    $filename = strtolower($filename);
    $filename = preg_replace('/[^a-z0-9\.]+/', '_', $filename);
    return $filename;
}

if ($is_admin && isset($_POST['add_book'])) {
    $topic = $_POST['topic'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    $file = slugify($_FILES['file']['name']);
    $cover = slugify($_FILES['cover']['name']);

    move_uploaded_file($_FILES['file']['tmp_name'], "uploads/" . $file);
    move_uploaded_file($_FILES['cover']['tmp_name'], "uploads/" . $cover);

    $stmt = $db->prepare("INSERT INTO books (topic,title,description,file,cover) VALUES (?,?,?,?,?)");
    $stmt->bindValue(1, $topic);
    $stmt->bindValue(2, $title);
    $stmt->bindValue(3, $description);
    $stmt->bindValue(4, $file);
    $stmt->bindValue(5, $cover);
    $stmt->execute();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$books_by_topic = [];
$res = $db->query("SELECT * FROM books");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $books_by_topic[$row['topic']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Precis on MTOF</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.7.6/lottie.min.js"></script>
<style>
body { font-family: Arial, sans-serif; 
       background: radial-gradient(circle at 50% 100%, #f1fbff80 5%, #f1fbff 5% 10%, #f1fbff80 10% 15%, #f1fbff 15% 20%, #f1fbff80 20% 25%, #f1fbff 25% 30%, #f1fbff80 30% 35%, #f1fbff 35% 40%, transparent 40%), radial-gradient(circle at 100% 50%, #f1fbff80 5%, #f1fbff 5% 10%, #f1fbff80 10% 15%, #f1fbff 15% 20%, #f1fbff80 20% 25%, #f1fbff 25% 30%, #f1fbff80 30% 35%, #f1fbff 35% 40%, transparent 40%), radial-gradient(circle at 50% 0%, #f1fbff80 5%, #f1fbff 5% 10%, #f1fbff80 10% 15%, #f1fbff 15% 20%, #f1fbff80 20% 25%, #f1fbff 25% 30%, #f1fbff80 30% 35%, #f1fbff 35% 40%, transparent 40%), radial-gradient(circle at 0 50%, #f1fbff80 5%, #f1fbff 5% 10%, #f1fbff80 10% 15%, #f1fbff 15% 20%, #f1fbff80 20% 25%, #f1fbff 25% 30%, #f1fbff80 30% 35%, #f1fbff 35% 40%, transparent 40%);
        background-size: 3em 3em;
        background-color: #ffffff;
        opacity: 1
       overflow-x:hidden; 
       }
.splash { position:fixed; top:0; left:0; width:100%; height:100%; background:#fff; z-index:9999; display:flex; justify-content:center; align-items:center; }
 .slider {
  overflow: hidden;
  position: relative;
  width: 100%;
  height: 200px;
  border-radius: 8px;
}

.slide-track {
  display: flex;
  transition: transform 1.6s ease-in-out;
}

.slide-track img {
  width: calc(100% - 20px); /* ছবির মধ্যে gap এর জন্য */
  height: 200px;
  object-fit: cover;
  flex-shrink: 0;
  margin-right: 15px;
  border-radius: 8px;
}
.book-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; }
.book { text-align:center; background:#fff; padding:10px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.book img { width:140px; height:140px; object-fit:cover; }
.sidebar {
  position: fixed;
  left: -250px;
  top: 0;
  width: 250px;
  height: 100%;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  color: #fff;
  padding: 20px;
  transition: 0.3s ease;
  z-index: 1001;
  box-shadow: 2px 0 10px rgba(0,0,0,0.2);
  border-right: 1px solid rgba(255,255,255,0.2);
  overflow-y: auto;
}

.sidebar.open {
  left: 0;
}

.sidebar h4 {
  color: #fff;
  font-weight: bold;
  margin-bottom: 15px;
}

.sidebar a {
  color: #ddd;
  display: block;
  margin: 10px 0;
  text-decoration: none;
}

.sidebar a:hover {
  color: #fff;
}

.sidebar .social-icons {
  background: rgba(255, 255, 255, 0.2);
  border-left: 4px solid #00aced;
  color: #fff;
  width: 235px;
  padding-left: ;
  border-radius: 4px;
  position: absolute;
  bottom: 20px;
  left: 10px;
  display: flex;
  gap: 50px;
  justify-content: center;
}

.sidebar .menu-items {
  flex-grow: 1;
}
.sidebar .social-icons a {
  font-size: 40px;
  color: #fff;
  transition: 0.2s;
}

.sidebar .social-icons a:hover {
  color: #00aced;
}

.sidebar-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
  background: rgba(0,0,0,0.3);
  z-index: 1000;
  opacity: 0;
  visibility: hidden;
  transition: 0.3s;
}

.sidebar-backdrop.show {
  opacity: 1;
  visibility: visible;
}
.main { padding:20px; }
.menu-btn, .close-btn { font-size:24px; cursor:pointer; color:#333; }
.dot-container {
  position: absolute;
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
  text-align: center;
}
.dot {
  height: 10px;
  width: 10px;
  margin: 0 4px;
  background: rgba(255,255,255,0.3);
  border-radius: 50%;
  display: inline-block;
  transition: all 0.3s ease;
}

.dot.active {
  background: #fff;
  width: 40px;          
  border-radius: 5px;   
  transform: scale(1.1);
}
  .btn {
    background: ;
    color: black;
  }
  .bell-animation {
  animation: bell-shake 1s ease-in-out infinite;
}

@keyframes bell-shake {
  0% {
    transform: rotate(0deg);
  }
  25% {
    transform: rotate(20deg);
  }
  50% {
    transform: rotate(0deg);
  }
  75% {
    transform: rotate(-20deg);
  }
  100% {
    transform: rotate(0deg);
  }
}
.high {
  background: rgba(255, 255, 255, 0.2);
  border-left: 4px solid #07f24e;
  color: #fff;
  font-weight: bold;
  padding-left: 15px;
  border-radius: 4px;
}
.feed {
  background: rgba(255, 255, 255, 0.2);
  border-left: 4px solid #00aced;
  color: #fff;
  font-weight: bold;
  padding-left: 15px;
  border-radius: 4px;
}
.about {
  background: #00aced;
  color: #fff;
  font-weight: bold;
  border-radius: 20px;
  padding: 5px 15px;
  display: inline-block;
}
.topic {
  background: rgba(100, 176, 207, 0.2);
  border-left: 4px solid #07f24e;
  color: #fff;
  font-weight: bold;
  padding-left: 15px;
  border-radius: 4px;
}
.notification {
  position: relative;
  display: inline-block;
  font-size: 24px;
  color: black;
  cursor: pointer;
  animation: ring 2s infinite;
}

.notification .badge {
  position: absolute;
  top: -1px;
  right: -1px;
  background: red;
  color: white;
  font-size: 12px;
  padding: 2px 5px;
  border-radius: 50%;
  animation: pulse 1.5s infinite;
}
@keyframes pulse {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.2); opacity: 0.8; }
  100% { transform: scale(1); opacity: 1; }
}
.logo-container {
  text-align: center;
  margin-bottom: 15px;
}

.logo-container img {
  width: 60px;    
  height: auto;
  border-radius: 8px;
}

</style>
</head>
<body>

<div class="splash" id="splash"></div>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<div class="sidebar-backdrop" id="sidebar-backdrop" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
  <div class="d-flex justify-content-between">
    <h4>We will change</h4>
    <span class="close-btn" onclick="toggleSidebar()">✖</span>
  </div>
  <div class="menu-items">
  <a href="https://about.me/only_jhd" class="about">About</a>
  <a href="mailto:jahidgroupof@gmail.com" class="feed">Feedback</a>
  <a href="tel:01724073223" class="high">Contact</a>
  </div>
  <div class="social-icons">
    <a href="https://facebook.com/imjhd" target="_blank"><i class="fab fa-facebook-f"></i></a>
    <a href="https://instagram.com/only_jhd" target="_blank"><i class="fab fa-instagram"></i></a>
    <a href="https://wa.me/+8801724073223" target="_blank"><i class="fab fa-whatsapp"></i></a>
  </div>
  <div class="logo-container">
  <img src="https://i.ibb.co/rfdZ9wKh/MTDS.png" alt="Logo">
  <div style="font-size:14px; color:#fff;">Courtesy by LAC JAHID</div>
</div>
</div>

<div class="main">
<div class="d-flex justify-content-between align-items-center mb-3">
<span class="menu-btn" onclick="toggleSidebar()">☰</span>
<h5>Precis on MTOF </h5>
<div class="notification">
<button class="btn" onclick="window.location.href='https://jahid.byethost15.com/notification.php'">
<h2><i class="fas fa-bell bell-animation"></i></h2></button><span class="badge">3</span>
</div>
<?php if ($is_admin): ?>
<button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addBookModal">Add New Book</button>
<?php endif; ?>
</div>

<div class="slider mb-2">
<div class="slide-track" id="slide-track">
<img src="https://i.ibb.co/35yWRpvV/s-1.png">
<img src="https://i.ibb.co.com/MxC43w30/s2.png">
<img src="https://i.ibb.co.com/KpgwrK2v/s3.png">
<img src="https://i.ibb.co.com/7JwgWP0j/s4.png">
<img src="https://i.ibb.co.com/m5j4RL6h/s5.png">
</div>
<div class="dot-container">
<span class="dot active"></span>
<span class="dot"></span>
<span class="dot"></span>
<span class="dot"></span>
<span class="dot"></span>
</div>
</div>

<?php foreach ($books_by_topic as $topic => $books): ?>
<h5 class="topic"><?= htmlspecialchars($topic) ?></h5>
<div class="book-grid mb-4">
<?php foreach ($books as $book): ?>
<div class="book">
<a href="books/<?= htmlspecialchars(pathinfo($book['file'], PATHINFO_FILENAME)) ?>" target="_blank">
<img src="uploads/<?= htmlspecialchars($book['cover']) ?>"><br>
<b><?= htmlspecialchars($book['title']) ?></b><br>
<small><?= htmlspecialchars($book['description']) ?></small>
</a>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>

</div>

<div class="modal fade" id="addBookModal">
<div class="modal-dialog">
<div class="modal-content">
<form method="post" enctype="multipart/form-data">
<div class="modal-header"><h5>Add New Book</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input name="topic" placeholder="Topic" class="form-control mb-1" required>
<h6>Book Cover</h6>
<input type="file" name="cover" class="form-control mb-1" required>
<input name="title" placeholder="Title" class="form-control mb-1" required>
<input name="description" placeholder="Description" class="form-control mb-1">
<h6>Book Pdf File</h6>
<input type="file" name="file" class="form-control mb-1" required>
</div>
<div class="modal-footer">
<button class="btn btn-primary" name="add_book">Add Book</button>
</div>
</form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
lottie.loadAnimation({
  container:document.getElementById('splash'),
  renderer:'svg',
  loop:true,
  autoplay:true,
  path:'uploads/education.json'
});
setTimeout(()=>{ document.getElementById('splash').style.display='none'; },3000);

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  sidebar.classList.toggle('open');
  backdrop.classList.toggle('show');
}

const track = document.getElementById('slide-track');
const dots = document.querySelectorAll('.dot');
const slides = document.querySelectorAll('#slide-track img');
let index=0;
setInterval(()=>{
    index=(index+1)%slides.length;
    track.style.transform=`translateX(-${index*100}%)`;
    dots.forEach(dot=>dot.classList.remove('active'));
    dots[index].classList.add('active');
},3000);

</script>

</body>
</html>