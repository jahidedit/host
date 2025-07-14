<?php
session_start();

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = '123456'; // সাধারণ ইউজার
}
$user = $_SESSION['user'];

$db = new SQLite3('notify.db');
$db->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    message TEXT,
    image TEXT,
    user TEXT,
    created_at TEXT
)");
$db->exec("CREATE TABLE IF NOT EXISTS likes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user TEXT,
    notif_id INTEGER
)");

function uploadToImgBB($tmp) {
    $api = "9ca74f1f419c47089fb43ba8ca1bf14c";
    $data = base64_encode(file_get_contents($tmp));
    $url = "https://api.imgbb.com/1/upload?key=$api";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['image' => $data]
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $j = json_decode($res, true);
    return $j['data']['url'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];
    if ($a === 'post' && $user === '474652') {
        $msg = $_POST['message'];
        $img = '';
        if (!empty($_FILES['photo']['tmp_name'])) {
            $img = uploadToImgBB($_FILES['photo']['tmp_name']);
        }
        $s = $db->prepare("INSERT INTO notifications (message, image, user, created_at) VALUES (:m, :i, :u, datetime('now'))");
        $s->bindValue(':m', $msg);
        $s->bindValue(':i', $img);
        $s->bindValue(':u', $user);
        $s->execute();
        exit;
    }
    if ($a === 'edit' && $user === '474652') {
        $nid = intval($_POST['nid']);
        $msg = $_POST['message'];
        $img = '';
        if (!empty($_FILES['photo']['tmp_name'])) {
            $img = uploadToImgBB($_FILES['photo']['tmp_name']);
        }
        if ($img) {
            $s = $db->prepare("UPDATE notifications SET message=:m, image=:i WHERE id=:id");
            $s->bindValue(':m', $msg);
            $s->bindValue(':i', $img);
        } else {
            $s = $db->prepare("UPDATE notifications SET message=:m WHERE id=:id");
            $s->bindValue(':m', $msg);
        }
        $s->bindValue(':id', $nid);
        $s->execute();
        exit;
    }
    if ($a === 'like') {
        $nid = intval($_POST['nid']);
        $chk = $db->querySingle("SELECT COUNT(*) FROM likes WHERE notif_id=$nid AND user='$user'");
        if ($chk) {
            $db->exec("DELETE FROM likes WHERE notif_id=$nid AND user='$user'");
        } else {
            $s = $db->prepare("INSERT INTO likes (notif_id, user) VALUES (:n, :u)");
            $s->bindValue(':n', $nid);
            $s->bindValue(':u', $user);
            $s->execute();
        }
        exit;
    }
    if ($a === 'delete' && $user === '474652') {
        $nid = intval($_POST['nid']);
        $db->exec("DELETE FROM notifications WHERE id=$nid");
        $db->exec("DELETE FROM likes WHERE notif_id=$nid");
        exit;
    }
}

$notifs = $db->query("SELECT * FROM notifications ORDER BY id DESC");

function getLikes($db, $nid) {
    return $db->querySingle("SELECT COUNT(*) FROM likes WHERE notif_id=$nid");
}

