<?php
session_start();
include 'conf.php';
// Dummy Login (replace with actual logic)
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = '123456'; // Normal user
    // $_SESSION['user'] = '474652'; // Admin user
}
// Connect to SQLite database
$db = new SQLite3('exchange.db');

// Create table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS numbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    designation TEXT NOT NULL,
    office TEXT NOT NULL,
    residence TEXT NOT NULL
)");

// Handle adding a new number
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_number'])) {
    $designation = $_POST['designation'];
    $office = $_POST['office'];
    $residence = $_POST['residence'];
    $stmt = $db->prepare("INSERT INTO numbers (designation, office, residence) VALUES (:designation, :office, :residence)");
    $stmt->bindValue(':designation', $designation);
    $stmt->bindValue(':office', $office);
    $stmt->bindValue(':residence', $residence);
    $stmt->execute();
}

// Delete Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_number'])) {
    $id = $_POST['id'];
    if (!empty($id)) {
        $stmt = $db->prepare("DELETE FROM numbers WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']); // to refresh after delete
        exit();
    }
}
//Edit Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_number'])) {
    $id = $_POST['id'];
    $designation = $_POST['designation'];
    $office = $_POST['office'];
    $residence = $_POST['residence'];

    if (!empty($id) && $designation && $office && $residence) {
        $stmt = $db->prepare("UPDATE numbers SET designation = :designation, office = :office, residence = :residence WHERE id = :id");
        $stmt->bindValue(':designation', $designation);
        $stmt->bindValue(':office', $office);
        $stmt->bindValue(':residence', $residence);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$exchange = isset($_GET['exchange']) ? $_GET['exchange'] : 'BSR';

// সার্চ এবং সোর্ট একসাথে
$query = "SELECT * FROM numbers 
          WHERE designation LIKE :search OR office LIKE :search 
          ORDER BY office ASC";

$stmt = $db->prepare($query);
$stmt->bindValue(':search', "%$search%");
$results = $stmt->execute();

// Exchange prefix
$prefixes = [
    'BSR' => '0176997',
    'MTR' => '0176940',
    'AKR' => '0176990',
    'SMD' => '0176991',
    'CXB' => '0176994',
    'ZHR' => '0176950'
];
?>
<!DOCTYPE html>
    <html lang="en" data-theme="light">
<head>
  <meta name="viewport" content="width=720">
   <meta charset="UTF-8">
    <title>All Bases Exchange</title>
        <!-- Bootstrap 5 CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
            --baf-blue: #008ce1;
            --baf-light-blue: #0078b4;
            --baf-gold: #ffd700;
            --baf-light: #f0f8ff;
            --baf-dark: #003366;
            --danger: #e74c3c;
            --success: #2ecc71;
            --warning: #f39c12;
            --info: #3498db;
            --light: #ffffff;
            --gray: #95a5a6;
            --dark: #2c3e50;
            --bg-color: #f5f7fa;
            --text-color: #333333;
            --card-bg: #ffffff;
            --input-bg: #ffffff;
            --border-color: #dddddd;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('https://images.unsplash.com/photo-1518343265568-51eec52d40da?fit=crop&w=1600&q=80') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            border-radius: 20px;
            border-top: 5px solid var(--baf-gold);
            border-bottom: 5px solid var(--baf-gold);
            padding: 0.0002rem;
            width: 100%;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
            border-top: 5px solid var(--baf-gold);
            max-width: 900px;
            position: relative;
            overflow: hidden;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        header {
            background-color:;
            text-align: left;
            padding: 2px;
            color: #ffffff;
            min-width: 350px;
            white-space: nowrap;
            overflow-x: auto;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        }
        select, input[type=text], button {
            margin: 5px;
            padding: 10px;
            border-radius: 8px;
            border: solid #ccc;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.1);
        }
        input[type=text] {
            width: 250px;
        }
         .input-prefix {
            
            background-color: var(--baf-blue);
            color: #13e100;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 50px;
            height: 100%;
            border: 1px solid #005f8e;
        }

        input, select {
            width: 20%;
            padding: 0.85rem 1.2rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: ;
            color: var(--text-color);
        }
        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        .input-group input {
            border-radius: 0 6px 6px 0;
            border-left: none;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--baf-light-blue);
            box-shadow: 0 0 0 3px rgba(0, 95, 142, 0.2);
        }
        .search-box {
            display: flex;
            align-items: center;
            
        }
        .table-container {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        padding: 0.5rem;
}

table {
  width: 100%;
  border-collapse: collapse;
  color: #fff; /* light text for dark bg */
  font-size: 0.95rem;
}

th {
  background: rgba(0, 0, 0, 0.7);
  color: #fff;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
th, td {
  border: 1px solid rgba(255, 255, 255, 0.4);
}

td {
  position: relative;
}

td::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  height: 1px;
  background: rgba(255, 255, 255, 0.2);
}

tr {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  transition: background 0.3s;
}

tr:hover {
  background: rgba(255, 255, 255, 0.2);
}

