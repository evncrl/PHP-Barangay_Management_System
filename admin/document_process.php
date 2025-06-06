<?php
include_once '../includes/adminHeader.php';
include '../includes/config.php';
require '../includes/fpdf/fpdf.php'; // Load FPDF
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../src/Exception.php';
require '../src/PHPMailer.php';
require '../src/SMTP.php';


// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$countQuery = "SELECT 
                SUM(status = 'pending') AS pending_count, 
                SUM(status = 'approved') AS approved_count, 
                SUM(status = 'rejected') AS rejected_count, 
                COUNT(*) AS total_count 
              FROM documents";
$countResult = mysqli_query($conn, $countQuery);
$countRow = mysqli_fetch_assoc($countResult);

$pendingCount = $countRow['pending_count'] ?? 0;
$approvedCount = $countRow['approved_count'] ?? 0;
$rejectedCount = $countRow['rejected_count'] ?? 0;
$totalCount = $countRow['total_count'] ?? 0;

function updateIssueDate($conn, $new_status, $document_id, $pdf_path, $issue_date = null)
{
    // If it's not approved, set the issue_date to NULL
    if (!$issue_date) {
        $issue_date = NULL;
    }

    // Update the document status and issue date
    $update_sql = "UPDATE documents SET status = ?, issue_date = ?, file_path = ? WHERE document_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssi", $new_status, $issue_date, $pdf_path, $document_id);
    $update_stmt->execute();
}

function sendStatusEmail($email, $name, $document_type, $status, $pdf_path = null) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'romerosfragrance@gmail.com';
        $mail->Password = 'rrzy yexb qevi hxmw';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('romerosfragrance@gmail.com', 'Barangay Lower Bicutan');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        
        if ($status === 'approved') {
            $mail->Subject = 'Your Document Has Been Approved - ' . $document_type;
            $mail->Body = "
                <h2>Document Approved: $document_type</h2>
                <p>Dear $name,</p>
                <p>We are pleased to inform you that your <strong>$document_type</strong> request has been <strong>approved</strong>.</p>
                <p>You may now download your document from our system or visit our office to claim a printed copy.</p>
                <p>Thank you for using our services.</p>
                <p><strong>Barangay Lower Bicutan</strong></p>
            ";
            
            if ($pdf_path && file_exists($pdf_path)) {
                $mail->addAttachment($pdf_path, "$document_type.pdf");
            }
        } else {
            $mail->Subject = 'Your Document Request Has Been Rejected - ' . $document_type;
            $mail->Body = "
                <h2>Document Rejected: $document_type</h2>
                <p>Dear $name,</p>
                <p>We regret to inform you that your <strong>$document_type</strong> request has been <strong>rejected</strong>.</p>
                <p>Please visit our office for more information or to submit a new request.</p>
                <p>Thank you for your understanding.</p>
                <p><strong>Barangay Lower Bicutan</strong></p>
            ";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: ".$e->getMessage());
        return false;
    }
}


