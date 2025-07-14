<?php
session_start();
if (!isset($_SESSION['user'])) $_SESSION['user'] = 0;
$is_admin = ($_SESSION['user'] == 474652);

$db = new SQLite3('books.db');
$db->exec("CREATE TABLE IF NOT EXISTS books (id INTEGER PRIMARY KEY, topic TEXT, title TEXT, description TEXT, file TEXT, cover TEXT)");

if ($is_admin && isset($_POST['add_book'])) {
    $topic = $_POST['topic'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $file = $_FILES['file']['name'];
    $cover = $_FILES['cover']['name'];
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
<title>PDF Library</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.7.6/lottie.min.js"></script>
<style>
body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #f0f4ff, #e8ffe8); overflow-x:hidden; }
.splash { position:fixed; top:0; left:0; width:100%; height:100%; background:#fff; z-index:9999; display:flex; justify-content:center; align-items:center; }
.slider { overflow:hidden; position:relative; width:100%; height:200px; border-radius:8px; }
.slide-track { display:flex; transition:transform 0.6s ease-in-out; }
.slide-track img { width:100%; height:200px; object-fit:cover; flex-shrink:0; }
.book-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; }
.book { text-align:center; background:#fff; padding:10px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.book img { width:100px; height:140px; object-fit:cover; }
.sidebar { position:fixed; left:-220px; top:0; width:200px; height:100%; background:#4b6cb7; color:#fff; padding:20px; transition:0.3s; z-index:1000; }
.sidebar.open { left:0; }
.sidebar a { color:#fff; display:block; margin:10px 0; text-decoration:none; }
.main { padding:20px; }
.menu-btn, .close-btn { font-size:24px; cursor:pointer; color:#333; }
.dot-container {text-align:center; margin-top:5px;}
.dot { height:10px; width:10px; margin:0 2px; background:#bbb; border-radius:50%; display:inline-block;}
.active {background:#717171;}
</style>
</head>
<body>

<div class="splash" id="splash"></div>

<div class="sidebar" id="sidebar">
<div class="d-flex justify-content-between">
<h4>Menu</h4>
<span class="close-btn" onclick="toggleSidebar()">✖</span>
</div>
<a href="#">About</a>
<a href="#">Feedback</a>
<a href="#">Contact</a>
</div>

<div class="main">
<div class="d-flex justify-content-between align-items-center mb-3">
<span class="menu-btn" onclick="toggleSidebar()">☰</span>
<?php if ($is_admin): ?>
<button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addBookModal">Add New Book</button>
<?php endif; ?>
</div>

<div class="slider mb-2">
<div class="slide-track" id="slide-track">
<img src="https://i.ibb.co/35yWRpvV/s-1.png">
<img src="https://i.ibb.co.com/MxC43w30/s2.png">
<img src="https://i.ibb.co.com/KpgwrK2v/s3.png">
</div>
</div>
<div class="dot-container">
<span class="dot active"></span>
<span class="dot"></span>
<span class="dot"></span>
</div>

<?php foreach ($books_by_topic as $topic => $books): ?>
<h5 class="mt-3"><?= htmlspecialchars($topic) ?></h5>
<div class="book-grid mb-4">
<?php foreach ($books as $book): ?>
<div class="book">
<a href="download.php?file=<?= urlencode($book['file']) ?>">
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
<input name="title" placeholder="Title" class="form-control mb-1" required>
<input name="description" placeholder="Description" class="form-control mb-1">
<input type="file" name="file" class="form-control mb-1" required>
<input type="file" name="cover" class="form-control mb-1" required>
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
    document.getElementById('sidebar').classList.toggle('open');
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
