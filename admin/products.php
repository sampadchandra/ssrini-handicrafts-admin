<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Ssrini Handicrafts</title>

    <style>
        /* =========================================================
           GLOBAL RESET
        ========================================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            width: 100%;
            min-height: 100%;
        }

        body {
            width: 100%;
            min-height: 100vh;
            font-family: Inter, Arial, sans-serif;
            background: #f7f5fb;
            color: #25212d;
            overflow-x: hidden;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        button {
            -webkit-tap-highlight-color: transparent;
        }

        /* =========================================================
           PAGE LAYOUT
        ========================================================= */
        .admin-layout {
            min-height: 100vh;
            width: 100%;
        }

        .products-main {
            min-height: 100vh;
            margin-left: 260px;
            width: calc(100% - 260px);
            position: relative;
        }

        /* =========================================================
           PRODUCTS PAGE
        ========================================================= */
        .products-page {
            min-height: 100vh;
            width: 100%;
            padding: 32px;
            position: relative;
            isolation: isolate;
            background: transparent !important;
        }

        /* =========================================================
           BACKGROUND ARTWORK
        ========================================================= */
        .products-page::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                linear-gradient(rgba(247, 245, 251, 0.70), rgba(247, 245, 251, 0.70)),
                url("../assets/images/jewellery-display.jpg");
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
            background-attachment: scroll;
            pointer-events: none;
            z-index: 0;
        }

        .products-page > * {
            position: relative;
            z-index: 1;
        }

        /* =========================================================
           SIDEBAR BASE SUPPORT
        ========================================================= */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border-right: 1px solid #eee8f3;
            box-shadow: 8px 0 30px rgba(35, 20, 50, 0.06);
            overflow: hidden;
        }

        .sidebar-brand {
            min-height: 78px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            border-bottom: 1px solid #f0ebf3;
            flex-shrink: 0;
        }

        .sidebar-brand h2 {
            font-size: 20px;
            font-weight: 700;
            color: #7627c9;
            white-space: nowrap;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 18px 14px 20px;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: #ddd4e5;
            border-radius: 10px;
        }

        .nav-section {
            margin-bottom: 18px;
        }

        .nav-section-title {
            padding: 0 12px;
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #9b929f;
        }

        .nav-item {
            width: 100%;
            min-height: 44px;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 12px;
            margin-bottom: 4px;
            border-radius: 10px;
            text-decoration: none;
            color: #5c5663;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .nav-item:hover {
            background: #f7f1fa;
            color: #7627c9;
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(118, 39, 201, 0.12), rgba(197, 43, 159, 0.08));
            color: #7627c9;
            font-weight: 700;
        }

        .nav-icon {
            width: 24px;
            min-width: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .nav-label {
            flex: 1;
        }

        .nav-arrow {
            margin-left: auto;
            font-size: 21px;
            line-height: 1;
            transition: transform 0.2s ease;
        }

        .nav-dropdown.active .nav-arrow {
            transform: rotate(90deg);
        }

        .nav-submenu {
            display: none;
            padding: 2px 0 5px 47px;
        }

        .nav-submenu.open {
            display: block;
        }

        .nav-submenu a {
            display: block;
            padding: 8px 10px;
            margin-bottom: 2px;
            border-radius: 8px;
            color: #77717f;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .nav-submenu a:hover {
            background: #f7f1fa;
            color: #7627c9;
        }

        .sidebar-footer {
            padding: 14px;
            border-top: 1px solid #f0ebf3;
            flex-shrink: 0;
        }

        .logout-link {
            color: #d43b4a;
        }

        .logout-link:hover {
            background: #fff0f1;
            color: #c92d3d;
        }

        /* =========================================================
           HEADER
        ========================================================= */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .page-title h1 {
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .page-title p {
            color: #77717f;
            font-size: 14px;
        }

        /* =========================================================
           ADD PRODUCT BUTTON
        ========================================================= */
        .add-product-btn {
            border: none;
            background: linear-gradient(135deg, #7627c9, #c52b9f);
            color: white;
            padding: 13px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 8px 18px rgba(118, 39, 201, 0.25);
            transition: all 0.25s ease;
        }

        .add-product-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(118, 39, 201, 0.32);
        }

        /* =========================================================
           FILTER BAR
        ========================================================= */
        .filter-panel {
            background: white;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 28px;
            box-shadow: 0 8px 25px rgba(30, 20, 50, 0.06);
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 14px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            height: 46px;
            border: 1px solid #e3dfea;
            border-radius: 10px;
            padding: 0 15px;
            outline: none;
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }

        .search-box input:focus {
            border-color: #9b48d1;
            box-shadow: 0 0 0 3px rgba(155, 72, 209, 0.1);
        }

        .filter-select {
            width: 100%;
            height: 46px;
            border: 1px solid #e3dfea;
            border-radius: 10px;
            padding: 0 12px;
            background: white;
            outline: none;
            cursor: pointer;
        }

        .filter-select:focus {
            border-color: #9b48d1;
            box-shadow: 0 0 0 3px rgba(155, 72, 209, 0.1);
        }

        /* =========================================================
           PRODUCT GRID
        ========================================================= */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
        }

        /* =========================================================
           PRODUCT CARD
        ========================================================= */
        .product-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(30, 20, 50, 0.07);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            position: relative;
            min-width: 0;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 35px rgba(30, 20, 50, 0.12);
        }

        /* =========================================================
           PRODUCT IMAGE
        ========================================================= */
        .product-image {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, #eee9f5, #f8f5fa);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .no-image {
            color: #999;
            font-size: 14px;
        }

        /* =========================================================
           CARD CONTENT
        ========================================================= */
        .product-content {
            padding: 18px;
        }

        .product-category {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #f1e6fa;
            color: #7b32bd;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
            overflow-wrap: anywhere;
        }

        .product-code {
            font-size: 12px;
            color: #8b8592;
            margin-bottom: 15px;
            overflow-wrap: anywhere;
        }

        /* =========================================================
           PRICE
        ========================================================= */
        .product-price-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;
            margin-bottom: 14px;
        }

        .product-price {
            font-size: 19px;
            font-weight: 700;
        }

        .discount-price {
            color: #9a939f;
            font-size: 13px;
            text-decoration: line-through;
        }

        /* =========================================================
           STOCK
        ========================================================= */
        .product-stock {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding-top: 13px;
            border-top: 1px solid #eeeaf1;
            font-size: 13px;
        }

        .stock-number {
            font-weight: 700;
            text-align: right;
        }

        .stock-good {
            color: #219653;
        }

        .stock-low {
            color: #e09b24;
        }

        .stock-out {
            color: #dc3545;
        }

        /* =========================================================
           CARD ACTIONS
        ========================================================= */
        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }

        .action-btn {
            flex: 1;
            padding: 9px;
            border-radius: 9px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .edit-btn {
            background: #f1e7f8;
            color: #7831b7;
        }

        .delete-btn {
            background: #fff0f1;
            color: #d43b4a;
        }

        /* =========================================================
           EMPTY STATE
        ========================================================= */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 70px 20px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(30, 20, 50, 0.06);
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #88818f;
            font-size: 14px;
        }

        /* =========================================================
           LOADING
        ========================================================= */
        .loading {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px;
            color: #777;
        }

        /* =========================================================
           MODAL
        ========================================================= */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(30, 20, 40, 0.48);
            backdrop-filter: blur(8px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .product-modal {
            width: min(760px, 100%);
            max-height: calc(100vh - 48px);
            overflow-y: auto;
            background: #ffffff;
            border-radius: 22px;
            box-shadow: 0 30px 80px rgba(25, 15, 40, 0.25);
            transform: translateY(25px) scale(0.97);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .product-modal {
            transform: translateY(0) scale(1);
        }

        /* =========================================================
           MODAL HEADER
        ========================================================= */
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding: 25px 28px;
            border-bottom: 1px solid #eeeaf2;
        }

        .modal-header h2 {
            font-size: 22px;
            margin-bottom: 5px;
        }

        .modal-header p {
            color: #89818f;
            font-size: 13px;
        }

        .modal-close-btn {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border: none;
            border-radius: 10px;
            background: #f6f3f8;
            color: #706976;
            font-size: 25px;
            line-height: 1;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-close-btn:hover {
            background: #eee8f2;
            transform: rotate(90deg);
        }

        /* =========================================================
           MODAL BODY
        ========================================================= */
        .modal-body {
            padding: 28px;
        }

        /* =========================================================
           FORM
        ========================================================= */
        .form-section {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #39323f;
        }

        .form-label span {
            color: #c52b9f;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 20px;
        }

        .form-grid.three-columns {
            grid-template-columns: repeat(3, 1fr);
        }

        .form-group {
            margin-bottom: 20px;
            min-width: 0;
        }

        .form-input {
            width: 100%;
            height: 45px;
            padding: 0 13px;
            border: 1px solid #e2dce7;
            border-radius: 10px;
            background: #fff;
            color: #302a36;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus {
            border-color: #9b48d1;
            box-shadow: 0 0 0 3px rgba(155, 72, 209, 0.1);
        }

        .form-textarea {
            height: auto;
            padding: 12px;
            resize: vertical;
            min-height: 100px;
        }

        /* =========================================================
           PRICE INPUT
        ========================================================= */
        .input-prefix {
            position: relative;
        }

        .input-prefix > span {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #77707d;
            font-weight: 600;
            z-index: 1;
        }

        .input-prefix .form-input {
            padding-left: 32px;
        }

        /* =========================================================
           IMAGE UPLOAD
        ========================================================= */
        .image-upload-box {
            display: flex;
            gap: 18px;
            padding: 16px;
            border: 1px dashed #d9cde1;
            border-radius: 14px;
            background: #faf8fc;
        }

        .image-preview {
            width: 110px;
            height: 110px;
            flex-shrink: 0;
            border-radius: 12px;
            background: #eee9f3;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #9b929f;
        }

        .image-preview span {
            font-size: 25px;
            margin-bottom: 5px;
        }

        .image-preview p {
            font-size: 10px;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-upload-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .upload-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .upload-description {
            font-size: 12px;
            color: #908896;
            margin-bottom: 12px;
        }

        .upload-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            padding: 8px 13px;
            border-radius: 8px;
            background: #f0e4f7;
            color: #7831b7;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .upload-btn:hover {
            transform: translateY(-1px);
            background: #e8d8f2;
        }

        /* =========================================================
           MODAL FOOTER
        ========================================================= */
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 20px 28px;
            border-top: 1px solid #eeeaf2;
            background: #fcfbfd;
        }

        .cancel-btn,
        .save-product-btn {
            min-width: 120px;
            height: 44px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .cancel-btn {
            background: #f1eef3;
            color: #5f5865;
        }

        .cancel-btn:hover {
            transform: translateY(-1px);
            background: #e9e4eb;
        }

        .save-product-btn {
            background: linear-gradient(135deg, #7627c9, #c52b9f);
            color: white;
            box-shadow: 0 7px 16px rgba(118, 39, 201, 0.22);
        }

        .save-product-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(118, 39, 201, 0.3);
        }

        .save-product-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* =========================================================
           LOADER
        ========================================================= */
        .button-loader {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* =========================================================
           TABLET
        ========================================================= */
        @media (max-width: 1100px) {
            .products-main {
                margin-left: 230px;
                width: calc(100% - 230px);
            }

            .sidebar {
                width: 230px;
            }

            .products-page {
                padding: 24px;
            }

            .filter-panel {
                grid-template-columns: 1fr 1fr;
            }

            .products-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* =========================================================
           MOBILE
        ========================================================= */
        @media (max-width: 768px) {
            body {
                overflow-x: hidden;
            }

            .products-main {
                margin-left: 0;
                width: 100%;
            }

            .sidebar {
                width: 280px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: 15px 0 40px rgba(20, 10, 30, 0.18);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .sidebar-mobile-overlay.active {
                display: block;
            }

            .products-page {
                padding: 76px 16px 20px;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
                margin-bottom: 20px;
            }

            .page-title {
                padding-left: 0;
            }

            .page-title h1 {
                font-size: 26px;
            }

            .page-title p {
                font-size: 13px;
            }

            .add-product-btn {
                width: 100%;
                min-height: 46px;
            }

            .filter-panel {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 14px;
                border-radius: 14px;
                margin-bottom: 20px;
            }

            .search-box input,
            .filter-select {
                height: 45px;
            }

            .products-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .product-card {
                border-radius: 15px;
            }

            .product-image {
                height: 230px;
            }

            .product-content {
                padding: 16px;
            }

            .product-name {
                font-size: 17px;
            }

            .modal-overlay {
                padding: 10px;
            }

            .product-modal {
                max-height: calc(100vh - 20px);
                border-radius: 17px;
            }

            .modal-header {
                padding: 18px 20px;
            }

            .modal-header h2 {
                font-size: 19px;
            }

            .modal-body {
                padding: 20px;
            }

            .form-grid,
            .form-grid.three-columns {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .image-upload-box {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .image-upload-content {
                align-items: center;
            }

            .modal-footer {
                padding: 15px 20px;
                gap: 8px;
            }

            .cancel-btn,
            .save-product-btn {
                flex: 1;
                min-width: 0;
            }
        }

        /* =========================================================
           SMALL MOBILE
        ========================================================= */
        @media (max-width: 420px) {
            .products-page {
                padding: 72px 12px 16px;
            }

            .page-title h1 {
                font-size: 24px;
            }

            .product-image {
                height: 210px;
            }

            .product-actions {
                gap: 6px;
            }

            .action-btn {
                padding: 10px 6px;
            }

            .modal-header {
                padding: 16px;
            }

            .modal-body {
                padding: 16px;
            }

            .modal-footer {
                padding: 14px 16px;
            }
        }
    </style>
</head>

<body>

<?php
/*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
|
| IMPORTANT:
| sidebar.php should be inside:
| includes/sidebar.php
|
*/
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- =========================================================
     MOBILE SIDEBAR BUTTON
========================================================= -->
<!-- <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu">
    ☰
</button> -->

<!-- =========================================================
     MOBILE SIDEBAR OVERLAY
========================================================= -->
<div class="sidebar-mobile-overlay" id="sidebarMobileOverlay"></div>

<!-- =========================================================
     MAIN CONTENT
========================================================= -->
<main class="products-main">
    <div class="products-page">

        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->
        <header class="page-header">
            <div class="page-title">
                <h1>Products</h1>
                <p>Manage your products and inventory</p>
            </div>

            <button type="button" class="add-product-btn" id="addProductBtn">
                + Add Product
            </button>
        </header>

        <!-- =====================================================
             FILTER PANEL
        ====================================================== -->
        <section class="filter-panel">
            <div class="search-box">
                <input type="search" id="searchInput" placeholder="Search products..." autocomplete="off">
            </div>

            <select id="categoryFilter" class="filter-select">
                <option value="">All Categories</option>
            </select>

            <select id="stockFilter" class="filter-select">
                <option value="">All Stock</option>
                <option value="in_stock">In Stock</option>
                <option value="low">Low Stock</option>
                <option value="out">Out of Stock</option>
            </select>

            <select id="sortFilter" class="filter-select">
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
                <option value="name_asc">Name: A to Z</option>
                <option value="name_desc">Name: Z to A</option>
                <option value="stock_low">Stock: Low to High</option>
                <option value="stock_high">Stock: High to Low</option>
            </select>
        </section>

        <!-- =====================================================
             PRODUCT GRID
        ====================================================== -->
        <section class="products-grid" id="productsGrid">
            <div class="loading">Loading products...</div>
        </section>

        <!-- =====================================================
             ADD PRODUCT MODAL
        ====================================================== -->
        <div class="modal-overlay" id="addProductModal" aria-hidden="true">
            <div class="product-modal" role="dialog" aria-modal="true" aria-labelledby="addProductModalTitle">
                <div class="modal-header">
                    <div>
                        <h2 id="addProductModalTitle">Add New Product</h2>
                        <p>Add a new product to your inventory</p>
                    </div>

                    <button type="button" class="modal-close-btn" id="closeProductModal" aria-label="Close">
                        &times;
                    </button>
                </div>

                <form id="addProductForm" enctype="multipart/form-data">
                    <div class="modal-body">

                        <!-- PRODUCT IMAGE -->
                        <div class="form-section">
                            <label class="form-label">Product Image</label>
                            <div class="image-upload-box">
                                <div class="image-preview" id="imagePreview">
                                    <span>📷</span>
                                    <p>No image selected</p>
                                </div>

                                <div class="image-upload-content">
                                    <p class="upload-title">Upload product image</p>
                                    <p class="upload-description">JPG, PNG or WEBP</p>

                                    <label for="productImage" class="upload-btn">Choose Image</label>
                                    <input type="file" id="productImage" name="image" accept="image/jpeg,image/png,image/webp" hidden>
                                </div>
                            </div>
                        </div>

                        <!-- BASIC INFORMATION -->
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="productName" class="form-label">Product Name <span>*</span></label>
                                <input type="text" id="productName" name="name" class="form-input" placeholder="Enter product name" maxlength="150" required>
                            </div>

                            <div class="form-group">
                                <label for="productCode" class="form-label">Product Code / SKU <span>*</span></label>
                                <input type="text" id="productCode" name="product_code" class="form-input" placeholder="e.g. BAG001" maxlength="100" required>
                            </div>

                            <div class="form-group">
                                <label for="productCategory" class="form-label">Category <span>*</span></label>
                                <select id="productCategory" name="category_id" class="form-input" required>
                                    <option value="">Select category</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="productStatus" class="form-label">Status</label>
                                <select id="productStatus" name="status" class="form-input">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="form-group">
                            <label for="productDescription" class="form-label">Description</label>
                            <textarea id="productDescription" name="description" class="form-input form-textarea" placeholder="Describe your product..." rows="4"></textarea>
                        </div>

                        <!-- PRICE / STOCK -->
                        <div class="form-grid three-columns">
                            <div class="form-group">
                                <label for="productPrice" class="form-label">Price <span>*</span></label>
                                <div class="input-prefix">
                                    <span>₹</span>
                                    <input type="number" id="productPrice" name="price" class="form-input" placeholder="0.00" min="0" step="0.01" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="discountPrice" class="form-label">Discount Price</label>
                                <div class="input-prefix">
                                    <span>₹</span>
                                    <input type="number" id="discountPrice" name="discount_price" class="form-input" placeholder="Optional" min="0" step="0.01">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="stockQuantity" class="form-label">Stock Quantity <span>*</span></label>
                                <input type="number" id="stockQuantity" name="stock_quantity" class="form-input" placeholder="0" min="0" step="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="cancel-btn" id="cancelProductModal">Cancel</button>
                        <button type="submit" class="save-product-btn" id="saveProductBtn">
                            <span id="saveProductText">Add Product</span>
                            <span id="saveProductLoader" class="button-loader" hidden></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =====================================================
             EDIT PRODUCT MODAL
        ====================================================== -->
        <div class="modal-overlay" id="editProductModal" aria-hidden="true">
            <div class="product-modal" role="dialog" aria-modal="true" aria-labelledby="editProductModalTitle">
                <div class="modal-header">
                    <div>
                        <h2 id="editProductModalTitle">Edit Product</h2>
                        <p>Update your product information</p>
                    </div>

                    <button type="button" class="modal-close-btn" id="closeEditProductModal" aria-label="Close">
                        &times;
                    </button>
                </div>

                <form id="editProductForm" enctype="multipart/form-data">
                    <input type="hidden" id="editProductId" name="id">

                    <div class="modal-body">

                        <!-- IMAGE -->
                        <div class="form-section">
                            <label class="form-label">Product Image</label>
                            <div class="image-upload-box">
                                <div class="image-preview" id="editImagePreview">
                                    <span>📷</span>
                                    <p>No image</p>
                                </div>

                                <div class="image-upload-content">
                                    <p class="upload-title">Change product image</p>
                                    <p class="upload-description">JPG, PNG or WEBP</p>

                                    <label for="editProductImage" class="upload-btn">Choose Image</label>
                                    <input type="file" id="editProductImage" name="image" accept="image/jpeg,image/png,image/webp" hidden>
                                </div>
                            </div>
                        </div>

                        <!-- BASIC INFORMATION -->
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="editProductName" class="form-label">Product Name <span>*</span></label>
                                <input type="text" id="editProductName" name="name" class="form-input" maxlength="150" required>
                            </div>

                            <div class="form-group">
                                <label for="editProductCode" class="form-label">Product Code / SKU <span>*</span></label>
                                <input type="text" id="editProductCode" name="product_code" class="form-input" maxlength="100" required>
                            </div>

                            <div class="form-group">
                                <label for="editProductCategory" class="form-label">Category <span>*</span></label>
                                <select id="editProductCategory" name="category_id" class="form-input" required>
                                    <option value="">Select category</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="editProductStatus" class="form-label">Status</label>
                                <select id="editProductStatus" name="status" class="form-input">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="form-group">
                            <label for="editProductDescription" class="form-label">Description</label>
                            <textarea id="editProductDescription" name="description" class="form-input form-textarea" rows="4"></textarea>
                        </div>

                        <!-- PRICE / STOCK -->
                        <div class="form-grid three-columns">
                            <div class="form-group">
                                <label for="editProductPrice" class="form-label">Price <span>*</span></label>
                                <div class="input-prefix">
                                    <span>₹</span>
                                    <input type="number" id="editProductPrice" name="price" class="form-input" min="0" step="0.01" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="editDiscountPrice" class="form-label">Discount Price</label>
                                <div class="input-prefix">
                                    <span>₹</span>
                                    <input type="number" id="editDiscountPrice" name="discount_price" class="form-input" min="0" step="0.01">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="editStockQuantity" class="form-label">Stock Quantity <span>*</span></label>
                                <input type="number" id="editStockQuantity" name="stock_quantity" class="form-input" min="0" step="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="cancel-btn" id="cancelEditProductModal">Cancel</button>
                        <button type="submit" class="save-product-btn" id="updateProductBtn">
                            <span id="updateProductText">Update Product</span>
                            <span id="updateProductLoader" class="button-loader" hidden></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</main>

<script>
/*
|--------------------------------------------------------------------------
| MOBILE SIDEBAR
|--------------------------------------------------------------------------
*/
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
// FIX 2: Admin Sidebar selector with fallback class
const adminSidebar = document.getElementById('adminSidebar') || document.querySelector('.sidebar');
const sidebarMobileOverlay = document.getElementById('sidebarMobileOverlay');

function openMobileSidebar() {
    if (!adminSidebar) return;
    adminSidebar.classList.add('mobile-open');
    sidebarMobileOverlay.classList.add('active');
}

function closeMobileSidebar() {
    if (!adminSidebar) return;
    adminSidebar.classList.remove('mobile-open');
    sidebarMobileOverlay.classList.remove('active');
}

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', function () {
        if (adminSidebar && adminSidebar.classList.contains('mobile-open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    });
}

if (sidebarMobileOverlay) {
    sidebarMobileOverlay.addEventListener('click', closeMobileSidebar);
}

/*
|--------------------------------------------------------------------------
| CLOSE MOBILE SIDEBAR AFTER NAVIGATION
|--------------------------------------------------------------------------
*/
if (adminSidebar) {
    adminSidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            closeMobileSidebar();
        });
    });
}

/*
|--------------------------------------------------------------------------
| PRODUCTS DOM ELEMENTS
|--------------------------------------------------------------------------
*/
const productsGrid = document.getElementById('productsGrid');
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const stockFilter = document.getElementById('stockFilter');
const sortFilter = document.getElementById('sortFilter');
const addProductBtn = document.getElementById('addProductBtn');
const addProductModal = document.getElementById('addProductModal');
const closeProductModal = document.getElementById('closeProductModal');
const cancelProductModal = document.getElementById('cancelProductModal');
const addProductForm = document.getElementById('addProductForm');
const productImage = document.getElementById('productImage');
const imagePreview = document.getElementById('imagePreview');
const editProductModal = document.getElementById('editProductModal');
const closeEditProductModal = document.getElementById('closeEditProductModal');
const cancelEditProductModal = document.getElementById('cancelEditProductModal');
const editProductForm = document.getElementById('editProductForm');
const editProductId = document.getElementById('editProductId');
const editProductImage = document.getElementById('editProductImage');
const editImagePreview = document.getElementById('editImagePreview');
const editProductName = document.getElementById('editProductName');
const editProductCode = document.getElementById('editProductCode');
const editProductCategory = document.getElementById('editProductCategory');
const editProductStatus = document.getElementById('editProductStatus');
const editProductDescription = document.getElementById('editProductDescription');
const editProductPrice = document.getElementById('editProductPrice');
const editDiscountPrice = document.getElementById('editDiscountPrice');
const editStockQuantity = document.getElementById('editStockQuantity');

/*
|--------------------------------------------------------------------------
| LOAD PRODUCTS
|--------------------------------------------------------------------------
*/
async function loadProducts() {
    productsGrid.innerHTML = `
        <div class="loading">
            Loading products...
        </div>
    `;

    const params = new URLSearchParams();
    const search = searchInput.value.trim();
    const category = categoryFilter.value;
    const stock = stockFilter.value;
    const sort = sortFilter.value;

    if (search !== '') params.set('search', search);
    if (category !== '') params.set('category_id', category);
    if (stock !== '') params.set('stock', stock);
    params.set('sort', sort);

    try {
        const response = await fetch('../api/products.php?' + params.toString());
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Unable to load products.');
        }

        renderProducts(result.data);
    } catch (error) {
        console.error(error);
        productsGrid.innerHTML = `
            <div class="empty-state">
                <h3>Unable to load products</h3>
                <p>Please refresh the page and try again.</p>
            </div>
        `;
    }
}

/*
|--------------------------------------------------------------------------
| RENDER PRODUCTS
|--------------------------------------------------------------------------
*/
function renderProducts(products) {
    if (!products.length) {
        productsGrid.innerHTML = `
            <div class="empty-state">
                <h3>No Products Found</h3>
                <p>There are currently no products matching your search.</p>
            </div>
        `;
        return;
    }

    productsGrid.innerHTML = products.map(product => {
        const stock = Number(product.stock_quantity);
        let stockClass = 'stock-good';
        let stockText = 'In Stock';

        if (stock === 0) {
            stockClass = 'stock-out';
            stockText = 'Out of Stock';
        } else if (stock <= 5) {
            stockClass = 'stock-low';
            stockText = 'Low Stock';
        }

        const imageHTML = product.image
            ? `<img src="../assets/uploads/${escapeHTML(product.image)}" alt="${escapeHTML(product.name)}">`
            : `<span class="no-image">No Image</span>`;

        const discountHTML = (product.discount_price !== null && product.discount_price !== '')
            ? `<span class="discount-price">₹${Number(product.price).toLocaleString('en-IN')}</span>`
            : '';

        const displayPrice = (product.discount_price !== null && product.discount_price !== '')
            ? product.discount_price
            : product.price;

        return `
            <article class="product-card">
                <div class="product-image">
                    ${imageHTML}
                </div>

                <div class="product-content">
                    <span class="product-category">
                        ${escapeHTML(product.category_name || 'Uncategorized')}
                    </span>

                    <h2 class="product-name">
                        ${escapeHTML(product.name)}
                    </h2>

                    <p class="product-code">
                        Code: ${escapeHTML(product.product_code)}
                    </p>

                    <div class="product-price-row">
                        <span class="product-price">
                            ₹${Number(displayPrice).toLocaleString('en-IN')}
                        </span>
                        ${discountHTML}
                    </div>

                    <div class="product-stock">
                        <span>Stock</span>
                        <span class="stock-number ${stockClass}">
                            ${stock} — ${stockText}
                        </span>
                    </div>

                    <div class="product-actions">
                        <button type="button" class="action-btn edit-btn" data-id="${product.id}">
                            Edit
                        </button>
                        <button type="button" class="action-btn delete-btn" data-id="${product.id}">
                            Delete
                        </button>
                    </div>
                </div>
            </article>
        `;
    }).join('');
}

/*
|--------------------------------------------------------------------------
| HTML ESCAPE
|--------------------------------------------------------------------------
*/
function escapeHTML(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/*
|--------------------------------------------------------------------------
| FILTER EVENTS
|--------------------------------------------------------------------------
*/
searchInput.addEventListener('input', loadProducts);
categoryFilter.addEventListener('change', loadProducts);
stockFilter.addEventListener('change', loadProducts);
sortFilter.addEventListener('change', loadProducts);

/*
|--------------------------------------------------------------------------
| INITIAL LOAD
|--------------------------------------------------------------------------
*/
// FIX 1: Categories dropdown loaded at page startup for the top filter bar
loadCategories();
loadProducts();

/*
|--------------------------------------------------------------------------
| ADD PRODUCT MODAL
|--------------------------------------------------------------------------
*/
addProductBtn.addEventListener('click', function () {
    addProductModal.classList.add('active');
    addProductModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    loadCategories();
});

/*
|--------------------------------------------------------------------------
| CLOSE ADD MODAL
|--------------------------------------------------------------------------
*/
function closeAddProductModal() {
    addProductModal.classList.remove('active');
    addProductModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    addProductForm.reset();
    imagePreview.innerHTML = `
        <span>📷</span>
        <p>No image selected</p>
    `;
}

closeProductModal.addEventListener('click', closeAddProductModal);
cancelProductModal.addEventListener('click', closeAddProductModal);

/*
|--------------------------------------------------------------------------
| CLICK OUTSIDE ADD MODAL
|--------------------------------------------------------------------------
*/
addProductModal.addEventListener('click', function (event) {
    if (event.target === addProductModal) {
        closeAddProductModal();
    }
});

/*
|--------------------------------------------------------------------------
| ESC KEY
|--------------------------------------------------------------------------
*/
document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;

    if (addProductModal.classList.contains('active')) {
        closeAddProductModal();
        return;
    }

    if (editProductModal.classList.contains('active')) {
        closeEditModal();
    }
});

/*
|--------------------------------------------------------------------------
| IMAGE PREVIEW
|--------------------------------------------------------------------------
*/
productImage.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) {
        imagePreview.innerHTML = `
            <span>📷</span>
            <p>No image selected</p>
        `;
        return;
    }

    const reader = new FileReader();
    reader.onload = function (event) {
        imagePreview.innerHTML = `
            <img src="${event.target.result}" alt="Product preview">
        `;
    };
    reader.readAsDataURL(file);
});