// Handle status update
if (isset($_POST['update_status'])) {
    $document_id = $_POST['document_id'];
    $new_status = $_POST['status'];
    $issue_date = ($new_status === 'approved') ? date("Y-m-d") : NULL;

    // Fetch user and document details
    $sql = "SELECT u.email, u.address, d.document_type, d.purpose, d.user_id
            FROM documents d
            JOIN users u ON d.user_id = u.user_id
            WHERE d.document_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];

        // Fetch business details (getting business_id as well)
        $business_sql = "SELECT business_id, business_name, business_owner, business_address 
        FROM business_list WHERE user_id = ? 
        ORDER BY business_id DESC LIMIT 1";  // ✅ Ensure latest business is selected

        $business_stmt = $conn->prepare($business_sql);
        $business_stmt->bind_param("i", $user_id);
        $business_stmt->execute();
        $business_result = $business_stmt->get_result();

        if ($business_result->num_rows > 0) {
            $business_row = $business_result->fetch_assoc();
            $business_id = $business_row['business_id'];
            $business_name = $business_row['business_name'];
            $business_owner = $business_row['business_owner'];
            $business_address = $business_row['business_address'];
        } else {
            $business_id = NULL;
            $business_owner = "Unknown";
            $business_address = "Unknown";
        }

        // Generate PDF if approved and type is 'Barangay Business Clearance'
        if ($new_status === 'approved' && $row['document_type'] === 'Barangay Business Clearance') {

            $resident_sql = "SELECT r.fname, r.lname, r.age, r.address 
                             FROM residents r
                             JOIN users u ON r.user_id = u.user_id
                             WHERE u.user_id = ?";
            $resident_stmt = $conn->prepare($resident_sql);
            $resident_stmt->bind_param("i", $user_id);
            $resident_stmt->execute();
            $resident_result = $resident_stmt->get_result();
            $resident_row = $resident_result->fetch_assoc();

            $pdf_path = "../documents/business_clearance_$document_id.pdf";

            // Generate PDF
            $pdf = new FPDF();
            $pdf->AddPage();

            // Header images
            $pdf->Image('../includes/style/TAGUIG.png', 10, 10, 30);
            $pdf->Image('../includes/style/lowerbicutanlogo.png', 170, 10, 30);

            // Header text
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');
            $pdf->Cell(0, 5, 'National Capital Region', 0, 1, 'C');
            $pdf->Cell(0, 5, 'City of Taguig', 0, 1, 'C');

            $pdf->SetFont('Arial', 'B', 13);
            $pdf->Cell(0, 10, 'Barangay Lower Bicutan', 0, 1, 'C');

            // Space before document title
            $pdf->Ln(10);

            $pdf->SetFont('Arial', 'B', 24);
            $pdf->Cell(0, 10, 'Barangay Business Clearance', 0, 1, 'C');
            $pdf->Ln(10);

            // Document content
            $pdf->SetFont('Arial', '', 12);

            // Add salutation
            $pdf->Cell(10);
            $pdf->Write(10, "TO WHOM IT MAY CONCERN:\n\n");

            // First paragraph
            $pdf->Cell(10);
            $pdf->Write(10, "This is to certify that ");

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Write(10, $business_name);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, ", owned and operated by ");

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Write(10, $business_owner);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, ", of legal age, and a Filipino citizen, is legally conducting business at ");

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Write(10, $business_address);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, ", Barangay Lower Bicutan, Taguig City, Metro Manila.");
            $pdf->Ln(10);

            // Second paragraph (Business verification)
            $pdf->Ln(6);
            $pdf->MultiCell(0, 10, "Based on the records of this Barangay and verification conducted, the said business is duly recognized and compliant with existing barangay regulations. Furthermore, there are no objections or complaints from the community regarding its operation.");
            $pdf->Ln(10);

            $pdf->Write(10, "This Barangay Business Clearance is issued upon the request of ");

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Write(10, $resident_row['fname'] . " " . $resident_row['lname']);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, " for the purpose of ");

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Write(10, htmlspecialchars($row['purpose']));
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, ".");

            $pdf->Write(10, " Issued this ");

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Write(10, date("jS")); // Bold only the day number
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, " day of ");

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Write(10, date("F Y")); // Bold only the month and year
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, " at Barangay Lower Bicutan, Taguig City, Metro Manila.");
            $pdf->Ln(20);

            // Signature of Barangay Captain
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Roel O. Pacayra', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'Barangay Captain', 0, 1, 'C');

            // Save the PDF
            $pdf->Output('F', $pdf_path);


            // Update the database with the new status and PDF path
            updateIssueDate($conn, $new_status, $document_id, $pdf_path, $issue_date);
           
            $resident_name = $resident_row['fname'] . " " . $resident_row['lname'];
            sendStatusEmail($row['email'], $resident_name, $row['document_type'], 'approved', $pdf_path);

            // ✅ **Update business_list with document_id**
            if (!is_null($business_id)) {
                $update_business_sql = "UPDATE business_list SET document_id = ? WHERE business_id = ?";
                $update_stmt = $conn->prepare($update_business_sql);
                $update_stmt->bind_param("ii", $document_id, $business_id);

                if ($update_stmt->execute()) {
                    error_log("✅ document_id successfully updated for business_id $business_id");
                } else {
                    error_log("❌ Failed to update document_id: " . $update_stmt->error);
                }

                $update_stmt->close();
            } else {
                error_log("⚠️ No business found for user_id $user_id, skipping document_id update.");
            }
        } else {
            // If rejected, update status without generating a PDF
            $sql = "UPDATE documents SET status = ?, issue_date = NULL WHERE document_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $new_status, $document_id);
            $stmt->execute();
        }

        // ----- CLEARANCE -----
        // If the document type is Barangay Clearance, process it similarly
        if ($new_status === 'approved' && $row['document_type'] === 'Barangay Clearance') {
            // Get resident data based on user_id
            $resident_sql = "SELECT r.fname, r.lname, r.age, r.address 
                             FROM residents r
                             JOIN users u ON r.user_id = u.user_id
                             WHERE u.user_id = ?";
            $resident_stmt = $conn->prepare($resident_sql);

            // Ensure that $row['user_id'] contains a valid value
            if (isset($row['user_id']) && is_numeric($row['user_id'])) {
                $resident_stmt->bind_param("i", $row['user_id']);
                $resident_stmt->execute();
                $resident_result = $resident_stmt->get_result();

                // Check if any data was returned
                if ($resident_result->num_rows > 0) {
                    // Fetch the resident details
                    $resident_row = $resident_result->fetch_assoc();

                    // Prepare the path for the Barangay Clearance PDF
                    $pdf_path = "../documents/barangay_clearance_$document_id.pdf";

                    // Generate the PDF
                    $pdf = new FPDF();
                    $pdf->AddPage();

                    //header img
                    $pdf->Image('../includes/style/TAGUIG.png', 10, 10, 30);
                    $pdf->Image('../includes/style/lowerbicutanlogo.png', 170, 10, 30);

                    //font header text
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');
                    $pdf->Cell(0, 5, 'National Capital Region', 0, 1, 'C');
                    $pdf->Cell(0, 5, 'City of Taguig', 0, 1, 'C');

                    $pdf->SetFont('Arial', 'B', 13);
                    $pdf->Cell(0, 10, 'Barangay Lower Bicutan', 0, 1, 'C');

                    //space before document title
                    $pdf->Ln(10);

                    $pdf->SetFont('Arial', 'B', 16);
                    $pdf->Cell(0, 10, 'Barangay Clearance', 0, 1, 'C');
                    $pdf->Ln(10);
                    $pdf->SetFont('Arial', '', 12);

                    // Writing the Barangay Clearance content using fetched resident data
                    $pdf->Write(10, "TO WHOM IT MAY CONCERN:\n\n");

                    $pdf->Cell(10);
                    $pdf->Write(10, "This is to certify that ");
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, $resident_row['fname'] . " " . $resident_row['lname']);
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, ", of legal age, ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, $resident_row['age'] . " years old");
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, ", and a Filipino citizen, is a bona fide resident of ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, htmlspecialchars($resident_row['address']));
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, ", Taguig City.");
                    $pdf->Ln(10);

                    $pdf->Ln(6);

                    // Use MultiCell for paragraph text (since it wraps properly)
                    $pdf->MultiCell(0, 10, "As per records of this Barangay and based on verification conducted, [he/she] has no derogatory records and has been recognized as a law-abiding citizen of this community.");
                    $pdf->Ln(10);

                    $pdf->Write(10, "This Barangay Clearance is issued upon the request of ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, $resident_row['fname'] . " " . $resident_row['lname']);
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " for the purpose of ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, htmlspecialchars($row['purpose']));
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, ".");
                    $pdf->Write(10, " Issued this ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, date("jS")); // Bold only the day number
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " " . date("F") . " "); // Normal font for the month
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, date("Y")); // Bold only the year
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " at Barangay Lower Bicutan, Taguig City, Metro Manila.");
                    $pdf->Ln(20);

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 10, 'Roel O. Pacayra', 0, 1, 'C');
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Cell(0, 10, 'Barangay Captain', 0, 1, 'C');

                    $pdf->Output('F', $pdf_path); // Save the PDF
                    $issue_date = date("Y-m-d H:i:s");
                    updateIssueDate($conn, $new_status, $document_id, $pdf_path, $issue_date);
                   
                   $resident_name = $resident_row['fname'] . " " . $resident_row['lname'];
