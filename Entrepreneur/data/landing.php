<?php
// Legacy design source only. The live page is Entrepreneur/panel/landing.php;
// direct requests must not execute this copy.
if (!defined('REPDOC_ROOT')) {
    http_response_code(404);
    exit;
}
// etc/landing.php - Enhanced professional landing page with file-manager style UI
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Archive · Prottoyon & Certificates</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Exo+2:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-deep:     #0d0d0d;
            --bg-card:     #161616;
            --bg-card-h:   #1e1e1e;
            --border:      #2a2a2a;
            --border-glow: #2ecc71;
            --green:       #2ecc71;
            --green-dark:  #1a8a4a;
            --green-glow:  rgba(46, 204, 113, 0.18);
            --green-glow2: rgba(46, 204, 113, 0.06);
            --text-main:   #e8e8e8;
            --text-sub:    #888;
            --text-muted:  #555;
            --accent-blue: #3498db;
            --accent-warn: #f39c12;
            --accent-red:  #e74c3c;
            --accent-purple: #9b59b6;
        }

        body {
            background-color: var(--bg-deep);
            color: var(--text-main);
            font-family: 'Exo 2', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated grid background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(46,204,113,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(46,204,113,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            top: -180px;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 400px;
            background: radial-gradient(ellipse, rgba(46,204,113,0.10) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .page-wrapper {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px 80px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 56px;
            animation: fadeDown 0.7s ease both;
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--green-glow2);
            border: 1px solid rgba(46,204,113,0.25);
            border-radius: 100px;
            padding: 6px 18px;
            font-size: 0.72rem;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--green);
            margin-bottom: 20px;
        }

        .header-badge i {
            font-size: 0.7rem;
        }

        .header h1 {
            font-family: 'Rajdhani', sans-serif;
            font-size: clamp(2.4rem, 5vw, 3.6rem);
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.1;
            color: #fff;
        }

        .header h1 span {
            color: var(--green);
        }

        .header-line {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--green), transparent);
            margin: 18px auto 14px;
            border-radius: 2px;
        }

        .header p {
            color: var(--text-sub);
            font-size: 1rem;
            font-weight: 300;
            letter-spacing: 0.03em;
        }

        /* Counter bar */
        .counter-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 32px;
            margin-bottom: 48px;
            animation: fadeDown 0.7s 0.15s ease both;
        }

        .counter-item {
            text-align: center;
        }

        .counter-num {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--green);
            line-height: 1;
        }

        .counter-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .counter-divider {
            width: 1px;
            height: 32px;
            background: var(--border);
        }

        /* Search bar */
        .search-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 16px;
            margin-bottom: 36px;
            transition: border-color 0.3s;
            animation: fadeDown 0.7s 0.25s ease both;
        }

        .search-bar:focus-within {
            border-color: var(--green);
            box-shadow: 0 0 0 3px var(--green-glow2);
        }

        .search-bar i {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .search-bar input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-main);
            font-family: 'Exo 2', sans-serif;
            font-size: 0.9rem;
            padding: 14px 0;
        }

        .search-bar input::placeholder {
            color: var(--text-muted);
        }

        /* View toggle (file-manager style) */
        .view-toggle {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }

        .view-btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .view-btn.active {
            background: var(--green-glow);
            border-color: var(--green);
            color: var(--green);
        }

        .view-btn:hover {
            border-color: var(--green);
            color: var(--green);
        }

        /* Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
            animation: fadeUp 0.7s 0.3s ease both;
        }

        /* List view */
        .product-grid.list-view {
            grid-template-columns: 1fr;
        }

        .product-grid.list-view .product-card {
            flex-direction: row;
            align-items: center;
            padding: 18px 24px;
        }

        .product-grid.list-view .card-icon-wrap {
            margin-right: 16px;
        }

        .product-grid.list-view .card-body {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .product-grid.list-view .card-title {
            min-width: 180px;
            margin-bottom: 0;
        }

        .product-grid.list-view .card-desc {
            flex: 1;
        }

        .product-grid.list-view .card-footer-row {
            margin-top: 0;
        }

        /* Card */
        .product-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 22px 20px 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            cursor: default;
        }

        .product-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--green-glow2) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.35s ease;
            pointer-events: none;
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: rgba(46,204,113,0.35);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4), 0 0 0 1px rgba(46,204,113,0.12);
        }

        .product-card:hover::before {
            opacity: 1;
        }

        /* Folder style variation */
        .product-card.folder-type {
            border-left: 3px solid var(--accent-blue);
        }

        .product-card.prottoyon-type {
            border-left: 3px solid var(--accent-purple);
        }

        .product-card.certificate-type {
            border-left: 3px solid var(--green);
        }

        .card-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            flex-shrink: 0;
            transition: background 0.3s, border-color 0.3s;
        }

        .product-card:hover .card-icon-wrap {
            background: var(--green-glow);
            border-color: rgba(46,204,113,0.4);
        }

        .card-body {
            flex: 1;
        }

        .card-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            color: #fff;
            margin-bottom: 4px;
        }

        .card-desc {
            font-size: 0.8rem;
            color: var(--text-sub);
            line-height: 1.4;
        }

        .card-meta {
            display: flex;
            gap: 8px;
            margin-top: 6px;
            font-size: 0.68rem;
            color: var(--text-muted);
        }

        .card-meta i {
            margin-right: 3px;
        }

        .card-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 4px;
        }

        .card-tag {
            font-size: 0.65rem;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-muted);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 5px;
            padding: 2px 8px;
        }

        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            color: #fff;
            text-decoration: none;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 8px 16px;
            border-radius: 7px;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.2s, box-shadow 0.2s;
            white-space: nowrap;
        }

        .btn-download:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(46,204,113,0.35);
            color: #fff;
            text-decoration: none;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-sub);
            padding: 6px 12px;
            font-size: 0.7rem;
        }

        .btn-outline:hover {
            background: var(--green-glow2);
            border-color: var(--green);
            color: var(--green);
            box-shadow: none;
        }

        .no-results {
            grid-column: 1/-1;
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
            display: none;
        }

        .no-results i {
            font-size: 2.5rem;
            margin-bottom: 14px;
            color: var(--border);
        }

        /* Footer */
        .footer-section {
            text-align: center;
            margin-top: 56px;
            animation: fadeUp 0.7s 0.5s ease both;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: transparent;
            color: var(--green);
            border: 1.5px solid rgba(46,204,113,0.4);
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 10px;
            transition: background 0.25s, border-color 0.25s, box-shadow 0.25s, transform 0.2s;
        }

        .btn-home:hover {
            background: var(--green-glow);
            border-color: var(--green);
            box-shadow: 0 0 20px rgba(46,204,113,0.2);
            transform: translateY(-2px);
            text-decoration: none;
            color: var(--green);
        }

        /* Breadcrumb (file manager style) */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            padding: 8px 0;
            color: var(--text-muted);
            font-size: 0.8rem;
            animation: fadeDown 0.7s 0.2s ease both;
        }

        .breadcrumb a {
            color: var(--text-sub);
            text-decoration: none;
            transition: color 0.2s;
        }

        .breadcrumb a:hover {
            color: var(--green);
        }

        .breadcrumb i {
            font-size: 0.6rem;
        }

        /* Icons */
        .ic-blue  { color: var(--accent-blue); }
        .ic-green { color: var(--green); }
        .ic-warn  { color: var(--accent-warn); }
        .ic-red   { color: var(--accent-red); }
        .ic-white { color: #ccc; }
        .ic-purple { color: var(--accent-purple); }

        /* Animations */
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 600px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
            .counter-bar {
                gap: 18px;
            }
            .product-grid.list-view .product-card {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">

        <!-- Header -->
        <header class="header">
            <div class="header-badge">
                <i class="fas fa-folder-tree"></i>
                File Manager · Document Archive
            </div>
            <h1>প্রত্যয়ন <span>&</span> সার্টিফিকেট</h1>
            <div class="header-line"></div>
            <p>Prottoyon · Certificates · Official Templates</p>
        </header>

        <!-- Breadcrumb (file-manager style) -->
        <div class="breadcrumb">
            <a href="#"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="#">Documents</a>
            <i class="fas fa-chevron-right"></i>
            <span style="color: var(--green);">All Templates</span>
        </div>

        <!-- Counter Bar -->
        <div class="counter-bar">
            <div class="counter-item">
                <div class="counter-num" id="total-count">14</div>
                <div class="counter-label">Documents</div>
            </div>
            <div class="counter-divider"></div>
            <div class="counter-item">
                <div class="counter-num">5</div>
                <div class="counter-label">প্রত্যয়ন</div>
            </div>
            <div class="counter-divider"></div>
            <div class="counter-item">
                <div class="counter-num">9</div>
                <div class="counter-label">Certificates</div>
            </div>
        </div>

        <!-- Search + View Toggle -->
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search documents… (e.g., prottoyon, birth, certificate)" oninput="filterCards()" />
            <div class="view-toggle">
                <button class="view-btn active" onclick="setView('grid')" id="gridViewBtn"><i class="fas fa-th"></i></button>
                <button class="view-btn" onclick="setView('list')" id="listViewBtn"><i class="fas fa-list"></i></button>
            </div>
        </div>

        <!-- Grid -->
        <div class="product-grid" id="productGrid">

            <!-- ORIGINAL ITEMS (kept) -->
            <div class="product-card certificate-type" data-name="high school old certificate" data-type="certificate">
                <div class="card-icon-wrap">
                    <i class="fas fa-user-graduate ic-blue"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">High School Old</div>
                    <div class="card-desc">OLD High School Certificate Blank template</div>
                    <div class="card-meta"><i class="far fa-file-image"></i> PNG · 2.1 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Certificate</span>
                    <a href="oldblank.PNG" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="product-card certificate-type" data-name="girls old certificate template" data-type="certificate">
                <div class="card-icon-wrap">
                    <i class="fas fa-certificate ic-green"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">Girls Old</div>
                    <div class="card-desc">Certificate Template for grade 8 girls</div>
                    <div class="card-meta"><i class="far fa-file-image"></i> JPG · 1.8 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Certificate</span>
                    <a href="8mgirls.jpg" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="product-card certificate-type" data-name="old birth certificate template" data-type="certificate">
                <div class="card-icon-wrap">
                    <i class="fas fa-university ic-warn"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">OLD Birth Certificate</div>
                    <div class="card-desc">Old Birth Certificate blank template</div>
                    <div class="card-meta"><i class="far fa-file-image"></i> JPG · 2.4 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Birth Cert</span>
                    <a href="oldbc.jpg" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="product-card certificate-type" data-name="psc certificate blank" data-type="certificate">
                <div class="card-icon-wrap">
                    <i class="fas fa-file-signature ic-red"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">PSC Certificate</div>
                    <div class="card-desc">PSC Certificate blank form</div>
                    <div class="card-meta"><i class="far fa-file-image"></i> JPG · 1.5 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Certificate</span>
                    <a href="PSC.jpg" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="product-card prottoyon-type" data-name="oxford kg prottoyon birth proof" data-type="prottoyon">
                <div class="card-icon-wrap">
                    <i class="fas fa-file-alt ic-purple"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">Oxford KG Prottoyon</div>
                    <div class="card-desc">Prottoyon blank for birth proof (Oxford Kindergarten)</div>
                    <div class="card-meta"><i class="far fa-file-image"></i> JPG · 1.2 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Prottoyon</span>
                    <a href="Oxford_Prottoyon.JPG" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="product-card prottoyon-type" data-name="gobarpara primary prottoyon birth proof" data-type="prottoyon">
                <div class="card-icon-wrap">
                    <i class="fas fa-file-alt ic-purple"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">Gobarpara Primary Prottoyon</div>
                    <div class="card-desc">Gobarpara Primary School prottoyon blank for birth proof</div>
                    <div class="card-meta"><i class="far fa-file-image"></i> JPG · 1.3 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Prottoyon</span>
                    <a href="Gobarpara_Primary.jpg" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="product-card certificate-type" data-name="tika card epi vaccination blank" data-type="certificate">
                <div class="card-icon-wrap">
                    <i class="fas fa-syringe ic-green"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">Tika Card (EPI)</div>
                    <div class="card-desc">EPI Vaccination Card blank — Black & White</div>
                    <div class="card-meta"><i class="far fa-file-alt"></i> Google Docs</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Health Card</span>
                    <a href="https://docs.google.com/document/d/1v9rU5lP_wjHwexDzvcwfhhBAyNRzfYoN/edit?usp=sharing&ouid=108834765163028255543&rtpof=true&sd=true"
                       class="btn-download" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Download
                    </a>
                </div>
            </div>

            <div class="product-card certificate-type" data-name="kabin nama nikahnama psd file marriage" data-type="certificate">
                <div class="card-icon-wrap">
                    <i class="fas fa-file-signature ic-warn"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">Kabin Nama / Nikahnama</div>
                    <div class="card-desc">Marriage Certificate (Kabin/Nikahnama) PSD template</div>
                    <div class="card-meta"><i class="fas fa-layer-group"></i> PSD · 4.2 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">PSD File</span>
                    <a href="https://drive.google.com/file/d/1NWN_NM5OAmeSymwHGOQl5AMv5U4vyNgW/view?usp=sharing"
                       class="btn-download" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Download
                    </a>
                </div>
            </div>

            <!-- NEW ITEMS (Prottoyon.gov.bd style + related) -->
            <div class="product-card prottoyon-type" data-name="prottoyon form birth registration online prottoyon.gov.bd" data-type="prottoyon">
                <div class="card-icon-wrap">
                    <i class="fas fa-passport ic-blue"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">Birth Registration Prottoyon</div>
                    <div class="card-desc">Official Birth Registration Certificate (Prottoyon Form - prottoyon.gov.bd style)</div>
                    <div class="card-meta"><i class="far fa-file-pdf"></i> PDF · 890 KB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">প্রত্যয়ন</span>
                    <a href="#" class="btn-download btn-outline">
                        <i class="fas fa-eye"></i> Preview
                    </a>
                    <a href="#" class="btn-download">
                        <i class="fas fa-download"></i> PDF
                    </a>
                </div>
            </div>

            <div class="product-card prottoyon-type" data-name="character certificate prottoyon school" data-type="prottoyon">
                <div class="card-icon-wrap">
                    <i class="fas fa-user-check ic-green"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">Character Certificate</div>
                    <div class="card-desc">School Character Certificate / Prottoyon for students</div>
                    <div class="card-meta"><i class="far fa-file-word"></i> DOCX · 245 KB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Prottoyon</span>
                    <a href="#" class="btn-download btn-outline">
                        <i class="fas fa-eye"></i> Preview
                    </a>
                    <a href="#" class="btn-download">
                        <i class="fas fa-download"></i> DOCX
                    </a>
                </div>
            </div>

            <div class="product-card prottoyon-type" data-name="income certificate prottoyon union parishad" data-type="prottoyon">
                <div class="card-icon-wrap">
                    <i class="fas fa-hand-holding-usd ic-warn"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">Income Certificate</div>
                    <div class="card-desc">Union Parishad Income Certificate / Prottoyon format</div>
                    <div class="card-meta"><i class="far fa-file-pdf"></i> PDF · 1.1 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">প্রত্যয়নপত্র</span>
                    <a href="#" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="product-card certificate-type" data-name="nationality certificate bangladesh" data-type="certificate">
                <div class="card-icon-wrap">
                    <i class="fas fa-flag ic-red"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">Nationality Certificate</div>
                    <div class="card-desc">Bangladesh Nationality Certificate template (প্রত্যয়নপত্র)</div>
                    <div class="card-meta"><i class="far fa-file-image"></i> JPG · 2.0 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Certificate</span>
                    <a href="#" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="product-card certificate-type" data-name="jsc junior school certificate blank" data-type="certificate">
                <div class="card-icon-wrap">
                    <i class="fas fa-scroll ic-blue"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">JSC Certificate</div>
                    <div class="card-desc">Junior School Certificate (JSC) blank template</div>
                    <div class="card-meta"><i class="far fa-file-image"></i> PNG · 1.9 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Certificate</span>
                    <a href="#" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="product-card certificate-type" data-name="ssc certificate blank bangladesh" data-type="certificate">
                <div class="card-icon-wrap">
                    <i class="fas fa-graduation-cap ic-green"></i>
                </div>
                <div class="card-body">
                    <div class="card-title">SSC Certificate</div>
                    <div class="card-desc">Secondary School Certificate (SSC) blank template</div>
                    <div class="card-meta"><i class="far fa-file-image"></i> JPG · 2.3 MB</div>
                </div>
                <div class="card-footer-row">
                    <span class="card-tag">Certificate</span>
                    <a href="#" class="btn-download">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <!-- no results placeholder -->
            <div class="no-results" id="noResults">
                <i class="fas fa-search"></i>
                <p>No documents found.</p>
                <p style="font-size: 0.8rem; margin-top: 8px;">Try "prottoyon", "birth", or "certificate"</p>
            </div>

        </div>

        <!-- Footer -->
        <div class="footer-section">
            <a href="../index.php" class="btn-home">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>

    </div>

    <script>
        // Filter function (supports searching name, description, and type)
        function filterCards() {
            const q = document.getElementById('searchInput').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.product-card[data-name]');
            let visible = 0;
            
            cards.forEach(card => {
                const name = card.dataset.name || '';
                const type = card.dataset.type || '';
                const desc = card.querySelector('.card-desc')?.innerText.toLowerCase() || '';
                const match = name.includes(q) || desc.includes(q) || type.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            
            document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
            document.getElementById('total-count').textContent = q ? visible : cards.length;
        }

        // View toggle (grid / list)
        function setView(view) {
            const grid = document.getElementById('productGrid');
            const gridBtn = document.getElementById('gridViewBtn');
            const listBtn = document.getElementById('listViewBtn');
            
            if (view === 'list') {
                grid.classList.add('list-view');
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
            } else {
                grid.classList.remove('list-view');
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
            }
        }

        // Initialize total count on load
        window.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.product-card[data-name]');
            document.getElementById('total-count').textContent = cards.length;
        });
    </script>
</body>
</html>