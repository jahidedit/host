<?php
include 'db.php';

// Handle Add Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $desc = $_POST['description'];

    if (in_array($type, ['income', 'expense'])) {
        $sql = "INSERT INTO transactions (type, amount, description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sds", $type, $amount, $desc);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle Delete Transaction
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM transactions WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: tk.php");
    exit;
}

// Handle Edit Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $desc = $_POST['description'];
    $sql = "UPDATE transactions SET type=?, amount=?, description=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsi", $type, $amount, $desc, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: tk.php");
    exit;
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM transactions WHERE id=$id");
    $edit_data = $result->fetch_assoc();
}

// Fetch Transactions
$result = $conn->query("SELECT * FROM transactions ORDER BY date DESC");
$income = 0;
$expense = 0;
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
    if ($row['type'] === 'income') {
        $income += $row['amount'];
    } else {
        $expense += $row['amount'];
    }
}
$balance = $income - $expense;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Money Tracker</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f4f8; }
        h2 { color: #333; }
        form { margin-bottom: 10px; background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        input, select { padding: 8px; margin: 5px; border-radius: 5px; border: 1px solid #ccc; }
        button { padding: 8px 12px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #45a049; }
        .btn {
            background-color: #04AA6D; /* Green */
            border: none;
            color: white;
            padding: 16px 32px;
            border-radius: 10px;
           text-align: center;
           text-decoration: none;
           display: inline-block;
           font-size: 18px;
           margin: 4px 2px;
           transition-duration: 0.4s;
           cursor: pointer;
        }
        .gbtn {
            background-color: #4CAF50;
            color: white;
        }
        .gbtn:hover {
            background-color: #40916c;
        }
        table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .actions a { margin: 0 5px; color: #2196F3; text-decoration: none; }
        .actions a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h2>Balance: <?= number_format($balance, 2) ?> Tk <a href="https://jahid.byethost15.com/profile.php" class="btn gbtn">
        <i class='fas fa-address-book'></i>  
        Profile</a></h2>
 

<?php if ($edit_data): ?>
<h3>Edit Transaction</h3>
<form method="post">
    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
    <select name="type">
        <option value="income" <?= $edit_data['type'] == 'income' ? 'selected' : '' ?>>Income</option>
        <option value="expense" <?= $edit_data['type'] == 'expense' ? 'selected' : '' ?>>Expense</option>
    </select>
    <input type="number" name="amount" value="<?= $edit_data['amount'] ?>" step="0.01" required>
    <input type="text" name="description" value="<?= $edit_data['description'] ?>">
    <button type="submit" name="edit">Update</button>
</form>
<?php else: ?>
<h3>Add Transaction</h3>
<form method="post">
    <select name="type">
        <option value="income">Income</option>
        <option value="expense">Expense</option>
    </select>
    <input type="number" name="amount" step="0.01" required placeholder="Amount">
    <input type="text" name="description" placeholder="Description">
    <button type="submit" name="add">Add</button>
</form>
<?php endif; ?>

<h3>Transaction History</h3>
<table>
    <tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th><th>Actions</th></tr>
    <?php foreach ($transactions as $txn): ?>
    <tr>
        <td><?= $txn['date'] ?></td>
        <td><?= ucfirst($txn['type']) ?></td>
        <td><?= $txn['amount'] ?> Tk</td>
        <td><?= $txn['description'] ?></td>
        <td class="actions">
            <a href="?edit=<?= $txn['id'] ?>">Edit</a>
            <a href="?delete=<?= $txn['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>

<?php $conn->close(); ?>
