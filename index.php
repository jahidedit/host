<?php
session_start();
// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");


if(!isset($_SESSION['logged']) && !isset($_SESSION['user'])){
    header("Location: login.php");
    exit;  
}

//session timeout

$time = 86400;

if(isset($_SESSION['last_activity'])){
    $session_time = time()-$_SESSION['last_activity'];
    if($session_time > $time){
        session_unset();
        session_destroy;
        header("Location: login.php");
        exit();
    }
}

$_SESSION['last_activity']=time();

include 'conf.php';

$svc_no = $_SESSION['user'];
$sql = "SELECT * FROM users WHERE service_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $svc_no);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 1){
    $current_user = $result->fetch_assoc();
    $rank = $current_user['rank'];
    $name = $current_user['name'];
    $name = $rank." ".$name;
    
}else{
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}








// Connect to SQLite database
$db = new SQLite3('supply.db');

// Create table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS sup_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item TEXT NOT NULL,
    qty INTEGER NOT NULL,
    ldi DATE NOT NULL,
    tenure INTEGER NOT NULL,
    next_due DATE,
    issued_date DATE,
    issued_by TEXT
)");


// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Update LDI date
        if (isset($data['action']) && $data['action'] === 'update_ldi') {
            $id = SQLite3::escapeString($data['id']);
            $ldi = SQLite3::escapeString($data['ldi']);
            
            $db->exec("UPDATE sup_items SET ldi = '$ldi' WHERE id = $id");
            
            echo json_encode(['success' => true]);
            exit();
        }
        
        // Issue item
        if (isset($data['action']) && $data['action'] === 'issue_item') {
            $id = SQLite3::escapeString($data['id']);
            $issueDate = SQLite3::escapeString($data['issue_date']);
            $issuedBy = SQLite3::escapeString($data['issued_by'] ?? 'System');
            
            $db->exec("UPDATE sup_items SET 
                      ldi = '$issueDate',
                      issued_date = '$issueDate',
                      issued_by = '$issuedBy'
                      WHERE id = $id");
            
            echo json_encode(['success' => true]);
            exit();
        }
        
        // Add new item
        if (isset($data['action']) && $data['action'] === 'add_item') {
            $item = SQLite3::escapeString($data['item']);
            $qty = (int)$data['qty'];
            $tenure = (int)$data['tenure'];
            $ldi = date('Y-m-d');
            
            $db->exec("INSERT INTO sup_items (item, qty, ldi, tenure, issued_date, issued_by) 
                      VALUES ('$item', $qty, '$ldi', $tenure, '$ldi', 'System')");
            $id = $db->lastInsertRowID();
            
            echo json_encode(['success' => true, 'id' => $id]);
            exit();
        }
        
        // Update item details
        if (isset($data['action']) && $data['action'] === 'update_item') {
            $id = SQLite3::escapeString($data['id']);
            $item = SQLite3::escapeString($data['item']);
            $qty = (int)$data['qty'];
            $tenure = (int)$data['tenure'];
            
            $db->exec("UPDATE sup_items SET 
                      item = '$item',
                      qty = $qty,
                      tenure = $tenure
                      WHERE id = $id");
            
            echo json_encode(['success' => true]);
            exit();
        }
        
        // Delete item
        if (isset($data['action']) && $data['action'] === 'delete_item') {
            $id = SQLite3::escapeString($data['id']);
            
            $db->exec("DELETE FROM sup_items WHERE id = $id");
            
            echo json_encode(['success' => true]);
            exit();
        }
        
        // Rename item
        if (isset($data['action']) && $data['action'] === 'rename_item') {
            $id = SQLite3::escapeString($data['id']);
            $item = SQLite3::escapeString($data['item']);
            
            $db->exec("UPDATE sup_items SET item = '$item' WHERE id = $id");
            
            echo json_encode(['success' => true]);
            exit();
        }
    }
    
    // Get all items with calculated fields
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $tableCheck = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='sup_items'");
        
        if (!$tableCheck) {
            echo json_encode([]);
            exit();
        }

        $result = $db->query("
            SELECT *, 
            date(ldi, '+' || tenure || ' months') as next_due,
            ROUND(julianday(date(ldi, '+' || tenure || ' months')) - julianday('now')) as day_left,
            CASE 
                WHEN julianday(date(ldi, '+' || tenure || ' months')) < julianday('now') THEN 'Due'
                ELSE 'Not Due'
            END as status
            FROM sup_items 
            ORDER BY 
                CASE WHEN status = 'Due' THEN 0 ELSE 1 END,
                day_left ASC
        ");
        
        $items = [];
        if ($result) {
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $items[] = $row;
            }
        }
        echo json_encode($items);
        exit();
    }
}else{
    header('Content-Type: text/html; charset=utf-8');
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=900">
    <title>Supply Inventory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>

        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --white: #ffffff;
            --black: #000000;
            
            --bg-color: #ffffff;
            --text-color: #212529;
            --card-bg: #f8f9fa;
            --border-color: #dee2e6;
            --hover-bg: #e9ecef;
            --due-bg: #fff0f3;
            --due-bg-dark: #2a0a14;
            --overdue-bg: #fff0f3;
            --overdue-bg-dark: #33000d;
            --warning-bg: #fff3cd;
            --warning-bg-dark: #332701;
            --success-bg: #d1e7dd;
            --success-bg-dark: #0d3321;
        }

        [data-theme="dark"] {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #343a40;
            --dark: #f8f9fa;
            --gray: #adb5bd;
            --gray-light: #495057;
            --white: #212529;
            --black: #f8f9fa;
            
            --bg-color: #121212;
            --text-color: #e0e0e0;
            --card-bg: #1e1e1e;
            --border-color: #333333;
            --hover-bg: #2d2d2d;
            --due-bg: var(--due-bg-dark);
            --overdue-bg: var(--overdue-bg-dark);
            --warning-bg: var(--warning-bg-dark);
            --success-bg: var(--success-bg-dark);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 0;
            margin: 0;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            user-select: none; /* Standard */
              -webkit-user-select: none; /* Safari */
              -moz-user-select: none; /* Firefox */
              -ms-user-select: none; /* IE10+ */
            
        }
        
  

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
            width: 100%;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        h1 {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 700;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.95rem;
            gap: 0.5rem;
        }

        .btn i {
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        .logout-btn {
		background-color: #ff5252;
		color: white;
		border-color: #ff5252;
    	}

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #d61a6f;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .btn-outline:hover {
            background-color: var(--hover-bg);
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-color);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0.5rem;
            border-radius: 50%;
        }

        .theme-toggle:hover {
            background-color: var(--hover-bg);
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th, td {
            padding: 0.85rem 1.2rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--light);
            color: var(--dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
        }

        tr.due {
            background-color: var(--due-bg);
        }

        tr.overdue {
            background-color: var(--overdue-bg);
        }

        tr.warning {
            background-color: var(--warning-bg);
        }

        tr.success {
            background-color: var(--success-bg);
        }

        tr:not(.due):not(.overdue):not(.warning):not(.success):hover {
            background-color: var(--hover-bg);
        }

        .status {
            display: inline-block;
            padding: 0.35rem 0.7rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-due {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--danger);
        }

        .status-not-due {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--success);
        }

        .badge {
            display: inline-block;
            padding: 0.35rem 0.7rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-warning {
            background-color: rgba(248, 150, 30, 0.2);
            color: var(--warning);
        }

        .badge-danger {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--danger);
        }

        .badge-success {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--success);
        }

        .last-issued {
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .actions-cell {
            white-space: nowrap;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1050;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            transform: translateY(-50px);
            transition: transform 0.3s;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: var(--text-color);
            opacity: 0.5;
            cursor: pointer;
            padding: 0;
        }

        .close:hover {
            opacity: 1;
        }

        .date-options {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .date-option {
            flex: 1;
            text-align: center;
        }

        .date-option input[type="radio"] {
            display: none;
        }

        .date-option label {
            display: block;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .date-option input[type="radio"]:checked + label {
            border-color: var(--primary);
            background-color: rgba(67, 97, 238, 0.1);
        }

        .date-option label:hover {
            background-color: var(--hover-bg);
        }

        .custom-date {
            margin-top: 1rem;
            display: none;
        }

        .custom-date.show {
            display: block;
        }

        .toast {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            padding: 1rem 1.5rem;
            background-color: var(--card-bg);
            border-left: 4px solid var(--success);
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateX(120%);
            transition: transform 0.3s;
            z-index: 1100;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-error {
            border-left-color: var(--danger);
        }

        .toast-icon {
            font-size: 1.5rem;
        }

        .toast-success .toast-icon {
            color: var(--success);
        }

        .toast-error .toast-icon {
            color: var(--danger);
        }

        .toast-body {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .toast-message {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--text-color);
            opacity: 0.5;
            cursor: pointer;
            font-size: 1.25rem;
        }

        .toast-close:hover {
            opacity: 1;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* Context Menu */
        .context-menu {
            position: fixed;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            min-width: 200px;
            overflow: hidden;
        }

        .context-menu.show {
            display: block;
        }

        .context-menu-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-color);
        }

        .context-menu-item:hover {
            background-color: var(--hover-bg);
        }

        .context-menu-item i {
            width: 20px;
            text-align: center;
        }

        .context-menu-divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 0.25rem 0;
        }

        /* Modal Backdrop */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1050;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-out;
            backdrop-filter: blur(5px);
        }
        
        /* Modal Content */
        .modal-content {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(-30px);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        /* Modal Header */
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--card-bg);
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        .modal-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        /* Modal Body */
        .modal-body {
            padding: 1.5rem;
            color: var(--text-color);
        }
        
        /* Modal Footer */
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background-color: var(--card-bg);
            position: sticky;
            bottom: 0;
        }
        
        /* Close Button */
        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: var(--text-muted);
            opacity: 0.75;
            cursor: pointer;
            padding: 0.25rem;
            transition: all 0.2s;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close:hover {
            opacity: 1;
            background-color: var(--hover-bg);
            color: var(--text-color);
        }
        
        /* Form Elements in Modal */
        .modal .form-group {
            margin-bottom: 1.25rem;
        }
        
        .modal .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
            font-size: 0.875rem;
        }
        
        .modal .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .modal .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 90, 249, 0.2);
        }
        
        /* Date Options in Modal */
        .date-options {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        
        .date-option {
            flex: 1;
        }
        
        .date-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .date-option label {
            display: block;
            padding: 0.875rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            background-color: var(--bg-color);
            font-size: 0.875rem;
        }
        
        .date-option input[type="radio"]:checked + label {
            border-color: var(--primary);
            background-color: rgba(58, 90, 249, 0.1);
            font-weight: 500;
        }
        
        .date-option label:hover {
            background-color: var(--hover-bg);
        }
        
        /* Custom Date Input */
        .custom-date {
            margin-top: 1.25rem;
            display: none;
        }
        
        .custom-date.show {
            display: block;
        }
        
        /* Active Modal State */
        .modal.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
        }
        
        
       