function userLiked($db, $nid, $user) {
    return $db->querySingle("SELECT COUNT(*) FROM likes WHERE notif_id=$nid AND user='$user'") > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Notifications</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
body {font-family:sans-serif;
background: linear-gradient(135deg, #fffef680 50%, transparent 0),linear-gradient(-135deg, #fffef680 50%, transparent 0);
        background-size: 3em 3em;
        background-color: #e9ffff;
        opacity: 1
margin:0;padding:1rem;
}
.notif {background:white;border-radius:1rem;padding:1rem;margin:1rem 0;box-shadow:0 0 10px #ccc;}
img.notif-img {width:100%;max-width:400px;max-height:300px;object-fit:cover;border-radius:.5rem;margin-bottom:.5rem;cursor:pointer;display:block;}
textarea {width:100%;height:80px;margin:.5rem 0;resize:vertical;}
.reactions {margin-top:.5rem;font-size:1.4rem;cursor:pointer;user-select:none;}
.liked {color:red;}
.delete-btn, .edit-btn {
  float: right;
  cursor: pointer;
  font-size: 1.2rem;
  margin-left: 8px;
  color: #444;
}
.delete-btn:hover {color: red;}
.edit-btn:hover {color: #007bff;}
.title {font-size:1.3rem;font-weight:bold;margin:.2rem 0;}
.desc {font-size:1rem;color:#444;white-space:pre-line; cursor:pointer;}
#postForm {display:none;margin-bottom:1rem;}
button.togglePost {background:#007bff;color:#fff;border:none;padding:.5rem 1rem;border-radius:.5rem;cursor:pointer;}
.modal-backdrop.show {
  backdrop-filter: blur(6px);
  background-color: rgba(0,0,0,0.4);
}
.swipe-topbar {
        overflow-x: auto;
        white-space: nowrap;
        background: ;
        padding: 10px;
        display: flex;
        scroll-behavior: smooth;
        margin-bottom: 10px;
        
    }

    .swipe-topbar a {
        display: inline-block;
        color: white;
        padding: 10px 16px;
        margin-right: 8px;
        text-decoration: none;
        border-radius: 25px;
        background: radial-gradient(circle at top left, transparent 14%,#19ebeb 15%,#19ebeb 20% , transparent 21%),radial-gradient(circle at top right, transparent 14%,#19ebeb 15%,#19ebeb 20% , transparent 21%),radial-gradient(circle at bottom left, transparent 14%,#19ebeb 15%,#19ebeb 20% , transparent 21%),radial-gradient(circle at bottom right, transparent 14%,#19ebeb 15%,#19ebeb 20% , transparent 21%),radial-gradient(circle at top,#19ebeb 20% , transparent 21%), radial-gradient(circle at bottom,#19ebeb 20% , transparent 21%), radial-gradient(circle at right,#19ebeb 20% , transparent 21%), radial-gradient(circle at left,#19ebeb 20% , transparent 21%), radial-gradient(circle,#19ebeb 20% , transparent 21%);
        background-size: 2em 2em;
        background-color: #28e3e3;
        opacity: 1
        font-size: 0.95rem;
        flex-shrink: 0;
        transition: 0.3s ease;
        position: relative;
        animation: popIn 0.5s ease;
    }

    .swipe-topbar a.active {
        background-color: #00b894;
        font-weight: bold;
        transform: scale(1.05);
        box-shadow: 0 0 10px #00b89488;
    }

    .swipe-topbar a .icon {
        display: inline-block;
        margin-right: 5px;
        animation: bounce 1.5s infinite;
    }

    @keyframes bounce {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-3px);
        }
    }

    @keyframes popIn {
        0% {
            transform: scale(0.8);
            opacity: 0;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .swipe-topbar::-webkit-scrollbar {
        display: none;
    }

</style>
</head>
<body>
<div class="swipe-topbar">
    <a href="https://jahid.byethost15.com/profile.php"> <span class="icon"><i class="fa fa-home"></i></span>Home</a>
    <a href="https://jahid.byethost15.com/ex/bus.php" class=<span class="icon"><i class="fa fa-bus"></i></span>Bus Schedule</a>
    <a href="https://jahid.byethost15.com/ex/base.php"><span class="icon"><i class="fa fa-phone"></i></span>Exchange Number</a>
    <a href="https://jahid.byethost15.com/ex/contact.php"><span class="icon"><i class="fa fa-phone-square"></i></span>MT SQN Contacts</a>
    <a href="https://jahid.byethost15.com/logout.php"><span class="icon"><i class="fa fa-sign-out"></i></span>Logout</a>
</div>
<h2><i class="fa fa-bell" aria-hidden="true"></i> Notifications</h2>

<?php if ($user === '474652'): ?>
<button class="togglePost" onclick="document.getElementById('postForm').style.display =
document.getElementById('postForm').style.display==='none'?'block':'none'"><i class="fa fa-paper-plane" aria-hidden="true"></i>New Post</button>

<form id="postForm" method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="post" />
<textarea name="message" placeholder="Title on first line, then description..." required></textarea>
<input type="file" name="photo" accept="image/*" /><br />
<button><i class="fa fa-plus-square" aria-hidden="true"> Post</i></button>
</form>
<?php endif; ?>

<div id="notifArea">
<?php while ($n = $notifs->fetchArray(SQLITE3_ASSOC)): ?>
<div class="notif" data-id="<?= $n['id'] ?>">
    <?php if ($user === '474652'): ?>
    <span class="delete-btn" onclick="deleteNotif(<?= $n['id'] ?>)" title="Delete"><i class="fa-solid fa-trash"></i></span>
    <span class="edit-btn" onclick='openEdit(<?= $n['id'] ?>, <?= json_encode($n['message'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Edit"><i class="fa-solid fa-pen-to-square"></i></span>
    <?php endif; ?>

    <?php if ($n['image']): ?>
    <img src="<?= $n['image'] ?>" class="notif-img" />
    <?php endif; ?>

    <?php
    $text = htmlspecialchars($n['message']);
    $lines = explode("\n", $text);
    $title = array_shift($lines);
    $desc = implode("\n", $lines);
    ?>
    <p class="title"><?= $title ?></p>
    <p class="desc msg" onclick="toggleSeeMoreText(this)">
        <?php if (mb_strlen(strip_tags($desc)) > 150): ?>
            <span class="short"><?= nl2br(mb_substr($desc, 0, 150)) ?>...</span>
            <span class="full" style="display:none;"><?= nl2br($desc) ?></span>
        <?php else: ?>
            <?= nl2br($desc) ?>
        <?php endif; ?>
    </p>

    <div class="reactions">
        <span onclick="like(<?= $n['id'] ?>, this)" class="<?= userLiked($db, $n['id'], $user) ? 'liked' : '' ?>">❤️</span>
        <span id="likeCount<?= $n['id'] ?>"><?= getLikes($db, $n['id']) ?></span>
    </div>
</div>
<?php endwhile; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;">
  <div style="background:#fff;padding:1rem;border-radius:.5rem;max-width:500px;width:90%;">
    <h3>Edit Post</h3>
    <form id="editForm" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit" />
      <input type="hidden" name="nid" id="editNid" />
      <textarea name="message" id="editMessage" required style="width:100%; height:100px;"></textarea><br/>
      <input type="file" name="photo" accept="image/*" /><br/>
      <button type="submit">💾 Save</button>
      <button type="button" onclick="closeEdit()">❌ Cancel</button>
    </form>
  </div>
</div>

<script>
// See More/Less toggle
function toggleSeeMoreText(el) {
    const shortEl = el.querySelector('.short');
    const fullEl = el.querySelector('.full');
    if (!shortEl || !fullEl) return;
    const fullDisplay = window.getComputedStyle(fullEl).display;
    if (fullDisplay === 'none') {
        fullEl.style.display = 'inline';
        shortEl.style.display = 'none';
    } else {
        fullEl.style.display = 'none';
        shortEl.style.display = 'inline';
    }
}

// Like toggle
function like(id, el) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=like&nid=' + id
    }).then(() => {
        el.classList.toggle('liked');
        let c = document.getElementById('likeCount' + id);
        let cnt = parseInt(c.innerText);
        c.innerText = el.classList.contains('liked') ? cnt + 1 : cnt - 1;
    });
}

// Delete notification
function deleteNotif(id) {
    if (!confirm('Delete this?')) return;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete&nid=' + id
    }).then(() => {
        document.querySelector('[data-id="' + id + '"]').remove();
    });
}

// Double tap like on image
document.querySelectorAll('.notif-img').forEach(img => {
    let last = 0;
    img.addEventListener('click', () => {
        let now = Date.now();
        if (now - last < 300) {
            img.closest('.notif').querySelector('.reactions span').click();
        }
        last = now;
    });
});

// Tab indent in textarea
document.querySelectorAll('textarea').forEach(t => {
    t.addEventListener('keydown', e => {
        if (e.key === 'Tab') {
            e.preventDefault();
            let s = t.selectionStart,
                e2 = t.selectionEnd;
            const tabSpaces = "    ";
            t.value = t.value.substring(0, s) + tabSpaces + t.value.substring(e2);
            t.selectionStart = t.selectionEnd = s + tabSpaces.length;
        }
    });
});

// Post form AJAX submit
document.getElementById('postForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    fetch('', {
        method: 'POST',
        body: data
    }).then(() => {
        form.reset();
        location.reload();
    });
});

// Edit modal open/close
function openEdit(id, msg) {
    document.getElementById('editNid').value = id;
    document.getElementById('editMessage').value = msg;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEdit() {
    document.getElementById('editModal').style.display = 'none';
}

// Edit form AJAX submit
document.getElementById('editForm').addEventListener('submit', e => {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    fetch('', {
        method: 'POST',
        body: data
    }).then(() => {
        closeEdit();
        location.reload();
    });
});
</script>

</body>
</html>