sendStatusEmail($row['email'], $resident_name, $row['document_type'], 'approved', $pdf_path);
                } else {
                    echo "<script>alert('No resident data found for the provided user ID.'); window.location.href='document_process.php';</script>";
                }
            } else {
                echo "<script>alert('Invalid user ID.'); window.location.href='document_process.php';</script>";
            }
        }

        // ----- JOB SEEKER -----
        if ($new_status === 'approved' && $row['document_type'] === 'Clearance for First-time Job Seeker') {
            // Get resident data based on user_id
            $resident_sql = "SELECT r.fname, r.lname, r.age, r.address 
                             FROM residents r
                             JOIN users u ON r.user_id = u.user_id
                             WHERE u.user_id = ?";
            $resident_stmt = $conn->prepare($resident_sql);
        
            if (isset($row['user_id']) && is_numeric($row['user_id'])) {
                $resident_stmt->bind_param("i", $row['user_id']);
                $resident_stmt->execute();
                $resident_result = $resident_stmt->get_result();
        
                if ($resident_result->num_rows > 0) {
                    $resident_row = $resident_result->fetch_assoc();
                    $pdf_path = "../documents/barangay_clearance_$document_id.pdf";
        
                    // Generate PDF
                    $pdf = new FPDF();
                    $pdf->AddPage();
        
                    //header img
                    $pdf->Image('../includes/style/TAGUIG.png', 10, 10, 30);
                    $pdf->Image('../includes/style/lowerbicutanlogo.png', 170, 10, 30);
        
                    //font header text
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');
                    $pdf->Cell(0, 5, 'National Capital Region', 0, 1, 'C');
                    $pdf->Cell(0, 5, 'City of Taguig', 0, 1, 'C');
        
                    $pdf->SetFont('Arial', 'B', 13);
                    $pdf->Cell(0, 10, 'Barangay Lower Bicutan', 0, 1, 'C');
        
                    //space before document title
                    $pdf->Ln(10);
        
                    $pdf->SetFont('Arial', 'B', 16);
                    $pdf->Cell(0, 10, 'Barangay Clearance for First-time Job Seeker', 0, 1, 'C');
                    $pdf->Ln(10);
        
                    $pdf->SetFont('Arial', '', 12);
                    
                    // Add salutation
                    $pdf->Write(10, "TO WHOM IT MAY CONCERN:\n\n");
        
                    // First paragraph (Indented)
                    $pdf->Cell(10); // Adds indentation
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, "This is to certify that ");
        
                    $pdf->SetFont('Arial', 'B', 12); // Bold for name
                    $pdf->Write(10, $resident_row['fname'] . " " . $resident_row['lname']);
        
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, ", of legal age, ");
        
                    $pdf->SetFont('Arial', 'B', 12); // Bold for age
                    $pdf->Write(10, $resident_row['age']);
        
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, ", and a Filipino citizen, is a bona fide resident of ");
        
                    $pdf->SetFont('Arial', 'B', 12); // Bold for address
                    $pdf->Write(10, htmlspecialchars($resident_row['address']));
        
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " Lower Bicutan, Taguig City.");
                    $pdf->Ln(10);
        
                    $pdf->Ln(6);
        
                    // Other paragraphs (No indentation)
                    $pdf->MultiCell(0, 10, "As per records of this Barangay and based on verification conducted, [he/she] is of good moral character, has no derogatory record, and is a law-abiding citizen of this community.", 0, 'J');
        
                    $pdf->MultiCell(0, 10, "Furthermore, this certifies that [he/she] is a first-time job seeker, applying for employment for the first time.", 0, 'J');
                    $pdf->Ln(10);
        
                    // Bold for requestor's name in final paragraph
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, "This Barangay Clearance is issued upon the request of ");
        
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, $resident_row['fname'] . " " . $resident_row['lname']);
        
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " in accordance with Republic Act No. 11261, or the First Time Jobseekers Assistance Act, which grants waivers on government fees and clearances for first-time job seekers.");
                    $pdf->Ln(10);
        
                    $pdf->Ln(6);
        
                    // Issuance date with bold formatting
                    $pdf->Write(10, "Issued this ");
        
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, date("jS")); // Bold only the day number
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " day of ");
        
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, date("F Y")); // Bold only the month and year
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " at Barangay Lower Bicutan, Taguig City, Metro Manila.");
                    $pdf->Ln(20);
        
                    // Signature of Barangay Captain
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 10, 'Roel O. Pacayra', 0, 1, 'C');
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Cell(0, 10, 'Barangay Captain', 0, 1, 'C');
        
                    // Add blank pages for Barangay Clearance and Oath of Undertaking
                    $pdf->AddPage(); // Blank page for Barangay Clearance
                     //header img
                     $pdf->Image('../includes/style/TAGUIG.png', 10, 10, 30);
                     $pdf->Image('../includes/style/lowerbicutanlogo.png', 170, 10, 30);
         
                     //font header text
                     $pdf->SetFont('Arial', '', 12);
                     $pdf->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');
                     $pdf->Cell(0, 5, 'National Capital Region', 0, 1, 'C');
                     $pdf->Cell(0, 5, 'City of Taguig', 0, 1, 'C');
         
                     $pdf->SetFont('Arial', 'B', 13);
                     $pdf->Cell(0, 10, 'Barangay Lower Bicutan', 0, 1, 'C');

                     //space before document title
                    $pdf->Ln(10);

                    $pdf->SetFont('Arial', 'B', 16);
                    $pdf->Cell(0, 10, 'Barangay Clearance', 0, 1, 'C');
                    $pdf->Ln(10);
                    $pdf->SetFont('Arial', '', 12);

                    // Writing the Barangay Clearance content using fetched resident data
                    $pdf->Write(10, "TO WHOM IT MAY CONCERN:\n\n");

                    $pdf->Cell(10);
                    $pdf->Write(10, "This is to certify that ");
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, $resident_row['fname'] . " " . $resident_row['lname']);
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, ", of legal age, ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, $resident_row['age'] . " years old");
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, ", and a Filipino citizen, is a bona fide resident of ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, htmlspecialchars($resident_row['address']));
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, ", Taguig City.");
                    $pdf->Ln(10);

                    $pdf->Ln(6);

                    // Use MultiCell for paragraph text (since it wraps properly)
                    $pdf->MultiCell(0, 10, "As per records of this Barangay and based on verification conducted, [he/she] has no derogatory records and has been recognized as a law-abiding citizen of this community.");
                    $pdf->Ln(10);

                    $pdf->Write(10, "This Barangay Clearance is issued upon the request of ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, $resident_row['fname'] . " " . $resident_row['lname']);
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " for the purpose of ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, htmlspecialchars($row['purpose']));
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, ".");
                    $pdf->Write(10, " Issued this ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, date("jS")); // Bold only the day number
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " " . date("F") . " "); // Normal font for the month
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, date("Y")); // Bold only the year
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " at Barangay Lower Bicutan, Taguig City, Metro Manila.");
                    $pdf->Ln(20);

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 10, 'Roel O. Pacayra', 0, 1, 'C');
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Cell(0, 10, 'Barangay Captain', 0, 1, 'C');


                    $pdf->AddPage(); // Blank page for Oath of Undertaking
                     //header img
                     $pdf->Image('../includes/style/TAGUIG.png', 10, 10, 30);
                     $pdf->Image('../includes/style/lowerbicutanlogo.png', 170, 10, 30);
         
                     //font header text
                     $pdf->SetFont('Arial', '', 12);
                     $pdf->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');
                     $pdf->Cell(0, 5, 'National Capital Region', 0, 1, 'C');
                     $pdf->Cell(0, 5, 'City of Taguig', 0, 1, 'C');
         
                     $pdf->SetFont('Arial', 'B', 13);
                     $pdf->Cell(0, 10, 'Barangay Lower Bicutan', 0, 1, 'C');

                     //space before document title
                    $pdf->Ln(6);

                    $pdf->SetFont('Arial', 'B', 15);
                    $pdf->Cell(0, 10, 'Oath of Undertaking', 0, 1, 'C');
                    $pdf->Ln(6);
                    $pdf->Cell(10);