tr.active-state {
    background-color: var(--hover-bg) !important;
    opacity: 0.9;
    transition: background-color 0.1s, opacity 0.1s;
}


@media (hover: none) {
    tr:active {
        background-color: var(--hover-bg) !important;
        opacity: 0.9;
    }
}


        /* Responsive Modal */
        @media (max-width: 576px) {
            .modal-content {
                margin: 0.75rem;
                max-width: calc(100% - 1.5rem);
            }
            
            .date-options {
                flex-direction: column;
            }
            
            .modal-footer {
                flex-direction: column-reverse;
                gap: 0.5rem;
            }
            
            .modal-footer .btn {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0.5rem;
            }
            
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                padding: 1rem 0.5rem;
            }
            
            .actions {
                width: 100%;
                display: flex;
                justify-content: space-between;
            }
            
            th, td {
                padding: 0.5rem;
                font-size: 0.875rem;
            }
            
            .modal-content {
                margin: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .date-options {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .table-container {
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
        }

    </style>
</head>
<body oncontextmenu="return false;">
    <div class="container">
        <header>
            <h1><i class="fas fa-shopping-bag"></i><? echo $name; ?> </h1>
	<h1 style="text-align:center;">
<button class="btn btn-primary" onclick="window.location.href='profile.php'">
<i class='fas fa-address-book'></i>  
<span>Profile</span></button></h1>
    <p>logged in: <?php echo $_SESSION['user']; ?></p>
            <div class="actions">
                <button id="themeToggle" class="theme-toggle">
                    <i class="fas fa-moon"></i>
                </button>
                <button id="addItemBtn" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
<h1 style="text-align:center;">
<button class="btn logout-btn" onclick="window.location.href='logout.php'">
<i class="fas fa-sign-out-alt"></i> 
<span>Logout</span></button>
        </header>

        <div class="table-container">
            <table id="inventoryTable">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Last Issued</th>
                        <th>Tenure (M)</th>
                        <th>Next Due</th>
                        <th>Days Left</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody"></tbody>
            </table>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div id="addItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Item</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    <div class="form-group">
                        <label for="itemName" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="itemName" required placeholder="Enter item name">
                    </div>
                    <div class="form-group">
                        <label for="itemQty" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="itemQty" min="1" value="1" required placeholder="Enter quantity">
                    </div>
                    <div class="form-group">
                        <label for="itemTenure" class="form-label">Issue Duration (Months)</label>
                        <input type="number" class="form-control" id="itemTenure" min="1" value="12" required placeholder="Enter duration in months">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveItemBtn">Save Item</button>
            </div>
        </div>
    </div>

    <!-- Issue Item Modal -->
    <div id="issueItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Issue Item</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="issueItemForm">
                    <input type="hidden" id="issueItemId">
                    <div class="form-group">
                        <label class="form-label">Select Issue Date</label>
                        <div class="date-options">
                            <div class="date-option">
                                <input type="radio" name="issueDateOption" id="currentDateOption" value="current" checked>
                                <label for="currentDateOption">Current Date</label>
                            </div>
                            <div class="date-option">
                                <input type="radio" name="issueDateOption" id="customDateOption" value="custom">
                                <label for="customDateOption">Custom Date</label>
                            </div>
                        </div>
                        <div class="custom-date" id="customDateContainer">
                            <label for="customDate" class="form-label">Select Previous Date</label>
                            <input type="date" class="form-control" id="customDate" max="<?= date('Y-m-d') ?>" placeholder="Select date (max: today)">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmIssueBtn">Confirm Issue</button>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div id="editItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Item</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    <input type="hidden" id="editItemId">
                    <div class="form-group">
                        <label for="editItemName" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="editItemName" required placeholder="Enter item name">
                    </div>
                    <div class="form-group">
                        <label for="editItemQty" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="editItemQty" min="1" required placeholder="Enter quantity">
                    </div>
                    <div class="form-group">
                        <label for="editItemTenure" class="form-label">Issue Duration (Months)</label>
                        <input type="number" class="form-control" id="editItemTenure" min="1" required placeholder="Enter duration in months">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Rename Item Modal -->
    <div id="renameItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rename Item</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="renameItemForm">
                    <input type="hidden" id="renameItemId">
                    <div class="form-group">
                        <label for="renameItemName" class="form-label">New Item Name</label>
                        <input type="text" class="form-control" id="renameItemName" required placeholder="Enter new item name">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveRenameBtn">Rename Item</button>
            </div>
        </div>
    </div>

    <!-- Change Date Modal -->
    <div id="changeDateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Last Issued Date</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="changeDateForm">
                    <input type="hidden" id="changeDateItemId">
                    <div class="form-group">
                        <label for="newDate" class="form-label">New Last Issued Date</label>
                        <input type="date" class="form-control" id="newDate" max="<?= date('Y-m-d') ?>" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveDateBtn">Change Date</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this item? This action cannot be undone.</p>
                <input type="hidden" id="deleteItemId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Item</button>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="contextMenu" class="context-menu">
        <div class="context-menu-item" data-action="rename">
            <i class="fas fa-edit"></i>
            <span>Rename Item</span>
        </div>
        <div class="context-menu-item" data-action="edit">
            <i class="fas fa-pencil-alt"></i>
            <span>Edit Details</span>
        </div>
        <div class="context-menu-item" data-action="change-date">
            <i class="fas fa-calendar-alt"></i>
            <span>Change Date</span>
        </div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item" data-action="delete">
            <i class="fas fa-trash"></i>
            <span>Delete Item</span>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="toast-body">
            <div class="toast-title">Success</div>
            <div class="toast-message">Operation completed successfully.</div>
        </div>
        <button class="toast-close">&times;</button>
    </div>


    <script>
// Theme Toggle
const themeToggle = document.getElementById('themeToggle');
const html = document.documentElement;

const savedTheme = localStorage.getItem('theme') || 
                  (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
html.setAttribute('data-theme', savedTheme);
updateThemeIcon();

themeToggle.addEventListener('click', () => {
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon();
});

function updateThemeIcon() {
    const currentTheme = html.getAttribute('data-theme');
    themeToggle.innerHTML = currentTheme === 'dark' ? 
        '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
}

// Modal Handling
const modals = {
    addItem: document.getElementById('addItemModal'),
    issueItem: document.getElementById('issueItemModal'),
    editItem: document.getElementById('editItemModal'),
    renameItem: document.getElementById('renameItemModal'),
    changeDate: document.getElementById('changeDateModal'),
    deleteItem: document.getElementById('deleteItemModal')
};

const showModal = (modal) => {
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
};

const hideModal = (modal) => {
    modal.classList.remove('show');
    document.body.style.overflow = '';
};

// Close modals when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            hideModal(modal);
        }
    });
});

