<p style="margin:0 0 6px;color:#9a94b8;font-size:14px">Hi <?= e(explode(' ', $name)[0]) ?>,</p>
<h1 style="margin:0 0 10px;font-size:24px;color:#fcee0a"><?= e($title) ?></h1>
<p style="margin:0 0 18px;font-size:15px;line-height:1.5;color:#ece9f8"><?= e($intro) ?></p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#110e20;border:1px solid #2c2552;margin-bottom:18px">
  <tr>
    <td style="padding:12px 14px;font-size:12px;color:#9a94b8">Order<br><b style="font-size:15px;color:#ece9f8"><?= e($order['order_number']) ?></b></td>
    <td style="padding:12px 14px;font-size:12px;color:#9a94b8">Tracking no.<br><b style="font-size:15px;color:#00f0ff"><?= e($order['tracking_number']) ?></b></td>
  </tr>
  <tr>
    <td style="padding:0 14px 12px;font-size:12px;color:#9a94b8">Status<br><b style="font-size:14px;color:#ece9f8"><?= e(status_label($order['status'])) ?></b></td>
    <td style="padding:0 14px 12px;font-size:12px;color:#9a94b8"><?= $order['status'] === 'delivered' ? 'Delivered' : 'Estimated delivery' ?><br><b style="font-size:14px;color:#ece9f8"><?= $order['status'] === 'cancelled' ? '—' : e($order['status'] === 'delivered' ? date('M j', strtotime((string) $order['delivered_at'])) : $eta) ?></b></td>
  </tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin-bottom:18px">
  <?php foreach ($items as $it): ?>
    <tr><td style="padding:8px 0;border-bottom:1px solid #2c2552;color:#ece9f8"><?= e($it['product_name']) ?> <span style="color:#9a94b8">× <?= (int) $it['quantity'] ?></span></td>
        <td align="right" style="padding:8px 0;border-bottom:1px solid #2c2552;color:#ece9f8;white-space:nowrap"><?= e(peso($it['line_total'])) ?></td></tr>
  <?php endforeach; ?>
  <tr><td style="padding:6px 0;color:#9a94b8">Shipping</td><td align="right" style="color:#ece9f8"><?= $order['shipping_fee'] > 0 ? e(peso($order['shipping_fee'])) : 'Free' ?></td></tr>
  <?php if ($order['discount'] > 0): ?><tr><td style="padding:6px 0;color:#9a94b8">Voucher</td><td align="right" style="color:#3dffa8">−<?= e(peso($order['discount'])) ?></td></tr><?php endif; ?>
  <tr><td style="padding:10px 0;font-weight:bold;color:#ece9f8">Total (<?= e(payment_label($order['payment_method'])) ?>)</td><td align="right" style="font-weight:bold;font-size:18px;color:#fcee0a"><?= e(peso($order['total'])) ?></td></tr>
</table>

<p style="margin:0 0 4px;font-size:13px;color:#9a94b8">Ship to: <?= e($order['recipient_name']) ?>, <?= e($order['city']) ?>, <?= e($order['province']) ?></p>
<p style="margin:22px 0 0"><a href="<?= e($cta) ?>" style="display:inline-block;background:#fcee0a;color:#0c0a17;text-decoration:none;font-weight:bold;padding:12px 20px"><?= e($ctaLabel) ?></a></p>
