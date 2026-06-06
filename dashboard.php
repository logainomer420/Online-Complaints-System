<?php
session_start();
include 'config.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$message = "";

// ACTION 1: Student submits a new complaint
if (isset($_POST['submit_complaint']) && $role === 'student') {
    $category = $_POST['category'];
    $details = $_POST['details'];

    $stmt = $conn->prepare("INSERT INTO complaints (user_id, category, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $category, $details);
    if ($stmt->execute()) {
        $message = "Complaint filed and tracking initialized!";
    }
    $stmt->close();
}

// ACTION 2: Admin updates live status tracker
if (isset($_POST['update_status']) && $role === 'admin') {
    $complaint_id = $_POST['complaint_id'];
    $new_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE complaints SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $complaint_id);
    if ($stmt->execute()) {
        $message = "Complaint tracking status updated successfully!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Online Education Complaint System</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f3f4f6; color: #333; }
        nav { background: #fff; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .logout-btn { color: #dc3545; text-decoration: none; font-weight: bold; border: 1px solid #dc3545; padding: 6px 12px; border-radius: 5px; }
        .logout-btn:hover { background: #dc3545; color: white; }
        .main-container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .msg { background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: 500; }
        
        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); height: fit-content; }
        h3 { margin-bottom: 15px; color: #4f46e5; border-bottom: 2px solid #f3f4f6; padding-bottom: 8px; }
        
        label { display: block; margin-top: 12px; font-size: 14px; font-weight: 600; }
        select, textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .submit-btn { width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 6px; margin-top: 15px; font-weight: bold; cursor: pointer; }
        .submit-btn:hover { background: #4338ca; }
        
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { background-color: #f9fafb; color: #4b5563; font-weight: 600; }
        
        /* Status Badges */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-Pending { background: #fef3c7; color: #d97706; }
        .badge-In-Progress { background: #e0f2fe; color: #0284c7; }
        .badge-Resolved { background: #d1e7dd; color: #0f5132; }

        .admin-action-form { display: flex; gap: 5px; align-items: center; }
        .admin-action-form select { margin-top: 0; padding: 5px; font-size: 12px; width: auto; }
        .update-btn { padding: 5px 10px; background: #10b981; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

<nav>
    <h2>EduComplaint Portal <span style="font-size: 14px; color:#666;">(<?php echo ucfirst($role); ?> Workspace)</span></h2>
    <div>
        <span style="margin-right: 15px;">Hello, <strong><?php echo htmlspecialchars($username); ?></strong></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="main-container">
    <?php if($message != ""): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if($role === 'student'): ?>
        <!-- STUDENT WORKSPACE -->
        <div class="grid">
            <div class="card">
                <h3>File a Complaint</h3>
                <form method="POST">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="">Choose issue area</option>
                        <option>Exams & Grading</option>
                        <option>Faculty & Teaching</option>
                        <option>Facility & Infrastructure</option>
                        <option>Fees & Finance</option>
                        <option>Other</option>
                    </select>

                    <label>Detailed Explanation</label>
                    <textarea name="details" rows="6" placeholder="Provide accurate event timeline, class names, or details..." required></textarea>

                    <button type="submit" name="submit_complaint" class="submit-btn">File and Track Complaint</button>
                </form>
            </div>

            <div class="card">
                <h3>Your Tracked Complaints</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Status State</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT id, category, details, status FROM complaints WHERE user_id = ? ORDER BY id DESC");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if($result->num_rows == 0) {
                            echo "<tr><td colspan='4' style='text-align:center; color:#999;'>You haven't filed any complaints yet.</td></tr>";
                        }
                        while($row = $result->fetch_assoc()) {
                            // Safe replace spaces for class names
                            $status_class = str_replace(' ', '-', $row['status']);
                            echo "<tr>";
                            echo "<td>#".$row['id']."</td>";
                            echo "<td>".htmlspecialchars($row['category'])."</td>";
                            echo "<td>".htmlspecialchars(substr($row['details'], 0, 60))."...</td>";
                            echo "<td><span class='badge badge-".$status_class."'>".$row['status']."</span></td>";
                            echo "</tr>";
                        }
                        $stmt->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- ADMIN DASHBOARD WORKSPACE -->
        <div class="card">
            <h3>Institution Administration Resolution Panel</h3>
            <table>
                <thead>
                    <tr>
                        <th>Complaint ID</th>
                        <th>Filed By</th>
                        <th>Category</th>
                        <th>Full Details</th>
                        <th>Current State</th>
                        <th>Modify State Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT complaints.id, complaints.category, complaints.details, complaints.status, users.username 
                              FROM complaints 
                              JOIN users ON complaints.user_id = users.id 
                              ORDER BY complaints.id DESC";
                    $result = $conn->query($query);

                    if($result->num_rows == 0) {
                        echo "<tr><td colspan='6' style='text-align:center; color:#999;'>No structural complaints received from students.</td></tr>";
                    }
                    while($row = $result->fetch_assoc()) {
                        $status_class = str_replace(' ', '-', $row['status']);
                        echo "<tr>";
                        echo "<td>#".$row['id']."</td>";
                        echo "<td><strong>".htmlspecialchars($row['username'])."</strong></td>";
                        echo "<td>".htmlspecialchars($row['category'])."</td>";
                        echo "<td>".htmlspecialchars($row['details'])."</td>";
                        echo "<td><span class='badge badge-".$status_class."'>".$row['status']."</span></td>";
                        echo "<td>
                                <form method='POST' class='admin-action-form'>
                                    <input type='hidden' name='complaint_id' value='".$row['id']."'>
                                    <select name='status'>
                                        <option value='Pending'".($row['status']=='Pending'?' selected':'').">Pending</option>
                                        <option value='In Progress'".($row['status']=='In Progress'?' selected':'').">In Progress</option>
                                        <option value='Resolved'".($row['status']=='Resolved'?' selected':'').">Resolved</option>
                                    </select>
                                    <button type='submit' name='update_status' class='update-btn'>Save</button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>