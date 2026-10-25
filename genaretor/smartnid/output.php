<?php
// Central auth + wallet — same pattern as genaretor/birth/output.php
// (replaces the old per-tool auth: ../config/database.php + ../customer/index.php).
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();
repdoc_security_headers();

// output.php
$document_title = "Smart Nid Card - " . uniqid(); // Unique ID for the certificate

// Billing: one charge per generated card (price 0 = free, admins free).
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $charge = charge_tool('smartnid');
    if (!$charge['ok']) {
        http_response_code(402);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>ব্যালেন্স পর্যাপ্ত নয়</title>'
           . '<p style="font:16px/1.7 system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center">'
           . e($charge['message'])
           . '<br><br><a href="index.php">ফর্মে ফিরে যান</a></p>';
        exit;
    }
}

// Handle photo upload and processing (GD re-encode strips embedded scripts)
$upload_path = 'photos/uploads/';
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0755, true);
}

function processImage($file, $maxWidth, $maxHeight) {
    if (($file['error'] ?? 1) !== 0) return false;
    if ((int) ($file['size'] ?? 0) <= 0 || (int) ($file['size'] ?? 0) > 2 * 1024 * 1024) return false;
    $temp_name = $file['tmp_name'];
    $original_name = $file['name'];
    $extension = strtolower((string) pathinfo((string) $original_name, PATHINFO_EXTENSION));
    if ($extension === 'jpeg') $extension = 'jpg';
    if (!in_array($extension, ['jpg', 'png'], true)) return false;
    // Server-side MIME + real-image check (never trust $_FILES['type']).
    try {
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($temp_name);
            if (!in_array($mime, ['image/jpeg', 'image/png'], true)) return false;
        }
    } catch (Throwable $e) { return false; }
    $imgInfo = @getimagesize($temp_name);
    if ($imgInfo === false || !in_array($imgInfo[2] ?? 0, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) return false;
    $extension = ($imgInfo[2] === IMAGETYPE_PNG) ? 'png' : 'jpg';
    $new_name = uniqid('img_', true) . '.' . $extension;
    $upload_file = 'photos/uploads/' . $new_name;

    // Suppress warnings for PNG color profile issues
    $source = null;
    // Create image from uploaded file based on extension
    switch(strtolower($extension)) {
        case 'jpg':
        case 'jpeg':
            $source = @imagecreatefromjpeg($temp_name);
            break;
        case 'png':
            // Suppress libpng warnings
            @ini_set('gd.png.ignore_warning', 1);
            $source = @imagecreatefrompng($temp_name);
            break;
        default:
            return false;
    }

    // Check if image creation failed
    if (!$source) {
        return false;
    }
    
    $width = imagesx($source);
    $height = imagesy($source);
    
    // Calculate new dimensions
    $ratio = min($maxWidth/$width, $maxHeight/$height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Create a new true color image
    $processed_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG images
    if(strtolower($extension) == 'png') {
        imagealphablending($processed_image, false);
        imagesavealpha($processed_image, true);
        $transparent = imagecolorallocatealpha($processed_image, 0, 0, 0, 127);
        imagefilledrectangle($processed_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize the image
    imagecopyresampled($processed_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save with original format
    $success = false;
    switch(strtolower($extension)) {
        case 'jpg':
        case 'jpeg':
            $success = imagejpeg($processed_image, $upload_file, 100);
            break;
        case 'png':
            $success = imagepng($processed_image, $upload_file, 0);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($processed_image);
    
    return $success ? $upload_file : false;
}

$photo_path = '';
$signature_path = '';

if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $photo_path = processImage($_FILES['photo'], 300, 300);
}

if (isset($_FILES['signature']) && $_FILES['signature']['error'] == 0) {
    $signature_path = processImage($_FILES['signature'], 300, 80);
}

// Get form data with defaults for direct access
$name_bn = isset($_POST['name_bn']) ? $_POST['name_bn'] : "নাম বাংলায়";
$name_en = isset($_POST['name_en']) ? $_POST['name_en'] : "Name in English";
$father_name = isset($_POST['father_name']) ? $_POST['father_name'] : "পিতার নাম";
$mother_name = isset($_POST['mother_name']) ? $_POST['mother_name'] : "মাতার নাম";
$dob = isset($_POST['dob']) ? $_POST['dob'] : "31 Jan 1990";
$nidno = isset($_POST['nidno']) ? $_POST['nidno'] : "123 456 7890";
$address1 = isset($_POST['address1']) ? $_POST['address1'] : "৩২/১, খুলনা সিটি কর্পোরেশন - ৩৪০০";
$address2 = isset($_POST['address2']) ? $_POST['address2'] : "খুলনা সদর, খুলনা";
$blood_group = isset($_POST['blood_group']) ? $_POST['blood_group'] : "A+";
$gender = isset($_POST['gender']) ? $_POST['gender'] : "M";
$place_of_birth = isset($_POST['place_of_birth']) ? $_POST['place_of_birth'] : "KHULNA";
$pin = isset($_POST['pin']) ? $_POST['pin'] : "19905678901000007";
// Full submission goes to the database (admin → Document Logs).
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    log_document('smartnid', (string) $nidno, (string) $dob,
        !empty($photo_path) ? 'genaretor/smartnid/' . $photo_path : '',
        !empty($signature_path) ? 'genaretor/smartnid/' . $signature_path : '',
        $_POST);
}

// Format NID number as XXX XXX XXXX
function formatNID($nid) {
    // Remove any existing spaces and non-numeric characters
    $nid = preg_replace('/[^0-9]/', '', $nid);
    // Format as XXX XXX XXXX
    return substr($nid, 0, 3) . ' ' . substr($nid, 3, 3) . ' ' . substr($nid, 6, 4);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars((string) $document_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="robots" content="noindex">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <style type="text/css">
        /* Define the @font-face rule for Kalpurush */
        @font-face {
            font-family: 'Kalpurush';
            src: url('fonts/kalpurush.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        /* Define the @font-face rule for Nikosh */
        @font-face {
            font-family: 'arial';
            src: url('fonts/arial.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'DejaVuSerif';
            src: url('fonts/DejaVuSerif.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        p {margin: 0; padding: 0;}
        .bcxnxx {font-size:18px; font-family:DejaVuSerif; color:#000000;}
        .ft03 {font-size:8px; font-family:'Kalpurush', sans-serif; color:#000000;}
        .address {font-size:10px; font-family:'Kalpurush', sans-serif; color:#000000;}
        .ft04 {font-size:7px; font-family:'arial'; color:#000000;}
        .smalldob {font-size:6px; font-family:'arial'; color:#000000;}
        .ft06 {font-size:6px; font-family:'arial'; color:#000000;}
        .ft07 {font-size:8px; font-family:'arial'; color:#000000;}
        .name_en {font-size:8px; font-family:'arial'; color:#000000;}
        .nidno {font-size:10px; font-family:'Arial'; color:#000000;}
        .dob {font-size:8.1px; font-family:'arial'; color:#000000;}
        .ft05 {font-size:11.5px; font-family:'Kalpurush'; color:#000000;}
        .name_bn {font-size:11.5px; font-family:'Kalpurush'; color:#000000;}
        .mababa {font-size:10px; font-family:'Kalpurush'; color:#000000;}

    </style>
    <script src="../assets/JavaScript/bcmath-min.js" type="text/javascript"></script>
    <script src="../assets/JavaScript/pdf417-min.js" type="text/javascript"></script>
</head>
<body bgcolor=white>
<div id="page1-div" style="position:relative;width:892px;height:1263px;">
    
    <img src="photos/bg.png" alt="Back Logo" style="width: 650px; height: auto; display: block; margin: 50px auto;position:absolute;top:20px;left:130px;">
    <?php if ($photo_path): ?>
    <img src="<?php echo e($photo_path); ?>" alt="User Photo" style="width: 80px; height: 140px; object-fit: contain; display: block; margin: 50px auto;position:absolute;top:60px;left:145px;">
    <img src="<?php echo e($photo_path); ?>" alt="User Photo" style="width: 25px; height: 28px; object-fit: contain; display: block; margin: 50px auto;position:absolute;top:53px;left:385px; opacity: 0.5;">
    <img src="<?php echo e($photo_path); ?>" alt="User Photo" style="width: 25px; height: 28px; object-fit: contain; display: block; margin: 50px auto;position:absolute;top:90px;left:740px; opacity: 0.5;">
    <?php else: ?>
    <img src="photos/mains.png" alt="Back Logo" style="width: 80px; height: 80px; object-fit: contain; display: block; margin: 50px auto;position:absolute;top:78px;left:140px;">
    <img src="photos/mains.png" alt="Back Logo" style="width: 25px; height: 25px; object-fit: contain; display: block; margin: 50px auto;position:absolute;top:52px;left:383px; opacity: 0.7;">
    <?php endif; ?>

    <?php if ($signature_path): ?>
    <img src="<?php echo e($signature_path); ?>" alt="User Signature" style="width: 80px; height: 25px; object-fit: contain; display: block; margin: 50px auto;position:absolute;top:180px;left:140px;">
    <?php endif; ?>

    <p style="font-weight: none;position:absolute;top:120px;left:381px;white-space:nowrap;opacity: 0.9;" class="smalldob"><?php echo e($dob ?? ''); ?></p>

    <img src="photos/imsmask.png" alt="Hedavanga Sorkar" style="width: 112px; height: auto; display: block; margin: 0px auto;position:absolute;top:117px;left:140px;">

    

    <p style="font-weight: none;position:absolute;top:129px;left:229px;white-space:nowrap" class="ft03">নাম</p>
    <p style="font-weight: none;position:absolute;top:156px;left:229px;white-space:nowrap" class="ft04">Name</p>
    <p style="font-weight: none;position:absolute;top:178px;left:229px;white-space:nowrap" class="ft03">পিতা</p>
    <p style="font-weight: none;position:absolute;top:200px;left:229px;white-space:nowrap" class="ft03">মাতা</p>
    <p style="font-weight: none;position:absolute;top:235px;left:229px;white-space:nowrap; font-size: 7px;" class="ft06">Date Of Birth</p>
    <p style="font-weight: none;position:absolute;top:247px;left:229px;white-space:nowrap; font-size: 7px;" class="ft06">NID No.</p>
    <p style="font-weight: none;position:absolute;top:172px;left:475px;white-space:nowrap" class="ft06">Blood Group: </p>
    <p style="font-weight: none;position:absolute;top:172px;left:695px;white-space:nowrap" class="ft06">Issue Date:</p>
    <p style="font-weight: none;position:absolute;top:172px;left:555px;white-space:nowrap" class="ft06">Place of Birth:</p>




    <p style="font-weight: bold;position:absolute;top:137px;left:229px;white-space:nowrap" class="name_bn"><?php echo e($name_bn ?? ''); ?></p>
    <p style="font-weight: bold;position:absolute;top:166px;left:229px;white-space:nowrap" class="name_en"><?php echo e($name_en ?? ''); ?></p>    
    <p style="font-weight: none;position:absolute;top:187px;left:229px;white-space:nowrap" class="mababa"><?php echo e($father_name ?? ''); ?></p>
    <p style="font-weight: none;position:absolute;top:210px;left:229px;white-space:nowrap" class="mababa"><?php echo e($mother_name ?? ''); ?></p>
    <p style="font-weight: bold;position:absolute;top:235px;left:279px;white-space:nowrap" class="dob"><?php echo e($dob ?? ''); ?></p>
    <p style="font-weight: bold;position:absolute;top:245.5px;left:267px;white-space:nowrap" class="nidno"><?php echo e(formatNID($nidno ?? '')); ?></p>
    <p class="pinno" style="display:none;"><?php echo e($pin ?? ''); ?></p>
    <p style="font-weight: none;position:absolute;top:130px;left:475px;white-space:nowrap" class="address"><?php echo e($address1 ?? ''); ?></p>
    <p style="font-weight: none;position:absolute;top:142px;left:475px;white-space:nowrap" class="address"><?php echo e($address2 ?? ''); ?></p>
    <p style="font-weight: bold;position:absolute;top:170.7px;left:512px;white-space:nowrap" class="ft07"><?php echo e($blood_group ?? ''); ?></p>
    <p style="font-weight: bold;position:absolute;top:170.5px;left:594px;white-space:nowrap" class="ft07"><?php echo e($place_of_birth ?? ''); ?></p>
    <p style="font-weight: bold;position:absolute;top:170.5px;left:728px;white-space:nowrap" class="ft07"><?php echo e(date('d M Y')); ?></p>

    <!-- Add barcode container -->
    <div id="barcode" style="position:absolute;top:80px;left:480px;width:285px;height:20px;"></div>

    <!-- Add MRZ Text -->
    <?php
    // Format name for MRZ (uppercase, remove spaces and special characters)
    $mrz_name = strtoupper(preg_replace('/[^A-Za-z]/', '<', $name_en));
    
    // Get NID number without spaces and special characters
    $mrz_nid = preg_replace('/[^0-9]/', '', $nidno);
    
    // Format date as YYMMDD
    $date_obj = DateTime::createFromFormat('d M Y', $dob);
    $mrz_dob = $date_obj ? $date_obj->format('ymd') : '000000';
    
    // Format first line: I<BGD + NID number + filler
    $line1 = str_pad("I<BGD" . $mrz_nid, 30, "<");
    
    // Format second line: DOB + Gender + NID + BGD + filler + check digit
    $line2 = str_pad($mrz_dob . $gender . $mrz_nid . "BGD", 29, "<") . "4";
    
    // Format third line: Name with proper spacing
    $name_parts = explode(' ', $name_en);
    $surname = strtoupper($name_parts[0]);
    $givennames = strtoupper(implode('<', array_slice($name_parts, 1)));
    $line3 = str_pad($surname . "<<" . $givennames, 30, "<");
    ?>
    <div style="position:absolute;top:210px;left:475px;width:290px;background-color:#ffffff;">
        <pre style="font-family:'Courier', monospace;font-size:13px;margin:0;padding:0;line-height:1;white-space:pre;letter-spacing:2px;color:#000000;-webkit-text-stroke: 0.2px black;"><?php 
            echo htmlspecialchars($line1) . "\n" . htmlspecialchars($line2) . "\n" . htmlspecialchars($line3); 
        ?></pre>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // User values are JSON-encoded server-side to avoid JS-string breakout.
        <?php $hub3_code = '<pin>' . ($pin ?? '') . '</pin><n>' . ($name_en ?? '') . '</n><DOB>' . ($dob ?? '') . '</DOB><FP></FP><F>Right Index</F><TYPE>A</TYPE><V>2.0</V><ds>302c0214674e6fbb5028864b862521ff1f2f699f708abedf02141c67e9f6bb3bc25d73ce29a0cc7d11e122b8179f</ds>'; ?>
        var hub3_code = <?php echo json_encode($hub3_code, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

        PDF417.init(hub3_code);
        var barcode = PDF417.getBarcodeArray();

        // block sizes (width and height) in pixels
        var bw = 2;
        var bh = 2;

        // create canvas element based on number of columns and rows in barcode
        var canvas = document.createElement('canvas');
        canvas.width = bw * barcode['num_cols'];
        canvas.height = bh * barcode['num_rows'];
        
        // Set the canvas display style properties
        canvas.style.width = '100%';
        canvas.style.height = 'auto';
        canvas.style.maxHeight = '39px';
        canvas.style.display = 'block';
        
        // Clear the barcode container before appending
        var barcodeContainer = document.getElementById('barcode');
        while (barcodeContainer.firstChild) {
            barcodeContainer.removeChild(barcodeContainer.firstChild);
        }
        
        barcodeContainer.appendChild(canvas);

        var ctx = canvas.getContext('2d');
        // Make sure the rendering context is properly scaled
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // graph barcode elements
        var y = 0;
        // for each row
        for (var r = 0; r < barcode['num_rows']; ++r) {
            var x = 0;
            // for each column
            for (var c = 0; c < barcode['num_cols']; ++c) {
                if (barcode['bcode'][r][c] == 1) {
                    ctx.fillRect(x, y, bw, bh);
                }
                x += bw;
            }
            y += bh;
        }
    });

    window.print();
    </script>

</div>
<!-- Code injected by live-server -->
<script>
	// <![CDATA[  <-- For SVG support
	if ('WebSocket' in window) {
		(function () {
			function refreshCSS() {
				var sheets = [].slice.call(document.getElementsByTagName("link"));
				var head = document.getElementsByTagName("head")[0];
				for (var i = 0; i < sheets.length; ++i) {
					var elem = sheets[i];
					var parent = elem.parentElement || head;
					parent.removeChild(elem);
					var rel = elem.rel;
					if (elem.href && typeof rel != "string" || rel.length == 0 || rel.toLowerCase() == "stylesheet") {
						var url = elem.href.replace(/(&|\?)_cacheOverride=\d+/, '');
						elem.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_cacheOverride=' + (new Date().valueOf());
					}
					parent.appendChild(elem);
				}
			}
			var protocol = window.location.protocol === 'http:' ? 'ws://' : 'wss://';
			var address = protocol + window.location.host + window.location.pathname + '/ws';
			var socket = new WebSocket(address);
			socket.onmessage = function (msg) {
				if (msg.data == 'reload') window.location.reload();
				else if (msg.data == 'refreshcss') refreshCSS();
			};
			if (sessionStorage && !sessionStorage.getItem('IsThisFirstTime_Log_From_LiveServer')) {
				console.log('Live reload enabled.');
				sessionStorage.setItem('IsThisFirstTime_Log_From_LiveServer', true);
			}
		})();
	}
	else {
		console.error('Upgrade your browser. This Browser is NOT supported WebSocket for Live-Reloading.');
	}
	// ]]>
</script>
</body>
</html>