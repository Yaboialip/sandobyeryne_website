<?php
// checkout_handler.php - HOSTINGER OPTIMIZED & FIXED
// Form processing untuk Sando's by Eryne

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// KONFIGURASI - GANTI SESUAI DOMAIN HOSTINGER KAMU
$admin_email = "Rynssoo.oo@gmail.com"; 
$from_email = "noreply@sandobyeryne.com"; // GANTI dengan domain hostinger kamu yang benar
$subject = "🥪 Pesanan Baru - Sando's by Eryne";
$upload_dir = "uploads/transfer_proofs/";

// Buat folder upload jika belum ada
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal membuat folder upload: ' . error_get_last()['message']
        ]);
        exit;
    }
}

// Validasi method POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak valid - hanya POST yang diizinkan'
    ]);
    exit;
}

try {
    // Ambil data form dengan validasi
    $fullName = isset($_POST['fullName']) ? sanitize($_POST['fullName']) : '';
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
    $address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
    $city = isset($_POST['city']) ? sanitize($_POST['city']) : '';
    $postalCode = isset($_POST['postalCode']) ? sanitize($_POST['postalCode']) : '';
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
    $cartData = isset($_POST['cartData']) ? $_POST['cartData'] : '';

    // Validasi data wajib
    $errors = [];
    if (empty($fullName)) $errors[] = "Nama lengkap wajib diisi";
    if (empty($phone)) $errors[] = "Nomor HP wajib diisi";
    if (empty($address)) $errors[] = "Alamat wajib diisi";
    if (empty($city)) $errors[] = "Kota wajib diisi";
    if (empty($cartData)) $errors[] = "Data pesanan tidak valid";

    // Validasi file upload bukti transfer
    $uploaded_file_path = null;
    $uploaded_file_name = null;
    $file_size = 0;

    if (!isset($_FILES['paymentProof']) || $_FILES['paymentProof']['error'] == UPLOAD_ERR_NO_FILE) {
        $errors[] = "Bukti transfer wajib diupload";
    } else {
        $file = $_FILES['paymentProof'];
        
        if ($file['error'] != UPLOAD_ERR_OK) {
            $errors[] = "Error upload: " . getUploadError($file['error']);
        } else {
            // Validasi tipe file - gunakan ekstensi sebagai backup
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $errors[] = "Format file tidak valid. Gunakan JPG, PNG, GIF, atau WebP";
            }
            
            // Validasi ukuran (5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = "File terlalu besar. Maksimal 5MB";
            }
            
            // Jika valid, proses upload
            if (empty($errors)) {
                $safe_filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
                $new_filename = "transfer_" . date('YmdHis') . "_" . uniqid() . "." . $file_extension;
                $uploaded_file_path = $upload_dir . $new_filename;
                $uploaded_file_name = $file['name'];
                $file_size = $file['size'];
                
                if (!move_uploaded_file($file['tmp_name'], $uploaded_file_path)) {
                    $errors[] = "Gagal menyimpan bukti transfer: " . error_get_last()['message'];
                    $uploaded_file_path = null;
                }
            }
        }
    }

    // Return error jika ada
    if (!empty($errors)) {
        // Hapus file jika sudah diupload tapi ada error lain
        if ($uploaded_file_path && file_exists($uploaded_file_path)) {
            unlink($uploaded_file_path);
        }
        
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors),
            'errors' => $errors
        ]);
        exit;
    }

    // Decode dan validasi cart data
    $cart_items = json_decode($cartData, true);
    if (!$cart_items || !is_array($cart_items)) {
        echo json_encode([
            'success' => false,
            'message' => 'Data pesanan tidak valid: ' . json_last_error_msg()
        ]);
        exit;
    }

    // Hitung total dan buat detail pesanan
    $subtotal = 0;
    $order_details = "";
    $total_items = 0;

    foreach ($cart_items as $item) {
        if (!isset($item['name'], $item['price'], $item['quantity'])) continue;
        
        $price = (int) preg_replace('/[^0-9]/', '', $item['price']);
        $quantity = (int) $item['quantity'];
        $item_total = $price * $quantity;
        $subtotal += $item_total;
        $total_items += $quantity;
        
        $packs = ceil($quantity / 2);
        $order_details .= "• " . $item['name'] . "\n";
        $order_details .= "  - " . $quantity . " pieces (" . $packs . " pack" . ($packs > 1 ? 's' : '') . ")\n";
        $order_details .= "  - " . $item['price'] . " per piece\n";
        $order_details .= "  - Subtotal: Rp " . number_format($item_total, 0, ',', '.') . "\n\n";
    }

    if ($subtotal == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Pesanan kosong atau tidak valid'
        ]);
        exit;
    }

    // Buat content email
    $email_content = buildEmailContent(
        $fullName, $phone, $address, $city, $postalCode, 
        $order_details, $subtotal, $notes, $uploaded_file_name, $file_size, $total_items
    );

    // Kirim email dengan multiple methods
    $email_sent = false;
    $email_error = '';

    // Method 1: Try basic PHP mail first
    try {
        $email_sent = sendEmailBasic($admin_email, $from_email, $subject, $email_content, $uploaded_file_path, $uploaded_file_name);
    } catch (Exception $e) {
        $email_error .= "Basic mail failed: " . $e->getMessage() . "; ";
    }

    // Method 2: Try without attachment if failed
    if (!$email_sent) {
        try {
            $email_sent = sendEmailNoAttachment($admin_email, $from_email, $subject, $email_content . "\n\n⚠️ PERHATIAN: Bukti transfer tersimpan di server: " . $uploaded_file_name);
        } catch (Exception $e) {
            $email_error .= "No attachment mail failed: " . $e->getMessage() . "; ";
        }
    }

    // Response
    if ($email_sent) {
        echo json_encode([
            'success' => true,
            'message' => 'Pesanan berhasil dikirim! Cek email untuk konfirmasi. Terima kasih! 🥪❤️'
        ]);
    } else {
        // Log error untuk debugging
        error_log("Email send failed: " . $email_error);
        
        echo json_encode([
            'success' => false,
            'message' => 'Pesanan tersimpan tapi gagal mengirim email. Hubungi admin dengan screenshot ini. Error: ' . $email_error
        ]);
    }

} catch (Exception $e) {
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}