// Close modals with close buttons
document.querySelectorAll('.close, [data-dismiss="modal"]').forEach(btn => {
    btn.addEventListener('click', (e) => {
        const modal = e.target.closest('.modal');
        hideModal(modal);
    });
});

// Toast Notification
const toast = document.getElementById('toast');
const showToast = (message, isError = false) => {
    const toastTitle = toast.querySelector('.toast-title');
    const toastMessage = toast.querySelector('.toast-message');
    const toastIcon = toast.querySelector('.toast-icon i');
    
    toastTitle.textContent = isError ? 'Error' : 'Success';
    toastMessage.textContent = message;
    
    toast.classList.remove('toast-error');
    toastIcon.className = isError ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
    
    if (isError) {
        toast.classList.add('toast-error');
    }
    
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 5000);
};

// Close toast
document.querySelector('.toast-close').addEventListener('click', () => {
    toast.classList.remove('show');
});

// Context Menu - Fixed version
const contextMenu = document.getElementById('contextMenu');
let selectedItemId = null;
let selectedItemData = null;
let contextMenuVisible = false;
let touchTimer = null;
let isZooming = false;
let ignoreNextClick = false;

function showContextMenu(e, item) {
    e.preventDefault();
    e.stopPropagation();
    
    if (ignoreNextClick) {
        ignoreNextClick = false;
        return;
    }
    
    hideContextMenu();
    
    selectedItemId = item.id;
    selectedItemData = item;
    
    let posX, posY;
    if (e.type.includes('touch')) {
        const touch = e.changedTouches[0];
        posX = touch.clientX;
        posY = touch.clientY;
    } else {
        posX = e.clientX;
        posY = e.clientY;
    }
    
    contextMenu.style.display = 'block';
    contextMenu.style.left = `${posX}px`;
    contextMenu.style.top = `${posY}px`;
    
    const rect = contextMenu.getBoundingClientRect();
    if (rect.right > window.innerWidth) {
        contextMenu.style.left = `${window.innerWidth - rect.width}px`;
    }
    if (rect.bottom > window.innerHeight) {
        contextMenu.style.top = `${window.innerHeight - rect.height}px`;
    }
    
    contextMenu.classList.add('show');
    contextMenuVisible = true;
    
    // Add a small delay before attaching the close handler
    setTimeout(() => {
        const closeMenu = (e) => {
            // Don't close if clicking on the context menu itself
            if (contextMenu.contains(e.target)) return;
            
            hideContextMenu();
            document.removeEventListener('click', closeMenu);
            document.removeEventListener('touchstart', closeMenu);
            document.removeEventListener('scroll', closeMenu);
        };
        
        document.addEventListener('click', closeMenu);
        document.addEventListener('touchstart', closeMenu, { passive: true });
        document.addEventListener('scroll', closeMenu, { passive: true });
    }, 50);
}

