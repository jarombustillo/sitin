<?php
session_start();
include("connect.php");

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle resource submission
if (isset($_POST['submit'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $resource_type = mysqli_real_escape_string($conn, $_POST['resource_type']);
    $link = !empty($_POST['link']) ? mysqli_real_escape_string($conn, $_POST['link']) : '';
    
    // File upload handling
    $file_path = '';
    $file_name = '';
    $file_type = '';
    
    if (!empty($_FILES['resource_file']['name'])) {
        $file_name = $_FILES['resource_file']['name'];
        $file_type = pathinfo($file_name, PATHINFO_EXTENSION);
        $temp_name = $_FILES['resource_file']['tmp_name'];
        
        // Generate unique filename
        $unique_file_name = uniqid() . '_' . $file_name;
        $upload_dir = 'uploads/resources/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_path = $upload_dir . $unique_file_name;
        
        if (move_uploaded_file($temp_name, $file_path)) {
            $file_path = $unique_file_name;
        } else {
            $error_message = "Error uploading file.";
        }
    }

    // Insert the resource
    $query = "INSERT INTO lab_resources (TITLE, DESCRIPTION, CATEGORY, RESOURCE_TYPE, LINK, FILE_PATH, FILE_NAME, FILE_TYPE, UPLOAD_DATE) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssss", $title, $description, $category, $resource_type, $link, $file_path, $file_name, $file_type);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Resource added successfully!";
        } else {
            $error_message = "Error adding resource: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Error preparing statement: " . mysqli_error($conn);
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Get file path before deleting
    $query = "SELECT FILE_PATH FROM lab_resources WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    // Delete the resource
    $query = "DELETE FROM lab_resources WHERE ID = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Delete the file if it exists
        if (!empty($row['FILE_PATH'])) {
            $file_path = 'uploads/resources/' . $row['FILE_PATH'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $success_message = "Resource deleted successfully!";
    } else {
        $error_message = "Error deleting resource: " . mysqli_error($conn);
    }
}

// Fetch existing resources
$query = "SELECT * FROM lab_resources ORDER BY UPLOAD_DATE DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Resources Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html, body {
            background: linear-gradient(135deg, #14569b, #2a3f5f);
            min-height: 100vh;
            width: 100%;
        }

        /* Navbar Styles */
        .navbar {
            background-color: #212529 !important;
            padding: 0.5rem 1rem;
        }

        .navbar-brand {
            color: white !important;
            font-weight: 500;
        }

        .nav-link {
            color: rgba(255,255,255,.8) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #fff !important;
        }

        .nav-link.active {
            color: #fff !important;
            font-weight: 500;
        }

        .logout-btn {
            background-color: #e74c3c;
            color: white !important;
            padding: 8px 20px !important;
            border-radius: 4px;
            transition: all 0.3s ease;
            margin-left: 20px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #c0392b;
            color: white !important;
            text-decoration: none;
        }

        /* Content Area */
        .content {
            padding: 30px;
            min-height: calc(100vh - 56px);
            background: #f0f2f5;
        }

        .parent {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            min-height: calc(100vh - 116px);
        }

        h1 {
            color: #14569b;
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
            margin-top: 20px;
            height: calc(100% - 80px);
        }

        .div1, .div2 {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .div2 {
            overflow-y: auto;
            max-height: 100%;
        }

        .resource-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #14569b;
            font-weight: 500;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            transition: all 0.2s;
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            border-color: #14569b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(20, 86, 155, 0.1);
        }

        .submit-btn {
            background: #14569b;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            width: 100%;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: #0f4578;
            transform: translateY(-1px);
        }

        .resource-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .resource-info {
            flex: 1;
        }

        .resource-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .resource-description {
            color: #64748b;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .resource-meta {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .resource-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: #14569b;
        }

        .delete-btn {
            background: #dc3545;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .resources-list {
            height: 100%;
            overflow-y: auto;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #14569b;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #0f4578;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .content-wrapper {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .div1 {
                width: 100%;
            }
            
            .div2 {
                height: 500px;
            }
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .file-upload {
            position: relative;
            width: 100%;
        }

        .file-input {
            position: absolute;
            width: 0.1px;
            height: 0.1px;
            opacity: 0;
            overflow: hidden;
            z-index: -1;
        }

        .file-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: #f8fafc;
            border: 2px dashed #14569b;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-label:hover {
            background: #f1f5f9;
            border-color: #0f4578;
        }

        .file-label i {
            font-size: 1.2rem;
            color: #14569b;
        }

        .file-info {
            margin-top: 8px;
            font-size: 0.9rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin.php">Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="current_sitin.php">Sit-In</a></li>
                    <li class="nav-item"><a class="nav-link" href="sitinrecords.php">Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_reservations.php">Reservations</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/feedback.php">Feedback</a></li>
                    <li class="nav-item"><a class="nav-link active" href="labresources.php">Lab Resources</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
                <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
            </div>
        </div>
    </nav>

    <div class="content">
        <div class="parent">
            <h1>Lab Resources Management</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="content-wrapper">
                <div class="div1">
                    <div class="resource-form">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" name="title" required>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" required></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category">Category</label>
                                    <select id="category" name="category" required>
                                        <option value="Programming">Programming</option>
                                        <option value="Web Development">Web Development</option>
                                        <option value="Database">Database</option>
                                        <option value="Networking">Networking</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="resource_type">Resource Type</label>
                                    <select id="resource_type" name="resource_type" required>
                                        <option value="Document">Document</option>
                                        <option value="Video">Video</option>
                                        <option value="Tutorial">Tutorial</option>
                                        <option value="Code Sample">Code Sample</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="link">Resource Link (Optional)</label>
                                    <input type="text" id="link" name="link" placeholder="Enter URL (e.g., Google Drive link)">
                                </div>

                                <div class="form-group">
                                    <label for="resource_file">Upload File (Optional)</label>
                                    <div class="file-upload">
                                        <input type="file" id="resource_file" name="resource_file" class="file-input">
                                        <label for="resource_file" class="file-label">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Choose a file</span>
                                        </label>
                                        <div class="file-info"></div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="submit" class="submit-btn">
                                <i class="fas fa-plus"></i> Add Resource
                            </button>
                        </form>
                    </div>
                </div>

                <div class="div2">
                    <div class="resources-list">
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <div class="resource-card">
                                <div class="resource-info">
                                    <div class="resource-title"><?php echo htmlspecialchars($row['TITLE']); ?></div>
                                    <div class="resource-description"><?php echo htmlspecialchars($row['DESCRIPTION']); ?></div>
                                    <div class="resource-meta">
                                        Category: <?php echo htmlspecialchars($row['CATEGORY']); ?> |
                                        Type: <?php echo htmlspecialchars($row['RESOURCE_TYPE']); ?> |
                                        Added: <?php echo date('M d, Y', strtotime($row['UPLOAD_DATE'])); ?>
                                    </div>
                                </div>
                                <div class="resource-actions">
                                    <?php if (!empty($row['LINK'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['LINK']); ?>" target="_blank" class="action-btn edit-btn" title="View Resource">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($row['FILE_PATH'])): ?>
                                        <a href="uploads/resources/<?php echo htmlspecialchars($row['FILE_PATH']); ?>" download class="action-btn edit-btn" title="Download File">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?delete=<?php echo $row['ID']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this resource?')" title="Delete Resource">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload handling
        document.getElementById('resource_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const fileInfo = document.querySelector('.file-info');
            if (fileName) {
                fileInfo.textContent = `Selected file: ${fileName}`;
            } else {
                fileInfo.textContent = '';
            }
        });
    </script>
</body>
</html>