tr.coas   { background: rgba(252, 236, 230, 0.3); }
tr.acas   { background: rgba(252, 244, 207, 0.3); }
tr.oc     { background: rgba(225, 255, 222, 0.3); }
tr.oic    { background: rgba(230, 250, 252, 0.2); }
tr.pa     { background: rgba(249, 242, 255, 0.2); }
tr.others { background: rgba(255, 255, 255, 0.1); }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-in-out;
        }
        .modal-content {
            background: #ffffff;
            padding: 25px;
            border-radius: 15px;
            width: 350px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
        }
         .modal-dialog {
            background: ;
            padding: 25px;
            border-radius: 15px;
            width: 350px;
            animation: slideIn 0.3s ease-out;
        }
	 .modal-title {
  color: #dc3545;
  font-weight: bold;
}
          .modal-contentcall {
            background: #ffffff;
            border-top: 5px solid var(--baf-gold);
            border-bottom: 5px solid var(--baf-gold);
            display: ;
            text-align: center;
            padding: 25px;
            border-radius: 15px;
            width: 350px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
        }
        .btn {
            background-color: #04AA6D; /* Green */
           border: none;
           border-radius: 10px;
           color: white;
           box-shadow: 0 2px 6px rgba(0,0,0,0.1);
           padding: 16px 32px;
           text-align: center;
           text-decoration: none;
           display: inline-block;
           font-size: 18px;
           margin: 4px 2px;
           transition-duration: 0.4s;
           cursor: pointer;
        }
        .small-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  font-size: 22px;
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 12px;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  background: rgba(255, 255, 255, 0.1);
  box-shadow: 0 8px 16px rgba(0,0,0,0.2);
  cursor: pointer;
  transition: all 0.3s ease;
}

.small-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 12px 24px rgba(0,0,0,0.3);
}