function hideContextMenu() {
    if (!contextMenuVisible) return;
    
    contextMenu.classList.remove('show');
    setTimeout(() => {
        contextMenu.style.display = 'none';
    }, 200);
    
    contextMenuVisible = false;
    ignoreNextClick = true;
    setTimeout(() => { ignoreNextClick = false; }, 100);
}

function handleContextMenuAction(action) {
    if (!selectedItemId || !selectedItemData) return;
    
    switch (action) {
        case 'rename':
            openRenameModal(selectedItemData);
            break;
        case 'edit':
            openEditModal(selectedItemData);
            break;
        case 'change-date':
            openChangeDateModal(selectedItemData);
            break;
        case 'delete':
            openDeleteModal(selectedItemData);
            break;
    }
}

// Touch handlers for single-finger long press
function handleTouchStart(e) {
    if (e.touches.length > 1) {
        isZooming = true;
        if (touchTimer) {
            clearTimeout(touchTimer);
            touchTimer = null;
        }
        return;
    }
    
    if (e.touches.length === 1) {
        const touch = e.touches[0];
        touchTimer = setTimeout(() => {
            if (!isZooming && selectedItemData) {
                showContextMenu(e, selectedItemData);
            }
        }, 800);
    }
}

function handleTouchMove(e) {
    if (e.touches.length > 1) {
        isZooming = true;
        if (touchTimer) {
            clearTimeout(touchTimer);
            touchTimer = null;
        }
        return;
    }
    
    if (touchTimer) {
        clearTimeout(touchTimer);
        touchTimer = null;
    }
}

