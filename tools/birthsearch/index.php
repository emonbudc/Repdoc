<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/SearchEngine.php';

$db = getDB();
$engine = new SearchEngine($db);

$stats = $engine->getStats();
$wards = $engine->getWards();
$sourceFiles = $engine->getSourceFiles();

$q = trim($_GET['q'] ?? '');
$name = trim($_GET['name'] ?? '');
$father = trim($_GET['father'] ?? '');
$mother = trim($_GET['mother'] ?? '');
$dob = trim($_GET['dob'] ?? '');
$gender = trim($_GET['gender'] ?? '');
$regNum = trim($_GET['reg_num'] ?? '');
$ward = trim($_GET['ward'] ?? '');
$sourceFile = trim($_GET['source_file'] ?? '');
$status = trim($_GET['status'] ?? '');
$sortBy = trim($_GET['sort_by'] ?? 'relevance');
$sortDir = trim($_GET['sort_dir'] ?? 'asc');
$view = trim($_GET['view'] ?? 'table');
$openId = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>জন্ম নিবন্ধন রেকর্ড অনুসন্ধান — Balua Union Digital Center</title>
    <meta name="robots" content="noindex">
    <meta name="description" content="Balua Union Digital Center — independent search console for locally indexed birth records. Not the official BDRIS portal.">
    <link rel="icon" type="image/png" href="image/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700&family=Noto+Serif+Bengali:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>
    <a class="skip-link" href="#resultsSection">মূল ফলাফলে যান (Skip to results)</a>
    <div class="app">
        <div class="govt-strip" aria-hidden="true"></div>
        <!-- Top Navigation Bar -->
        <header class="topbar">
            <div class="topbar-container">
                <a href="index.php" class="brand" title="Balua Union Digital Center — Home">
                    <img class="brand-logo" src="image/logo.png" alt="Balua Union Digital Center">
                    
                </a>

                <!-- Navigation Links -->

                <!-- Topbar Utilities -->
                <div class="topbar-actions">
                    
                    </span>

                    

                    <div class="user-chip" title="লগড ইন: বালুয়া ইউনিয়ন ডিজিটাল সেন্টার">
                        <div class="user-avatar" aria-hidden="true">ব</div>
                        <div class="user-details">
                            <span class="user-name">বালুয়া ইউনিয়ন ডিজিটাল সেন্টার</span>
                            <span class="user-role">Baluahat, Sonatola, Bogura</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <nav class="navbar" aria-label="প্রধান নেভিগেশন">
            <div class="navbar-inner">
                <div class="nav-links">
                    <a href="index.php" class="nav-link active" id="navDashboard" aria-current="page">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <span>অনুসন্ধান</span>
                    </a>
                    <a href="javascript:void(0)" class="nav-link" id="navRecords" onclick="window.showAllRecords()">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>রেকর্ড তালিকা</span>
                    </a>
                    <a href="manage.php" class="nav-link">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        <span>রেকর্ড ব্যবস্থাপনা</span>
                    </a>
                </div>
            </div>
        </nav>
