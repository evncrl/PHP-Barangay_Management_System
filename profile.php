<?php
include 'includes/config.php'; // Adjust path as needed
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: error.php"); // Redirect to error page
    exit;
}

// Check if user_id is set
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header("Location: error.php"); // Redirect if no user_id provided
    exit;
}

$user_id = intval($_GET['user_id']);

// Fetch user details from users table
$sql = "SELECT email, role, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Fetch resident details from residents table
$sql = "SELECT * FROM residents WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resident_result = $stmt->get_result();
$resident = $resident_result->fetch_assoc();
$stmt->close();

// If user or resident not found, redirect
if (!$user || !$resident) {
    header("Location: error.php");
    exit;
}

// Handle verification status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verification_status'])) {
    $new_status = $_POST['verification_status'];
    if ($new_status === 'Approved' || $new_status === 'Rejected') {
        $update_sql = "UPDATE residents SET verification_status = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();
        $stmt->close();
        header("Location: profile.php?user_id=" . $user_id . "&status=updated");
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
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
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            color: #333;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Container Styling */
        .container {
            width: 90%;
            max-width: 1000px;
            margin: 20px auto;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        /* Header Section */
        .header-section {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
            padding-bottom: 10px;
            position: relative;
        }

        .section-title:after {
            content: '';
            position: absolute;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, #2c3e50, #4ca1af);
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        /* Profile and ID Image Section */
        .profile-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            gap: 30px;
        }

        .profile-container {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        .valid-id-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .profile-image {
            width: 250px;
            height: 250px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .profile-image:hover {
            transform: scale(1.05);
        }

        .valid-id-section {
            width: 100%;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .valid-id-section h3 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
            position: relative;
            display: block;
            text-align: left;
        }

        .valid-id-section h3:after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background: #2c3e50;
            bottom: -5px;
            left: 0;
        }

        .valid-id-image {
            width: 100%;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            object-fit: contain;
        }

        .valid-id-image:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        /* Image Modal for Enlarged View */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            overflow: auto;
            transition: 0.3s ease;
        }

        .modal-content {
            display: block;
            margin: 5% auto;
            max-width: 90%;
            max-height: 90%;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 40px;
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }

        .close-modal:hover {
            color: #bbb;
        }

        /* Form Styling */
        form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        /* Form Group */
        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .form-group label {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            height: 45px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #4ca1af;
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 161, 175, 0.2);
            background-color: #fff;
        }

        /* Submit Button */
        .submit-btn {
            display: inline-block;
            margin-top: 30px;
            padding: 14px 28px;
            background: linear-gradient(to right, #2c3e50, #4ca1af);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            width: auto;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .submit-btn:hover {
            background: linear-gradient(to right, #4ca1af, #2c3e50);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        /* Verification Status Section */
        .verification-status {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .verification-status h3 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .status-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }

        .status-btn {
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .approve-btn {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .approve-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .reject-btn {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .reject-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .current-status {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            color: white;
        }

        .current-status.approved {
            background-color: #28a745;
        }

        .current-status.rejected {
            background-color: #dc3545;
        }

        .current-status.pending {
            background-color: #ffc107;
            color: #212529;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 20px;
            }

            form {
                grid-template-columns: 1fr;
            }

            .profile-section {
                flex-direction: column;
            }

            .profile-container,
            .valid-id-container {
                width: 100%;
                align-items: center;
            }

            .valid-id-section h3:after {
                left: 50%;
                transform: translateX(-50%);
            }

            .valid-id-section h3 {
                text-align: center;
            }
            
            .status-options {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h2 class="section-title">User Profile</h2>
        </div>
        
        <div class="profile-section">
            <div class="profile-container">
                <?php if (!empty($resident['profile_image'])): ?>
                    <img src="/saad/user/uploads/<?= htmlspecialchars(basename($resident['profile_image'])) ?>" alt="Profile Image" class="profile-image">
                <?php else: ?>
                    <img src="../images/default-profile.png" alt="Default Profile" class="profile-image">
                <?php endif; ?>
            </div>

            <div class="valid-id-container">
                <div class="valid-id-section">
                    <h3>Valid ID</h3>
                    <?php if (!empty($resident['valid_id'])): ?>
                        <img src="/saad/user/uploads/<?= htmlspecialchars(basename($resident['valid_id'])) ?>" alt="Valid ID" class="valid-id-image">
                    <?php else: ?>
                        <p>No valid ID uploaded.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form>
            <div class="form-group">
                <label>Email:</label>
                <input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Role:</label>
                <input type="text" value="<?= htmlspecialchars($user['role']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Account Created:</label>
                <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" value="<?= htmlspecialchars($resident['fname'] . ' ' . $resident['mname'] . ' ' . $resident['lname']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Address:</label>
                <input type="text" value="<?= htmlspecialchars($resident['address']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Phone:</label>
                <input type="text" value="<?= htmlspecialchars($resident['phone']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Birthdate:</label>
                <input type="text" value="<?= htmlspecialchars($resident['birthdate']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Age:</label>
                <input type="text" value="<?= htmlspecialchars($resident['age']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Citizenship:</label>
                <input type="text" value="<?= htmlspecialchars($resident['citizenship']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Occupation:</label>
                <input type="text" value="<?= htmlspecialchars($resident['occupation']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Civil Status:</label>
                <input type="text" value="<?= htmlspecialchars($resident['civil_status']) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Years of Residency:</label>
                <input type="text" value="<?= htmlspecialchars($resident['years_of_residency']) ?>" readonly>
            </div>
        </form>

        <?php if (isset($resident['verification_status'])): ?>
            <?php if ($resident['verification_status'] !== 'Approved' && $resident['verification_status'] !== 'Rejected'): ?>
            <div class="verification-status">
                <h3>Verification Status</h3>
                <form method="POST" action="">
                    <div class="status-options">
                        <button type="submit" name="verification_status" value="Approved" class="status-btn approve-btn">Approve</button>
                        <button type="submit" name="verification_status" value="Rejected" class="status-btn reject-btn">Reject</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="verification-status">
                <h3>Verification Status</h3>
                <div class="current-status <?= strtolower($resident['verification_status']) ?>">
                    <?= htmlspecialchars($resident['verification_status']) ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="text-align: center;">
            <a href="/saad/admin/users.php" class="submit-btn">Back to User List</a>
        </div>
    </div>

    <!-- Modal for image enlargement -->
    <div id="imageModal" class="modal">
        <span class="close-modal">&times;</span>
        <img class="modal-content" id="enlargedImage">
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the modal
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('enlargedImage');
            const closeModal = document.querySelector('.close-modal');
            
            // Get images that should trigger the modal
            const profileImg = document.querySelector('.profile-image');
            const validIdImg = document.querySelector('.valid-id-image');
            
            // Function to open modal with specific image
            function openModal(imgSrc) {
                modal.style.display = 'block';
                modalImg.src = imgSrc;
            }
            
            // Add click event to profile image
            if (profileImg) {
                profileImg.addEventListener('click', function() {
                    openModal(this.src);
                });
            }
            
            // Add click event to valid ID image
            if (validIdImg) {
                validIdImg.addEventListener('click', function() {
                    openModal(this.src);
                });
            }
            
            // Close modal when clicking X
            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Close modal when clicking outside the image
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.style.display === 'block') {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>