function handleTouchEnd() {
    isZooming = false;
    if (touchTimer) {
        clearTimeout(touchTimer);
        touchTimer = null;
    }
}

// Event Listeners for Context Menu Actions
document.querySelectorAll('.context-menu-item').forEach(item => {
    item.addEventListener('click', (e) => {
        const action = e.currentTarget.getAttribute('data-action');
        handleContextMenuAction(action);
        hideContextMenu();
    });
});

// Modal Functions
function openRenameModal(item) {
    document.getElementById('renameItemId').value = item.id;
    document.getElementById('renameItemName').value = item.item;
    showModal(modals.renameItem);
}

function openEditModal(item) {
    document.getElementById('editItemId').value = item.id;
    document.getElementById('editItemName').value = item.item;
    document.getElementById('editItemQty').value = item.qty;
    document.getElementById('editItemTenure').value = item.tenure;
    showModal(modals.editItem);
}

function openChangeDateModal(item) {
    document.getElementById('changeDateItemId').value = item.id;
    document.getElementById('newDate').value = item.ldi;
    showModal(modals.changeDate);
}

function openDeleteModal(item) {
    document.getElementById('deleteItemId').value = item.id;
    showModal(modals.deleteItem);
}

function openIssueModal(itemId) {
    currentIssueItemId = itemId;
    document.getElementById('issueItemId').value = itemId;
    document.getElementById('customDate').value = '';
    document.getElementById('currentDateOption').checked = true;
    document.getElementById('customDateContainer').classList.remove('show');
    showModal(modals.issueItem);
}

