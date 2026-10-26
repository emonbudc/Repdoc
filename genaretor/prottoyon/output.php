<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();
repdoc_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(repdoc_url('genaretor/prottoyon/'));
}

$me = current_user();
$typeKey = prottoyon_clean($_POST['type'] ?? '', 80);
$type = prottoyon_type($typeKey);
if ($type === null) {
    flash_set('error', 'সনদের ধরন সঠিক নয়।');
    redirect(repdoc_url('genaretor/prottoyon/'));
}

$back = repdoc_url('genaretor/prottoyon/form.php?type=' . rawurlencode($typeKey));
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'নিরাপত্তা টোকেন মিলেনি — আবার চেষ্টা করুন।');
    redirect($back);
}

$token = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['submission_token'] ?? '')) ?? '';
if ($token === '' || empty($_SESSION['pp_tokens'][$token])) {
    flash_set('error', 'এই ফর্মটি ইতিমধ্যে জমা হয়েছে বা মেয়াদ শেষ। আবার চেষ্টা করুন।');
    redirect($back);
}
if (!empty($_SESSION['pp_done'][$token])) {
    redirect(repdoc_url('genaretor/prottoyon/view.php?id=' . (int) $_SESSION['pp_done'][$token]));
}
unset($_SESSION['pp_tokens'][$token]);

$office = office_profile();
if (is_udc() && ($me['role'] ?? '') !== 'admin') {
    if (($office['office_name'] ?? '') === '' || ($office['upazila'] ?? '') === '' || ($office['district'] ?? '') === '') {
        flash_set('error', 'আগে অফিস সেটিংস সম্পূর্ণ করুন।');
        redirect(repdoc_url('genaretor/prottoyon/office.php'));
    }
}

$collected = prottoyon_collect_input($_POST);
$data = $collected['data'];
$errors = $collected['errors'];

$photoPath = '';
$photo = $_FILES['photo'] ?? null;
if (is_array($photo)) {
    $stored = prottoyon_store_upload($photo, (int) $me['id'], 'photo', 2 * 1024 * 1024, true);
    if (!empty($stored['ok'])) {
        $photoPath = (string) $stored['path'];
    } elseif (empty($stored['skip'])) {
        $errors[] = (string) ($stored['message'] ?? 'ফটো আপলোড হয়নি।');
    }
}

$docFiles = $_FILES['documents'] ?? null;
if (is_array($docFiles) && is_array($docFiles['name'] ?? null)) {
    $count = count($docFiles['name']);
    if ($count > 5) {
        $errors[] = 'সর্বোচ্চ ৫টি সংযুক্তি দেওয়া যাবে।';
        $count = 0;
    }
    for ($i = 0; $i < $count; $i++) {
        $file = [
            'name' => $docFiles['name'][$i] ?? '',
            'type' => $docFiles['type'][$i] ?? '',
            'tmp_name' => $docFiles['tmp_name'][$i] ?? '',
            'error' => $docFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $docFiles['size'][$i] ?? 0,
        ];
        $stored = prottoyon_store_upload($file, (int) $me['id'], 'doc');
        if (!empty($stored['ok'])) {
            $data['documents'][] = ['path' => (string) $stored['path'], 'name' => (string) ($stored['name'] ?? 'সংযুক্তি')];
        } elseif (empty($stored['skip'])) {
            $errors[] = (string) ($stored['message'] ?? 'সংযুক্তি আপলোড হয়নি।');
        }
    }
}

if ($errors) {
    if ($photoPath !== '') prottoyon_delete_upload($photoPath);
    foreach (($data['documents'] ?? []) as $doc) {
        if (is_array($doc) && !empty($doc['path'])) prottoyon_delete_upload((string) $doc['path']);
    }
    $data['documents'] = [];
    $_SESSION['prottoyon_old'] = $data;
    $_SESSION['prottoyon_errors'] = $errors;
    redirect($back);
}

$appId = prottoyon_app_id();
$office = array_merge(prottoyon_office_defaults(), $office);
try {
    $number = prottoyon_next_cert_no($office);
} catch (Throwable $e) {
    if ($photoPath !== '') prottoyon_delete_upload($photoPath);
    foreach (($data['documents'] ?? []) as $doc) {
        if (is_array($doc) && !empty($doc['path'])) prottoyon_delete_upload((string) $doc['path']);
    }
    $data['documents'] = [];
    flash_set('error', 'সনদ নম্বর তৈরি করা যায়নি — আবার চেষ্টা করুন।');
    redirect($back);
}

$data['meta']['app_id'] = $appId;
$data['meta']['union_code'] = $number['union_code'];
$data['meta']['union_code_fallback'] = $number['fallback'];
$data['meta']['submitted_at'] = date('c');
$data['documents'] = $data['documents'] ?? [];

$charge = charge_tool(prottoyon_tool_key($typeKey), $appId);
if (!$charge['ok']) {
    if ($photoPath !== '') prottoyon_delete_upload($photoPath);
    foreach (($data['documents'] ?? []) as $doc) {
        if (is_array($doc) && !empty($doc['path'])) prottoyon_delete_upload((string) $doc['path']);
    }
    $data['documents'] = [];
    flash_set('error', (string) ($charge['message'] ?? 'ব্যালেন্স পর্যাপ্ত নয়।'));
    $_SESSION['prottoyon_old'] = $data;
    redirect($back);
}

$dob = prottoyon_date_input((string) ($data['personal']['dob'] ?? ''));
$id = log_document(prottoyon_tool_key($typeKey), $appId, $dob, $photoPath, '', $data, $number['cert_no'], $office);
if ($id <= 0) {
    prottoyon_refund((int) $me['id'], (int) ($charge['charged'] ?? 0), 'prottoyon-refund:' . $appId, $appId);
    if ($photoPath !== '') prottoyon_delete_upload($photoPath);
    foreach (($data['documents'] ?? []) as $doc) {
        if (is_array($doc) && !empty($doc['path'])) prottoyon_delete_upload((string) $doc['path']);
    }
    $data['documents'] = [];
    flash_set('error', 'সনদ সংরক্ষণ করা যায়নি — চার্জ ফেরত দেওয়া হয়েছে।');
    $_SESSION['prottoyon_old'] = $data;
    redirect($back);
}

$_SESSION['pp_done'][$token] = $id;
if (count($_SESSION['pp_done'] ?? []) > 20) {
    $_SESSION['pp_done'] = array_slice($_SESSION['pp_done'], -20, null, true);
}
flash_set('ok', 'সনদ তৈরি হয়েছে। প্রিন্ট বা PDF হিসেবে সংরক্ষণ করুন।');
redirect(repdoc_url('genaretor/prottoyon/view.php?id=' . $id));
