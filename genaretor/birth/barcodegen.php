<?php
/**
 * Code 128 Barcode Generator
 * Renders a Code 128 barcode inline as SVG in the browser.
 * Usage: barcode_generator.php?data=YourTextHere&width=2&height=80
 */

// ─── Configuration ────────────────────────────────────────────────────────────
$data        = isset($_GET['data'])   ? $_GET['data']   : '20221019510113898';
$barWidth    = isset($_GET['width'])  ? (int)$_GET['width']  : 3;   // px per module
$barHeight   = isset($_GET['height']) ? (int)$_GET['height'] : 80;  // px height
$quietZone   = $barWidth * 10;
$svgHeight   = $barHeight + 40;

$embed = isset($_GET['embed']);
if ($embed) {
    header('Content-Type: image/svg+xml');
}

// ─── Code 128 character set (value => bar/space pattern) ─────────────────────
// Each pattern is 11 bits: 1=bar, 0=space (plus 2-bit stop pattern added separately)
$code128 = [
//  val  => pattern     char(B)
     0 => '11011001100', //  (space)
     1 => '11001101100', // !
     2 => '11001100110', // "
     3 => '10010011000', // #
     4 => '10010001100', // $
     5 => '10001001100', // %
     6 => '10011001000', // &
     7 => '10011000100', // '
     8 => '10001100100', // (
     9 => '11001001000', // )
    10 => '11001000100', // *
    11 => '11000100100', // +
    12 => '10110011100', // ,
    13 => '10011011100', // -
    14 => '10011001110', // .
    15 => '10111001100', // /
    16 => '10011101100', // 0
    17 => '10011100110', // 1
    18 => '11001110010', // 2
    19 => '11001011100', // 3
    20 => '11001001110', // 4
    21 => '11011100100', // 5
    22 => '11001110100', // 6
    23 => '11101101110', // 7
    24 => '11101001100', // 8
    25 => '11100101100', // 9
    26 => '11100100110', // :
    27 => '11101100100', // ;
    28 => '11100110100', // <
    29 => '11100110010', // =
    30 => '11011011000', // >
    31 => '11011000110', // ?
    32 => '11000110110', // @
    33 => '10100011000', // A
    34 => '10001011000', // B
    35 => '10001000110', // C
    36 => '10110001000', // D
    37 => '10001101000', // E
    38 => '10001100010', // F
    39 => '11010001000', // G
    40 => '11000101000', // H
    41 => '11000100010', // I
    42 => '10110111000', // J
    43 => '10110001110', // K
    44 => '10001101110', // L
    45 => '10111011000', // M
    46 => '10111000110', // N
    47 => '10001110110', // O
    48 => '11101110110', // P
    49 => '11010001110', // Q
    50 => '11000101110', // R
    51 => '11011101000', // S
    52 => '11011100010', // T
    53 => '11011101110', // U
    54 => '11101011000', // V
    55 => '11101000110', // W
    56 => '11100010110', // X
    57 => '11101101000', // Y
    58 => '11101100010', // Z
    59 => '11100011010', // [
    60 => '11101111010', // \
    61 => '11001000010', // ]
    62 => '11110001010', // ^
    63 => '10100110000', // _
    64 => '10100001100', // `
    65 => '10010110000', // a
    66 => '10010000110', // b
    67 => '10000101100', // c
    68 => '10000100110', // d
    69 => '10110010000', // e
    70 => '10110000100', // f
    71 => '10011010000', // g
    72 => '10011000010', // h
    73 => '10000110100', // i
    74 => '10000110010', // j
    75 => '11000010010', // k
    76 => '11001010000', // l
    77 => '11110111010', // m
    78 => '11000010100', // n
    79 => '10001111010', // o
    80 => '10100111100', // p
    81 => '10010111100', // q
    82 => '10010011110', // r
    83 => '10111100100', // s
    84 => '10011110100', // t
    85 => '10011110010', // u
    86 => '11110100100', // v
    87 => '11110010100', // w
    88 => '11110010010', // x
    89 => '11011011110', // y
    90 => '11011110110', // z
    91 => '11110110110', // {
    92 => '10101111000', // |
    93 => '10100011110', // }
    94 => '10001011110', // ~
    95 => '10111101000', // DEL
    96 => '10111100010', // FNC3
    97 => '11110101000', // FNC2
    98 => '11110100010', // SHIFT
    99 => '10111011110', // Code C
   100 => '10111101110', // Code B  <-- START B
   101 => '11101011110', // Code A
   102 => '11110101110', // FNC1
   103 => '11010000100', // START A
   104 => '11010010000', // START B
   105 => '11010011100', // START C
];
$STOP_PATTERN = '1100011101011'; // Stop symbol (13 modules)