// Date options in issue modal
document.querySelectorAll('input[name="issueDateOption"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
        const customDateContainer = document.getElementById('customDateContainer');
        if (e.target.value === 'custom') {
            customDateContainer.classList.add('show');
        } else {
            customDateContainer.classList.remove('show');
        }
    });
});

// Check if device is touch-enabled
function isTouchDevice() {
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
}

// Inventory Data Handling
let inventoryData = [];
let currentIssueItemId = null;

// Enhanced fetch function with better error handling
async function safeFetch(url, options = {}) {
    try {
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Expected JSON but got:', text);
            throw new Error('Server returned non-JSON response');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

// Load inventory data
async function loadInventory() {
    try {
        const data = await safeFetch('index.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        inventoryData = Array.isArray(data) ? data : [];
        renderInventory(inventoryData);
    } catch (error) {
        console.error('Error loading inventory:', error);
        showToast('Error loading inventory data', true);
        renderInventory([]);
    }
}

// Format date to "17 Apr 2025" format
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid Date';
    
    const options = { day: 'numeric', month: 'short', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Render inventory table
function renderInventory(items) {
    const tbody = document.getElementById('inventoryTableBody');
    if (!tbody) {
        console.error('Table body element not found');
        return;
    }
    
    tbody.innerHTML = '';
    
    if (!items || items.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td colspan="8">
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No items found</h3>
                    <p>Add your first item to get started</p>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
        return;
    }
    
    items.forEach(item => {
        if (!item || typeof item !== 'object') {
            console.warn('Invalid item data:', item);
            return;
        }
        
        const tr = document.createElement('tr');
        tr.dataset.id = item.id;
        tr.itemData = item;
        
        // Apply status classes
        if (item.status === 'Due') {
            tr.classList.add('overdue');
        } else if (item.day_left <= 7) {
            tr.classList.add('due');
        } else if (item.day_left <= 30) {
            tr.classList.add('warning');
        } else {
            tr.classList.add('success');
        }
        
        const ldiFormatted = formatDate(item.ldi);
        const nextDueFormatted = formatDate(item.next_due);
        
        const statusClass = item.status === 'Due' ? 'status-due' : 'status-not-due';
        const statusBadge = `<span class="status ${statusClass}">${item.status || 'Unknown'}</span>`;
        
        let daysLeftBadge = '';
        const dayLeft = item.day_left || 0;
        
        if (dayLeft < 0) {
            daysLeftBadge = `<span class="badge badge-danger">${Math.abs(dayLeft)} days overdue</span>`;
        } else if (dayLeft <= 7) {
            daysLeftBadge = `<span class="badge badge-danger">${dayLeft} days left</span>`;
        } else if (dayLeft <= 30) {
            daysLeftBadge = `<span class="badge badge-warning">${dayLeft} days left</span>`;
        } else {
            daysLeftBadge = `<span class="badge badge-success">${dayLeft} days left</span>`;
        }
        
        const issueDisabled = item.status !== 'Due' ? 'disabled' : '';
        const issueButton = `
            <button class="btn btn-sm btn-danger" ${issueDisabled} 
                onclick="openIssueModal(${item.id})">
                <i class="fas fa-box-open"></i> Issue
            </button>
        `;
        
        tr.innerHTML = `
            <td>${item.item || 'N/A'}</td>
            <td>${item.qty || 0}</td>
            <td><span class="last-issued">${ldiFormatted}</span></td>
            <td>${item.tenure || 0}</td>
            <td>${nextDueFormatted}</td>
            <td>${daysLeftBadge}</td>
            <td>${statusBadge}</td>
            <td class="actions-cell">${issueButton}</td>
        `;
        
        // Add context menu event
        tr.addEventListener('contextmenu', (e) => {
            if (isTouchDevice()) return;
            showContextMenu(e, item);
        });
        
        // Add active state for touch feedback
        tr.addEventListener('mousedown', () => tr.classList.add('active-state'));
        tr.addEventListener('mouseup', () => tr.classList.remove('active-state'));
        tr.addEventListener('mouseleave', () => tr.classList.remove('active-state'));
        
        // Touch events for mobile context menu
        if (isTouchDevice()) {
            tr.addEventListener('touchstart', function(e) {
                selectedItemData = this.itemData;
                handleTouchStart(e);
            }, { passive: true });
            
            tr.addEventListener('touchmove', handleTouchMove, { passive: true });
            tr.addEventListener('touchend', handleTouchEnd, { passive: true });
        }
        
        tbody.appendChild(tr);
    });
}

// Form Handlers with improved error handling
document.getElementById('addItemBtn')?.addEventListener('click', () => {
    document.getElementById('addItemForm')?.reset();
    showModal(modals.addItem);
});

document.getElementById('saveItemBtn')?.addEventListener('click', async () => {
    const itemName = document.getElementById('itemName')?.value.trim();
    const itemQty = document.getElementById('itemQty')?.value;
    const itemTenure = document.getElementById('itemTenure')?.value;
    
    if (!itemName) {
        showToast('Please enter an item name', true);
        return;
    }
    
    try {
        const data = await safeFetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'add_item',
                item: itemName,
                qty: itemQty,
                tenure: itemTenure
            })
        });
        
        if (data?.success) {
            showToast('Item added successfully');
            hideModal(modals.addItem);
            loadInventory();
        } else {
            showToast('Failed to add item', true);
        }
    } catch (error) {
        showToast('Error adding item: ' + error.message, true);
        console.error('Error:', error);
    }
});

