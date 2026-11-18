# Hloba Sneaker Wash
Management & Order Tracking System
A full-stack PHP and MySQL web application built to manage sneaker cleaning orders, staff workflows, inventory, and customer receipts.

## 🛠️ Tech Stack
* **Backend & Frontend:** PHP, HTML, CSS
* **Database:** MySQL / MariaDB (`hloba_sneaker_wash.sql`)
* **Local Server Environment:** XAMPP / Apache

## 📂 Key Features & Role-Based Dashboards
* **Manager Dashboard (`dashboard_manager.php`):** Oversees business operations, staff communications, expenditures, and inventory tracking.
* **Receptionist Dashboard (`dashboard_receptionist.php`):** Handles customer check-ins, order logging, and receipt generation (`print_receipt.php`).
* **Washer Dashboard (`dashboard_washer.php`):** Tracks active cleaning tasks and updates shoe status in real time.
* **Driver Dashboard (`dashboard_driver.php`):** Manages pickup and delivery logistics.
* **Customer Portal (`dashboard_customer.php`):** Allows clients to view their order progress and service history.

## 🚀 Setup Instructions
1. Import `hloba_sneaker_wash.sql` into your local MySQL server via **phpMyAdmin**.
2. Place the `wash` folder inside your local web server root directory (e.g., `htdocs` in XAMPP).
3. Update database credentials in `config.php` if necessary, then open `localhost/wash/` in your browser.
