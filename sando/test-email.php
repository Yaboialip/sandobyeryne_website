<?php
$to = "Rynssoo.oo@gmail.com";
$subject = "Test Email Hostinger";
$message = "Test email berhasil!";
$headers = "From: noreply@yourdomain.com";

if (mail($to, $subject, $message, $headers)) {
    echo "✅ Email berhasil dikirim!";
} else {
    echo "❌ Email gagal dikirim";
}
?>