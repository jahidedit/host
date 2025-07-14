<?php
session_start();
include 'conf.php';
// Dummy Login (replace with actual logic)
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = '123456'; // Normal user
    // $_SESSION['user'] = '474652'; // Admin user
}

$db = new SQLite3("contacts.db");
$db->exec("CREATE TABLE IF NOT EXISTS contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT NOT NULL
)");

function getRankOrder($name) {
    $ranks = ['MWO'=>1, 'SWO'=>2, 'WO'=>3, 'SGT'=>4, 'CPL'=>5, 'LAC'=>6, 'AC'=>7];
    foreach ($ranks as $prefix => $order) {
        if (stripos($name, $prefix) === 0) return $order;
    }
    return 99;
}

if (isset($_FILES['vcf']) && $_SESSION['user'] == 474652) {
    $data = file_get_contents($_FILES['vcf']['tmp_name']);
    $cards = explode("END:VCARD", $data);
    $db->exec("DELETE FROM contacts");
    $stmt = $db->prepare("INSERT INTO contacts (name, phone) VALUES (:name, :phone)");
    foreach ($cards as $card) {
        if (stripos($card, "BEGIN:VCARD") !== false) {
            preg_match("/FN:(.+)/i", $card, $name);
            preg_match("/TEL(?:;[^:]*):([\d\-\+]+)/i", $card, $phone);
            if (!empty($name[1]) && !empty($phone[1])) {
                $stmt->bindValue(":name", trim($name[1]));
                $stmt->bindValue(":phone", trim($phone[1]));
                $stmt->execute();
            }
        }
    }
    echo "<script>alert('VCF Imported Successfully');location='';</script>";
    exit;
}

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'search') {
        $term = $_POST['term'];
        $sort = $_POST['sort'] ?? 'rank';
        $stmt = $db->prepare("SELECT * FROM contacts WHERE name LIKE :term OR phone LIKE :term");
        $stmt->bindValue(':term', "%$term%");
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            if ($sort == 'rank') $row['rank_order'] = getRankOrder($row['name']);
            $out[] = $row;
        }
        if ($sort == 'az') {
            usort($out, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        } else {
            usort($out, fn($a, $b) => $a['rank_order'] === $b['rank_order']
                ? strcasecmp($a['name'], $b['name'])
                : $a['rank_order'] - $b['rank_order']);
        }
        echo json_encode($out); exit;
    }
    if ($_POST['action'] == 'add' && $_SESSION['user'] == 474652) {
        $stmt = $db->prepare("INSERT INTO contacts (name, phone) VALUES (:n, :p)");
        $stmt->bindValue(":n", $_POST['name']);
        $stmt->bindValue(":p", $_POST['phone']);
        $stmt->execute(); echo "added"; exit;
    }
    if ($_POST['action'] == 'delete' && $_SESSION['user'] == 474652) {
        $db->exec("DELETE FROM contacts WHERE id=" . intval($_POST['id']));
        echo "deleted"; exit;
    }
    if ($_POST['action'] == 'edit' && $_SESSION['user'] == 474652) {
        $stmt = $db->prepare("UPDATE contacts SET name=:n, phone=:p WHERE id=:id");
        $stmt->bindValue(":n", $_POST['name']);
        $stmt->bindValue(":p", $_POST['phone']);
        $stmt->bindValue(":id", $_POST['id']);
        $stmt->execute(); echo "edited"; exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>📇 MT SQN BSR pers contact</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
body {
  background: linear-gradient(45deg, #eef7ff 0%, #eef7ff 5%,transparent 5%, transparent 10%, #eef7ff 10%, #eef7ff 15%,transparent 15%,transparent 20%, #eef7ff 20%, #eef7ff 25%,transparent 25%,transparent 30%, #eef7ff 30%, #eef7ff 35%,transparent 35%,transparent 40%, #eef7ff 40%, #eef7ff 45%,transparent 45%,transparent 50%, #eef7ff 50%, #eef7ff 100%,transparent 55%,transparent 60%, #eef7ff 60%, #eef7ff 65%,transparent 65%,transparent 70%, #eef7ff 70%, #eef7ff 75%,transparent 70%,transparent 80%, #eef7ff 80%, #eef7ff 85%,transparent 85%,transparent 90%, #eef7ff 90%, #eef7ff 95%,transparent 95%), linear-gradient(135deg, #eef7ff 0%, #eef7ff 5%,transparent 5%, transparent 10%, #eef7ff 10%, #eef7ff 15%,transparent 15%,transparent 20%, #eef7ff 20%, #eef7ff 25%,transparent 25%,transparent 30%, #eef7ff 30%, #eef7ff 35%,transparent 35%,transparent 40%, #eef7ff 40%, #eef7ff 45%,transparent 45%,transparent 50%, #eef7ff 50%, #eef7ff 55%,transparent 55%,transparent 60%, #eef7ff 60%, #eef7ff 65%,transparent 65%,transparent 70%, #eef7ff 70%, #eef7ff 75%,transparent 70%,transparent 80%, #eef7ff 80%, #eef7ff 85%,transparent 85%,transparent 90%, #eef7ff 90%, #eef7ff 95%,transparent 95%);
        background-size: 3em 3em;
        background-color: #fefff5;
        opacity: 1
  padding: 1rem;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.5rem;
}
.card {
  background: linear-gradient(135deg, #edf6ff80 50%, transparent 0),linear-gradient(-135deg, #edf6ff80 50%, transparent 0);
        background-size: 3em 3em;
        background-color: #ffffff;
        opacity: 1
  border-radius: 0.7rem;
  padding: 0.3rem;
  box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}
.actions button, .actions a {
  margin-right: 0.4rem;
}
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
<div class="container">
<div class="swipe-topbar">
    <a href="https://jahid.byethost15.com/profile.php"> <span class="icon"><i class="fa fa-home"></i></span>Home</a>
    <a href="https://jahid.byethost15.com/precis.php" class=<span class="icon"><i class="fa fa-book"></i></span>MTOF Precis</a>
    <a href="https://jahid.byethost15.com/ex/bus.php" class=<span class="icon"><i class="fa fa-bus"></i></span>Bus Schedule</a>
    <a href="https://jahid.byethost15.com/ex/base.php"><span class="icon"><i class="fa fa-phone"></i></span>Exchange Number</a>
    <a href="https://jahid.byethost15.com/notification.php"><span class="icon"><i class="fa fa-bell" aria-hidden="true"></i></span>Notification</a>
    <?php if ($_SESSION['user'] == '474652'): ?>
    <a href="https://jahid.byethost15.com/logout.php"><span class="icon"><i class="fa fa-sign-out"></i></span>Logout</a>
    <?php endif; ?>
</div>
  <h2 class="text-center mb-4"><i class="fas fa-address-book"></i>MT SQN BSR Contact book</h2>
  
  <div class="row mb-3">
    <div class="col-md-6 mb-2">
      <input type="text" class="form-control" placeholder="🔍 Search contact..." onkeyup="search(this.value)" />
    </div>
    <div class="col-md-6">
      <select id="sortmode" class="form-select" onchange="search()">
        <option value="rank">🔰 Rank Order</option>
        <option value="az">🔤 A-Z</option>
      </select>
    </div>
  </div>
  
  <?php if ($_SESSION['user'] == 474652): ?>
  <form class="mb-3" method="post" enctype="multipart/form-data">
    <div class="input-group">
      <input type="file" name="vcf" accept=".vcf" required class="form-control" />
      <button class="btn btn-primary" type="submit"><i class="fas fa-upload"></i> Upload VCF</button>
    </div>
  </form>
  
  <form class="row g-2 mb-4" onsubmit="addContact(event)">
    <div class="col-md-5">
      <input type="text" name="name" class="form-control" placeholder="Name" required />
    </div>
    <div class="col-md-5">
      <input type="text" name="phone" class="form-control" placeholder="Phone" required />
    </div>
    <div class="col-md-2">
      <button class="btn btn-success w-100" type="submit"><i class="fas fa-plus"></i> Add</button>
    </div>
  </form>
  <?php endif; ?>
  
  <div id="list" class="grid"></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="editForm">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Edit Contact</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editId" />
        <div class="mb-3">
          <label for="editName" class="form-label">Name</label>
          <input type="text" id="editName" class="form-control" required />
        </div>
        <div class="mb-3">
          <label for="editPhone" class="form-label">Phone</label>
          <input type="text" id="editPhone" class="form-control" required />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this contact?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let contacts = [];
let editModal = new bootstrap.Modal(document.getElementById('editModal'));
let deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
let deleteId = null;

function render() {
  let html = '';
  contacts.forEach(c => {
    html += `<div class="card">
      <strong>${c.name}</strong>
      <small>${c.phone}</small>
      <div class="actions mt-2">
        <a href="tel:${c.phone}" class="btn btn-success btn-sm me-1"><i class="fas fa-phone"> Call</i></a>
        <?php if ($_SESSION['user'] == 474652): ?>
        <button class="btn btn-warning btn-sm me-1" onclick="openEdit(${c.id}, '${escapeQuotes(c.name)}', '${escapeQuotes(c.phone)}')"><i class="fas fa-edit"></i></button>
        <button class="btn btn-danger btn-sm" onclick="openDelete(${c.id})"><i class="fas fa-trash"></i></button>
        <?php endif; ?>
      </div>
    </div>`;
  });
  document.getElementById('list').innerHTML = html;
}

function escapeQuotes(str) {
  return str.replace(/'/g, "\\'").replace(/\"/g, '\\"');
}

function search(term = '') {
  const sort = document.getElementById('sortmode').value;
  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=search&term=${encodeURIComponent(term)}&sort=${sort}`
  })
  .then(res => res.json())
  .then(data => {
    contacts = data;
    render();
  });
}
search();

function addContact(e) {
  e.preventDefault();
  let form = e.target;
  let formData = new FormData(form);
  formData.append('action', 'add');
  fetch('', { method: 'POST', body: formData })
    .then(() => {
      form.reset();
      search();
    });
}

function openEdit(id, name, phone) {
  document.getElementById('editId').value = id;
  document.getElementById('editName').value = name;
  document.getElementById('editPhone').value = phone;
  editModal.show();
}

document.getElementById('editForm').addEventListener('submit', function(e) {
  e.preventDefault();
  let id = document.getElementById('editId').value;
  let name = document.getElementById('editName').value;
  let phone = document.getElementById('editPhone').value;
  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=edit&id=${id}&name=${encodeURIComponent(name)}&phone=${encodeURIComponent(phone)}`
  }).then(() => {
    editModal.hide();
    search();
  });
});

function openDelete(id) {
  deleteId = id;
  deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
  if (!deleteId) return;
  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=delete&id=${deleteId}`
  }).then(() => {
    deleteModal.hide();
    search();
  });
});
</script>
</body>
</html>
