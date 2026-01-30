<style>
    .hero-section {
        position: relative;
        width: 100%;
        height: 80vh; /* Adjust as needed */
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #000;
    }
    .hero-video {
        position: absolute;
        top: 50%;
        left: 50%;
        min-width: 100%;
        min-height: 100%;
        width: auto;
        height: auto;
        z-index: 0;
        -ms-transform: translateX(-50%) translateY(-50%);
        -moz-transform: translateX(-50%) translateY(-50%);
        -webkit-transform: translateX(-50%) translateY(-50%);
        transform: translateX(-50%) translateY(-50%);
        object-fit: cover; /* Ensure the video covers the entire area */
    }
    .hero-content {
        position: relative;
        z-index: 1;
        color: white;
        text-align: center;
        padding: 20px;
    }
    .hero-content h1 {
        font-size: 3em;
        margin-bottom: 10px;
    }
    .hero-content p {
        font-size: 1.2em;
    }

    @media (max-width: 768px) {
        .hero-section {
            height: 100vh; /* Make hero section full height on mobile */
        }
        .hero-content h1 {
            font-size: 2em;
        }
        .hero-content p {
            font-size: 1em;
        }
    }
</style>

<section class="hero-section">
    <video autoplay muted loop class="hero-video">
        <source src="https://cdn.shopify.com/videos/c/o/v/836ccc3023fe49a69beaa7696b634a01.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <!-- Optional: Add content over the video if needed -->
    <!--
    <div class="hero-content">
        <h1>Welcome to Sparkle</h1>
        <p>Experience comfort and sustainability</p>
    </div>
    -->
</section>