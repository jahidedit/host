<?php
session_start();
include 'conf.php';
date_default_timezone_set('Asia/Dhaka');
// Dummy Login (replace with actual logic)
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = '123456'; // Normal user
    // $_SESSION['user'] = '474652'; // Admin user
}

$db = new SQLite3('bus.db');
$db->exec("CREATE TABLE IF NOT EXISTS buses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  from_location TEXT NOT NULL,
  destination TEXT NOT NULL,
  time TEXT NOT NULL,
  description TEXT NOT NULL,
  days TEXT DEFAULT 'all'
)");

if (isset($_POST['action']) && $_POST['action'] === 'add' && $_SESSION['user'] == '474652') {
  $from = $_POST['from'];
  $to = $_POST['to'];
  $time = str_pad(str_replace(':', '', $_POST['time']), 4, '0', STR_PAD_LEFT);
  $desc = $_POST['desc'];
  $days = $_POST['days'] ?? 'all';

  $stmt = $db->prepare("INSERT INTO buses (from_location, destination, time, description, days)
                        VALUES (:from, :to, :time, :desc, :days)");
  $stmt->bindValue(':from', $from);
  $stmt->bindValue(':to', $to);
  $stmt->bindValue(':time', $time);
  $stmt->bindValue(':desc', $desc);
  $stmt->bindValue(':days', $days);
  $stmt->execute();
  exit('success');
}

if (isset($_GET['fetch'])) {
  $from = $_GET['from'];
  $filter = $_GET['filter'] ?? 'today';
  $daytype = $_GET['daytype'] ?? 'all';

  $stmt = $db->prepare("SELECT * FROM buses WHERE from_location = :from");
  $stmt->bindValue(':from', $from);
  $res = $stmt->execute();

  $now = new DateTime();
  $today = strtolower($now->format('l'));
  $govHolidays = ['2025-06-28', '2025-08-15', '2025-12-16'];
  $todayDate = $now->format('Y-m-d');
  $isGovHoliday = in_array($todayDate, $govHolidays);
  $isHoliday = in_array($today, ['friday', 'saturday']) || $isGovHoliday;

  $buses = [];
  while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    if ($daytype === 'working' && !in_array($row['days'], ['working', 'all'])) continue;
    if ($daytype === 'holiday' && !in_array($row['days'], ['holiday', 'all'])) continue;

    $bus_time = DateTime::createFromFormat('Hi', $row['time']);
    if (!$bus_time) continue;
    $bus_time->setDate($now->format('Y'), $now->format('m'), $now->format('d'));
    if ($bus_time < $now) $bus_time->modify('+1 day');
    $diff = $bus_time->getTimestamp() - $now->getTimestamp();

    if ($filter === 'today' && $bus_time->format('Y-m-d') !== $now->format('Y-m-d')) continue;

    $buses[] = [
      'id' => $row['id'],
      'time' => $row['time'],
      'from' => $row['from_location'],
      'to' => $row['destination'],
      'desc' => $row['description'],
      'diff' => $diff
    ];
  }

  usort($buses, function($a, $b) {
    return $a['diff'] <=> $b['diff'];
  });

  echo json_encode($buses);
  exit;
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bus Schedule BSR-AKR-HQ</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">

<style>
:root {
  --bg: #1e1e2f; --text: #fff; --card: #2d2d3a; --highlight: #9ce6ff; --desc: #ccc;
}
.light {
  --bg: #f5f5f5; --text: #111; --card: #fff; --highlight: #007acc; --desc: #333;
  
}
body {
  background: radial-gradient(circle at center ,#e4f5f2, #e4f5f2 10%, transparent 10%, transparent  20%, #e4f5f2 20%, #e4f5f2 30%, transparent 30%, transparent 40%, #e4f5f2 40%, #e4f5f2 50%, transparent 50%, transparent 60%, #e4f5f2 60%, #e4f5f2 70%, transparent 70%, transparent 80%, #e4f5f2 80%, #e4f5f2 90%, transparent 90%);
        background-size: 3em 3em;
        background-color: #EFF4EE;
        opacity: 0.75
  font-family: sans-serif;
  margin: 0; padding: 2rem; transition: 0.3s ease;
}
.container { max-width: 700px; margin: auto; }
select, input, button {
  width: 100%; padding: 0.6rem; margin: 0.4rem 0;
  border: none; border-radius: 0.5rem; font-size: 1rem;
  box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
}
button { background: var(--highlight); color: black; font-weight: bold; cursor: pointer; }
.btn {
          background: var(--highlight); 
color: white; 
font-weight: bold; 
cursor: pointer;
width: 120px;
		height: 35px;
text-align: left;
          }
.card {
     background: radial-gradient(circle at top,transparent 60%, #3FA679 61%,#3FA679 66%, transparent 67%),radial-gradient(circle at bottom,transparent 60%, #3FA679 61%,#3FA679 66%, transparent 67%),radial-gradient(circle at left,transparent 60%, #3FA679 61%,#3FA679 66%, transparent 67%),radial-gradient(circle at right,transparent 60%, #3FA679 61%,#3FA679 66%, transparent 67%);
      background-size: 3em 3em;
      background-color: #40A97B;
      color: white;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      opacity: 1 
      padding: 0.3rem; border-radius: 0.2rem;
      margin-top: 0.3rem; cursor: pointer;
}
h2 {
  font-family: 'Orbitron', sans-serif;
  font-size: 1.5rem;
  color: green;
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 0px;
  margin-top: 0px;
  text-shadow: 5px 3px 6px rgba(0,0,0,0.45);
}
.countdown { font-weight: bold; 
color: var(--highlight);
text-shadow: 5px 3px 6px rgba(0,0,0,0.45);

}
.desc { display: none; 
font-size: 0.9rem; color: var(--desc); 
margin-top: 0.5rem; 
    
}
.flex { display: flex; 
        gap: 0.5rem; 
    
}

@keyframes moveShuttle {
  0%   { transform: translateX(0); }
  100% { transform: translateX(150px); }
}

@keyframes fadeSmoke {
  0%, 100% { opacity: 0.2; transform: translateX(0); }
  50%      { opacity: 1; transform: translateX(10px); }
}

.shuttle-wrapper {
  display: inline-block;
  animation: moveShuttle 4s ease-in-out infinite alternate;
}

.animated-shuttle {
  font-size: 2.5rem;
  color: #4fc3f7;
  text-shadow: 0 0 10px #4fc3f7;
  animation: moveShuttle 4s ease-in-out infinite alternate;
}


.smoke {
  display: inline-block;
  color: yellow;
  font-size: 1rem;
  transform: scaleY(-1); /* ফ্লিপ করা */
  animation: fadeSmoke 1.5s ease-in-out infinite;
  vertical-align: middle;
  margin-right: 0.3rem;
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
</head><body>

<div class="container">
<div class="swipe-topbar">
    <?php if ($_SESSION['user'] == '474652'): ?>
    <a href="https://jahid.byethost15.com/profile.php"> <span class="icon"><i class="fa fa-home"></i></span>Home</a>
    
    <a href="https://jahid.byethost15.com/ex/base.php"><span class="icon"><i class="fa fa-phone"></i></span>Exchange Number</a>
    <a href="https://jahid.byethost15.com/ex/contact.php"><span class="icon"><i class="fa fa-phone-square"></i></span>MT SQN Contacts</a>
    <a href="https://jahid.byethost15.com/notification.php"><span class="icon"><i class="fa fa-bell" aria-hidden="true"></i></span>Notification</a>
    
    <a href="https://jahid.byethost15.com/logout.php"><span class="icon"><i class="fa fa-sign-out"></i></span>Logout</a>
    <?php endif; ?>
</div>
 <h2>
  Bus Schedule
</h2>
		 <span class="shuttle-wrapper">
    <span class="smoke">💨</span>
    <i class="fa-solid fa-van-shuttle animated-shuttle"></i>
  </span>
  <div class="flex">
    <select id="fromSelect" onchange="fetchSchedule()">
      <option value="BSR">BSR</option>
      <option value="AKR">AKR</option>
      <option value="HQ">HQ</option>
    </select>
    <select id="filterSelect" onchange="fetchSchedule()">
      <option value="today">Today</option>
      <option value="all">All Day</option>
    </select>
    <select id="dayTypeSelect" onchange="fetchSchedule()">
      <option value="working">Working Days</option>
      <option value="holiday">Holidays</option>
    </select>
  </div>
  <div id="schedule"></div>

  <?php if ($_SESSION['user'] == '474652'): ?>
  <hr><h3>Add New Bus</h3>
  <form id="addForm">
    <input type="text" name="from" placeholder="From" required>
    <input type="text" name="to" placeholder="Destination" required>
    <input type="text" name="time" placeholder="Time (e.g., 0545)" required>
    <input type="text" name="desc" placeholder="Description">
    <select name="days" required>
      <option value="working">Working Days</option>
      <option value="holiday">Holidays Only</option>
    </select>
    <button type="submit">Add Bus</button>
  </form>
  <?php endif; ?>
</div>

<audio id="notifySound" src="https://www.soundjay.com/buttons/beep-07a.mp3"></audio>
<script>
let buses = [];

function fetchSchedule() {
  let from = document.getElementById('fromSelect').value;
  let filter = document.getElementById('filterSelect').value;
  let daytype = document.getElementById('dayTypeSelect').value;
  if (!from) return document.getElementById('schedule').innerHTML = "";
  fetch('?fetch=1&from=' + from + '&filter=' + filter + '&daytype=' + daytype)
    .then(res => res.json())
    .then(data => {
      buses = data;
      renderBuses();
      startCountdown();
    });
}
// 🟢 Set default values on page load
window.addEventListener('DOMContentLoaded', () => {
  document.getElementById('fromSelect').value = 'BSR';
  document.getElementById('filterSelect').value = 'today';
  document.getElementById('dayTypeSelect').value = 'working';
  fetchSchedule();
});
function renderBuses() {
  let html = buses.map(bus => {
    return `
      <div class="card" onclick="toggleDesc(${bus.id})">
        <div><i class="fa fa-bus"></i> ${bus.time} hrs</div>
        <div><i class="fa fa-road"></i> ${bus.from} <i class="fa fa-plane"></i> ${bus.to}</div>
        <div class="countdown" id="left${bus.id}"><i class="fas fa-clock fa-pulse"></i>${formatTime(bus.diff)}</div>
        <div class="desc" id="desc${bus.id}"><i class="fa-sharp-duotone fa-solid fa-clipboard fa-beat-fade"></i> ${bus.desc}</div>
      </div>
    `;
  }).join('');
  document.getElementById('schedule').innerHTML = html;
}

function formatTime(seconds) {
  let m = Math.floor(seconds / 60), h = Math.floor(m / 60);
  return `${h ? h + 'h ' : ''}${m % 60}m ${seconds % 60}s left`;
}

function startCountdown() {
  clearInterval(window.timer);
  window.timer = setInterval(() => {
    buses.forEach(bus => {
      if (bus.diff > 0) bus.diff -= 1;
      if (bus.diff === 600) document.getElementById('notifySound').play();
      let el = document.getElementById('left' + bus.id);
      if (el) el.innerText = '⏰ ' + formatTime(bus.diff);
    });
  }, 1000);
}

function toggleDesc(id) {
  let d = document.getElementById('desc' + id);
  if (d) d.style.display = d.style.display === 'block' ? 'none' : 'block';
}

function toggleTheme() {
  document.body.classList.toggle('light');
}

document.getElementById('addForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  let fd = new FormData(this);
  fd.append('action', 'add');
  fetch('', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(d => {
      if (d === 'success') {
        alert("✅ Bus Added!");
        this.reset();
        document.getElementById('dayTypeSelect').value = 'all';
        fetchSchedule();
      } else alert("❌ Failed to add bus");
    });
});
</script>
</body></html>