.small-btn:active {
  transform: scale(0.95);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

        .gbtn {
            background-color: #4CAF50;
            color: white;
        }
        .gbtn:hover {
            background-color: #40916c;
        }
        .cbtn {
            background-color: white; 
            transition: transform 0.5s ease;
            color: black; 
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            height: 100px;
            width: 150px;
            border: 2px solid #04AA6D;
        }
        .cbtn:hover {
            background-color: #04AA6D;
            color: white;
            transition: translateY(-5px);
        }
        .ybtn {
            background-color: #f9c74f;
            color: black;
        }
        .ybtn:hover {
            background-color: #4CAF50;
            color: white;
        }
        @keyframes fadeIn {
            from { opacity: 0; } to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .icon {
            margin-right: 5px;
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
<body oncontextmenu="return false;">
  <div class="container">
  <div class="swipe-topbar">
    <a href="https://jahid.byethost15.com/profile.php"> <span class="icon"><i class="fa fa-home"></i></span>Home</a>
    <a href="https://jahid.byethost15.com/ex/bus.php" class=<span class="icon"><i class="fa fa-bus"></i></span>Bus Schedule</a>
    <a href="https://jahid.byethost15.com/ex/contact.php"><span class="icon"><i class="fa fa-phone"></i></span>BSR MT SQN Number</a>
    <a href="https://jahid.byethost15.com/notification.php"><span class="icon"><i class="fa fa-bell" aria-hidden="true"></i></span>Notification</a>
     <?php if ($_SESSION['user'] == '474652'): ?>
    <a href="https://jahid.byethost15.com/logout.php"><span class="icon"><i class="fa fa-sign-out"></i></span>Logout</a>
    <?php endif; ?>
</div>
    <header>
     <div class="">
        <form method="GET" class="search-box">
          <a href="air.php" class="btn">AirHQ Unit</a>
           <a href="https://jahid.byethost15.com/profile.php" class="btn small-btn">
        <i class='fas fa-address-book'></i>  
        </a>
            <select name="exchange">
                <option value="BSR" <?= $exchange=='BSR'?'selected':'' ?>>BSR</option>
                <option value="MTR" <?= $exchange=='MTR'?'selected':'' ?>>MTR</option>
		<option value="AKR" <?= $exchange=='AKR'?'selected':'' ?>>AKR</option>
		<option value="SMD" <?= $exchange=='SMD'?'selected':'' ?>>SMD</option>
		<option value="CXB" <?= $exchange=='CXB'?'selected':'' ?>>CXB</option>
                <option value="ZHR" <?= $exchange=='ZHR'?'selected':'' ?>>ZHR</option>
            </select>
            <input type="text" name="search" placeholder="Search by designation or office" value="<?= htmlspecialchars($search) ?>">
            <button class="btn gbtn" type="submit">Search</button>
        </form>
        <?php if ($_SESSION['user'] == 474652): ?>
        <button class="btn ybtn" onclick="document.getElementById('addModal').style.display='flex'">Add Number</button><?php endif; ?>
        </div>
    </header>
    <div class="table-container">
    <table>
    <tr>
        <th>Designation</th>
        <th>Office</th>
        <th>Residence</th>
        <th>Action</th>
    </tr>
    <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)):
    $class = 'others';
      if (stripos($row['designation'], 'AOC') !== false) $class = 'aoc';
      elseif (stripos($row['designation'], 'OC') !== false) $class = 'oc';
      elseif (stripos($row['designation'], 'OIC') !== false) $class = 'oic';
      elseif (stripos($row['designation'], 'Adjt') !== false) $class = 'adjt';
      elseif (stripos($row['designation'], 'PA') !== false) $class = 'pa';
    ?>
    <tr class="<?= $class ?>" data-id="<?= $row['id'] ?>" ondblclick="callPopup('<?= $row['designation'] ?>', '<?= $row['office'] ?>')">
        <td><?= htmlspecialchars($row['designation']) ?></td>
        <td><?= htmlspecialchars($row['office']) ?></td>
        <td><?= htmlspecialchars($row['residence']) ?></td>
        <td><button class="btn gbtn" onclick="showCallPopup('<?= $row['designation'] ?>', '<?= $row['office'] ?>')">Call</button>
         <?php if ($_SESSION['user'] == 474652): ?>
        <button onclick="confirmDelete(<?= $row['id'] ?>)" class="btn small-btn"><i class="fa fa-trash"></i></button>
        
  <button class="btn small-btn"
    onclick="openEditModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['designation']) ?>', '<?= htmlspecialchars($row['office']) ?>', '<?= htmlspecialchars($row['residence']) ?>')">
    <i class="fa fa-edit"></i>
  </button>
  <?php endif; ?>
</td>
    </tr>
    <?php endwhile; ?>
</table>
</div>
</div>

<!-- Add Number Modal -->
<div class="modal" id="addModal">
      <div class="card">
        <h3>Add Number</h3> 
        <form method="POST">
          <div class="input-group">
            <input type="text" name="designation" placeholder="Designation" required><br><br>
            <input type="text" name="office" placeholder="Office" required><br><br>
            <input type="text" name="residence" placeholder="Residence" required><br><br>
            <button class="btn gbtn" name="add_number" type="submit">Add</button>
            <button class="btn ybtn" type="button" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
        </form>
        </div>
        </div>
</div>

<!-- Call Modal -->
<div class="modal" id="callModal">
    <div class="modal-contentcall">
        <h3 id="callTitle">Call Info</h3>
        <p><button class="btn cbtn">Exchange: <span id="exchangeNum"></span></button>
        <button class="btn cbtn">Mobile: <span id="mobileNum"></span></button></p>
        <button class="btn gbtn" id="confirmCall">Confirm</button>
        <button class="btn ybtn" onclick="document.getElementById('callModal').style.display='none'">Cancel</button></div>
    </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-contentcall">
      <form method="POST">
          <h3 class="modal-title">Confirm Delete</h3>
        <div class="modal-body">
          Are you sure you want to delete this number?
          <input type="hidden" name="id" id="deleteModalId">
        </div><br>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="delete_number" class="btn btn-danger">Delete</button>
      </form>
    </div>
  </div>
</div>
<!-- Edit Modal -->
<div class="modal" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-contentcall">
      <form method="POST">
        <h3 class="modal-title">Edit Items</h3>
        <div class="">
          <input type="hidden" name="id" id="editModalId">
          <div class="mb-2">
            <label>Designation</label><br>
            <input type="text" name="designation" id="editModalDesignation" class="form-control" required>
          </div>
            <label>Office</label><br>
            <input type="text" name="office" id="editModalOffice" class="form-control" required>
            <label>Residence</label><br>
            <input type="text" name="residence" id="editModalResidence" class="form-control" required>
        </div>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_number" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
</div>
<script>
function openEditModal(id, designation, office, residence) {
  document.getElementById('editModalId').value = id;
  document.getElementById('editModalDesignation').value = designation;
  document.getElementById('editModalOffice').value = office;
  document.getElementById('editModalResidence').value = residence;

  const modal = new bootstrap.Modal(document.getElementById('editModal'));
  modal.show();
}

  function confirmDelete(id) {
  document.getElementById('deleteModalId').value = id;
  var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
  deleteModal.show();
}
    function showCallPopup(designation, office) {
        const selectedExchange = document.querySelector('select[name="exchange"]').value;
        const prefixMap = {
            BSR: "0176997",
            MTR: "0176940",
            AKR: "0176990",
            SMD: "0176991",
            CXB: "0176994",
            ZHR: "0176950"
        };
        const prefix = prefixMap[selectedExchange];

        document.getElementById('callTitle').innerText = designation;
        document.getElementById('exchangeNum').innerText = office;
        document.getElementById('mobileNum').innerText = prefix + office;
        document.getElementById('callModal').style.display = 'flex';

        document.getElementById('confirmCall').onclick = function() {
            const fullNumber = prefix + office;
            window.location.href = 'tel:' + fullNumber;
        };
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    };

</script>

</body>
</html>