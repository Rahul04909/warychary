<?php
// Include database connection
require_once __DIR__ . '/../database/config.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Fetch all products
try {
    $query = "SELECT * FROM products ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    $products = [];
}
?>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
    .products-section {
        padding: 60px 0;
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
        width: 100%;
        overflow: hidden;
        position: relative;
    }
    
    .products-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(138, 43, 226, 0.3), transparent);
    }
    
    .products-container {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 35px;
        padding: 0 25px;
        box-sizing: border-box;
    }
    
    .product-card {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border-radius: 20px;
        box-shadow: 
            0 5px 15px rgba(0,0,0,0.05),
            0 10px 30px rgba(138, 43, 226, 0.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        border: 1px solid rgba(138, 43, 226, 0.1);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
    }
    
    .product-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #8a2be2, #ff69b4, #27ae60);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .product-card:hover {
        transform: translateY(-12px);
        box-shadow: 
            0 15px 35px rgba(0,0,0,0.1),
            0 20px 50px rgba(138, 43, 226, 0.15);
    }
    
    .product-card:hover::before {
        opacity: 1;
    }
    
    .product-image-container {
        width: 100%;
        height: 300px;
        overflow: hidden;
        position: relative;
        background: linear-gradient(135deg, #f9f7ff, #f0ebff);
        flex-shrink: 0;
        padding: 25px;
        box-sizing: border-box;
    }
    
    .product-image-container::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: linear-gradient(transparent, rgba(255,255,255,0.8));
        z-index: 1;
    }
    
    .product-image-container img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
        transition: transform 0.5s ease;
        position: relative;
        z-index: 2;
        filter: drop-shadow(0 5px 15px rgba(0,0,0,0.1));
    }
    
    .product-card:hover .product-image-container img {
        transform: scale(1.08);
    }
    
    .product-details {
        padding: 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        background: #ffffff;
        position: relative;
        z-index: 2;
    }
    
    .product-name {
        font-size: 1.35rem;
        color: #2c3e50;
        margin-bottom: 15px;
        font-weight: 700;
        text-align: center;
        line-height: 1.4;
        letter-spacing: -0.01em;
        background: linear-gradient(135deg, #2c3e50, #4a6583);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .product-price {
        text-align: center;
        margin: 20px 0;
        position: relative;
    }
    
    .product-price::before {
        content: '';
        position: absolute;
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, #27ae60, #2ecc71);
        bottom: -12px;
        left: 50%;
        transform: translateX(-50%);
        border-radius: 2px;
    }
    
    .sales-price {
        font-size: 2rem;
        color: #27ae60;
        font-weight: 800;
        display: inline-block;
        position: relative;
        text-shadow: 0 2px 4px rgba(39, 174, 96, 0.2);
    }
    
    .sales-price::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 8px;
        background: rgba(39, 174, 96, 0.1);
        bottom: 2px;
        left: 0;
        border-radius: 4px;
        z-index: -1;
    }
    
    .card-bottom {
        margin-top: auto;
        border-top: 1px solid rgba(138, 43, 226, 0.1);
        padding-top: 25px;
        position: relative;
    }
    
    .free-gift-label {
        background: linear-gradient(135deg, #8a2be2, #9b51e0);
        color: white;
        padding: 12px 20px;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        margin: 0 auto 25px;
        display: table;
        text-align: center;
        max-width: 90%;
        word-wrap: break-word;
        box-shadow: 0 5px 15px rgba(138, 43, 226, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .free-gift-label::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        transform: rotate(45deg);
        animation: shine 3s infinite;
    }
    
    @keyframes shine {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    
    .free-gift-label i {
        margin-right: 8px;
        font-size: 1.1rem;
    }
    
    .button-group {
        display: flex;
        justify-content: center;
    }
    
    .btn {
        width: 85%;
        padding: 16px 25px;
        border: none;
        border-radius: 50px;
        font-size: 1.05rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        text-align: center;
        text-decoration: none;
        display: inline-block;
        white-space: nowrap;
        box-sizing: border-box;
        box-shadow: 0 6px 20px rgba(39, 174, 96, 0.3);
        letter-spacing: 0.5px;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }
    
    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #219a52, #27ae60, #2ecc71);
        z-index: -1;
        transition: opacity 0.4s ease;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(39, 174, 96, 0.4);
    }
    
    .btn-primary:hover::before {
        opacity: 0.9;
    }
    
    /* Premium badge for featured products */
    .premium-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        color: #8a6d00;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        z-index: 3;
        box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    /* Rating stars */
    .product-rating {
        display: flex;
        justify-content: center;
        margin: 15px 0;
        gap: 3px;
    }
    
    .rating-star {
        color: #ffc107;
        font-size: 1.1rem;
    }
    
    /* Tablet Styles */
    @media (max-width: 1024px) {
        .products-section {
            padding: 50px 0;
        }
        
        .products-container {
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            padding: 0 20px;
        }
        
        .product-image-container {
            height: 280px;
            padding: 20px;
        }
        
        .product-name {
            font-size: 1.25rem;
        }
        
        .sales-price {
            font-size: 1.8rem;
        }
    }
    
    /* Mobile Large Styles */
    @media (max-width: 768px) {
        .products-section {
            padding: 40px 0;
        }
        
        .products-container {
            grid-template-columns: 1fr;
            gap: 25px;
            padding: 0 15px;
        }
        
        .product-card {
            max-width: 100%;
        }
        
        .product-image-container {
            height: 250px;
            padding: 15px;
        }
        
        .product-details {
            padding: 20px;
        }
        
        .product-name {
            font-size: 1.2rem;
            margin-bottom: 12px;
        }
        
        .sales-price {
            font-size: 1.7rem;
        }
        
        .free-gift-label {
            font-size: 0.85rem;
            padding: 10px 16px;
            margin-bottom: 20px;
        }
        
        .button-group {
            gap: 10px;
        }
        
        .btn {
            padding: 14px 20px;
            font-size: 1rem;
            min-width: 120px;
        }
    }
    
    /* Mobile Small Styles */
    @media (max-width: 480px) {
        .products-section {
            padding: 30px 0;
        }
        
        .products-container {
            padding: 0 10px;
            gap: 20px;
        }
        
        .product-image-container {
            height: 220px;
            padding: 12px;
        }
        
        .product-details {
            padding: 18px;
        }
        
        .product-name {
            font-size: 1.15rem;
            margin-bottom: 10px;
        }
        
        .sales-price {
            font-size: 1.6rem;
        }
        
        .free-gift-label {
            font-size: 0.8rem;
            padding: 8px 14px;
            margin-bottom: 18px;
        }
        
        .button-group {
            flex-direction: column;
            gap: 10px;
        }
        
        .btn {
            width: 100%;
            min-width: auto;
            padding: 14px 18px;
            font-size: 0.95rem;
        }
        
        .card-bottom {
            padding-top: 20px;
        }
    }
    
    /* Extra Small Mobile Styles */
    @media (max-width: 360px) {
        .products-container {
            padding: 0 8px;
        }
        
        .product-image-container {
            height: 200px;
        }
        
        .product-details {
            padding: 15px;
        }
        
        .product-name {
            font-size: 1.1rem;
        }
        
        .sales-price {
            font-size: 1.5rem;
        }
        
        .free-gift-label {
            font-size: 0.75rem;
            padding: 7px 12px;
        }
    }
    
    /* Touch-friendly enhancements */
    @media (hover: none) and (pointer: coarse) {
        .product-card:hover {
            transform: none;
        }
        
        .btn:hover {
            transform: none;
        }
        
        .btn {
            min-height: 50px; /* Minimum touch target size */
        }
        
        .product-card {
            transition: none;
        }
    }
    
    /* High DPI displays */
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
        .product-image-container img {
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }
    }
</style>

<section class="products-section">
    <div class="products-container">
        <?php foreach($products as $product): ?>
            <div class="product-card">
                <!-- Premium Badge for featured products -->
                <?php if(isset($product['is_featured']) && $product['is_featured']): ?>
                    <div class="premium-badge">
                        <i class="bi bi-star-fill"></i> Premium
                    </div>
                <?php endif; ?>
                
                <div class="product-image-container">
                    <?php if(!empty($product['product_image'])): ?>
                        <img src="images/products/<?php echo htmlspecialchars($product['product_image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                             loading="lazy">
                    <?php endif; ?>
                </div>
                <div class="product-details">
                    <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                    
                    <!-- Product Rating (you can add this to your database) -->
                    <div class="product-rating">
                        <?php 
                        $rating = isset($product['rating']) ? $product['rating'] : 4.5;
                        $fullStars = floor($rating);
                        $hasHalfStar = $rating - $fullStars >= 0.5;
                        
                        for($i = 0; $i < $fullStars; $i++): ?>
                            <i class="bi bi-star-fill rating-star"></i>
                        <?php endfor; ?>
                        
                        <?php if($hasHalfStar): ?>
                            <i class="bi bi-star-half rating-star"></i>
                        <?php endif; ?>
                        
                        <?php 
                        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                        for($i = 0; $i < $emptyStars; $i++): ?>
                            <i class="bi bi-star rating-star"></i>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="product-price">
                        <span class="sales-price">₹<?php echo number_format($product['sales_price'], 2); ?></span>
                    </div>
                    <div class="card-bottom">
                        <?php if(!empty($product['offer_product_name'])): ?>
                            <div class="free-gift-label">
                                <i class="bi bi-gift-fill"></i> Free Gift: <?php echo htmlspecialchars($product['offer_product_name']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="button-group">
                            <a href="checkout.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-cart-plus"></i> Buy Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>