document.getElementById('confirmIssueBtn')?.addEventListener('click', async () => {
    const issueDateOption = document.querySelector('input[name="issueDateOption"]:checked')?.value;
    let issueDate;
    
    if (issueDateOption === 'current') {
        issueDate = new Date().toISOString().split('T')[0];
    } else {
        issueDate = document.getElementById('customDate')?.value;
        if (!issueDate) {
            showToast('Please select a custom date', true);
            return;
        }
    }
    
    try {
        const data = await safeFetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'issue_item',
                id: currentIssueItemId,
                issue_date: issueDate
            })
        });
        
        if (data?.success) {
            showToast('Item issued successfully');
            hideModal(modals.issueItem);
            loadInventory();
        } else {
            showToast('Failed to issue item', true);
        }
    } catch (error) {
        showToast('Error issuing item: ' + error.message, true);
        console.error('Error:', error);
    }
});

// Save Edit Button Handler
document.getElementById('saveEditBtn')?.addEventListener('click', async () => {
    const itemId = document.getElementById('editItemId')?.value;
    const itemName = document.getElementById('editItemName')?.value.trim();
    const itemQty = document.getElementById('editItemQty')?.value;
    const itemTenure = document.getElementById('editItemTenure')?.value;
    
    if (!itemName || !itemQty || !itemTenure) {
        showToast('Please fill all fields', true);
        return;
    }
    
    try {
        const data = await safeFetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'update_item',
                id: itemId,
                item: itemName,
                qty: itemQty,
                tenure: itemTenure
            })
        });
        
        if (data?.success) {
            showToast('Item updated successfully');
            hideModal(modals.editItem);
            loadInventory();
        } else {
            showToast('Failed to update item', true);
        }
    } catch (error) {
        showToast('Error updating item: ' + error.message, true);
        console.error('Error:', error);
    }
});