<main class="main-content">
            <!-- Registry Summary Boxes -->
            <section class="stat-boxes" aria-label="রেজিস্ট্রি সারসংক্ষেপ">
                <div class="stat-box">
                    <span class="k">মোট রেকর্ড</span>
                    <span class="v" id="statRecords" data-val="<?= $stats['records'] ?>"><?= number_format($stats['records']) ?></span>
                </div>
                <div class="stat-box">
                    <span class="k">উৎস ফাইল</span>
                    <span class="v"><?= number_format(count($sourceFiles)) ?></span>
                </div>
            </section>

            <!-- Primary Search & Filter Panel -->
            <section class="search-panel" aria-labelledby="searchHeading">
                <div class="search-header-row">
                    <div class="search-title-wrap">
                        <h2 id="searchHeading">জন্ম নিবন্ধন অনুসন্ধান</h2>
                    </div>
                </div>

                <div id="formErrorSummary" class="form-error-summary" role="alert" tabindex="-1" hidden></div>

                <form id="searchForm" class="search-form" autocomplete="off" onsubmit="return false;">
                    <!-- Main Search Input Group -->
                    <div class="search-input-group">
                        <div class="search-field-box" id="searchFieldBox">
                            <span class="search-leading-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </span>
                            <input type="search" name="q" id="searchInput" class="main-search-input"
                                   value="<?= htmlspecialchars($q) ?>"
                                   placeholder="নাম, ১৭ সংখ্যার নম্বর, পিতা/মাতার নাম..."
                                   autocomplete="off" spellcheck="false"
                                   aria-label="নাম, নিবন্ধন নম্বর, পিতা বা মাতার নাম দিয়ে জন্ম নিবন্ধন অনুসন্ধান"
                                   aria-expanded="false"
                                   aria-controls="suggestionsDropdown">
                            
                            <div class="search-input-tools">
                                <span class="lang-indicator" id="langIndicator" hidden>BN</span>
                                <button type="button" id="clearSearchBtn" class="clear-search-btn" title="মুছুন" aria-label="অনুসন্ধান মুছুন" hidden>
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                                <span class="kbd-shortcut" title="কীবোর্ড ফোকাস shortcut">/</span>
                            </div>
                        </div>

                        <!-- Live Auto-Suggestions Dropdown -->
                        <div id="suggestionsDropdown" class="suggestions-dropdown" role="listbox" aria-label="অনুসন্ধান পরামর্শ" hidden></div>

                        <!-- Search CTA Button -->
                        <button type="button" id="btnRunSearch" class="btn-primary">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <span>অনুসন্ধান করুন</span>
                        </button>
                    </div>

                    <!-- Search Mode Toggle -->
                    <div class="search-mode-toggle">
                        <span class="search-mode-label" id="matchModeLabel">মোড:</span>
                        <div class="view-toggle-group" role="group" aria-labelledby="matchModeLabel">
                            <button type="button" class="view-btn active" data-match-mode="partial" aria-pressed="true" title="আংশিক মিল">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <span>আংশিক</span>
                            </button>
                            <button type="button" class="view-btn" data-match-mode="exact" aria-pressed="false" title="হুবহু মিল">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                <span>হুবহু</span>
                            </button>
                        </div>
                    </div>

                    <!-- Search Controls Toolbar -->
                    <div class="search-controls-bar">
                        <button type="button" id="advFilterToggle" class="filter-toggle-btn" aria-expanded="false" aria-controls="advancedFiltersPanel">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            <span>বাড়তি ফিল্টার</span>
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>

                        <div class="search-actions-group">
                            <button type="button" id="btnResetFilters" class="btn-secondary" title="সব ফিল্টার রিসেট করুন">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                <span>রিসেট</span>
                            </button>

                            <div class="export-dropdown-wrapper" style="position:relative;">
                                <button type="button" id="btnExportMenu" class="btn-secondary" title="ডাটা এক্সপোর্ট করুন" aria-haspopup="menu" aria-expanded="false" aria-controls="exportMenuDropdown">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    <span>এক্সপোর্ট</span>
                                </button>
                                <div id="exportMenuDropdown" class="suggestions-dropdown" role="menu" aria-label="এক্সপোর্ট অপশন" style="right:0; left:auto; width:220px;" hidden>
                                    <button type="button" class="suggestion-row" role="menuitem" id="exportCSV">
                                        <span>CSV (Excel)</span>
                                    </button>
                                    <button type="button" class="suggestion-row" role="menuitem" id="exportJSON">
                                        <span>JSON</span>
                                    </button>
                                    <button type="button" class="suggestion-row" role="menuitem" id="exportPrint">
                                        <span>প্রিন্ট</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Advanced Filters Drawer -->
                    <div id="advancedFiltersPanel" class="advanced-filters-panel" hidden>
                        <div class="filters-grid">
                            <div class="form-group">
                                <label for="fName" class="form-label">ব্যক্তির নাম (বাংলা অথবা ইংরেজি)</label>
                                <input type="text" id="fName" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" placeholder="আব্দুর রহিম">
                            </div>
                            <div class="form-group">
                                <label for="fRegNum" class="form-label">জন্ম নিবন্ধন নম্বর (BRN — ১৭ সংখ্যা)</label>
                                <input type="text" id="fRegNum" name="reg_num" class="form-control mono" value="<?= htmlspecialchars($regNum) ?>" placeholder="১৭ সংখ্যার নম্বর" inputmode="numeric" maxlength="17" aria-describedby="fRegNumHint">
                                <span class="field-hint" id="fRegNumHint">ঠিক ১৭টি অঙ্ক।</span>
                            </div>
                            <div class="form-group">
                                <label for="fFather" class="form-label">পিতার নাম (বাংলা/ইংরেজি)</label>
                                <input type="text" id="fFather" name="father" class="form-control" value="<?= htmlspecialchars($father) ?>" placeholder="পিতার নাম">
                            </div>
                            <div class="form-group">
                                <label for="fMother" class="form-label">মাতার নাম (বাংলা/ইংরেজি)</label>
                                <input type="text" id="fMother" name="mother" class="form-control" value="<?= htmlspecialchars($mother) ?>" placeholder="মাতার নাম">
                            </div>
                            <div class="form-group">
                                <label for="fDob" class="form-label">জন্ম তারিখ (DD/MM/YYYY)</label>
                                <input type="text" id="fDob" name="dob" class="form-control mono" value="<?= htmlspecialchars($dob) ?>" placeholder="যেমন: 15/08/1995" inputmode="numeric" maxlength="10" aria-describedby="fDobHint">
                                <span class="field-hint" id="fDobHint">দিন/মাস/বছর।</span>
                            </div>
                            <div class="form-group">
                                <label for="fGender" class="form-label">লিঙ্গ (Gender)</label>
                                <select id="fGender" name="gender" class="form-control">
                                    <option value="">সব লিঙ্গ (All)</option>
                                    <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>পুরুষ (Male)</option>
                                    <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>মহিলা (Female)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="fWard" class="form-label">ওয়ার্ড নং (Ward)</label>
                                <input type="text" id="fWard" name="ward" class="form-control" value="<?= htmlspecialchars($ward) ?>" placeholder="ওয়ার্ড নং">
                            </div>
                            <div class="form-group">
                                <label for="fStatus" class="form-label">নিবন্ধন স্ট্যাটাস (Status)</label>
                                <select id="fStatus" name="status" class="form-control">
                                    <option value="">সব স্ট্যাটাস</option>
                                    <option value="verified" <?= $status === 'verified' ? 'selected' : '' ?>>যাচাইকৃত (Verified)</option>
                                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>যাচাই বাকি (Pending)</option>
                                    <option value="incomplete" <?= $status === 'incomplete' ? 'selected' : '' ?>>অসম্পূর্ণ (Incomplete)</option>
                                </select>
                            </div>
                        </div>

                        <div class="filter-footer-actions">
                            <button type="button" id="btnApplyAdvFilters" class="btn-primary" style="padding: 9px 18px; font-size: 0.88rem;">
                                <span>প্রয়োগ করুন</span>
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Search Results & Data Management Area -->
            <section class="results-section" id="resultsSection" tabindex="-1" aria-label="অনুসন্ধান ফলাফল">
                <!-- Results Header Bar -->
                <div class="results-header-bar">
                    <div class="results-stats-readout">
                        <span class="results-total-badge" id="resultsCountReadout">ফলাফল প্রদর্শিত হচ্ছে</span>
                        <span class="results-range-text" id="resultsRangeReadout"></span>
                        <div id="activeFilterChips" class="active-filter-chips"></div>
                    </div>

                    <div class="results-view-tools">
                        <!-- Per page selector -->
                        <select id="perPageSelect" class="per-page-select" title="প্রতি পৃষ্ঠায় রেকর্ডের সংখ্যা">
                            <option value="10">১০টি</option>
                            <option value="25">২৫টি</option>
                            <option value="50" selected>৫০টি</option>
                            <option value="100">১০০টি</option>
                        </select>

                        <!-- View Mode Switcher -->
                        <div class="view-toggle-group" role="group" aria-label="ভিউ মোড">
                            <button type="button" id="viewModeTableBtn" class="view-btn active" aria-pressed="true" aria-label="টেবিল ভিউ" title="টেবিল ভিউ">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </button>
                            <button type="button" id="viewModeCardsBtn" class="view-btn" aria-pressed="false" aria-label="কার্ড ভিউ" title="কার্ড ভিউ">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bulk Selection Action Banner -->
                <div id="bulkActionBanner" class="bulk-action-banner" hidden>
                    <div class="bulk-count-info">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span id="bulkSelectedCountText">০ টি রেকর্ড নির্বাচিত</span>
                    </div>
                    <div class="bulk-buttons">
                        <button type="button" class="btn-bulk" id="btnBulkCopyIds" title="নির্বাচিত আইডিগুলো কপি করুন">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            <span>আইডি কপি</span>
                        </button>
                        <button type="button" class="btn-bulk" id="btnBulkExport" title="নির্বাচিত রেকর্ডগুলো এক্সপোর্ট করুন">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <span>CSV</span>
                        </button>
                        <button type="button" class="btn-bulk" id="btnBulkClear" style="background:transparent; border-color:transparent;">
                            <span>বাতিল</span>
                        </button>
                    </div>
                </div>

                <!-- Loading Shimmer Skeletons Container -->
                <div id="loadingSkeletons" hidden>
                    <div class="skeleton-row"></div>
                    <div class="skeleton-row"></div>
                    <div class="skeleton-row"></div>
                    <div class="skeleton-row"></div>
                    <div class="skeleton-row"></div>
                </div>

                <!-- No Results / Empty State -->
                <div id="noResultsState" class="empty-state-card" hidden>
                    <div class="empty-illustration">
                        <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                    </div>
                    <h3>কোনো রেকর্ড পাওয়া যায়নি</h3>
                    <p>বানান দেখে আবার খুঁজুন।</p>
                    <button type="button" class="btn-secondary" onclick="window.resetAllFilters()">
                        <span>ফিল্টার রিসেট করুন</span>
                    </button>
                </div>

                <!-- Fetch Error State (network/server failure, distinct from empty) -->
                <div id="resultsErrorState" class="empty-state-card" role="alert" hidden>
                    <div class="empty-illustration" style="background:var(--status-incomplete-bg); color:var(--status-incomplete-text);">
                        <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <h3>অনুসন্ধানে সমস্যা হয়েছে</h3>
                    <p>সংযোগ ব্যর্থ হয়েছে। আবার চেষ্টা করুন।</p>
                    <button type="button" class="btn-secondary" id="btnRetrySearch">
                        <span>পুনরায় চেষ্টা করুন</span>
                    </button>
                </div>

                <!-- Interactive Data Table View -->
                <div id="tableViewWrapper" class="table-responsive-wrapper">
                    <table class="data-table" id="dataTable">
                        <thead>
                            <tr>
                                <th class="cell-checkbox">
                                    <input type="checkbox" id="selectAllCheckbox" class="custom-checkbox" title="সব নির্বাচন করুন">
                                </th>
                                <th class="sortable" data-sort="reg_num">
                                    <span>নিবন্ধন নম্বর</span>
                                    <span class="sort-icon">⇅</span>
                                </th>
                                <th class="sortable" data-sort="name">
                                    <span>নাম ও পিতা-মাতা</span>
                                    <span class="sort-icon">⇅</span>
                                </th>
                                <th class="sortable" data-sort="dob">
                                    <span>জন্ম তারিখ</span>
                                    <span class="sort-icon">⇅</span>
                                </th>
                                <th>লিঙ্গ</th>
                                <th class="sortable" data-sort="ward">
                                    <span>ঠিকানা</span>
                                    <span class="sort-icon">⇅</span>
                                </th>
                                <th>স্ট্যাটাস</th>
                                <th style="text-align: right; min-width: 170px;">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody id="dataTableBody">
                            <!-- Populated dynamically by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Interactive Card Grid View -->
                <div id="cardsViewWrapper" class="cards-grid-layout" hidden>
                    <!-- Populated dynamically by JavaScript -->
                </div>

                <!-- Pagination Container -->
                <div id="paginationContainer" class="pagination-container" hidden>
                    <span class="results-range-text" id="paginationRangeText"></span>
                    <div id="paginationInner" class="pagination-numbers"></div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="app-footer">
            <div class="app-footer-inner">
                <p class="credit">Made with ❤️ by <a href="https://github.com/emonbaluahat" target="_blank" rel="noopener">Abdullah Al Emon</a> | &copy; REPDOC</p>
            </div>
        </footer>
    </div>

    <!-- Live BDRIS Captcha Verification Modal -->
    <div id="verifyModal" class="detail-modal-wrapper" hidden>
        <div class="modal-backdrop" data-close-verify></div>
        <div class="detail-modal-card verify-modal-card" role="dialog" aria-modal="true" aria-labelledby="verifyModalTitle">
            <!-- Verify Header -->
            <div class="modal-header-section">
                <div class="modal-candidate-info">
                    <div class="modal-avatar-crest" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                    </div>
                    <div class="modal-titles">
                        <h2 id="verifyModalTitle">জন্ম নিবন্ধন যাচাইকরণ</h2>
                        <p>ক্যাপচা দিয়ে তথ্য মিলিয়ে নিন</p>
                    </div>
                </div>
                <button type="button" class="modal-close-btn" data-close-verify title="বন্ধ করুন" aria-label="যাচাই উইন্ডো বন্ধ করুন">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Verify Body -->
            <div class="modal-body-scrollable" style="padding: 24px;">
                <!-- Candidate Brief Card -->
                <div class="verify-candidate-banner" id="verifyCandidateBanner">
                    <div class="verify-candidate-row">
                        <span class="v-label">রেকর্ড নাম:</span>
                        <span class="v-val" id="verifyCandName">—</span>
                    </div>
                    <div class="verify-candidate-row">
                        <span class="v-label">১৭ ডিজিট নিবন্ধন (BRN):</span>
                        <span class="v-val mono" id="verifyCandBrn" style="color:var(--primary); font-weight:700;">—</span>
                    </div>
                    <div class="verify-candidate-row">
                        <span class="v-label">জন্ম তারিখ:</span>
                        <span class="v-val mono" id="verifyCandDob">—</span>
                    </div>
                </div>

                <form id="verifyForm" class="verify-form" onsubmit="return false;" style="margin-top: 18px;">
                    <input type="hidden" id="verifyRecordId" value="">
                    <input type="hidden" id="verifyCaptchaToken" value="">

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label for="verifyUbrnInput" class="form-label">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h10"/></svg>
                            <span>নিবন্ধন নম্বর (১৭ সংখ্যা)</span>
                        </label>
                        <input type="text" id="verifyUbrnInput" class="form-control mono" placeholder="১৭ সংখ্যার নম্বর" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="verifyDobInput" class="form-label">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span>জন্ম তারিখ</span>
                        </label>
                        <input type="text" id="verifyDobInput" class="form-control mono" placeholder="YYYY-MM-DD" required>
                    </div>

                    <!-- Captcha Section -->
                    <div class="captcha-verification-box">
                        <label class="form-label" style="margin-bottom: 8px;">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/></svg>
                            <span>ক্যাপচা</span>
                        </label>

                        <div class="captcha-display-row">
                            <div class="captcha-img-container" id="captchaImgContainer">
                                <div class="spinner" style="width:24px; height:24px;"></div>
                                <span style="font-size:0.78rem; color:var(--text-muted);">ক্যাপচা লোড হচ্ছে...</span>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <button type="button" id="btnRefreshCaptcha" class="btn-secondary" style="padding: 8px 12px;" title="নতুন ক্যাপচা আনুন">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                    <span>নতুন ক্যাপচা</span>
                                </button>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 12px;">
                            <input type="text" id="verifyCaptchaAnswer" class="form-control mono" placeholder="ছবির উত্তর লিখুন" required autocomplete="off" style="font-size:1.1rem; letter-spacing:1px; text-align:center;">
                        </div>
                        <button type="button" id="btnSubmitVerification" class="btn-primary" style="width:100%; margin-top:12px; padding:11px;" title="তথ্য যাচাই করুন">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            <span>যাচাই করুন</span>
                        </button>
                    </div>

                    <!-- Progress / Status Notice Box -->
                    <div id="verifyStatusBox" class="verify-status-box" hidden></div>
                </form>
            </div>

            <!-- Verify Footer Actions -->
            <div class="modal-footer-section">
                <button type="button" class="btn-secondary" data-close-verify>
                    <span>বাতিল</span>
                </button>
                <button type="button" class="btn-primary" id="btnVerifyModalPrint" disabled title="আগে যাচাই করুন">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    <span>সনদ প্রিন্ট</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Record Details & Certificate Drawer/Modal -->
    <div id="recordDetailModal" class="detail-modal-wrapper" hidden>
        <div class="modal-backdrop" data-close-modal></div>
        <div class="detail-modal-card" role="dialog" aria-modal="true" aria-labelledby="modalCandidateName">
            <!-- Modal Header -->
            <div class="modal-header-section">
                <div class="modal-candidate-info">
                    <div class="modal-avatar-crest">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div class="modal-titles">
                        <h2 id="modalCandidateName">লোড হচ্ছে...</h2>
                        <p id="modalCandidateSub">রেকর্ড বিবরণ</p>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" class="btn-primary" id="btnModalVerifyTrigger" style="padding: 6px 14px; font-size: 0.82rem;">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>যাচাই করুন</span>
                    </button>
                    <button type="button" class="modal-close-btn" data-close-modal title="বন্ধ করুন" aria-label="বিস্তারিত উইন্ডো বন্ধ করুন">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>

            <!-- Modal Tab Navigation -->
            <div class="modal-tabs-nav">
                <button type="button" class="modal-tab-btn active" data-tab="tabPersonal">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>ব্যক্তিগত</span>
                </button>
                <button type="button" class="modal-tab-btn" data-tab="tabParents">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>পিতা-মাতা</span>
                </button>
                <button type="button" class="modal-tab-btn" data-tab="tabRegistration">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>নিবন্ধন</span>
                </button>
                <button type="button" class="modal-tab-btn" data-tab="tabAudit">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                    <span>যাচাই</span>
                </button>
                <button type="button" class="modal-tab-btn" data-tab="tabCertificate">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    <span>সনদ</span>
                </button>
            </div>

            <!-- Modal Body Scrollable -->
            <div class="modal-body-scrollable" id="modalBodyContent">
                <!-- Dynamically rendered by JS -->
            </div>

            <!-- Modal Footer Actions -->
            <div class="modal-footer-section">
                <button type="button" class="btn-secondary" id="btnCopyRecordJSON" title="সম্পূর্ণ তথ্য JSON কপি করুন">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span>JSON কপি</span>
                </button>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-primary" id="btnPrintModalCert" hidden title="প্রিন্ট করতে প্রথমে যাচাই করুন">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        <span>প্রিন্ট</span>
                    </button>
                    <button type="button" class="btn-secondary" data-close-modal>
                        <span>বন্ধ করুন</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script>
        window.__INITIAL = {
            q: <?= json_encode($q, JSON_UNESCAPED_UNICODE) ?>,
            name: <?= json_encode($name, JSON_UNESCAPED_UNICODE) ?>,
            father: <?= json_encode($father, JSON_UNESCAPED_UNICODE) ?>,
            mother: <?= json_encode($mother, JSON_UNESCAPED_UNICODE) ?>,
            dob: <?= json_encode($dob, JSON_UNESCAPED_UNICODE) ?>,
            gender: <?= json_encode($gender, JSON_UNESCAPED_UNICODE) ?>,
            reg_num: <?= json_encode($regNum, JSON_UNESCAPED_UNICODE) ?>,
            ward: <?= json_encode($ward, JSON_UNESCAPED_UNICODE) ?>,
            source_file: <?= json_encode($sourceFile, JSON_UNESCAPED_UNICODE) ?>,
            status: <?= json_encode($status, JSON_UNESCAPED_UNICODE) ?>,
            sortBy: <?= json_encode($sortBy, JSON_UNESCAPED_UNICODE) ?>,
            sortDir: <?= json_encode($sortDir, JSON_UNESCAPED_UNICODE) ?>,
            view: <?= json_encode($view, JSON_UNESCAPED_UNICODE) ?>,
            openId: <?= (int) $openId ?>
        };
    </script>
    <script src="js/main.js?v=<?= time() ?>"></script>
</body>
</html>