/*
|--------------------------------------------------------------------------
| LOAD CATEGORIES
|--------------------------------------------------------------------------
*/
async function loadCategories() {
    try {
        const response = await fetch('../api/categories.php');
        const result = await response.json();

        if (!result.success) {
            throw new Error('Unable to load categories.');
        }

        const currentFilterValue = categoryFilter.value;
        categoryFilter.innerHTML = `<option value="">All Categories</option>`;
        const productCategory = document.getElementById('productCategory');
        productCategory.innerHTML = `<option value="">Select category</option>`;

        result.data.forEach(function (category) {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            productCategory.appendChild(option);

            const filterOption = document.createElement('option');
            filterOption.value = category.id;
            filterOption.textContent = category.name;
            categoryFilter.appendChild(filterOption);
        });

        if (currentFilterValue) {
            categoryFilter.value = currentFilterValue;
        }
    } catch (error) {
        console.error(error);
    }
}

/*
|--------------------------------------------------------------------------
| CREATE PRODUCT
|--------------------------------------------------------------------------
*/
addProductForm.addEventListener('submit', async function (event) {
    event.preventDefault();

    const saveProductBtn = document.getElementById('saveProductBtn');
    const saveProductText = document.getElementById('saveProductText');
    const saveProductLoader = document.getElementById('saveProductLoader');

    saveProductBtn.disabled = true;
    saveProductText.textContent = 'Adding...';
    saveProductLoader.hidden = false;

    try {
        const formData = new FormData(addProductForm);
        const response = await fetch('../api/products-create.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Unable to create product.');
        }

        alert(result.message || 'Product added successfully.');
        closeAddProductModal();
        loadProducts();
    } catch (error) {
        console.error(error);
        alert(error.message || 'Something went wrong.');
    } finally {
        saveProductBtn.disabled = false;
        saveProductText.textContent = 'Add Product';
        saveProductLoader.hidden = true;
    }
});