// Save Rename Button Handler
document.getElementById('saveRenameBtn')?.addEventListener('click', async () => {
    const itemId = document.getElementById('renameItemId')?.value;
    const itemName = document.getElementById('renameItemName')?.value.trim();
    
    if (!itemName) {
        showToast('Please enter a new item name', true);
        return;
    }
    
    try {
        const data = await safeFetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'rename_item',
                id: itemId,
                item: itemName
            })
        });
        
        if (data?.success) {
            showToast('Item renamed successfully');
            hideModal(modals.renameItem);
            loadInventory();
        } else {
            showToast('Failed to rename item', true);
        }
    } catch (error) {
        showToast('Error renaming item: ' + error.message, true);
        console.error('Error:', error);
    }
});

// Save Date Button Handler
document.getElementById('saveDateBtn')?.addEventListener('click', async () => {
    const itemId = document.getElementById('changeDateItemId')?.value;
    const newDate = document.getElementById('newDate')?.value;
    
    if (!itemId || !newDate) {
        showToast('Please select a valid date', true);
        return;
    }
    
    try {
        const data = await safeFetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'update_ldi',
                id: itemId,
                ldi: newDate
            })
        });
        
        if (data?.success) {
            showToast('Date updated successfully');
            hideModal(modals.changeDate);
            loadInventory();
        } else {
            showToast('Failed to update date', true);
        }
    } catch (error) {
        showToast('Error updating date: ' + error.message, true);
        console.error('Error:', error);
    }
});

// Confirm Delete Button Handler
document.getElementById('confirmDeleteBtn')?.addEventListener('click', async () => {
    const itemId = document.getElementById('deleteItemId')?.value;
    
    if (!itemId) {
        showToast('No item selected for deletion', true);
        return;
    }
    
    try {
        const data = await safeFetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'delete_item',
                id: itemId
            })
        });
        
        if (data?.success) {
            showToast('Item deleted successfully');
            hideModal(modals.deleteItem);
            loadInventory();
        } else {
            showToast('Failed to delete item', true);
        }
    } catch (error) {
        showToast('Error deleting item: ' + error.message, true);
        console.error('Error:', error);
    }
});

// Initialize the app
document.addEventListener('DOMContentLoaded', () => {
    if (window.self !== window.top) {
        document.body.classList.add('in-iframe');
    }
    
    loadInventory();
    
    // Set max date for date inputs
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('customDate')?.setAttribute('max', today);
    document.getElementById('newDate')?.setAttribute('max', today);
    
    // Additional safety for closing context menu
    document.addEventListener('click', (e) => {
        if (contextMenuVisible && !contextMenu.contains(e.target)) {
            hideContextMenu();
        }
    });
    
    document.addEventListener('touchstart', (e) => {
        if (contextMenuVisible && !contextMenu.contains(e.target)) {
            hideContextMenu();
        }
    }, { passive: true });
});

// Make functions available globally
window.openIssueModal = openIssueModal;
window.showContextMenu = showContextMenu;
window.hideContextMenu = hideContextMenu;
window.handleContextMenuAction = handleContextMenuAction;
    </script>
</body>
</html>