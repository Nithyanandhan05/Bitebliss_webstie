<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bite Bliss</title>
    <link rel="icon" type="image/png" href="img/logo_tag.png">
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .main-content {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .main-content h1 {
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 40px;
            color: #4a2c2a;
        }
        .faq-item {
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .faq-question {
            font-size: 1.3em;
            font-weight: bold;
            color: #6d4c41;
            cursor: pointer;
        }
        .faq-answer {
            margin-top: 10px;
            line-height: 1.7;
            color: #555;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>
    <br>
    <br>
    <br>
    <br>
    <main class="main-content">
        <h1>Frequently Asked Questions</h1>

        <div class="faq-item">
            <h3 class="faq-question">How do I place an order?</h3>
            <div class="faq-answer">
                <p>You can place an order directly through our website by adding items to your cart. You can also order by contacting us on WhatsApp at +91 8438425634.</p>
            </div>
        </div>

        <div class="faq-item">
            <h3 class="faq-question">Where do you deliver?</h3>
            <div class="faq-answer">
                <p>We currently deliver across Chennai. Please check our Shipping Policy page for more details on specific delivery zones and charges.</p>
            </div>
        </div>

        <div class="faq-item">
            <h3 class="faq-question">What is the shelf life of your brownies?</h3>
            <div class="faq-answer">
                <p>Our brownies are best enjoyed fresh! They can be stored in an airtight container at room temperature for up to 3 days, or in the refrigerator for up to a week.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <h3 class="faq-question">Are there any nuts in your brownies?</h3>
            <div class="faq-answer">
                <p>Some of our brownies, like the Walnut Crunch, contain nuts. All our products are prepared in a kitchen where nuts are present. If you have a severe allergy, please contact us before ordering.</p>
            </div>
        </div>

        <div class="faq-item">
            <h3 class="faq-question">Do you take bulk or corporate orders?</h3>
            <div class="faq-answer">
                <p>Yes, we do! We would love to be a part of your celebration or corporate event. Please contact us at support@bitebliss.shop for bulk order inquiries and special pricing.</p>
            </div>
        </div>

    </main>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="script.js"></script>


</body>
</html>