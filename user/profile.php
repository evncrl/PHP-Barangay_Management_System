<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("../includes/config.php");

$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

$userId = $_SESSION['user_id'];
$query = mysqli_query($conn, "SELECT * FROM residents WHERE user_id = '$userId'");
$userData = mysqli_fetch_assoc($query);

$isVerified = isset($userData['verification_status']) && $userData['verification_status'] == 'verified';

if (isset($_POST['submit_profile']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    // Collect form data
    $lname = trim($_POST['lname'] ?? '');
    $mname = trim($_POST['mname'] ?? ''); 
    $fname = trim($_POST['fname'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? ''); 
    $place_of_birth = trim($_POST['place_of_birth'] ?? ''); 
    $age = intval($_POST['age'] ?? 0); 
    $title = trim($_POST['title'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $civil_status = trim($_POST['civil_status'] ?? ''); 
    $occupation = trim($_POST['occupation'] ?? ''); 
    $citizenship = trim($_POST['citizenship'] ?? ''); 
    $voter_status = trim($_POST['voter_status'] ?? ''); 
    $years_of_residency = intval($_POST['years_of_residency'] ?? 0);

    $errors = [];
    $validIdPath = null; 
    $profileImagePath = null;

    // Validate required fields
    if (empty($lname) || empty($fname) || empty($address) || empty($phone)) {
        $errors['fields'] = 'Please fill in all required fields.';
    }

    // Handle Profile Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $imageType = $_FILES['profile_image']['type'];
        $imageSize = $_FILES['profile_image']['size'];

        if (!in_array($imageType, $allowedImageTypes)) {
            $errors['image'] = 'Only JPG, PNG, and GIF images are allowed.';
        } elseif ($imageSize > $maxFileSize) {
            $errors['image'] = 'The image file size should not exceed 5MB.';
        } else {
            $uploadDir = '../user/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid($userId . '_profile_') . '.' . $fileExtension;
            $profileImagePath = 'uploads/' . $newFileName;
            $uploadPath = $uploadDir . $newFileName;

            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                $errors['image'] = 'Failed to upload the image.';
                error_log('Upload failed: ' . $_FILES['profile_image']['error']);
            }
        }
    }

    // Handle Valid ID Upload
    if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] === UPLOAD_ERR_OK) {
        $validIdType = $_FILES['valid_id']['type'];
        $validIdSize = $_FILES['valid_id']['size'];

        if (!in_array($validIdType, $allowedImageTypes)) {
            $errors['valid_id'] = 'Only JPG, PNG, and GIF images are allowed for the valid ID.';
        } elseif ($validIdSize > $maxFileSize) {
            $errors['valid_id'] = 'The valid ID file size should not exceed 5MB.';
        } else {
            $uploadDir = '../user/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = pathinfo($_FILES['valid_id']['name'], PATHINFO_EXTENSION);
            $validIdFileName = uniqid($userId . '_valid_id_') . '.' . $fileExtension;
            $validIdPath = 'uploads/' . $validIdFileName;
            $validIdUploadPath = $uploadDir . $validIdFileName;

            if (!move_uploaded_file($_FILES['valid_id']['tmp_name'], $validIdUploadPath)) {
                error_log("File upload failed: " . $_FILES['valid_id']['error']);
                $errors['valid_id'] = 'Failed to upload the valid ID.';
            }
        }
    }

    if (empty($errors)) {
        $checkUser = mysqli_query($conn, "SELECT * FROM residents WHERE user_id = '$userId'");

        if (mysqli_num_rows($checkUser) > 0 && $isVerified) {
            // Update existing record without changing verification status
            if ($profileImagePath && $validIdPath) {
                $sql = "UPDATE residents SET title=?, lname=?, mname=?, fname=?, birthdate=?, place_of_birth=?, age=?, address=?, phone=?, civil_status=?, occupation=?, citizenship=?, voter_status=?, profile_image=?, valid_id=?, years_of_residency=? WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'ssssssisssssssssi', $title, $lname, $mname, $fname, $birthdate, $place_of_birth, $age, $address, $phone, $civil_status, $occupation, $citizenship, $voter_status, $profileImagePath, $validIdPath, $years_of_residency, $userId);
            } elseif ($profileImagePath) {
                $sql = "UPDATE residents SET title=?, lname=?, mname=?, fname=?, birthdate=?, place_of_birth=?, age=?, address=?, phone=?, civil_status=?, occupation=?, citizenship=?, voter_status=?, profile_image=?, years_of_residency=? WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'ssssssissssssssi', $title, $lname, $mname, $fname, $birthdate, $place_of_birth, $age, $address, $phone, $civil_status, $occupation, $citizenship, $voter_status, $profileImagePath, $years_of_residency, $userId);
            } elseif ($validIdPath) {
                $sql = "UPDATE residents SET title=?, lname=?, mname=?, fname=?, birthdate=?, place_of_birth=?, age=?, address=?, phone=?, civil_status=?, occupation=?, citizenship=?, voter_status=?, valid_id=?, years_of_residency=? WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'ssssssissssssssi', $title, $lname, $mname, $fname, $birthdate, $place_of_birth, $age, $address, $phone, $civil_status, $occupation, $citizenship, $voter_status, $validIdPath, $years_of_residency, $userId);
            } else {
                $sql = "UPDATE residents SET title=?, lname=?, mname=?, fname=?, birthdate=?, place_of_birth=?, age=?, address=?, phone=?, civil_status=?, occupation=?, citizenship=?, voter_status=?, years_of_residency=? WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'ssssssisssssssi', $title, $lname, $mname, $fname, $birthdate, $place_of_birth, $age, $address, $phone, $civil_status, $occupation, $citizenship, $voter_status, $years_of_residency, $userId);
            }

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Profile successfully updated.';
                header("Location: profile.php"); // Redirect to the profile page
                exit;
            } else {
                $_SESSION['error'] = 'Database error: ' . mysqli_stmt_error($stmt);
                header("Location: profile.php"); // Redirect to the profile page
                exit;
            }
        } 
        // User exists but is not verified
        else if (mysqli_num_rows($checkUser) > 0 && !$isVerified) {
            // Update existing record and set verification to pending
            if ($profileImagePath && $validIdPath) {
                $sql = "UPDATE residents SET title=?, lname=?, mname=?, fname=?, birthdate=?, place_of_birth=?, age=?, address=?, phone=?, civil_status=?, occupation=?, citizenship=?, voter_status=?, profile_image=?, valid_id=?, years_of_residency=?, verification_status='pending' WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'ssssssisssssssssi', $title, $lname, $mname, $fname, $birthdate, $place_of_birth, $age, $address, $phone, $civil_status, $occupation, $citizenship, $voter_status, $profileImagePath, $validIdPath, $years_of_residency, $userId);
            } elseif ($profileImagePath) {
                $sql = "UPDATE residents SET title=?, lname=?, mname=?, fname=?, birthdate=?, place_of_birth=?, age=?, address=?, phone=?, civil_status=?, occupation=?, citizenship=?, voter_status=?, profile_image=?, years_of_residency=?, verification_status='pending' WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'ssssssissssssssi', $title, $lname, $mname, $fname, $birthdate, $place_of_birth, $age, $address, $phone, $civil_status, $occupation, $citizenship, $voter_status, $profileImagePath, $years_of_residency, $userId);
            } elseif ($validIdPath) {
                $sql = "UPDATE residents SET title=?, lname=?, mname=?, fname=?, birthdate=?, place_of_birth=?, age=?, address=?, phone=?, civil_status=?, occupation=?, citizenship=?, voter_status=?, valid_id=?, years_of_residency=?, verification_status='pending' WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'ssssssissssssssi', $title, $lname, $mname, $fname, $birthdate, $place_of_birth, $age, $address, $phone, $civil_status, $occupation, $citizenship, $voter_status, $validIdPath, $years_of_residency, $userId);
            } else {
                $sql = "UPDATE residents SET title=?, lname=?, mname=?, fname=?, birthdate=?, place_of_birth=?, age=?, address=?, phone=?, civil_status=?, occupation=?, citizenship=?, voter_status=?, years_of_residency=?, verification_status='pending' WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'ssssssisssssssi', $title, $lname, $mname, $fname, $birthdate, $place_of_birth, $age, $address, $phone, $civil_status, $occupation, $citizenship, $voter_status, $years_of_residency, $userId);
            }

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Profile updated and sent for verification.';
                header("Location: profile.php"); // Redirect to the profile page
                exit;
            } else {
                $_SESSION['error'] = 'Database error: ' . mysqli_stmt_error($stmt);
                header("Location: profile.php"); // Redirect to the profile page
                exit;
            }
        }
        // New user
        else {
            // Insert new record with pending verification status
            $sql = "INSERT INTO residents (title, lname, mname, fname, birthdate, place_of_birth, age, address, phone, civil_status, occupation, citizenship, voter_status, user_id, profile_image, valid_id, years_of_residency, verification_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'ssssssisssssssssi', $title, $lname, $mname, $fname, $birthdate, $place_of_birth, $age, $address, $phone, $civil_status, $occupation, $citizenship, $voter_status, $userId, $profileImagePath, $validIdPath, $years_of_residency);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Profile submitted for verification. You will be able to access the system once an administrator verifies your account.';
                // Log out the user
                session_destroy(); // Destroy the session
                // Redirect to login page
                header("Location: /saad/user/login.php");
                exit; // Ensure no further code is executed
            } else {
                $_SESSION['error'] = 'Database error: ' . mysqli_stmt_error($stmt);
                header("Location: profile.php"); // Redirect to the profile page
                exit;
            }
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
        header("Location: profile.php");
        exit;
    }
}

