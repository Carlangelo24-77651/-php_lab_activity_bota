<?php
// DATABASE CONNECTION
require_once __DIR__ . '/dbcon.php';

$error = "";
$editMode = false;
$editId = 0;
$name = "";
$email = "";

// CREATE AND UPDATE USER
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? 'add';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $editId = (int)($_POST['id'] ?? 0);

    $editMode = ($action === 'update');

    if ($name == "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid name and email.";

    } elseif ($editMode) {

        // UPDATE EXISTING USER
        if ($editId <= 0) {

            $error = "Invalid user selected.";

        } else {

            $check = $conn->prepare(
                "SELECT id FROM users WHERE id = ?"
            );

            $check->bind_param("i", $editId);
            $check->execute();
            $check->store_result();

            if ($check->num_rows == 0) {
                $error = "User not found.";
            }

            $check->close();

            if ($error == "") {

                $stmt = $conn->prepare(
                    "UPDATE users SET name = ?, email = ? WHERE id = ?"
                );

                $stmt->bind_param("ssi", $name, $email, $editId);
                $stmt->execute();
                $stmt->close();

                header("Location: index.php?msg=updated");
                exit();
            }
        }

    } else {

        // ADD NEW USER
        $stmt = $conn->prepare(
            "INSERT INTO users (name, email) VALUES (?, ?)"
        );

        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $stmt->close();

        header("Location: index.php?msg=added");
        exit();
    }
}

// EDIT USER IN THE SAME FORM
if ($_SERVER["REQUEST_METHOD"] != "POST" && isset($_GET['edit'])) {

    $editId = (int)$_GET['edit'];

    if ($editId > 0) {

        $stmt = $conn->prepare(
            "SELECT name, email FROM users WHERE id = ?"
        );

        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $stmt->bind_result($name, $email);

        if ($stmt->fetch()) {

            $editMode = true;

        } else {

            $error = "User not found.";
            $name = "";
            $email = "";
        }

        $stmt->close();
    }
}

// READ - DISPLAY REGISTERED USERS
$result = $conn->query(
    "SELECT * FROM users ORDER BY id ASC"
);

// SUCCESS MESSAGES
$messages = [
    "added" => "User added successfully!",
    "updated" => "User updated successfully!",
    "deleted" => "User deleted successfully!"
];

$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>User Management System</title>

    <!-- BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background-color: #f5f9ff;
            font-family: Arial, sans-serif;
        }

        .main-container {
            max-width: 1600px;
            margin: 60px auto;
        }

        .page-title {
            text-align: center;
            font-size: 38px;
            font-weight: 400;
            margin-bottom: 65px;
            color: #222;
        }

        .section-title {
            font-size: 25px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .form-box {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
        }

        .form-label {
            font-size: 17px;
            margin-bottom: 10px;
        }

        .form-control {
            padding: 12px;
            border-radius: 6px;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #0866ff;
            border: none;
            font-size: 17px;
        }

        .submit-btn:hover {
            background-color: #0052cc;
        }

        .table th {
            font-weight: bold;
            padding: 14px 10px;
        }

        .table td {
            padding: 14px 10px;
            vertical-align: middle;
        }

        .action-buttons {
            white-space: nowrap;
        }

        .alert {
            border-radius: 6px;
        }
    </style>
</head>

<body>

<div class="container-fluid main-container px-4">

    <!-- PAGE TITLE -->
    <h1 class="page-title">
        User Management System
    </h1>

    <div class="row g-5">

        <!-- LEFT SIDE - ADD / EDIT USER -->
        <div class="col-lg-5">

            <h3 class="section-title">
                <?= $editMode ? "Edit User" : "Add New User" ?>
            </h3>

            <div class="form-box">

                <!-- SUCCESS NOTIFICATION -->
                <?php if (isset($messages[$msg])): ?>

                    <div class="alert alert-success alert-dismissible fade show"
                         role="alert">

                        <?= htmlspecialchars($messages[$msg]) ?>

                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                                aria-label="Close">
                        </button>

                    </div>

                <?php endif; ?>

                <!-- ERROR NOTIFICATION -->
                <?php if ($error != ""): ?>

                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endif; ?>

                <!-- ADD / EDIT FORM -->
                <form method="POST" action="index.php">

                    <input type="hidden"
                           name="action"
                           value="<?= $editMode ? 'update' : 'add' ?>">

                    <input type="hidden"
                           name="id"
                           value="<?= (int)$editId ?>">

                    <div class="mb-4">

                        <label class="form-label">
                            Full Name
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               placeholder="Enter Full Name"
                               value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                               required>

                    </div>

                    <div class="mb-4">

                        <label class="form-label">
                            Email Address
                        </label>

                        <input type="email"
                               name="email"
                               class="form-control"
                               placeholder="Enter Email Address"
                               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                               required>

                    </div>

                    <!-- SUBMIT / UPDATE BUTTON -->
                    <button type="submit"
                            class="btn btn-primary submit-btn">

                        <?= $editMode ? "Update" : "Submit" ?>

                    </button>

                    <!-- CANCEL EDIT BUTTON -->
                    <?php if ($editMode): ?>

                        <a href="index.php"
                           class="btn btn-outline-secondary w-100 mt-3">
                            Cancel Edit
                        </a>

                    <?php endif; ?>

                </form>

            </div>

        </div>

        <!-- RIGHT SIDE - REGISTERED USERS -->
        <div class="col-lg-7">

            <h3 class="section-title">
                Registered Users
            </h3>

            <div class="table-responsive">

                <table class="table table-hover">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result->num_rows > 0): ?>

                        <?php
                        // AUTOMATIC NUMBERING
                        $number = 1;
                        ?>

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>

                                <!-- DISPLAY NUMBER -->
                                <td>
                                    <?= $number++ ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['email']) ?>
                                </td>

                                <td>
                                    <?= date(
                                        "M d, Y",
                                        strtotime($row['created_at'])
                                    ) ?>
                                </td>

                                <td class="action-buttons">

                                    <!-- EDIT BUTTON -->
                                    <a href="index.php?edit=<?= (int)$row['id'] ?>"
                                       class="btn btn-success btn-sm">
                                        Edit
                                    </a>

                                    <!-- DELETE BUTTON -->
                                    <a href="delete.php?id=<?= (int)$row['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this user?');">
                                        Delete
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5"
                                class="text-center text-muted py-4">
                                No registered users yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- BOOTSTRAP 5 JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>