/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT
|--------------------------------------------------------------------------
*/
editProductForm.addEventListener('submit', async function (event) {
    event.preventDefault();

    const updateProductBtn = document.getElementById('updateProductBtn');
    const updateProductText = document.getElementById('updateProductText');
    const updateProductLoader = document.getElementById('updateProductLoader');

    updateProductBtn.disabled = true;
    updateProductText.textContent = 'Updating...';
    updateProductLoader.hidden = false;

    try {
        const formData = new FormData(editProductForm);
        const response = await fetch('../api/products-update.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Unable to update product.');
        }

        alert(result.message || 'Product updated successfully.');
        closeEditModal();
        loadProducts();
    } catch (error) {
        console.error(error);
        alert(error.message || 'Something went wrong.');
    } finally {
        updateProductBtn.disabled = false;
        updateProductText.textContent = 'Update Product';
        updateProductLoader.hidden = true;
    }
});

/*
|--------------------------------------------------------------------------
| OPEN EDIT PRODUCT MODAL
|--------------------------------------------------------------------------
*/
async function openEditProductModal(productId) {
    try {
        const response = await fetch(`../api/products.php?id=${encodeURIComponent(productId)}`);
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Unable to load product.');
        }

        let product = result.data;
        if (Array.isArray(product)) {
            product = product[0];
        }

        if (!product) {
            throw new Error('Product not found.');
        }

        editProductId.value = product.id;
        editProductName.value = product.name || '';
        editProductCode.value = product.product_code || '';
        editProductCategory.innerHTML = `<option value="">Select category</option>`;

        const categoryResponse = await fetch('../api/categories.php');
        const categoryResult = await categoryResponse.json();

        if (!categoryResult.success) {
            throw new Error('Unable to load categories.');
        }

        categoryResult.data.forEach(function (category) {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            editProductCategory.appendChild(option);
        });

        editProductCategory.value = product.category_id || '';
        editProductStatus.value = product.status || 'active';
        editProductDescription.value = product.description || '';
        editProductPrice.value = product.price ?? '';
        editDiscountPrice.value = product.discount_price ?? '';
        editStockQuantity.value = product.stock_quantity ?? '';

        if (product.image) {
            editImagePreview.innerHTML = `
                <img src="../assets/uploads/${escapeHTML(product.image)}" alt="${escapeHTML(product.name)}">
            `;
        } else {
            editImagePreview.innerHTML = `
                <span>📷</span>
                <p>No image</p>
            `;
        }

        editProductModal.classList.add('active');
        editProductModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    } catch (error) {
        console.error(error);
        alert(error.message || 'Unable to load product.');
    }
}