// Set profile image path
$profileImage = isset($userData['profile_image']) && !empty($userData['profile_image'])
    ? '../user/' . $userData['profile_image']
    : 'http://bootdey.com/img/Content/avatar/avatar1.png';

// Set valid ID image path
$validIdImage = isset($userData['valid_id']) && !empty($userData['valid_id'])
    ? '../user/' . $userData['valid_id']
    : 'default-valid-id-placeholder.png';

    include("../includes/header.php");

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body Styling */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            color: #333;
            min-height: 100vh;
            margin: 0;
            padding: 30px 20px;
        }

        .header-spacing {
            height: 30px;
            background-color: #f1f5f9;
        }

        /* Container Styling */
        .container {
            width: 90%;
            max-width: 1200px;
            background-color: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
        }

        /* Header Section */
        .header-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 40px;
            text-align: center;
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 10px;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #3a7bd5, #00d2ff);
            border-radius: 2px;
        }

        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
            width: 100%;
        }

        .profile-image-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin-bottom: 10px;
        }

        .profile-image {
            width: 200px;
            height: 200px;
            border-radius: 16px;
            object-fit: cover;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.02);
        }

        /* Form Styling */
        form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 5px;
        }

        .form-group label {
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border-radius: 10px;
            border: 1px solid #e1e1e1;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
            height: 48px;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #3a7bd5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(58, 123, 213, 0.1);
            background-color: #fff;
        }

        .form-group input[type="file"] {
            padding: 10px;
            height: auto;
            background-color: #f2f7ff;
            border: 1px dashed #3a7bd5;
        }

        /* File Upload Styling */
        .file-upload-group {
            grid-column: span 2;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .file-upload {
            flex: 1;
        }

        /* Submit Button */
        .submit-btn-container {
            grid-column: 1 / -1;
            margin-top: 20px;
            text-align: center;
        }

        .submit-btn {
            padding: 14px 30px;
            background: linear-gradient(90deg, #2c3e50, #4b6cb7);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.2);
            width: auto;
            min-width: 200px;
        }

        .submit-btn:hover {
            background: linear-gradient(90deg, #4b6cb7, #2c3e50);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(44, 62, 80, 0.25);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 25px 20px;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            form {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .file-upload-group {
                grid-column: span 1;
                flex-direction: column;
            }
            
            .profile-image {
                width: 150px;
                height: 150px;
            }
            
            .profile-image-container {
                width: 150px;
                height: 150px;
            }
        }

        /* Animation for status messages */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .status-message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease;
            text-align: center;
            font-weight: 500;
            grid-column: 1 / -1;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Form Field Groups */
        .form-section {
            grid-column: 1 / -1;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .form-section-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 600;
        }

        /* Preview for Valid ID */
        .id-preview-container {
            margin-top: 15px;
            display: flex;
            justify-content: center;
        }

       
    /* Style for the Valid ID image */
    .valid-id-image {
        max-width: 100%; /* Ensure the image doesn't exceed its container */
        width: 300px; /* Set a fixed width */
        height: auto; /* Maintain aspect ratio */
        border: 1px solid #ddd; /* Add a border */
        border-radius: 8px; /* Rounded corners */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add a subtle shadow */
    }

    /* Style for the "No valid ID uploaded" message */
    .id-preview-container p {
        font-style: italic;
        color: #888;
    }
    </style>
</head>
<body>
<div class="container">
    <!-- Header Section -->
    <div class="header-section">
        <h1 class="section-title">Resident Profile</h1>
        <div class="profile-header">
            <div class="profile-image-container">
                <img id="profileImagePreview" class="profile-image" src="<?php echo $profileImage; ?>" alt="Profile Picture">
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    <?php if(isset($_SESSION['success'])): ?>
        <div class="status-message success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="status-message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Form Section -->
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
        <!-- Personal Information Section -->
        <div class="form-section">
            <h2 class="form-section-title">Personal Information</h2>
        </div>

        <div class="file-upload-group">
            <div class="form-group file-upload">
                <label for="profile_image">Upload Profile Image</label>
                <input type="file" name="profile_image" id="profile_image" accept="image/*" onchange="previewImage(event, 'profileImagePreview')">
            </div>
            
            <div class="form-group file-upload">
                <label for="valid_id">Upload Valid ID</label>
                <input type="file" name="valid_id" id="valid_id" accept="image/*" onchange="previewImage(event, 'validIdPreview')">

            </div>
        </div>

        <div class="id-preview-container">
    <h3>Valid ID</h3>
    <?php if (!empty($validIdImage) && $validIdImage !== 'default-valid-id-placeholder.png'): ?>
        <img src="<?php echo htmlspecialchars($validIdImage); ?>" alt="Valid ID" class="valid-id-image">
    <?php else: ?>
        <p>No valid ID uploaded.</p>
    <?php endif; ?>
</div>

        <div class="form-group">
            <label for="inputTitle">Title</label>
            <input id="inputTitle" type="text" name="title" value="<?php echo $userData['title'] ?? ''; ?>" placeholder="Mr./Mrs./Ms.">
        </div>

        <div class="form-group">
            <label for="inputFirstName">First Name</label>
            <input id="inputFirstName" type="text" name="fname" value="<?php echo $userData['fname'] ?? ''; ?>" required placeholder="Enter your first name">
        </div>

        <div class="form-group">
            <label for="inputMiddleName">Middle Name</label>
            <input id="inputMiddleName" type="text" name="mname" value="<?php echo $userData['mname'] ?? ''; ?>" placeholder="Enter your middle name">
        </div>

        <div class="form-group">
            <label for="inputLastName">Last Name</label>
            <input id="inputLastName" type="text" name="lname" value="<?php echo $userData['lname'] ?? ''; ?>" required placeholder="Enter your last name">
        </div>

        <div class="form-group">
            <label for="inputBirthdate">Birthdate</label>
            <input id="inputBirthdate" type="date" name="birthdate" value="<?php echo $userData['birthdate'] ?? ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="inputPlaceOfBirth">Place of Birth</label>
            <input id="inputPlaceOfBirth" type="text" name="place_of_birth" value="<?php echo $userData['place_of_birth'] ?? ''; ?>" required placeholder="City, Province">
        </div>

        <div class="form-group">
            <label for="inputAge">Age</label>
            <input id="inputAge" type="text" name="age" value="<?php echo $userData['age'] ?? ''; ?>" readonly>
        </div>

        <!-- Contact Information Section -->
        <div class="form-section">
            <h2 class="form-section-title">Contact & Address</h2>
        </div>

        <div class="form-group">
            <label for="inputAddress">Complete Address</label>
            <input id="inputAddress" type="text" name="address" value="<?php echo $userData['address'] ?? ''; ?>" required placeholder="House/Lot #, Street, Barangay, City">
        </div>

        <div class="form-group">
            <label for="inputPhone">Phone Number</label>
            <input id="inputPhone" type="text" name="phone" value="<?php echo $userData['phone'] ?? ''; ?>" required placeholder="09XXXXXXXXX">
        </div>

        <!-- Additional Information Section -->
        <div class="form-section">
            <h2 class="form-section-title">Additional Information</h2>
        </div>

        <div class="form-group">
            <label for="inputCivilStatus">Civil Status</label>
            <select id="inputCivilStatus" name="civil_status" required>
                <option value="" disabled selected>Select civil status</option>
                <option value="Single" <?php echo ($userData['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                <option value="Married" <?php echo ($userData['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                <option value="Widowed" <?php echo ($userData['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                <option value="Separated" <?php echo ($userData['civil_status'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                <option value="Divorced" <?php echo ($userData['civil_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
            </select>
        </div>

        <div class="form-group">
            <label for="inputOccupation">Occupation</label>
            <input id="inputOccupation" type="text" name="occupation" value="<?php echo $userData['occupation'] ?? ''; ?>" required placeholder="Your current job">
        </div>

        <div class="form-group">
            <label for="inputCitizenship">Citizenship</label>
            <input id="inputCitizenship" type="text" name="citizenship" value="<?php echo $userData['citizenship'] ?? ''; ?>" required placeholder="Filipino">
        </div>

        <div class="form-group">
            <label for="inputVoterStatus">Voter Status</label>
            <select id="inputVoterStatus" name="voter_status" required>
                <option value="Yes" <?php echo ($userData['voter_status'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Active</option>
                <option value="No" <?php echo ($userData['voter_status'] ?? '') === 'No' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>

        <div class="form-group">
            <label for="years_of_residency">Years of Residency</label>
            <input type="number" id="years_of_residency" name="years_of_residency" value="<?php echo htmlspecialchars($userData['years_of_residency'] ?? ''); ?>" required placeholder="Number of years">
        </div>

        <!-- Submit Button -->
        <div class="submit-btn-container">
            <?php if (!$isVerified): ?>
                <button class="submit-btn" type="submit" name="submit_profile">
                    <?php echo (isset($userData['verification_status']) && $userData['verification_status'] == 'pending') ? 'Update Profile' : 'Request Verification'; ?>
                </button>
            <?php else: ?>
                <button class="submit-btn" type="submit" name="submit_profile">
                    Update Profile
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    // Calculate age from birthdate
    document.getElementById('inputBirthdate').addEventListener('change', function() {
        let birthdate = new Date(this.value);
        let today = new Date();
        
        let age = today.getFullYear() - birthdate.getFullYear();
        let monthDiff = today.getMonth() - birthdate.getMonth();
        let dayDiff = today.getDate() - birthdate.getDate();

        // Adjust age if birthday hasn't occurred yet this year
        if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
            age--;
        }

        document.getElementById('inputAge').value = age >= 0 ? age : '';
    });

    // Preview uploaded images
    function previewImage(event, previewId) {
        const preview = document.getElementById(previewId);
        const file = event.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(file);
        }
    }
</script>
</body>
</html>