// ===== FUNCTIONS =====

function sanitize($data) {
    if ($data === null || $data === '') return '';
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

function getUploadError($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return "File terlalu besar";
        case UPLOAD_ERR_PARTIAL:
            return "Upload tidak sempurna";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Folder temporary tidak ada";
        case UPLOAD_ERR_CANT_WRITE:
            return "Gagal menulis file";
        case UPLOAD_ERR_EXTENSION:
            return "Upload dihentikan oleh ekstensi";
        default:
            return "Unknown error: " . $error_code;
    }
}

function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function buildEmailContent($name, $phone, $address, $city, $postal, $order_details, $total, $notes, $file_name, $file_size, $total_items) {
    $content = "🥪 PESANAN BARU - SANDO'S BY ERYNE\n";
    $content .= "================================================\n\n";
    
    $content .= "👤 INFORMASI PEMBELI:\n";
    $content .= "Nama: " . $name . "\n";
    $content .= "HP: " . $phone . "\n\n";
    
    $content .= "📍 ALAMAT PENGIRIMAN:\n";
    $content .= $address . "\n";
    $content .= $city;
    if (!empty($postal)) $content .= " " . $postal;
    $content .= "\n\n";
    
    $content .= "📦 DETAIL PESANAN:\n";
    $content .= $order_details;
    
    $content .= "💰 TOTAL PEMBAYARAN:\n";
    $content .= "Total Items: " . $total_items . " pieces\n";
    $content .= "TOTAL: Rp " . number_format($total, 0, ',', '.') . "\n\n";
    
    if (!empty($file_name)) {
        $content .= "📸 BUKTI TRANSFER: ✅ UPLOADED\n";
        $content .= "- File: " . $file_name . "\n";
        $content .= "- Size: " . formatFileSize($file_size) . "\n\n";
    }
    
    if (!empty($notes)) {
        $content .= "📝 CATATAN: " . $notes . "\n\n";
    }
    
    $content .= "⚠️ IMPORTANT NOTES:\n";
    $content .= "• Transfer Only - PO Only\n";
    $content .= "• 1 Pack = 2 pieces sandwich\n";
    $content .= "• Bukti transfer terlampir ✅\n";
    $content .= "• Konfirmasi dalam 24 jam\n\n";
    
    $content .= "📅 Order Time: " . date('d/m/Y H:i:s') . " WIB\n";
    $content .= "🌐 IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
    $content .= "🖥️ User Agent: " . substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 100) . "\n\n";
    
    $content .= "Segera proses pesanan ini ya! 🥪❤️";
    
    return $content;
}

function sendEmailBasic($to_email, $from_email, $subject, $message, $file_path = null, $file_name = null) {
    // Validasi email
    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address: " . $to_email);
    }

    // Jika tidak ada attachment, kirim email biasa
    if (!$file_path || !file_exists($file_path)) {
        return sendEmailNoAttachment($to_email, $from_email, $subject, $message);
    }
    
    // Kirim dengan attachment
    $boundary = "boundary_" . md5(uniqid(time()));
    
    // Headers yang lebih kompatibel dengan Hostinger
    $headers = "From: Sando's by Eryne <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "X-Priority: 1\r\n";
    
    // Email body
    $email_body = "--" . $boundary . "\r\n";
    $email_body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $email_body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $email_body .= $message . "\r\n\r\n";
    
    // File attachment
    if (file_exists($file_path) && is_readable($file_path)) {
        $file_content = file_get_contents($file_path);
        if ($file_content !== false) {
            $file_content = chunk_split(base64_encode($file_content));
            $file_type = 'application/octet-stream'; // Generic type untuk kompatibilitas
            
            // Detect image type
            $image_info = getimagesize($file_path);
            if ($image_info !== false) {
                $file_type = $image_info['mime'];
            }
            
            $email_body .= "--" . $boundary . "\r\n";
            $email_body .= "Content-Type: " . $file_type . "; name=\"" . basename($file_name) . "\"\r\n";
            $email_body .= "Content-Transfer-Encoding: base64\r\n";
            $email_body .= "Content-Disposition: attachment; filename=\"" . basename($file_name) . "\"\r\n\r\n";
            $email_body .= $file_content . "\r\n";
        }
    }
    
    $email_body .= "--" . $boundary . "--\r\n";
    
    // Kirim email dengan error checking
    $result = @mail($to_email, $subject, $email_body, $headers);
    
    if (!$result) {
        $last_error = error_get_last();
        throw new Exception("Mail function failed: " . ($last_error['message'] ?? 'Unknown error'));
    }
    
    return $result;
}

function sendEmailNoAttachment($to_email, $from_email, $subject, $message) {
    // Headers sederhana untuk kompatibilitas maksimum
    $headers = "From: Sando's by Eryne <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    $result = @mail($to_email, $subject, $message, $headers);
    
    if (!$result) {
        $last_error = error_get_last();
        throw new Exception("Simple mail failed: " . ($last_error['message'] ?? 'Unknown error'));
    }
    
    return $result;
}

// Additional debugging function
function logDebugInfo($message) {
    $log_file = 'debug_checkout.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Log this request for debugging
logDebugInfo("Checkout request received from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
?>