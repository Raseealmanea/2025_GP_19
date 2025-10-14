
<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="stylee.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

        <script>
            function toggleDropdown(event) {
            event.stopPropagation(); // Prevent click from bubbling up
            document.getElementById("dropdownMenu").classList.toggle("show");
            }

            document.addEventListener("click", function() {
            const dropdown = document.getElementById("dropdownMenu");
            if (dropdown.classList.contains("show")) {
                dropdown.classList.remove("show");
            }
            });

            function logoutUser() {
            // Clear any saved session or token
            localStorage.clear();
            sessionStorage.clear();
            window.location.href ="login.php";
            }
        </script>
    </head>
    <body>
        <header class="header">
            <div class="header-left">
               <img src='logo.svg' alt="OuwN Logo" class="logo-img">
            </div>
            <div class="header-right">
                <div class="profile-icon" onclick="toggleDropdown(event)">
                    <img src="doctor.png" alt="Profile Icon">
                    <div class="dropdown" id="dropdownMenu">
                        <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
                <nav class="header-nav">
                    <a href="homePage.php#about">About</a>
                    <a href="homePage.php#vision">Vision</a>
                </nav>
            </div>
        </header>

        <main class="dashboard container">
            <h1>Dashboard</h1>

            <div class="dashboard-actions" id="dashboard-buttons">
                <button id="add-patient-btn" onclick="location.href='AddPatient.php'">
                <i class="fa-solid fa-user-plus"></i> Add Patient
                </button>
                <button id="write-notes-btn" onclick="location.href='MedicalNotes.php'">
                <i class="fa-solid fa-file-medical"></i> Write Notes
                </button>
            </div>



        </main>

        <footer>
                © 2025 OuwN Healthcare — All Rights Reserved
        </footer>

    </body>
</html>