/*
|--------------------------------------------------------------------------
| EDIT BUTTON CLICK
|--------------------------------------------------------------------------
*/
productsGrid.addEventListener('click', function (event) {
    const editButton = event.target.closest('.edit-btn');
    if (!editButton) return;

    const productId = editButton.dataset.id;
    if (!productId) {
        alert('Product ID not found.');
        return;
    }

    openEditProductModal(productId);
});

/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/
productsGrid.addEventListener('click', async function (event) {
    const deleteButton = event.target.closest('.delete-btn');
    if (!deleteButton) return;

    const productId = deleteButton.dataset.id;
    if (!productId) {
        alert('Product ID not found.');
        return;
    }

    const confirmed = confirm('Are you sure you want to delete this product?');
    if (!confirmed) return;

    deleteButton.disabled = true;
    deleteButton.textContent = 'Deleting...';

    try {
        const formData = new FormData();
        formData.append('id', productId);

        // FIX 3: Updated URL to products-delete.php for consistent naming
        const response = await fetch('../api/products-delete.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Unable to delete product.');
        }

        alert(result.message || 'Product deleted successfully.');
        loadProducts();
    } catch (error) {
        console.error(error);
        alert(error.message || 'Something went wrong while deleting the product.');
        deleteButton.disabled = false;
        deleteButton.textContent = 'Delete';
    }
});

