<?php
/**
 * DocxParser — shared DOCX → records parsing logic.
 *
 * Used by both:
 *   - build.php   (CLI / local full rebuild into SQLite)
 *   - install.php (InfinityFree-safe chunked web import into MySQL)
 */

const DOCX_BANGLA_DIGITS = '০১২৩৪৫৬৭৮৯';
const DOCX_ASCII_DIGITS  = '0123456789';

function docxBanglaToInt(string $s): int {
    $trans = array_combine(
        preg_split('//u', DOCX_BANGLA_DIGITS, -1, PREG_SPLIT_NO_EMPTY),
        preg_split('//u', DOCX_ASCII_DIGITS, -1, PREG_SPLIT_NO_EMPTY)
    );
    return (int) strtr($s, $trans);
}

function docxIsHeaderRow(string $text): bool {
    $t = mb_strtolower($text);
    foreach (['নিবন্ধনের', 'নাম্বার', 'জন্ম নিবন্ধন', 'সার্টিফিকেট'] as $kw) {
        if (mb_stripos($t, $kw) !== false) return true;
    }
    return false;
}

function docxRemoveSpacesInDigits(string $text): string {
    $result = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $bangla = preg_split('//u', DOCX_BANGLA_DIGITS, -1, PREG_SPLIT_NO_EMPTY);
    $ascii  = preg_split('//u', DOCX_ASCII_DIGITS, -1, PREG_SPLIT_NO_EMPTY);
    $i = 0;
    $n = count($result);
    while ($i < $n) {
        if (in_array($result[$i], $bangla, true) || in_array($result[$i], $ascii, true)) {
            $i++;
            while ($i < $n && $result[$i] === ' ') {
                if ($i + 1 < $n && (in_array($result[$i + 1], $bangla, true) || in_array($result[$i + 1], $ascii, true))) {
                    $result[$i] = '';
                    $i++;
                } else {
                    break;
                }
            }
        } else {
            $i++;
        }
    }
    return implode('', $result);
}