$pdf->SetFont('Arial', '', 10);
$pdf->Write(5, "I, ");
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(5, $resident_row['fname'] . " " . $resident_row['lname']);
$pdf->SetFont('Arial', '', 10);
$pdf->Write(5, ", ");
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(5, $resident_row['age']);
$pdf->SetFont('Arial', '', 10);
$pdf->Write(5, " years of age, a resident of ");
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(5, htmlspecialchars($resident_row['address']));
$pdf->SetFont('Arial', '', 10);
$pdf->Write(5, ", Lower Bicutan, Taguig City, availing the benefits of Republic Act 11261, otherwise known as the First Time Jobseekers Act of 2019, do hereby declare, agree, and undertake to abide by the following:");

                    $pdf->Ln(10);
                    
                    
                    // Oath Points
                    $pdf->SetFont('Arial', '', 10);
                    $oath_points = [
                        "That this is the first time that I will actively look for a job, and therefore request that a Barangay Certification be issued in my favor to avail the benefits of the law;",
                        "That I am aware that the benefit and privileges under the said law shall be valid only for one (1) year from the date that the Barangay Certification is issued;",
                        "That I can avail the benefits of the law only once;",
                        "That I understand my personal information shall be included in the Roster/List of First Time Jobseekers and will not be used unlawful purpose;",
                        "That I will inform and/or report to the Barangay Personally, through text or other means, or through my family/relatives once I get employed;",
                        "That I am not a beneficiary of the JobStart Program under R.A. No. 10869 and other laws that give similar exemptions for the documents of transactions exempted under R.A No. 11261;",
                        "That if issued requested Certification, I will not use the same in any fraud, neither falsify nor help and/or assist in the fabrication of the said certification;",
                        "That this undertaking is solely for the purpose of obtaining a Barangay Certification consistent with the objective of R.A. No. 11261 and not for any other purpose;",
                        "That I consent to the use of my personal information pursuant to the Data Privacy Act and other applicable laws, rules, and regulations."
                    ];
                    // Set a slightly smaller font size
