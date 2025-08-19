<?php
session_start();
require_once './vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/.');
$dotenv->load();

// Check required session data
if (empty($_SESSION['payment_intent_id']) || empty($_SESSION['payment_details'])) {
    header("Location: checkout.php");
    exit();
}

$paymentMethod = $_SESSION['payment_method'];
$orderId = $_SESSION['payment_details']['order_id'];
$totalAmount = $_SESSION['payment_details']['total_amount'];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T2JQR66S');</script>
<!-- End Google Tag Manager -->

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Details | St. Joseph Fish Brokerage Inc.</title>

  <!-- Favicons -->
  <link rel="icon" href="./assets/icons/logo.ico" sizes="16x16 32x32" type="image/x-icon">
  <link rel="icon" href="./assets/icons/logo.svg" type="image/svg+xml">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
  <link rel="stylesheet" href="https://unpkg.com/aos@3.0.0-beta.6/dist/aos.css" />

  <!-- CSS Files -->
  <link href="style.css" rel="stylesheet">
  <link href="output.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
    <section id="payment-section" class="my-10">
        <div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto">
            <h1 class="text-center text-4xl font-bold">
                Complete Payment
            </h1>
            <p class="text-gray-600 text-center mb-10">
                Please complete your payment for Order #<?= htmlspecialchars($orderId) ?>
            </p>
            
            <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
                <?php switch($paymentMethod): 
                    case 'card': ?>
                        <div id="card-payment">
                            <div id="card-element" class="p-3 border rounded-lg mb-4"></div>
                            <button id="submit-payment" class="w-full py-3 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                                Pay with Card
                            </button>
                        </div>
                    <?php break; 
                    case 'gcash': ?>
                        <div id="gcash-payment" class="text-center">
                            <img src="/assets/gcash-logo.png" alt="GCash" class="h-16 mx-auto mb-4">
                            <p class="mb-4">Amount: ₱<?= number_format($totalAmount, 2) ?></p>
                            <button id="submit-payment" class="w-full py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Pay with GCash
                            </button>
                        </div>
                    <?php break;
                    case 'paymaya': ?>
                        <div id="paymaya-payment" class="text-center">
                            <img src="/assets/paymaya-logo.png" alt="PayMaya" class="h-16 mx-auto mb-4">
                            <p class="mb-4">Amount: ₱<?= number_format($totalAmount, 2) ?></p>
                            <button id="submit-payment" class="w-full py-3 px-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                Pay with PayMaya
                            </button>
                        </div>
                    <?php break;
                    case 'grab_pay': ?>
                        <div id="grabpay-payment" class="text-center">
                            <img src="/assets/grabpay-logo.png" alt="GrabPay" class="h-16 mx-auto mb-4">
                            <p class="mb-4">Amount: ₱<?= number_format($totalAmount, 2) ?></p>
                            <button id="submit-payment" class="w-full py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Pay with GrabPay
                            </button>
                        </div>
                    <?php break;
                endswitch; ?>
                
                <div id="payment-message" class="mt-4 hidden"></div>
            </div>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymongo = new Paymongo('<?= $_ENV['PAYMONGO_PUBLIC_KEY'] ?>');
        const paymentMethod = '<?= $paymentMethod ?>';
        const paymentForm = document.getElementById('<?= $paymentMethod ?>-payment');
        const submitButton = document.getElementById('submit-payment');
        const paymentMessage = document.getElementById('payment-message');
        
        submitButton.addEventListener('click', async function(e) {
            e.preventDefault();
            submitButton.disabled = true;
            paymentMessage.classList.add('hidden');
            
            try {
                let paymentMethodId;
                
                if (paymentMethod === 'card') {
                    // Handle card payment
                    const elements = paymongo.elements();
                    const cardElement = elements.create('card');
                    cardElement.mount('#card-element');
                    
                    const { paymentMethod: pm, error } = await paymongo.createPaymentMethod('card');
                    if (error) throw error;
                    paymentMethodId = pm.id;
                } else {
                    // For e-wallets, we'll create the payment method server-side
                    paymentMethodId = null;
                }
                
                // Process payment
                const response = await fetch('./functions/process_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        payment_method_id: paymentMethodId
                    })
                });
                
                const result = await response.json();
                
                if (result.error) {
                    throw new Error(result.error);
                }
                
                if (result.data.attributes.next_action) {
                    // Redirect for 3DS or e-wallet authentication
                    window.location.href = result.data.attributes.next_action.redirect.url;
                } else {
                    // Payment succeeded without additional actions
                    window.location.href = '/order_success.php';
                }
            } catch (error) {
                paymentMessage.textContent = error.message;
                paymentMessage.classList.remove('hidden');
                submitButton.disabled = false;
                console.error('Payment error:', error);
            }
        });
    });
    </script>
</body>
</html>