// ─── Encode data using Code 128B ──────────────────────────────────────────────
function encode128B(string $data, array $code128, string $stopPattern, int $barWidth): string {
    // Map ASCII printable chars (32–126) to Code128 values 0–94
    $patterns = '';

    // START B = value 104
    $checksum = 104;
    $patterns .= $code128[104];

    for ($i = 0; $i < strlen($data); $i++) {
        $charVal = ord($data[$i]) - 32;
        if ($charVal < 0 || $charVal > 94) {
            $charVal = 0; // replace non-printable with space
        }
        $checksum += ($i + 1) * $charVal;
        $patterns .= $code128[$charVal];
    }

    // Check symbol
    $checkVal = $checksum % 103;
    $patterns .= $code128[$checkVal];

    // STOP
    $patterns .= $stopPattern;

    return $patterns;
}

$bitString = encode128B($data, $code128, $STOP_PATTERN, $barWidth);

// ─── Build SVG ────────────────────────────────────────────────────────────────
$totalModules = strlen($bitString);
$svgWidth     = ($totalModules * $barWidth) + ($quietZone * 2);

$svgBars = '';
$x = $quietZone;
for ($i = 0; $i < $totalModules; $i++) {
    if ($bitString[$i] === '1') {
        $svgBars .= "<rect x=\"{$x}\" y=\"0\" width=\"{$barWidth}\" height=\"{$barHeight}\" fill=\"black\"/>";
    }
    $x += $barWidth;
}

$textX    = $svgWidth / 2;
$textY    = $barHeight + 14;


$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$svgWidth}" height="{$svgHeight}" viewBox="0 0 {$svgWidth} {$svgHeight}">
  <rect width="{$svgWidth}" height="{$svgHeight}" fill="white"/>
  {$svgBars}
</svg>
SVG;

if ($embed) {
    echo $svg;
    exit;
}
?><?php if (!$embed): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Code 128 Barcode Generator</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      background: #f5f5f5;
    }
    .card {
      background: white;
      border-radius: 12px;
      padding: 40px;
      box-shadow: 0 2px 16px rgba(0,0,0,0.10);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 24px;
      min-width: 400px;
    }
    h1 { margin: 0; font-size: 1.3rem; color: #333; }
    .barcode-wrap {
      background: white;
      padding: 20px;
      border: 1px solid #eee;
      border-radius: 6px;
    }
    form { display: flex; flex-direction: column; gap: 10px; width: 100%; }
    label { font-size: 0.85rem; color: #555; }
    input[type=text], input[type=number] {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
      box-sizing: border-box;
    }
    .row { display: flex; gap: 12px; }
    .row > div { flex: 1; }
    button {
      background: #1a1a1a;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 10px;
      font-size: 1rem;
      cursor: pointer;
    }
    button:hover { background: #333; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Code 128 Barcode Generator</h1>

    <div class="barcode-wrap">
      <?= $svg ?>
    </div>

    <form method="GET">
      <label>Data to encode
        <input type="text" name="data" value="<?= htmlspecialchars($data) ?>" placeholder="Enter text or numbers">
      </label>
      <div class="row">
        <div>
          <label>Bar width (px)
            <input type="number" name="width" value="<?= $barWidth ?>" min="1" max="6">
          </label>
        </div>
        <div>
          <label>Bar height (px)
            <input type="number" name="height" value="<?= $barHeight ?>" min="20" max="300">
          </label>
        </div>
      </div>

      <button type="submit">Generate Barcode</button>
    </form>
  </div>
</body>
</html>
<?php endif; ?>