<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Partner - Warychary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            color: #333;
            line-height: 1.6;
        }
        
        .partner-section {
            max-width: 1200px;
            margin: 80px auto;
            padding: 0 20px;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title {
            font-size: 2.8rem;
            color: #8a2be2;
            margin-bottom: 15px;
            font-weight: 800;
            background: linear-gradient(135deg, #8a2be2, #ff69b4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .partner-container {
            display: flex;
            flex-wrap: wrap;
            gap: 50px;
            align-items: center;
        }
        
        .content-column {
            flex: 1;
            min-width: 300px;
        }
        
        .steps-column {
            flex: 1;
            min-width: 300px;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-top: 30px;
        }
        
        .benefit-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(138, 43, 226, 0.1);
        }
        
        .benefit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(138, 43, 226, 0.15);
        }
        
        .benefit-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #8a2be2, #9b51e0);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.8rem;
        }
        
        .benefit-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .benefit-description {
            color: #666;
            font-size: 0.95rem;
        }
        
        .steps-container {
            position: relative;
        }
        
        .step {
            display: flex;
            margin-bottom: 40px;
            position: relative;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 35px;
            top: 70px;
            width: 2px;
            height: calc(100% + 20px);
            background: linear-gradient(to bottom, #8a2be2, #ff69b4);
            z-index: 1;
        }
        
        .step-number {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #8a2be2, #9b51e0);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin-right: 25px;
            flex-shrink: 0;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(138, 43, 226, 0.3);
        }
        
        .step-content {
            flex: 1;
            padding-top: 10px;
        }
        
        .step-title {
            font-size: 1.4rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .step-description {
            color: #666;
            margin-bottom: 15px;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 16px 35px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(39, 174, 96, 0.3);
            margin-top: 20px;
            text-align: center;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
        }
        
        .cta-button i {
            margin-left: 8px;
        }
        
        .partner-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 60px;
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .stat-item {
            flex: 1;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #8a2be2;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* Responsive Styles */
        @media (max-width: 1024px) {
            .benefits-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .partner-container {
                flex-direction: column;
                gap: 40px;
            }
            
            .section-title {
                font-size: 2.3rem;
            }
            
            .step {
                flex-direction: column;
                text-align: center;
            }
            
            .step-number {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            /* Hide connecting lines on mobile */
            .step:not(:last-child)::after {
                display: none;
            }
            
            .partner-stats {
                flex-direction: column;
                gap: 30px;
            }
        }
        
        @media (max-width: 480px) {
            .section-title {
                font-size: 2rem;
            }
            
            .section-subtitle {
                font-size: 1rem;
            }
            
            .step-title {
                font-size: 1.2rem;
            }
            
            .benefit-card {
                padding: 20px;
            }
            
            .cta-button {
                width: 100%;
                padding: 14px 25px;
            }
        }
    </style>
</head>
<body>
    <section class="partner-section">
        <div class="section-header">
            <h1 class="section-title">Become a Warychary Partner</h1>
            <p class="section-subtitle">Join our growing network of partners and start earning with India's leading feminine hygiene brand. Empower women while building your business.</p>
        </div>
        
        <div class="partner-container">
            <div class="content-column">
                <h2 style="color: #2c3e50; margin-bottom: 20px; font-size: 1.8rem;">Why Partner With Warychary?</h2>
                <p style="color: #666; margin-bottom: 25px;">As a Warychary partner, you become part of a mission to provide high-quality, innovative sanitary products to women across India. We offer competitive commissions, marketing support, and a trusted brand that customers love.</p>
                
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <h3 class="benefit-title">High Commissions</h3>
                        <p class="benefit-description">Earn attractive commissions on every sale with regular payout cycles.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h3 class="benefit-title">Business Growth</h3>
                        <p class="benefit-description">Access training and resources to scale your business effectively.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3 class="benefit-title">Brand Trust</h3>
                        <p class="benefit-description">Leverage the reputation of a trusted and recognized brand.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="bi bi-headset"></i>
                        </div>
                        <h3 class="benefit-title">Dedicated Support</h3>
                        <p class="benefit-description">Get dedicated partner support for all your business needs.</p>
                    </div>
                </div>
                
                <a href="https://warychary.com/become-a-partner.php" class="cta-button">
                    Start Your Partnership Journey <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <div class="steps-column">
                <div class="steps-container">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3 class="step-title">Visit Our Partner Page</h3>
                            <p class="step-description">Go to our official partner registration page to begin the process.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3 class="step-title">Fill Registration Form</h3>
                            <p class="step-description">Provide your basic details and business information in our simple form.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3 class="step-title">Get Verified</h3>
                            <p class="step-description">Our team will verify your details and approve your partnership.</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3 class="step-title">Start Earning</h3>
                            <p class="step-description">Access your partner dashboard and begin selling Warychary products.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="partner-stats">
            <div class="stat-item">
                <div class="stat-number">5000+</div>
                <div class="stat-label">Active Partners</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-number">₹2.5Cr+</div>
                <div class="stat-label">Partner Earnings</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-number">98%</div>
                <div class="stat-label">Satisfaction Rate</div>
            </div>
        </div>
    </section>
</body>
</html>