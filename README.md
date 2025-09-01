# Sando's by Eryne - E-commerce Website

A complete e-commerce solution for a Japanese-style fruit sandwich business, built for client order management and online sales.

## Project Overview

This project is a full-stack web application that handles the entire customer journey from product browsing to order completion. Built for a small food business specializing in Japanese fruit sandwiches (sandos), it streamlines the ordering process and automates order management.

## Features

### Frontend
- **Responsive Product Catalog** - Mobile-first design showcasing sandwich varieties
- **Interactive Shopping Cart** - Add/remove items with quantity controls and persistent storage
- **Multi-step Checkout Process** - Customer information, delivery details, and payment proof upload
- **Real-time Form Validation** - Client-side validation with user-friendly error messages
- **File Upload System** - Image upload for payment proof with drag-and-drop support
- **Animated UI Elements** - Custom CSS animations and micro-interactions

### Backend
- **Order Processing** - PHP-based form handling and data validation
- **Email Notifications** - Automated order confirmations sent to business owner
- **File Management** - Secure upload and storage of payment proof images
- **Error Handling** - Comprehensive error logging and user feedback
- **Data Sanitization** - Input validation and security measures

## Tech Stack

- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Backend:** PHP
- **Storage:** LocalStorage for cart persistence, server-side file storage
- **Email:** PHP mail function with attachment support
- **Styling:** Custom CSS with Google Fonts (Fredoka One)

## Key Technical Implementations

### Cart Management
```javascript
// Persistent cart storage with error handling
function saveCart() {
    try {
        localStorage.setItem('cart', JSON.stringify(cart));
    } catch (e) {
        console.log('localStorage not available');
    }
}
```

### File Upload with Validation
```php
// Secure file upload with type and size validation
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    $errors[] = "Format file tidak valid. Gunakan JPG, PNG, GIF, atau WebP";
}
```

### Responsive Design
- Mobile-first CSS Grid layout
- Touch-friendly interface elements
- Optimized for various screen sizes
- Landscape orientation support

## Project Structure

```
├── index.html              # Product catalog homepage
├── product_detail_page.html # Individual product pages
├── cart.html               # Shopping cart management
├── checkout.html           # Order form and payment
├── checkout_handler.php    # Server-side order processing
├── images/                 # Product images and assets
└── uploads/               # Payment proof storage
```

## Business Logic

- **PO (Pre-Order) System** - All orders are pre-orders, not ready stock
- **Jakarta Area Only** - Delivery limited to Jakarta and surrounding areas
- **Bank Transfer Payment** - Manual payment verification with proof upload
- **Pack System** - 1 pack contains 2 sandwich pieces
- **24-hour Confirmation** - Order processing within business day

## Installation & Setup

1. Clone the repository
2. Upload files to web server with PHP support
3. Configure email settings in `checkout_handler.php`
4. Update domain and file paths as needed
5. Create `uploads/transfer_proofs/` directory with proper permissions

## Configuration

Update these variables in `checkout_handler.php`:
```php
$admin_email = "your-email@domain.com";
$from_email = "noreply@yourdomain.com";
```

## Security Features

- Input sanitization and validation
- File type and size restrictions
- SQL injection prevention
- Cross-site scripting (XSS) protection
- Secure file upload handling

## Mobile Optimization

- Touch-friendly UI elements
- Responsive breakpoints for all device sizes
- iOS Safari viewport fixes
- Android keyboard optimization
- Landscape mode support

## Client Requirements Met

- Simple, user-friendly interface
- Complete order management workflow
- Mobile-responsive design
- Payment proof verification
- Automated email notifications
- Jakarta-specific delivery system

## Live Demo

https://sandobyeryne.com/

## Contact

Built as a client project for Sando's by Eryne. For inquiries about similar e-commerce solutions, feel free to reach out.

---

*This project demonstrates full-stack web development capabilities including responsive design, form processing, file handling, and business workflow integration.*
