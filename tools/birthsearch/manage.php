<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/SearchEngine.php';

$db = getDB();
$engine = new SearchEngine($db);
$stats = $engine->getStats();
$sourceFiles = $engine->getSourceFiles();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রেকর্ড ব্যবস্থাপনা — Balua Union Digital Center</title>
    <meta name="robots" content="noindex">
    <meta name="description" content="Balua Union Digital Center record management — add, edit, delete, search and replace locally indexed records.">
    <link rel="icon" type="image/png" href="image/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700&family=Noto+Serif+Bengali:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
</head>
<body>
    <div class="app">
        <div class="govt-strip" aria-hidden="true"></div>
        <!-- Top Navigation Bar -->
        <header class="topbar">
            <div class="topbar-container">
                <a href="index.php" class="brand" title="Balua Union Digital Center — Home">
                    <img class="brand-logo" src="image/logo.png" alt="Balua Union Digital Center">
                    
                </a>

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
                    <a href="index.php" class="nav-link">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <span>অনুসন্ধান</span>
                    </a>
                    <a href="manage.php" class="nav-link active" aria-current="page">
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
                    <span class="v" id="statTotal" data-val="<?= $stats['records'] ?>"><?= number_format($stats['records']) ?></span>
                </div>
                <div class="stat-box">
                    <span class="k">উৎস ফাইল</span>
                    <span class="v"><?= number_format(count($sourceFiles)) ?></span>
                </div>
            </section>

            <!-- Management Tabs -->
            <section class="mgmt-panel">
                <div class="mgmt-tabs">
                    <button type="button" class="mgmt-tab active" data-tab="add">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>নতুন রেকর্ড যোগ</span>
                    </button>
                    <button type="button" class="mgmt-tab" data-tab="edit">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        <span>সম্পাদনা ও মুছুন</span>
                    </button>
                    <button type="button" class="mgmt-tab" data-tab="replace">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                        <span>সার্চ ও রিপ্লেস</span>
                    </button>
                </div>

                <!-- Tab 1: Add Record -->
                <div id="tabAdd" class="mgmt-content active">
                    <div class="mgmt-section-header">
                        <h3>নতুন রেকর্ড যোগ করুন</h3>
                        <p>তথ্য দিন। ইংরেজি ঐচ্ছিক।</p>
                    </div>
                    <form id="addForm" class="mgmt-form">
                        <input type="hidden" name="action" value="add">
                        <div class="mgmt-form-grid">
                            <div class="form-group">
                                <label class="form-label">নিবন্ধন নম্বর (BRN)</label>
                                <input type="text" name="registration_number" class="form-control mono" placeholder="১৭ সংখ্যা">
                            </div>
                            <div class="form-group">
                                <label class="form-label">নাম (বাংলা) *</label>
                                <input type="text" name="name" class="form-control" placeholder="ব্যক্তির নাম" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">নাম (English)</label>
                                <input type="text" name="name_en" class="form-control" placeholder="Name in English">
                            </div>
                            <div class="form-group">
                                <label class="form-label">পিতার নাম (বাংলা)</label>
                                <input type="text" name="father_name" class="form-control" placeholder="পিতার নাম">
                            </div>
                            <div class="form-group">
                                <label class="form-label">পিতার নাম (English)</label>
                                <input type="text" name="father_name_en" class="form-control" placeholder="Father's Name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">মাতার নাম (বাংলা)</label>
                                <input type="text" name="mother_name" class="form-control" placeholder="মাতার নাম">
                            </div>
                            <div class="form-group">
                                <label class="form-label">মাতার নাম (English)</label>
                                <input type="text" name="mother_name_en" class="form-control" placeholder="Mother's Name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">জন্ম তারিখ</label>
                                <input type="text" name="date_of_birth" class="form-control mono" placeholder="DD/MM/YYYY">
                            </div>
                            <div class="form-group">
                                <label class="form-label">লিঙ্গ</label>
                                <select name="gender" class="form-control">
                                    <option value="MALE">পুরুষ (MALE)</option>
                                    <option value="FEMALE">মহিলা (FEMALE)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">ওয়ার্ড নং</label>
                                <input type="text" name="ward" class="form-control" placeholder="নম্বর">
                            </div>
                            <div class="form-group">
                                <label class="form-label">জন্মস্থান (বাংলা)</label>
                                <input type="text" name="birth_place" class="form-control" placeholder="জন্মস্থান">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Place of Birth (English)</label>
                                <input type="text" name="place_of_birth_en" class="form-control" placeholder="Birth place in English">
                            </div>
                            <div class="form-group mgmt-form-full">
                                <label class="form-label">স্থায়ী ঠিকানা (বাংলা)</label>
                                <input type="text" name="permanent_address" class="form-control" placeholder="ঠিকানা">
                            </div>
                            <div class="form-group mgmt-form-full">
                                <label class="form-label">Permanent Address (English)</label>
                                <input type="text" name="permanent_address_en" class="form-control" placeholder="Full address in English">
                            </div>
                            <div class="form-group">
                                <label class="form-label">নিবন্ধন তারিখ</label>
                                <input type="text" name="registration_date" class="form-control mono" placeholder="DD/MM/YYYY">
                            </div>
                            <div class="form-group">
                                <label class="form-label">সনদ তারিখ</label>
                                <input type="text" name="certificate_date" class="form-control mono" placeholder="DD/MM/YYYY">
                            </div>
                            <div class="form-group">
                                <label class="form-label">পৃষ্ঠা নং</label>
                                <input type="number" name="page_number" class="form-control mono" placeholder="পৃষ্ঠা">
                            </div>
                            <div class="form-group">
                                <label class="form-label">ক্রমিক</label>
                                <input type="number" name="serial" class="form-control mono" placeholder="ক্রমিক">
                            </div>
                            <div class="form-group">
                                <label class="form-label">সোর্স ফাইল</label>
                                <input type="text" name="source_file" class="form-control" placeholder="বইয়ের নাম">
                            </div>
                            <div class="form-group">
                                <label class="form-label">স্ট্যাটাস</label>
                                <select name="status" class="form-control">
                                    <option value="pending">যাচাই বাকি (Pending)</option>
                                    <option value="verified">যাচাইকৃত (Verified)</option>
                                    <option value="incomplete">অসম্পূর্ণ (Incomplete)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mgmt-form-actions">
                            <button type="submit" class="btn-primary" id="btnAddSubmit">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                <span>রেকর্ড যোগ করুন</span>
                            </button>
                            <button type="reset" class="btn-secondary">রিসেট</button>
                        </div>
                    </form>
                </div>

                <!-- Tab 2: Edit / Delete -->
                <div id="tabEdit" class="mgmt-content" hidden>
                    <div class="mgmt-section-header">
                        <h3>খুঁজুন ও সম্পাদনা</h3>
                        <p>নাম বা নম্বর দিয়ে খুঁজুন</p>
                    </div>
                    <div class="mgmt-search-bar">
                        <div class="search-field-box">
                            <span class="search-leading-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </span>
                            <input type="text" id="editSearchInput" class="main-search-input" placeholder="নাম, নম্বর..." autocomplete="off">
                        </div>
                        <select id="editSearchField" class="form-control" style="max-width:200px;">
                            <option value="">সকল ফিল্ড</option>
                            <option value="name">নাম (বাংলা)</option>
                            <option value="name_en">নাম (English)</option>
                            <option value="father_name">পিতার নাম</option>
                            <option value="mother_name">মাতার নাম</option>
                            <option value="registration_number">নিবন্ধন নম্বর</option>
                            <option value="ward">ওয়ার্ড</option>
                            <option value="date_of_birth">জন্ম তারিখ</option>
                        </select>
                        <button type="button" class="btn-primary" id="btnEditSearch">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <span>খুঁজুন</span>
                        </button>
                    </div>

                    <!-- Edit Results -->
                    <div id="editResults" class="mgmt-results" hidden>
                        <div class="mgmt-results-header">
                            <span id="editResultsCount"></span>
                            <div id="editPagination" class="mgmt-pagination"></div>
                        </div>
                        <div id="editResultsList" class="mgmt-results-list"></div>
                    </div>

                    <!-- Inline Edit Form (hidden by default) -->
                    <div id="editFormPanel" class="mgmt-edit-panel" hidden>
                        <div class="mgmt-edit-header">
                            <h4>রেকর্ড সম্পাদনা <span id="editRecordId" class="mono"></span></h4>
                            <button type="button" class="btn-secondary" id="btnCloseEdit">বাতিল</button>
                        </div>
                        <form id="editForm" class="mgmt-form">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" id="editFormId">
                            <div class="mgmt-form-grid">
                                <div class="form-group">
                                    <label class="form-label">নিবন্ধন নম্বর</label>
                                    <input type="text" name="registration_number" class="form-control mono" id="ef_reg">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">নাম (বাংলা)</label>
                                    <input type="text" name="name" class="form-control" id="ef_name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">নাম (English)</label>
                                    <input type="text" name="name_en" class="form-control" id="ef_name_en">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">পিতার নাম (বাংলা)</label>
                                    <input type="text" name="father_name" class="form-control" id="ef_father">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">পিতার নাম (English)</label>
                                    <input type="text" name="father_name_en" class="form-control" id="ef_father_en">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">মাতার নাম (বাংলা)</label>
                                    <input type="text" name="mother_name" class="form-control" id="ef_mother">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">মাতার নাম (English)</label>
                                    <input type="text" name="mother_name_en" class="form-control" id="ef_mother_en">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">জন্ম তারিখ</label>
                                    <input type="text" name="date_of_birth" class="form-control mono" id="ef_dob">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">লিঙ্গ</label>
                                    <select name="gender" class="form-control" id="ef_gender">
                                        <option value="MALE">পুরুষ</option>
                                        <option value="FEMALE">মহিলা</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ওয়ার্ড</label>
                                    <input type="text" name="ward" class="form-control" id="ef_ward">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">জন্মস্থান (বাংলা)</label>
                                    <input type="text" name="birth_place" class="form-control" id="ef_bp">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Place of Birth (English)</label>
                                    <input type="text" name="place_of_birth_en" class="form-control" id="ef_bp_en">
                                </div>
                                <div class="form-group mgmt-form-full">
                                    <label class="form-label">স্থায়ী ঠিকানা (বাংলা)</label>
                                    <input type="text" name="permanent_address" class="form-control" id="ef_addr">
                                </div>
                                <div class="form-group mgmt-form-full">
                                    <label class="form-label">Permanent Address (English)</label>
                                    <input type="text" name="permanent_address_en" class="form-control" id="ef_addr_en">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">নিবন্ধন তারিখ</label>
                                    <input type="text" name="registration_date" class="form-control" id="ef_reg_date">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">সনদ তারিখ</label>
                                    <input type="text" name="certificate_date" class="form-control" id="ef_cert_date">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">পৃষ্ঠা নং</label>
                                    <input type="number" name="page_number" class="form-control mono" id="ef_page">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ক্রমিক</label>
                                    <input type="number" name="serial" class="form-control mono" id="ef_serial">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">সোর্স ফাইল</label>
                                    <input type="text" name="source_file" class="form-control" id="ef_src">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">স্ট্যাটাস</label>
                                    <select name="status" class="form-control" id="ef_status">
                                        <option value="pending">Pending</option>
                                        <option value="verified">Verified</option>
                                        <option value="incomplete">Incomplete</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mgmt-form-actions">
                                <button type="submit" class="btn-primary" id="btnEditSubmit">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                    <span>আপডেট করুন</span>
                                </button>
                                <button type="button" class="btn-danger" id="btnDeleteRecord">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    <span>মুছে ফেলুন</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab 3: Search & Replace -->
                <div id="tabReplace" class="mgmt-content" hidden>
                    <div class="mgmt-section-header">
                        <h3>সার্চ ও রিপ্লেস</h3>
                        <p>টেক্সট খুঁজে বদলান</p>
                    </div>
                    <div class="mgmt-replace-form">
                        <div class="mgmt-replace-row">
                            <div class="form-group" style="flex:1;">
                                <label class="form-label">খুঁজুন (Find)</label>
                                <input type="text" id="replaceFind" class="form-control" placeholder="খুঁজুন">
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label class="form-label">প্রতিস্থাপন (Replace)</label>
                                <input type="text" id="replaceWith" class="form-control" placeholder="বদলে লিখুন">
                            </div>
                            <div class="form-group" style="max-width:200px;">
                                <label class="form-label">ফিল্ড নির্বাচন</label>
                                <select id="replaceField" class="form-control">
                                    <option value="">সকল ফিল্ড</option>
                                    <option value="name">নাম (বাংলা)</option>
                                    <option value="name_en">নাম (English)</option>
                                    <option value="father_name">পিতার নাম (বাংলা)</option>
                                    <option value="father_name_en">পিতার নাম (English)</option>
                                    <option value="mother_name">মাতার নাম (বাংলা)</option>
                                    <option value="mother_name_en">মাতার নাম (English)</option>
                                    <option value="registration_number">নিবন্ধন নম্বর</option>
                                    <option value="ward">ওয়ার্ড</option>
                                    <option value="birth_place">জন্মস্থান</option>
                                    <option value="place_of_birth_en">Place of Birth</option>
                                    <option value="permanent_address">স্থায়ী ঠিকানা</option>
                                    <option value="permanent_address_en">Permanent Address</option>
                                    <option value="date_of_birth">জন্ম তারিখ</option>
                                    <option value="source_file">সোর্স ফাইল</option>
                                </select>
                            </div>
                            <div class="form-group" style="max-width:180px;">
                                <label class="form-label">অনুসন্ধান মোড</label>
                                <div class="view-toggle-group" style="width:100%;">
                                    <button type="button" class="view-btn active" data-match-mode="partial" style="flex:1; font-size:0.78rem; gap:4px;">
                                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                        শব্দে
                                    </button>
                                    <button type="button" class="view-btn" data-match-mode="exact" style="flex:1; font-size:0.78rem; gap:4px;">
                                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                        এক্সাক্ট
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mgmt-replace-actions">
                            <button type="button" class="btn-secondary" id="btnPreviewReplace">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <span>প্রিভিউ দেখুন</span>
                            </button>
                            <button type="button" class="btn-primary" id="btnExecuteReplace" disabled>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                                <span>সব রিপ্লেস করুন</span>
                            </button>
                        </div>
                    </div>

                    <!-- Replace Preview -->
                    <div id="replacePreview" class="mgmt-replace-preview" hidden>
                        <div class="mgmt-results-header">
                            <span id="replacePreviewCount"></span>
                        </div>
                        <div id="replacePreviewList" class="mgmt-results-list"></div>
                    </div>
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

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Confirm Delete Modal -->
    <div id="confirmModal" class="detail-modal-wrapper" hidden>
        <div class="modal-backdrop" data-close-confirm></div>
        <div class="detail-modal-card" style="max-width:420px;">
            <div class="modal-header-section">
                <div class="modal-candidate-info">
                    <div class="modal-avatar-crest danger" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </div>
                    <div class="modal-titles">
                        <h2>রেকর্ড মুছে ফেলুন?</h2>
                        <p>ফেরানো যাবে না</p>
                    </div>
                </div>
                <button type="button" class="modal-close-btn" data-close-confirm aria-label="নিশ্চিতকরণ বন্ধ করুন"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="modal-body-scrollable" style="padding:24px; text-align:center;">
                <p id="confirmMessage" style="font-size:0.95rem; color:var(--text-secondary);">রেকর্ডটি মুছে ফেলবেন?</p>
                <p id="confirmRecordInfo" class="mono" style="margin-top:8px; font-size:0.85rem; color:var(--text-muted);"></p>
            </div>
            <div class="modal-footer-section" style="justify-content:center; gap:12px;">
                <button type="button" class="btn-secondary" data-close-confirm>বাতিল</button>
                <button type="button" class="btn-danger" id="btnConfirmDelete">হ্যাঁ, মুছে ফেলুন</button>
            </div>
        </div>
    </div>

    <!-- Confirm Replace Modal -->
    <div id="replaceConfirmModal" class="detail-modal-wrapper" hidden>
        <div class="modal-backdrop" data-close-replace-confirm></div>
        <div class="detail-modal-card" style="max-width:480px;">
            <div class="modal-header-section">
                <div class="modal-candidate-info">
                    <div class="modal-avatar-crest warn" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                    </div>
                    <div class="modal-titles">
                        <h2>সার্চ ও রিপ্লেস নিশ্চিত করুন</h2>
                        <p>ফেরানো যাবে না</p>
                    </div>
                </div>
                <button type="button" class="modal-close-btn" data-close-replace-confirm aria-label="নিশ্চিতকরণ বন্ধ করুন"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="modal-body-scrollable" style="padding:24px;">
                <div id="replaceConfirmBody" style="text-align:center;"></div>
            </div>
            <div class="modal-footer-section" style="justify-content:center; gap:12px;">
                <button type="button" class="btn-secondary" data-close-replace-confirm>বাতিল</button>
                <button type="button" class="btn-primary" id="btnConfirmReplace">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                    <span>হ্যাঁ, রিপ্লেস করুন</span>
                </button>
            </div>
        </div>
    </div>

    <script src="js/manage.js?v=<?= time() ?>"></script>
</body>
</html>
