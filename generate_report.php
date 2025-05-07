<?php
session_start();
require_once 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Get report format from POST request
$format = isset($_POST['report_format']) ? $_POST['report_format'] : 'csv';

// Get all sit-in records
$sql = "SELECT 
    sr.ID,
    sr.IDNO,
    CONCAT(u.Lastname, ', ', u.Firstname, ' ', u.Midname) as student_name,
    u.course,
    sr.LABORATORY,
    sr.TIME_IN,
    sr.TIME_OUT,
    TIMESTAMPDIFF(MINUTE, sr.TIME_IN, sr.TIME_OUT) as duration_minutes,
    f.RATING,
    f.COMMENT
    FROM sitin_records sr
    LEFT JOIN user u ON sr.IDNO = u.IDNO
    LEFT JOIN feedback f ON f.SITIN_RECORD_ID = sr.ID
    ORDER BY sr.TIME_IN DESC";

$result = $conn->query($sql);

// Prepare data for report
$data = array();
$headers = array(
    'ID',
    'Student ID',
    'Student Name',
    'Course',
    'Laboratory',
    'Time In',
    'Time Out',
    'Duration (minutes)',
    'Rating',
    'Comment'
);

while ($row = $result->fetch_assoc()) {
    $data[] = array(
        $row['ID'],
        $row['IDNO'],
        $row['student_name'],
        $row['course'],
        $row['LABORATORY'],
        $row['TIME_IN'],
        $row['TIME_OUT'],
        $row['duration_minutes'],
        $row['RATING'],
        $row['COMMENT']
    );
}

// Generate report based on selected format
switch ($format) {
    case 'csv':
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sit-in_report_' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add headers to CSV
        fputcsv($output, array('Sit-in Report - Generated on ' . date('Y-m-d H:i:s')));
        fputcsv($output, $headers);
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        break;

    case 'excel':
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="sit-in_report_' . date('Y-m-d') . '.xls"');
        
        // Create HTML table for Excel
        echo '<table border="1">';
        echo '<tr><th colspan="' . count($headers) . '">Sit-in Report - Generated on ' . date('Y-m-d H:i:s') . '</th></tr>';
        
        // Add headers
        echo '<tr>';
        foreach ($headers as $header) {
            echo '<th>' . $header . '</th>';
        }
        echo '</tr>';
        
        // Add data rows
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . $cell . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
        break;

    case 'pdf':
        // Set headers for PDF download
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Sit-in Report</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <style>
                @page {
                    size: A4;
                    margin: 2cm;
                }
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    color: #2c3e50;
                }
                .header p {
                    margin: 5px 0;
                    color: #666;
                }
                .report-info {
                    margin-bottom: 20px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                .button-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 1000;
                }
                .print-button, .close-button {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 14px;
                    margin-left: 10px;
                    display: inline-block;
                    text-decoration: none;
                }
                .print-button {
                    background: #007bff;
                    color: white;
                }
                .print-button:hover {
                    background: #0056b3;
                }
                .close-button {
                    background: #6c757d;
                    color: white;
                }
                .close-button:hover {
                    background: #545b62;
                }
                @media print {
                    .button-container {
                        display: none;
                    }
                    body {
                        padding: 0;
                    }
                    table {
                        page-break-inside: auto;
                    }
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                    thead {
                        display: table-header-group;
                    }
                }
            </style>
        </head>
        <body>
            <div class="button-container">
                <button onclick="window.print()" class="print-button">
                    <i class="fas fa-print"></i> Print to PDF
                </button>
                <a href="reports.php" class="close-button">
                    <i class="fas fa-times"></i> Close
                </a>
            </div>
            
            <div class="header">
                <h1>Sit-in Monitoring System</h1>
                <p>Official Report</p>
            </div>
            
            <div class="report-info">
                <p><strong>Report Type:</strong> Sit-in Records</p>
                <p><strong>Generated On:</strong> <?php echo date('F d, Y h:i A'); ?></p>
                <p><strong>Total Records:</strong> <?php echo count($data); ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th><?php echo $header; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?php echo htmlspecialchars($cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="footer">
                <p>This is a computer-generated report. No signature is required.</p>
                <p>© <?php echo date('Y'); ?> Sit-in Monitoring System. All rights reserved.</p>
            </div>

            <script>
                // Ensure print button is clickable
                document.querySelector('.print-button').addEventListener('click', function() {
                    window.print();
                });
            </script>
        </body>
        </html>
        <?php
        break;

    case 'print':
        // Set headers for HTML print view
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Sit-in Report</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <style>
                @page {
                    size: A4;
                    margin: 2cm;
                }
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    color: #2c3e50;
                }
                .header p {
                    margin: 5px 0;
                    color: #666;
                }
                .report-info {
                    margin-bottom: 20px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                .button-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 1000;
                }
                .print-button, .close-button {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 14px;
                    margin-left: 10px;
                    display: inline-block;
                    text-decoration: none;
                }
                .print-button {
                    background: #007bff;
                    color: white;
                }
                .print-button:hover {
                    background: #0056b3;
                }
                .close-button {
                    background: #6c757d;
                    color: white;
                }
                .close-button:hover {
                    background: #545b62;
                }
                @media print {
                    .button-container {
                        display: none;
                    }
                    body {
                        padding: 0;
                    }
                    table {
                        page-break-inside: auto;
                    }
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                    thead {
                        display: table-header-group;
                    }
                }
            </style>
        </head>
        <body>
            <div class="button-container">
                <button onclick="window.print()" class="print-button">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="reports.php" class="close-button">
                    <i class="fas fa-times"></i> Close
                </a>
            </div>
            
            <div class="header">
                <h1>Sit-in Monitoring System</h1>
                <p>Official Report</p>
            </div>
            
            <div class="report-info">
                <p><strong>Report Type:</strong> Sit-in Records</p>
                <p><strong>Generated On:</strong> <?php echo date('F d, Y h:i A'); ?></p>
                <p><strong>Total Records:</strong> <?php echo count($data); ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th><?php echo $header; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?php echo htmlspecialchars($cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="footer">
                <p>This is a computer-generated report. No signature is required.</p>
                <p>© <?php echo date('Y'); ?> Sit-in Monitoring System. All rights reserved.</p>
            </div>

            <script>
                // Ensure print button is clickable
                document.querySelector('.print-button').addEventListener('click', function() {
                    window.print();
                });
            </script>
        </body>
        </html>
        <?php
        break;
}
?> 