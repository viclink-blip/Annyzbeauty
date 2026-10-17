<?php
// $payOrder = unpaid order row, $payHeading = box title
?>
<div class="payment-alert">
    <div class="payment-alert-header">
        <i class="bi bi-credit-card-fill" style="color:#fff;font-size:22px;"></i>
        <h5><?= sanitize($payHeading) ?></h5>
    </div>
    <div class="payment-alert-body">
        <div class="amount-highlight">
            <div>
                <div class="label">Amount to Pay</div>
                <div class="amount"><?= formatPrice((float)$payOrder['total']) ?></div>
            </div>
            <div style="text-align:right;">
                <div class="label">Order Reference</div>
                <div style="font-size:1rem;font-weight:700;"><?= sanitize($payOrder['order_number']) ?></div>
            </div>
        </div>

        <p style="font-size:14px;color:#555;margin-bottom:20px;">
            Pay securely with Paystack using M-Pesa or your card. Your order is confirmed
            automatically as soon as the payment goes through.
        </p>

        <div class="text-center">
            <a href="<?= SITE_URL ?>/paystack_pay.php?order=<?= (int)$payOrder['id'] ?>" class="btn-paystack">
                <i class="bi bi-lock-fill"></i>
                Pay <?= formatPrice((float)$payOrder['total']) ?> with Paystack
            </a>
            <div style="font-size:12px;color:#999;margin-top:12px;">
                Need help? <a href="<?= sanitize(whatsappLink('Hi Annyzbeauty, I need help paying for order ' . $payOrder['order_number'] . '.')) ?>"
                              target="_blank" rel="noopener" style="color:#25D366;font-weight:600;">Chat with us on WhatsApp</a>
            </div>
        </div>
    </div>
</div>
