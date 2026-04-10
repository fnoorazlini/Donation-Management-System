<!DOCTYPE html>
<html>
<head>


    <title>Home Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-image: url('images/mjtj.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
        }

        .content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            width: 100%;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.6);
            width: 80%;
            max-width: 1200px;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .container img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 28px;
        }

        p {
            font-size: 18px;
            line-height: 1.6;
        }

        .dropdown {
            margin-top: 30px;
            position: relative;
            display: inline-block;
            size: 36px; /* This property is invalid, removed */
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .options button {
            width: 100%;
            background-color: #4CAF50; /* Green background */
            color: white;
            padding: 15px 25px; /* Larger padding */
            margin: 15px 0; /* Increased margin */
            border: none;
            cursor: pointer;
            border-radius: 8px; /* Rounded corners */
            font-size: 18px; /* Larger font size */
            font-weight: bold; /* Bold text */
            text-transform: uppercase; /* Uppercase text */
            transition: background-color 0.3s ease; /* Smooth transition */
        }

        .options button:hover {
            background-color: #45a049; /* Darker green on hover */
        }

        .options button a {
            color: white;
            text-decoration: none;
            display: block;
        }

        .contact {
            text-align: center;
            margin-top: 20px;
        }

        .contact p {
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px 0;
        }

        .contact i {
            margin-right: 10px;
        }

        .fa-envelope {
            color: red;
        }

        .fa-facebook-square {
            color: #3b5998;
        }

        .fa-instagram {
            color: #E4405F;
        }

        .fa-whatsapp {
            color: #25D366;
        }

        .fa-tiktok {
            color: #69C9D0;
        }

        .contact a {
            color: inherit;
            text-decoration: none;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        function showAboutUs() {
            Swal.fire({
                title: '<strong>About Us</strong>',
                html: `
                    <p>Ma'ahad Tahfiz Wal Tarbiyyah Darul Iman is a beacon of Islamic education and spiritual growth, dedicated to nurturing the minds and souls of our students. Located in the heart of our community, we strive to provide a holistic educational experience that encompasses both academic excellence and religious devotion.</p>
                    <h3>Our Vision</h3>
                    <p>Integrated Asia-Pacific Elective Middle Studies Center Towards 2027</p>
                    <h3>Our Mission</h3>
                    <ul>
                        <li>To develop future generations of scholars and professionals as leaders who master integrated knowledge and information for the betterment of the world and the hereafter.</li>
                        <li>To provide integrated education programs (academic integration modules, religious studies, and Quran memorization).</li>
                    </ul>
                    <h3>Our Objectives</h3>
                    <ul style="text-align: left;">
                        <li>To develop educated Muslim generations capable of spreading Islam based on the Quran and Sunnah, as well as mastering current developments and modern technology.</li>
                        <li>Teaching Islamic knowledge that emphasizes the foundation of human development from all perspectives.</li>
                        <li>Producing students who memorize the Quran and are proficient in Arabic.</li>
                        <li>Educating the younger generation with noble character based on Islamic morals.</li>
                        <li>Instilling the aspiration of Islamic struggle to advance society and the Muslim community.</li>
                    </ul>
                    <h3>Our Community</h3>
                    <p>We believe in fostering a supportive and inclusive community where students, parents, and teachers work together to achieve common goals. Our school is a place where lifelong friendships are formed, and a sense of belonging is cultivated.</p>
                `,
                confirmButtonText: 'Close',
                customClass: {
                    confirmButton: 'custom-swal-button'
                }
            });
        }
    </script>
</head>
<body>

<div class="content">
    <div class="container">
        <img src="images/banner.png" alt="Header Image">
        <h2>WELCOME TO MA'AHAD TAHFIZ WALTARBIYYAH DARUL IMAN DONATION WEBSITE</h2>
        <p>Support our school's mission of providing quality education and nurturing future leaders. Your contributions make a difference in shaping young minds and building a brighter future.</p>
        <div class="dropdown">
            <button class="options" onclick="showOptions()">Select User <i class="fas fa-caret-down"></i></button>
            <div class="dropdown-content" id="myDropdown">
            <div class="dropdown-content" id="myDropdown">
    <a href="login.php"><i class="fas fa-hand-holding-heart"></i> Donor</a>
    <a href="loginclerk.php"><i class="fas fa-user-tie"></i> Clerk</a>
    <a href="loginprincipal.php"><i class="fas fa-chalkboard-teacher"></i> Principal</a>
</div>
            </div>
        </div>
        <button onclick="showAboutUs()">About Us</button>

        <h3>Contact:</h3>
        <div class="contact">
            <p><i class="fa-regular fa-envelope"></i> EMAIL: <a href="mailto:mtt_trg@yahoo.com">mtt_trg@yahoo.com</a></p>
            <p><i class="fa-brands fa-facebook-square"></i> FACEBOOK: <a href="http://facebook.com/mttdaruliman" target="_blank">http://facebook.com/mttdaruliman</a></p>
            <p><i class="fa-brands fa-instagram"></i> INSTAGRAM: <a href="https://instagram.com/mttdaruliman.edu.my" target="_blank">https://instagram.com/mttdaruliman.edu.my</a></p>
            <p><i class="fa-brands fa-whatsapp"></i> WHATSAPP: <a href="https://chat.whatsapp.com/DBJY9P1ix2DLh6en3GPLQ5" target="_blank">Channel mttdaruliman</a></p>
            <p><i class="fa-brands fa-tiktok"></i> TIKTOK: <a href="https://www.tiktok.com/@mttdaruliman?_t=8noUzg07WVt&_r=1" target="_blank">@mttdaruliman</a></p>
        </div>
    </div>
</div>

<script>
    function showOptions() {
        document.getElementById("myDropdown").classList.toggle("show");
    }

    // Close the dropdown menu if the user clicks outside of it
    window.onclick = function(event) {
        if (!event.target.matches('.options')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>

</body>
</html>
