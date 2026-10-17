<?php
$pageTitle     = 'Shipping & Delivery Policy';
$policyUpdated = '17 October 2026';
require_once 'includes/shop.php';
require_once 'includes/policy_top.php';
?>

<p>We deliver across Kenya, and to Uganda and Tanzania on request.</p>

<h2>1. Delivery times</h2>
<ul>
    <li><strong>Nairobi:</strong> 2 to 4 business days after your payment is confirmed.</li>
    <li><strong>Rest of Kenya:</strong> 4 to 7 business days.</li>
    <li><strong>Uganda and Tanzania:</strong> we will confirm the time and cost with you on WhatsApp before dispatch.</li>
</ul>
<p>Orders are processed Monday to Saturday. Delivery may take longer during public holidays or busy sales periods; we will let you know if your order is delayed.</p>

<h2>2. Delivery fees</h2>
<ul>
    <li>Standard delivery within Kenya is <strong><?= formatPrice(SHIPPING_FEE) ?></strong>.</li>
    <li>Delivery is <strong>free</strong> on orders of <?= formatPrice(FREE_SHIPPING_THRESHOLD) ?> or more.</li>
    <li>The exact fee is always shown at checkout before you pay.</li>
</ul>

<h2>3. Tracking your order</h2>
<p>You can see the status of your order on your <a href="orders.php">My Orders</a> page. We will also contact you on the phone number you gave us when your order is on the way.</p>

<h2>4. Receiving your order</h2>
<ul>
    <li>Please make sure your phone number and address are correct, and that someone is available to receive the parcel.</li>
    <li>Check your parcel when it arrives. If anything is damaged or missing, tell us within 48 hours (see our <a href="returns.php">Returns &amp; Refunds Policy</a>).</li>
    <li>If a delivery fails because we cannot reach you, we will try to arrange another delivery; a second delivery may carry an extra fee.</li>
</ul>

<?php require_once 'includes/policy_bottom.php'; ?>
