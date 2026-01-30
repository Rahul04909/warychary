<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer</title>
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif; /* Modern sans-serif font */
        }
        .footer {
            background-color: #2c3e50; /* Darker, modern background */
            color: #ecf0f1; /* Light gray text for contrast */
            padding: 60px 20px; /* Increased padding */
            text-align: center;
            width: 100%;
            box-sizing: border-box;
            border-top: 5px solid #7c43b9; /* Accent color border */
        }
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            max-width: 1200px; /* Max width for content */
            margin: 0 auto 40px auto; /* Center content and add bottom margin */
        }
        .footer-section {
            flex: 1;
            min-width: 250px; /* Increased min-width for sections */
            margin: 20px; /* Increased margin */
            text-align: left; /* Align text left within sections */
        }
        .footer-section h3 {
            color: #7c43b9; /* Accent color for headings */
            margin-bottom: 20px; /* Increased margin */
            font-size: 1.4em; /* Larger headings */
            position: relative;
            padding-bottom: 10px;
        }
        .footer-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: #7c43b9;
        }
        .footer-section p,
        .footer-section ul {
            font-size: 1em; /* Slightly larger font size */
            line-height: 1.8; /* Improved line height */
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .footer-section ul li {
            margin-bottom: 10px;
        }
        .footer-section ul li a {
            color: #ecf0f1;
            text-decoration: none;
            transition: color 0.3s ease; /* Smooth hover effect */
        }
        .footer-section ul li a:hover {
            color: #7c43b9; /* Accent color on hover */
            text-decoration: underline;
        }
        .social-icons {
            margin-top: 20px;
        }
        .social-icons a {
            color: #ecf0f1;
            font-size: 1.8em;
            margin: 0 10px;
            transition: color 0.3s ease;
        }
        .social-icons a:hover {
            color: #7c43b9;
        }
        .footer-bottom {
            border-top: 1px solid #34495e; /* Slightly lighter border */
            padding-top: 20px; /* Increased padding */
            font-size: 0.9em; /* Slightly larger font */
            color: #bdc3c7; /* Muted color for copyright */
        }

        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                align-items: center;
            }
            .footer-section {
                margin: 20px 0;
                text-align: center; /* Center text on mobile */
            }
            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }
        }
    </style>
</head>
<body>
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>Sparkle is dedicated to providing high-quality, eco-friendly feminine hygiene products. We believe in comfort, sustainability, and empowering women.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Products</a></li>
                    <li><a href="#">About WaryChary</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Info</h3>
                <p>Email: support@warychary.com</p>
                <p>Phone: +91-7988472662</p>
                <p>Address: Hissar, Haryana, India</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2025 WaryChary Care. All rights reserved. A Website Desigend By Rahul Dhiman 
        </div>
    </footer>
</body>
</html>