/*
|--------------------------------------------------------------------------
| CLOSE EDIT MODAL
|--------------------------------------------------------------------------
*/
function closeEditModal() {
    editProductModal.classList.remove('active');
    editProductModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    editProductForm.reset();
    editImagePreview.innerHTML = `
        <span>📷</span>
        <p>No image</p>
    `;
}

closeEditProductModal.addEventListener('click', closeEditModal);
cancelEditProductModal.addEventListener('click', closeEditModal);

/*
|--------------------------------------------------------------------------
| CLICK OUTSIDE EDIT MODAL
|--------------------------------------------------------------------------
*/
editProductModal.addEventListener('click', function (event) {
    if (event.target === editProductModal) {
        closeEditModal();
    }
});

/*
|--------------------------------------------------------------------------
| EDIT IMAGE PREVIEW
|--------------------------------------------------------------------------
*/
editProductImage.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (event) {
        editImagePreview.innerHTML = `
            <img src="${event.target.result}" alt="Product preview">
        `;
    };
    reader.readAsDataURL(file);
});

/*
|--------------------------------------------------------------------------
| WINDOW RESIZE
|--------------------------------------------------------------------------
*/
window.addEventListener('resize', function () {
    if (window.innerWidth > 768) {
        closeMobileSidebar();
    }
});
</script>

</body>
</html>