$pdf->SetFont('Arial', '', 10); 

// Adjust MultiCell() for better text wrapping
foreach ($oath_points as $index => $point) {
    $pdf->MultiCell(0, 5, ($index + 1) . ". " . $point, 0, 'J');
    $pdf->Ln(3);
}

// Reduce spacing before the signature section
$pdf->Ln(5);
$pdf->Cell(0, 7, "Signed this ");
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(7, date("jS F Y"));
$pdf->SetFont('Arial', '', 10);
$pdf->Write(7, " at Barangay Hall, Lower Bicutan.", 0, 1, 'C');

// Reduce spacing before the signature name
$pdf->Ln(5);
$pdf->Cell(0, 7, "Signed by: ");
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(7, $resident_row['fname'] . " " . $resident_row['lname']);
$pdf->SetFont('Arial', '', 10);

// Reduce spacing before the witness name
$pdf->Ln(5);
$pdf->Cell(0, 7, "Witnessed by: KAGAWAD NORMAN D. STA. ANA", 0, 1, 'C');
                    // Save the PDF
                    $pdf->Output('F', $pdf_path);
                    $issue_date = date("Y-m-d H:i:s");
                    updateIssueDate($conn, $new_status, $document_id, $pdf_path, $issue_date);
                    sendStatusEmail($row['email'], $resident_row['fname']." ".$resident_row['lname'], $row['document_type'], 'approved', $pdf_path);
                } else {
                    echo "<script>alert('No resident data found for the provided user ID.'); window.location.href='document_process.php';</script>";
                }
            } else {
                echo "<script>alert('Invalid user ID.'); window.location.href='document_process.php';</script>";
            }
        }
        //----- RECIDENCY -------
        if ($new_status === 'approved' && $row['document_type'] === 'Certificate of Residency') {
            // Get resident data based on user_id
            $resident_sql = "SELECT r.fname, r.lname, r.age, r.address, r.years_of_residency, d.purpose
                         FROM residents r
                         JOIN users u ON r.user_id = u.user_id
                         JOIN documents d ON u.user_id = d.user_id
                         WHERE u.user_id = ? AND d.document_id = ?";
            $resident_stmt = $conn->prepare($resident_sql);

            if (isset($row['user_id']) && is_numeric($row['user_id'])) {
                $resident_stmt->bind_param("ii", $row['user_id'], $document_id);
                $resident_stmt->execute();
                $resident_result = $resident_stmt->get_result();

                if ($resident_result->num_rows > 0) {
                    $resident_row = $resident_result->fetch_assoc();
                    $pdf_path = "../documents/residency_certificate_$document_id.pdf";

                    // Generate PDF
                    $pdf = new FPDF();
                    $pdf->AddPage();

                    //header img
                    $pdf->Image('../includes/style/TAGUIG.png', 10, 10, 30);
                    $pdf->Image('../includes/style/lowerbicutanlogo.png', 170, 10, 30);

                    //font header text
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');
                    $pdf->Cell(0, 5, 'National Capital Region', 0, 1, 'C');
                    $pdf->Cell(0, 5, 'City of Taguig', 0, 1, 'C');

                    $pdf->SetFont('Arial', 'B', 13);
                    $pdf->Cell(0, 10, 'Barangay Lower Bicutan', 0, 1, 'C');

                    //space before document title
                    $pdf->Ln(10);


                    $pdf->SetFont('Arial', 'B', 16);
                    $pdf->Cell(0, 10, 'Certificate of Residency', 0, 1, 'C');
                    $pdf->Ln(10);

                    $pdf->SetFont('Arial', '', 12);

                    // First paragraph (Indented)
                    $pdf->MultiCell(0, 10, "TO WHOM IT MAY CONCERN:\n\n");

                    $pdf->Cell(10); // Adds indentation
                    $pdf->Write(10, "This is to certify that ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, $resident_row['lname'] . ", " . $resident_row['fname']);
                    $pdf->SetFont('Arial', '', 12);

                    $pdf->Write(10, ", of legal age, ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, $resident_row['age']);
                    $pdf->SetFont('Arial', '', 12);

                    $pdf->Write(10, ", and a Filipino citizen, is a bona fide resident of ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, htmlspecialchars($resident_row['address']));
                    $pdf->SetFont('Arial', '', 12);

                    $pdf->Write(10, ", Lower Bicutan, Taguig City, Metro Manila.");
                    $pdf->Ln(10);

                    $pdf->Ln(6);
                    // Second paragraph (No forced line breaks, justified)
                    $pdf->Write(10, "Based on the records of this barangay, the above-named person has been residing in the said address for ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, htmlspecialchars($resident_row['years_of_residency']));
                    $pdf->SetFont('Arial', '', 12);

                    $pdf->Write(10, " years and is known to be a law-abiding citizen of this community.");
                    $pdf->Ln(10);

                    $pdf->Ln(6);
                    // Third paragraph (No forced line breaks, justified)
                    $pdf->Write(10, "This certification is issued upon the request of ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, $resident_row['lname'] . ", " . $resident_row['fname']);
                    $pdf->SetFont('Arial', '', 12);

                    $pdf->Write(10, " for ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, htmlspecialchars($resident_row['purpose']));
                    $pdf->SetFont('Arial', '', 12);

                    $pdf->Write(10, ".");
                    $pdf->Ln(10);

                    $pdf->Ln(6);
                    // Issuance date
                    $pdf->Write(10, "Issued this ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, date("jS")); // Bold only the day number
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " day of ");

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Write(10, date("F Y")); // Bold only the month and year
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Write(10, " at Barangay Lower Bicutan, Taguig City, Metro Manila.");
                    $pdf->Ln(20);

                    // Signature of Barangay Captain
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 10, 'Roel O. Pacayra', 0, 1, 'C');
                    $pdf->SetFont('Arial', '', 12);
                    $pdf->Cell(0, 10, 'Barangay Captain', 0, 1, 'C');

                    // Save the PDF
                    $pdf->Output('F', $pdf_path);
                    // Update database with PDF file path
                    $issue_date = date("Y-m-d H:i:s");
                    updateIssueDate($conn, $new_status, $document_id, $pdf_path, $issue_date);
                    sendStatusEmail($row['email'], $resident_row['fname']." ".$resident_row['lname'], $row['document_type'], 'approved', $pdf_path);
                } else {
                    echo "<script>alert('No resident data found for the provided user ID.'); window.location.href='document_process.php';</script>";
                }
            } else {
                echo "<script>alert('Invalid user ID.'); window.location.href='document_process.php';</script>";
            }
        }

//----- CERTIFICATE OF INDIGENCY -----
if ($new_status === 'approved' && $row['document_type'] === 'Certificate of Indigency') {
    // Enable detailed error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    error_log("INDIGENCY: Processing document_id: $document_id");

    try {
        // 1. Verify documents directory exists and is writable
        $documents_dir = "../documents/";
        if (!file_exists($documents_dir)) {
            if (!mkdir($documents_dir, 0755, true)) {
                throw new Exception("Failed to create documents directory. Check permissions.");
            }
            error_log("INDIGENCY: Created documents directory");
        }
        
        if (!is_writable($documents_dir)) {
            throw new Exception("Documents directory is not writable. Please check permissions.");
        }

        // 2. Get resident data with proper joins
        $resident_sql = "SELECT r.fname, r.lname, r.age, r.address, d.purpose, d.status
                        FROM residents r
                        JOIN users u ON r.user_id = u.user_id
                        JOIN documents d ON u.user_id = d.user_id
                        WHERE d.document_id = ?";
        
        $resident_stmt = $conn->prepare($resident_sql);
        if (!$resident_stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }

        $resident_stmt->bind_param("i", $document_id);
        
        if (!$resident_stmt->execute()) {
            throw new Exception("Database execute failed: " . $resident_stmt->error);
        }

        $resident_result = $resident_stmt->get_result();
        
        if ($resident_result->num_rows === 0) {
            throw new Exception("No resident data found for document_id: $document_id");
        }

        $resident_row = $resident_result->fetch_assoc();
        
        if ($resident_row['status'] !== 'approved') {
            throw new Exception("Document status must be 'approved' (current: " . $resident_row['status'] . ")");
        }

        // 3. Prepare PDF file paths
        $pdf_filename = "indigency_$document_id.pdf";
        $pdf_path = $documents_dir . $pdf_filename;
        $pdf_url = "documents/$pdf_filename"; // Web-accessible path
        
        error_log("INDIGENCY: PDF path: " . realpath($pdf_path));

        // 4. Generate PDF content
        $pdf = new FPDF();
        $pdf->AddPage();

        // Header images
        $pdf->Image('../includes/style/TAGUIG.png', 10, 10, 30);
        $pdf->Image('../includes/style/lowerbicutanlogo.png', 170, 10, 30);

        // Header text
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');
        $pdf->Cell(0, 5, 'National Capital Region', 0, 1, 'C');
        $pdf->Cell(0, 5, 'City of Taguig', 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 10, 'Barangay Lower Bicutan', 0, 1, 'C');
        $pdf->Ln(10);

        // Document title
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Certificate of Indigency', 0, 1, 'C');
        $pdf->Ln(10);

        // Document content
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(0, 10, "TO WHOM IT MAY CONCERN:\n\n");

        $pdf->Cell(10);
        $content = "This is to certify that ";
        $content .= $resident_row['lname'] . ", " . $resident_row['fname'];
        $content .= ", of legal age, " . $resident_row['age'];
        $content .= ", and a Filipino citizen, is a bona fide resident of ";
        $content .= htmlspecialchars($resident_row['address']) . ", Lower Bicutan, Taguig City.";
        $pdf->Write(10, $content);
        $pdf->Ln(10);

        $pdf->MultiCell(0, 10, "Based on the records of this barangay and as per verification conducted by the Barangay Office, the above-named person belongs to an indigent family, having no sufficient or regular source of income to support his/her daily needs.");
        $pdf->Ln(10);

        $pdf->Write(10, "This certification is issued upon the request of " . $resident_row['fname'] . " for ");
        $pdf->Write(10, htmlspecialchars($resident_row['purpose']) . ".");
        $pdf->Ln(10);

        $pdf->Write(10, "Issued this ");
        $pdf->Write(10, date("jS") . " day of " . date("F Y"));
        $pdf->Write(10, " at Barangay Lower Bicutan, Taguig City.");
        $pdf->Ln(20);

        // Signature
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Roel O. Pacayra', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Barangay Captain', 0, 1, 'C');

        // 5. Save PDF to server
        $pdf->Output('F', $pdf_path);
        sendStatusEmail($row['email'], $resident_row['fname']." ".$resident_row['lname'], $row['document_type'], 'approved', $pdf_path);
        
        if (!file_exists($pdf_path)) {
            throw new Exception("PDF file creation failed at: " . $pdf_path);
        }
        error_log("INDIGENCY: PDF successfully created");

        // 6. Update database with both file paths
        $update_sql = "UPDATE documents SET 
                      status = ?, 
                      issue_date = ?, 
                      file_path = ?,
                      pdf_file = ?
                      WHERE document_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            throw new Exception("Prepare update failed: " . $conn->error);
        }
        
        $issue_date = date("Y-m-d H:i:s");
        $update_stmt->bind_param("ssssi", 
            $new_status, 
            $issue_date, 
            $pdf_path,
            $pdf_url,
            $document_id
        );
        
        if (!$update_stmt->execute()) {
            throw new Exception("Update failed: " . $update_stmt->error);
        }
        error_log("INDIGENCY: Database updated successfully");

        // 7. Provide user feedback and PDF access
        echo "<script>
            // First try to open in new tab
            var pdfWindow = window.open('$pdf_url', '_blank');
            
            // If popup is blocked or fails, offer download
            setTimeout(function() {
                if (!pdfWindow || pdfWindow.closed || typeof pdfWindow.closed == 'undefined') {
                    if (confirm('Certificate generated! Would you like to download it now?')) {
                        // Create a temporary link and trigger download
                        var link = document.createElement('a');
                        link.href = '$pdf_url';
                        link.download = '$pdf_filename';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                }
                // Redirect after download attempt
                window.location.href = 'document_process.php';
            }, 500);
        </script>";
        exit;

    } catch (Exception $e) {
        error_log("INDIGENCY ERROR: " . $e->getMessage());
        echo "<script>
            alert('Error generating certificate: " . addslashes($e->getMessage()) . "');
            window.location.href = 'document_process.php';
        </script>";
        exit;
    


    } catch (Exception $e) {
        error_log("INDIGENCY ERROR: " . $e->getMessage());
        echo "<script>
            alert('Error generating Certificate of Indigency:\\n" . addslashes($e->getMessage()) . "');
            window.location.href = 'document_process.php';
        </script>";
        exit;
    }
}
    
}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
        }
        
        body {
            background-color: var(--light-bg);
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-header {
            background-color: var(--secondary-color);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 5px solid var(--primary-color);
        }
        
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            padding: 1rem;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 600;
            border: none;
        }
        
        .status-approved {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success-color);
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .status-pending {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .status-rejected {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .action-form {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 5px;
        }
        
        .btn-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .btn-link:hover {
            text-decoration: underline;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .search-filters {
            margin-bottom: 1rem;
        }
        
        @media (max-width: 992px) {
            .responsive-table {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

    <!-- Main Content -->
<main class="container">
    <!-- Dashboard Stats -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-3x mb-3 text-warning"></i>
                    <h5 class="card-title">Pending</h5>
                    <h3 class="card-text"><?= $pendingCount ?></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                    <h5 class="card-title">Approved</h5>
                    <h3 class="card-text"><?= $approvedCount ?></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-times-circle fa-3x mb-3 text-danger"></i>
                    <h5 class="card-title">Rejected</h5>
                    <h3 class="card-text"><?= $rejectedCount ?></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-file-alt fa-3x mb-3 text-primary"></i>
                    <h5 class="card-title">Total</h5>
                    <h3 class="card-text"><?= $totalCount ?></h3>
                </div>
            </div>
        </div>
    </div>
</main>
        
        <!-- Document Requests Table -->
        <div class="card">
        <div>
    <a href="export_options.php?action=export" class="btn btn-outline-light btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Export to Excel">
        <i class="fas fa-file-excel"></i> Export
    </a>
    <a href="export_options.php?action=print" class="btn btn-outline-light btn-sm ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
        <i class="fas fa-print"></i> Print
    </a>
</div>
            <div class="card-body p-0">
                <div class="responsive-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag me-1"></i> ID</th>
                                <th><i class="fas fa-envelope me-1"></i> Email</th>
                                <th><i class="fas fa-map-marker-alt me-1"></i> Address</th>
                                <th><i class="fas fa-file-alt me-1"></i> Document Type</th>
                                <th><i class="fas fa-info-circle me-1"></i> Purpose</th>
                                <th><i class="fas fa-calendar-alt me-1"></i> Request Date</th>
                                <th><i class="fas fa-calendar-check me-1"></i> Issue Date</th>
                                <th><i class="fas fa-receipt me-1"></i> Receipt</th>
                                <th><i class="fas fa-tasks me-1"></i> Action</th>
                                <th><i class="fas fa-download me-1"></i> Download</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all document requests with user details
                            $sql = "SELECT d.*, u.email, u.address 
                                    FROM documents d
                                    JOIN users u ON d.user_id = u.user_id
                                    ORDER BY d.request_date DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['document_id'] ?></td>
                                    <td><?= isset($row['email']) ? htmlspecialchars($row['email']) : 'N/A' ?></td>
                                    <td><?= isset($row['address']) ? htmlspecialchars($row['address']) : 'N/A' ?></td>
                                    <td>
                                        <?php 
                                        $docType = htmlspecialchars($row['document_type']);
                                        $docIcon = 'fa-file-alt';
                                        
                                        if (stripos($docType, 'certificate') !== false) {
                                            $docIcon = 'fa-certificate';
                                        } elseif (stripos($docType, 'license') !== false) {
                                            $docIcon = 'fa-id-card';
                                        } elseif (stripos($docType, 'permit') !== false) {
                                            $docIcon = 'fa-clipboard-check';
                                        }
                                        ?>
                                        <i class="fas <?= $docIcon ?> me-1"></i> <?= $docType ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['request_date'])) ?></td>
                                    <td><?= $row['issue_date'] ? date('M d, Y', strtotime($row['issue_date'])) : 'N/A' ?></td>
                                    
                                    <!-- Receipt File -->
                                    <td>
                                        <?php if (!empty($row['receipt_file'])): ?>
                                            <a href="<?= htmlspecialchars($row['receipt_file']) ?>" target="_blank" class="btn btn-link btn-sm">
                                                <i class="fas fa-eye me-1"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Status Action -->
                                    <td>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <form method="POST" class="action-form">
                                                <input type="hidden" name="document_id" value="<?= $row['document_id'] ?>">
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="approved">Approve</option>
                                                    <option value="pending" selected>Pending</option>
                                                    <option value="rejected">Reject</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($row['status'] === 'approved'): ?>
                                            <span class="status-approved">
                                                <i class="fas fa-check-circle me-1"></i> Approved
                                            </span>
                                        <?php elseif ($row['status'] === 'pending'): ?>
                                            <span class="status-pending">
                                                <i class="fas fa-clock me-1"></i> Pending
                                            </span>
                                        <?php elseif ($row['status'] === 'rejected'): ?>
                                            <span class="status-rejected">
                                                <i class="fas fa-times-circle me-1"></i> Rejected
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Document Download -->
                                    <td>
                                        <?php if ($row['status'] === 'approved' && !empty($row['file_path'])): ?>
                                            <a href="<?= $row['file_path'] ?>" download class="btn btn-success btn-sm">
                                                <i class="fas fa-download me-1"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="pagination-container">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2025 Document Management System. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap and other scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</body>
</html>