function docxCleanText(string $text): string {
    $text = docxRemoveSpacesInDigits($text);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

function docxCleanName(string $name): string {
    $name = docxCleanText($name);
    $name = preg_replace('/^(মোছাঃ?|মোছা:?\s*|মোঃ?\s*|মো:\s*|মো\s+|মহোদয়া:?\s*)/u', '', $name);
    $name = preg_replace('/^(শ্রী:?\s*|শ্রীমতী:?\s*|শ্রীমতি:?\s*)/u', '', $name);
    $name = preg_replace('/^:\s*/u', '', $name);
    return trim($name);
}

function docxCleanParentName(string $name): string {
    $name = docxCleanText($name);
    $name = preg_replace('/^(মোছাঃ?|মোছা:?\s*|মো:\s*|মোঃ?\s*|মো\s+)/u', '', $name);
    $name = preg_replace('/^(মৃতমৃ\s*|মৃত\s+)/u', '', $name);
    $name = preg_replace('/^[.:\]\s*]/u', '', $name);
    $name = trim($name);
    if (preg_match('/^\d+$/u', $name) || mb_strlen($name) < 2) return '';
    return $name;
}

function docxCleanDate(string $date): string { return docxCleanText($date); }

function docxCleanAddress(string $addr): string {
    $addr = docxCleanText($addr);
    $addr = preg_replace('/মৃতমৃ\s*/u', '', $addr);
    return trim($addr, " \t\n\r।,");
}

function docxCleanRegNumber(string $num): string {
    return preg_replace('/\s+/', '', $num);
}

function docxCleanGender(string $gender): string {
    $g = trim(mb_strtoupper($gender));
    if (mb_stripos($g, 'FEMALE') !== false || mb_stripos($g, 'মহিলা') !== false) return 'FEMALE';
    if (mb_stripos($g, 'MALE') !== false || mb_stripos($g, 'পুরুষ') !== false) return 'MALE';
    return $g;
}

function docxCleanWard(string $ward): string {
    $ward = docxCleanText($ward);
    $ward = preg_replace('/ওয়ার্ড\s*-\s*র্ড\s*/u', 'ওয়ার্ড ', $ward);
    $ward = preg_replace('/ওয়ার্ড\s*/u', '', $ward);
    return trim($ward);
}

function docxGetCellText(SimpleXMLElement $tc): string {
    $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $lines = [];
    foreach ($tc->children($ns)->p as $p) {
        $line = '';
        foreach ($p->children($ns)->r as $r) {
            if (isset($r->t)) {
                $line .= (string) $r->t;
            }
        }
        $lines[] = $line;
    }
    return implode("\n", $lines);
}

function docxParseFile(string $filepath): array {
    $pages = [];
    $currentPage = 0;
    $currentPageRows = [];

    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return ['error' => "Cannot open $filepath", 'pages' => []];
    }
    $docXml = $zip->getFromName('word/document.xml');
    $zip->close();
    if ($docXml === false) {
        return ['error' => "document.xml missing in $filepath", 'pages' => []];
    }

    $xml = new SimpleXMLElement($docXml);
    $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $body = $xml->children($ns)->body;
    if ($body === null) return ['error' => 'empty body', 'pages' => []];

    foreach ($body->children($ns) as $child) {
        $tag = $child->getName();

        if ($tag === 'p') {
            $text = '';
            foreach ($child->children($ns)->r as $r) {
                if (isset($r->t)) $text .= (string) $r->t;
            }
            $text = trim($text);
            // Page markers like "পাতা - ৩", "পাতা-12", "পৃষ্ঠা - ৫" (Bangla or English digits).
            if (preg_match('/পা?তা\s*-\s*([০-৯0-9]+)/u', $text, $m)
                || preg_match('/পৃষ্ঠা\s*-\s*([০-৯0-9]+)/u', $text, $m)
                || preg_match('/প[^\s]*ত[^\s]*\s*-\s*([০-৯0-9]+)/u', $text, $m)) {
                if (!empty($currentPageRows)) {
                    $pages[] = ['page' => $currentPage, 'rows' => $currentPageRows];
                }
                $currentPage = docxBanglaToInt($m[1]);
                $currentPageRows = [];
            }
        } elseif ($tag === 'tbl') {
            $rows = [];
            foreach ($child->children($ns)->tr as $tr) {
                $cells = [];
                foreach ($tr->children($ns)->tc as $tc) {
                    $cells[] = docxGetCellText($tc);
                }
                if (!empty($cells)) $rows[] = $cells;
            }
            $currentPageRows = array_merge($currentPageRows, $rows);
        }
    }

    if (!empty($currentPageRows)) {
        $pages[] = ['page' => $currentPage, 'rows' => $currentPageRows];
    }
    return ['error' => null, 'pages' => $pages];
}

function docxExtractRecords(array $pages, string $sourceFile): array {
    $records = [];
    foreach ($pages as $pageData) {
        $pageNum = $pageData['page'];
        foreach ($pageData['rows'] as $row) {
            if (count($row) < 13) continue;
            if (docxIsHeaderRow($row[0]) || docxIsHeaderRow($row[3])) continue;

            $name = docxCleanName($row[3]);
            if (mb_strlen($name) < 2) continue;

            $regNum     = docxCleanRegNumber($row[1]);
            $father     = docxCleanParentName($row[9]);
            $mother     = docxCleanParentName($row[7]);
            $dob        = docxCleanDate($row[4]);
            $gender     = docxCleanGender($row[5]);
            $birthPlace = docxCleanAddress($row[6]);
            $address    = docxCleanAddress($row[11]);
            $regDate    = docxCleanDate($row[0]);
            $certDate   = docxCleanDate($row[12]);
            $ward       = docxCleanWard($row[2]);

            $searchText = implode(' ', array_filter([
                $name, $father, $mother, $address, $birthPlace, $regNum
            ]));

            $records[] = [
                $regNum, $name, $father, $mother, $dob, $gender,
                $birthPlace, $address, $regDate, $certDate,
                $ward, $pageNum, 0, $sourceFile, $searchText
            ];
        }
    }
    return $records;
}

/** Sorted list of .docx filenames inside the data/ directory. */
function docxListFiles(string $dataDir): array {
    $docxFiles = [];
    if (is_dir($dataDir)) {
        foreach (scandir($dataDir) as $f) {
            if (stripos($f, '.docx') !== false) $docxFiles[] = $f;
        }
    }
    sort($docxFiles);
    return $docxFiles;
}
