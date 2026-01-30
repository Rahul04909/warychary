<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warychary Sanitary Pads - About</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f7fe;
            color: #333;
            line-height: 1.6;
        }
        
        .about-section {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .section-title {
            text-align: center;
            color: #8a2be2;
            margin-bottom: 40px;
            font-size: 2.5rem;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, #8a2be2, #ff69b4);
            margin: 10px auto;
            border-radius: 2px;
        }
        
        .about-container {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            align-items: center;
        }
        
        .content-column, .image-column {
            flex: 1;
            min-width: 300px;
        }
        
        .content-column h2 {
            color: #8a2be2;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        .content-column p {
            margin-bottom: 20px;
            font-size: 1.1rem;
            color: #555;
        }
        
        .features-list {
            margin: 25px 0;
        }
        
        .features-list li {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
            list-style-type: none;
        }
        
        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #8a2be2;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .why-choose {
            background: linear-gradient(135deg, #f3ecff, #e6d9ff);
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
            border-left: 5px solid #8a2be2;
        }
        
        .why-choose h3 {
            color: #8a2be2;
            margin-bottom: 15px;
        }
        
        .image-column {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .product-image {
            width: 100%;
            max-width: 500px;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(138, 43, 226, 0.2);
            transition: transform 0.3s ease;
        }
        
        .product-image:hover {
            transform: translateY(-5px);
        }
        
        @media (max-width: 768px) {
            .about-container {
                flex-direction: column;
            }
            
            .content-column, .image-column {
                width: 100%;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <section class="about-section">
        <h1 class="section-title">About Warychary</h1>
        
        <div class="about-container">
            <div class="content-column">
                <h2>Warychary Sanitary Pads with Smart Anion Chip – Ultimate Comfort & Protection</h2>
                
                <p>Experience unparalleled comfort and advanced protection with Warychary Sanitary Pads, now featuring a revolutionary Smart Anion Chip. Designed for the modern woman, these pads offer superior absorption, exceptional dryness, and an innovative anion layer to keep you feeling fresh and confident all day long.</p>
                
                
                <div class="why-choose">
                    <h3>Why Choose Warychary?</h3>
                    <p>At Warychary, we understand the importance of feminine hygiene and comfort. Our sanitary pads with Smart Anion Chip are meticulously designed to provide women with a reliable, comfortable, and hygienic solution during their menstrual cycle. Prioritize your well-being with Warychary – the smart choice for modern women.</p>
                </div>
            </div>
            
            <div class="image-column">
                <!-- Replace with your actual image path -->
                <img src="images/frontend/h-1-about.png" alt="Warychary Sanitary Pads with Smart Anion Chip" class="product-image">
            </div>
        </div>
    </section>
</body>
</html>