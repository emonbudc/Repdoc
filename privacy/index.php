<?php
declare(strict_types=1);

/**
 * Repdoc — Privacy Policy (গোপনীয়তা নীতি).
 *
 * Public, indexable page.
 * Design: single-flow, full-width sections, no cards or accordions.
 *
 * Privacy statements are written to reflect the actual implementation:
 * - Form submissions may be stored in the server database.
 * - Photos and signatures may be stored on server disk.
 * - Passwords are hashed rather than stored in plain text.
 * - Form rendering does not imply end-to-end encryption.
 * - No absolute guarantee of security is claimed.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';

$SEO_TITLE = 'গোপনীয়তা নীতি (Privacy Policy) — Repdoc';

$SEO_DESC = 'Repdoc-এর গোপনীয়তা নীতি জানুন। আপনার তথ্যের গোপনীয়তা, নিরাপত্তা, ফর্ম প্রক্রিয়াকরণ, ডেটা সংরক্ষণ এবং ব্যবহারকারীর অধিকার সম্পর্কে বিস্তারিত।';

$CANON     = repdoc_canonical('privacy/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');

?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($SEO_TITLE) ?></title>

    <meta
        name="description"
        content="<?= e($SEO_DESC) ?>"
    >

    <meta name="theme-color" content="#F8FAFC">

    <?php
    repdoc_seo_head([
        'title'       => $SEO_TITLE,
        'description' => $SEO_DESC,
        'canonical'   => $CANON,
        'type'        => 'website',
        'imageAlt'    => 'Repdoc — প্রাইভেসি পলিসি',
        'locale'      => 'bn_BD',
    ]);

    repdoc_jsonld([
        '@type'       => 'WebPage',
        '@id'         => $CANON . '#page',
        'name'        => $SEO_TITLE,
        'url'         => $CANON,
        'description' => $SEO_DESC,
        'inLanguage'  => 'bn',
        'isPartOf'    => [
            '@id' => repdoc_canonical() . '#website',
        ],
    ]);
    ?>

    <link
        rel="icon"
        type="image/png"
        href="<?= e(repdoc_url('public/favicon.png')) ?>"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>"
    >

    <link
        rel="stylesheet"
        href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>"
    >

    <style>
        .pp-page {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px 64px;
        }

        .pp-crumbs {
            font-size: .85rem;
            margin: 0 0 8px;
            color: var(--text-muted);
        }

        .pp-crumbs a {
            color: inherit;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .pp-crumbs a:hover {
            color: var(--text-primary);
        }

        .pp-page h1 {
            margin: 20px 0 12px;
            line-height: 1.45;
            letter-spacing: -.02em;
        }

        .pp-lead {
            max-width: 900px;
            font-size: 1.1rem;
            line-height: 1.9;
            margin: 0 0 16px;
        }

        .pp-updated {
            color: var(--text-muted);
            font-size: .9rem;
            margin: 0 0 28px;
        }

        .pp-section {
            border-top: 1px solid var(--border-color);
            padding: 24px 0;
        }

        .pp-section h2 {
            margin: 0 0 12px;
            font-size: 1.15rem;
            line-height: 1.6;
        }

        .pp-section p,
        .pp-section li {
            line-height: 1.9;
            margin: 0 0 10px;
        }

        .pp-section p:last-child,
        .pp-section li:last-child {
            margin-bottom: 0;
        }

        .pp-section ul {
            margin: 0;
            padding-inline-start: 22px;
        }

        .pp-section li {
            padding-inline-start: 4px;
        }

        .pp-section a {
            text-underline-offset: 3px;
        }

        .pp-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
            margin-top: 8px;
        }

        .pp-fine {
            text-align: center;
            margin-top: 32px;
            line-height: 1.8;
        }

        .pp-emphasis {
            font-weight: 600;
        }

        @media (max-width: 600px) {
            .pp-page {
                padding: 24px 16px 40px;
            }

            .pp-page h1 {
                font-size: 1.65rem;
                margin-top: 18px;
            }

            .pp-lead {
                font-size: 1rem;
                line-height: 1.85;
            }

            .pp-section {
                padding: 20px 0;
            }

            .pp-section h2 {
                font-size: 1.08rem;
            }

            .pp-actions {
                flex-direction: column;
            }

            .pp-actions .btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>

<body class="auth-body">

    <main class="pp-page">

        <nav class="pp-crumbs" aria-label="Breadcrumb">
            <a href="<?= e($HOME) ?>">হোম</a>
            <span aria-hidden="true"> › </span>
            <span aria-current="page">গোপনীয়তা নীতি</span>
        </nav>

        <a class="auth-brand" href="<?= e($HOME) ?>">
            <img
                src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>"
                alt=""
                width="96"
                height="32"
                loading="eager"
                decoding="async"
            >

            <span>
                <strong>Repdoc</strong>
                <small>repdoc.freedev.app · union toolkit</small>
            </span>
        </a>

        <h1>গোপনীয়তা নীতি</h1>

        <p class="pp-lead">
            আপনার তথ্যের গোপনীয়তা ও নিরাপত্তা আমাদের কাছে অত্যন্ত
            গুরুত্বপূর্ণ। Repdoc-এ আমরা আপনার তথ্য সুরক্ষিত রাখা,
            অননুমোদিত প্রবেশ প্রতিরোধ করা এবং আপনার তথ্যের যথাযথ
            ব্যবহার নিশ্চিত করতে প্রতিশ্রুতিবদ্ধ।
            আমাদের লক্ষ্য হলো একটি নিরাপদ, নির্ভরযোগ্য ও সহজ
            ডিজিটাল সেবা প্রদান করা, যেখানে আপনি আত্মবিশ্বাসের
            সঙ্গে আপনার প্রয়োজনীয় কাজ সম্পন্ন করতে পারবেন।
        </p>

        <p class="pp-updated">
            সর্বশেষ হালনাগাদ: ১৬ অক্টোবর ২০২৬
        </p>

        <!-- Section 1: Information security -->
        <section class="pp-section" aria-labelledby="pp-safe">

            <h2 id="pp-safe">
                ১. আপনার তথ্যের নিরাপত্তা
            </h2>

            <p>
                আপনার তথ্যের গোপনীয়তা রক্ষা করা Repdoc-এর
                অন্যতম প্রধান অগ্রাধিকার। তথ্য সুরক্ষিত রাখতে
                আমরা উপযুক্ত প্রযুক্তিগত ও প্রশাসনিক
                নিরাপত্তাব্যবস্থা বজায় রাখার চেষ্টা করি।
            </p>

            <ul>
                <li>
                    সেবা পরিচালনার জন্য প্রয়োজনীয় তথ্য আমাদের
                    নিয়ন্ত্রিত সার্ভারে সংরক্ষণ করা হতে পারে।
                </li>

                <li>
                    পাসওয়ার্ড সরাসরি সংরক্ষণ করা হয় না;
                    নিরাপদ পদ্ধতিতে হ্যাশ করে রাখা হয়।
                </li>

                <li>
                    অননুমোদিত প্রবেশ ও তথ্যের অপব্যবহার
                    প্রতিরোধে উপযুক্ত নিরাপত্তাব্যবস্থা
                    প্রয়োগ করা হয়।
                </li>

                <li>
                    আপনার তথ্য অনুমতি ছাড়া প্রকাশ বা বিক্রি
                    করা আমাদের নীতি নয়।
                </li>
            </ul>

            <p>
                <strong>আমাদের অঙ্গীকার:</strong>
                আপনার তথ্যের নিরাপত্তা ও গোপনীয়তাকে আমরা
                সর্বোচ্চ গুরুত্ব দিই। তবে ইন্টারনেটভিত্তিক
                কোনো সেবায় সব ধরনের ঝুঁকি থেকে শতভাগ
                নিরাপত্তার পরম নিশ্চয়তা দেওয়া সম্ভব নয়।
            </p>

        </section>

        <!-- Section 2: Forms and document generation -->
        <section class="pp-section" aria-labelledby="pp-flow">

            <h2 id="pp-flow">
                ২. ফর্ম ও ডকুমেন্ট তৈরির প্রক্রিয়া
            </h2>

            <p>
                Repdoc-এর ফর্মগুলো এমনভাবে তৈরি করা হয়েছে,
                যাতে ব্যবহারকারী একটি অ্যাকাউন্টে লগইন করে
                প্রয়োজনীয় তথ্য প্রদান, ডকুমেন্ট তৈরি এবং
                Word ও PDF ফরম্যাটে ডাউনলোডের কাজ একই
                প্ল্যাটফর্মে সম্পন্ন করতে পারেন।
            </p>

            <p>
                আমাদের ফর্মগুলো একটি সমন্বিত কার্যপ্রবাহে
                রেন্ডার ও পরিচালিত হয়। ফর্ম পূরণ থেকে
                ডকুমেন্ট তৈরি ও ডাউনলোড পর্যন্ত প্রক্রিয়াটি
                একই সেবার অংশ হিসেবে পরিচালিত হয়।
            </p>

            <p>
                তবে <span class="pp-emphasis">End-to-end rendering</span>
                এবং <span class="pp-emphasis">End-to-end encryption</span>
                এক বিষয় নয়। ফর্ম রেন্ডার করা বা ডকুমেন্ট
                তৈরি করার অর্থ এই নয় যে তথ্য পুরো প্রক্রিয়ায়
                এনক্রিপ্টেড থাকে অথবা কখনো সার্ভারে পৌঁছায় না।
            </p>

            <p>
                সেবার কার্যক্রম অনুযায়ী ফর্মে জমা দেওয়া তথ্য,
                ডকুমেন্ট-সংক্রান্ত রেকর্ড এবং আপলোড করা ছবি
                বা স্বাক্ষর আমাদের সার্ভারে সংরক্ষিত হতে পারে।
                তাই আপনার তথ্য সার্ভারে কখনোই যায় না—
                এমন দাবি আমরা করি না।
            </p>

        </section>

        <!-- Section 3: Access and disclosure -->
        <section class="pp-section" aria-labelledby="pp-access">

            <h2 id="pp-access">
                ৩. আপনার তথ্য কারা দেখতে পারেন?
            </h2>

            <p>
                আপনার তথ্যের প্রবেশাধিকার অনুমোদিত
                ব্যবহারকারী এবং সেবা পরিচালনার দায়িত্বে থাকা
                অনুমোদিত অ্যাডমিনের মধ্যে সীমাবদ্ধ রাখার
                উদ্দেশ্যে নিরাপত্তাব্যবস্থা প্রয়োগ করা হয়।
            </p>

            <ul>
                <li>
                    আপনার অ্যাকাউন্টের মাধ্যমে সংশ্লিষ্ট
                    তথ্য ও সেবা ব্যবহার করা যায়।
                </li>

                <li>
                    সাইট পরিচালনা, কারিগরি সহায়তা ও
                    প্রয়োজনীয় প্রশাসনিক কাজের জন্য
                    অনুমোদিত অ্যাডমিন তথ্য দেখতে পারেন।
                </li>

                <li>
                    আপনার তথ্য প্রকাশ্যে প্রদর্শন বা
                    অননুমোদিত তৃতীয় পক্ষের কাছে বিক্রি
                    করা আমাদের নীতি নয়।
                </li>
            </ul>

            <p>
                আইনগত বাধ্যবাধকতা বা বৈধ নিরাপত্তাজনিত
                প্রয়োজন দেখা দিলে প্রযোজ্য আইন অনুযায়ী
                তথ্য ব্যবহারের প্রয়োজন হতে পারে।
            </p>

        </section>

        <!-- Section 4: Cookies and third parties -->
        <section class="pp-section" aria-labelledby="pp-cookies">

            <h2 id="pp-cookies">
                ৪. কুকি ও তৃতীয় পক্ষের সেবা
            </h2>

            <p>
                লগইন অবস্থা বজায় রাখা এবং সাইটের প্রয়োজনীয়
                কার্যক্রম পরিচালনার জন্য সেশন কুকি ব্যবহার
                করা হতে পারে।
            </p>

            <p>
                ফন্ট ও ডিজাইন-সম্পর্কিত কিছু রিসোর্স
                তৃতীয় পক্ষের CDN থেকে লোড হতে পারে।
                এর ফলে সংশ্লিষ্ট সেবাদাতার কাছে IP ঠিকানা,
                ব্রাউজারের তথ্য বা অন্যান্য প্রয়োজনীয়
                প্রযুক্তিগত তথ্য পৌঁছাতে পারে।
            </p>

            <p>
                আমরা বিজ্ঞাপন বা ট্র্যাকিং কুকি ব্যবহার
                না করার নীতি অনুসরণ করি। তবে সাইটে
                সংযুক্ত তৃতীয় পক্ষের সেবাগুলোর নিজস্ব
                তথ্য-সংগ্রহ ও গোপনীয়তা নীতি আলাদা হতে পারে।
            </p>

        </section>

        <!-- Section 5: User rights -->
        <section class="pp-section" aria-labelledby="pp-rights">

            <h2 id="pp-rights">
                ৫. আপনার অধিকার ও তথ্য সংশোধন
            </h2>

            <p>
                আপনি আপনার তথ্য সংশোধন, অ্যাকাউন্ট-সংক্রান্ত
                সহায়তা অথবা অ্যাকাউন্ট মুছে ফেলার অনুরোধ
                জানাতে পারেন।
            </p>

            <p>
                এ জন্য আপনার অ্যাকাউন্টে
                <a href="<?= e($LOGIN_URL) ?>">লগ ইন</a>
                করে সাইট অ্যাডমিনের সঙ্গে যোগাযোগ করুন।
                প্রয়োজন অনুযায়ী আপনার UDC অফিসের মাধ্যমেও
                সহায়তা নিতে পারেন।
            </p>

            <p>
                প্রযোজ্য আইন এবং বৈধ তথ্য সংরক্ষণ-সংক্রান্ত
                বাধ্যবাধকতা অনুসারে অনুরোধগুলো পর্যালোচনা
                করা হবে।
            </p>

        </section>

        <!-- Section 6: Policy updates -->
        <section class="pp-section" aria-labelledby="pp-updates">

            <h2 id="pp-updates">
                ৬. গোপনীয়তা নীতির পরিবর্তন
            </h2>

            <p>
                সেবার কার্যক্রম, নিরাপত্তাব্যবস্থা বা
                প্রযোজ্য প্রয়োজন অনুযায়ী এই নীতি পরিবর্তিত
                হতে পারে। গুরুত্বপূর্ণ পরিবর্তন হলে
                এই পৃষ্ঠায় সংশোধিত নীতি ও হালনাগাদের
                তারিখ প্রকাশ করা হবে।
            </p>

        </section>

        <!-- Navigation actions -->
        <div class="pp-actions">

            <a
                class="btn btn-primary"
                href="<?= e($HOME) ?>"
            >
                হোমপেজে ফিরুন
            </a>

            <a
                class="btn btn-secondary"
                href="<?= e($LOGIN_URL) ?>"
            >
                লগ ইন
            </a>

        </div>

        <p class="auth-sub pp-fine">
            Repdoc — আপনার ডিজিটাল কাজের জন্য একটি
            নির্ভরযোগ্য ও দায়িত্বশীল প্ল্যাটফর্ম।
            <br>
            Independent tools site — not a government portal.
        </p>

    </main>

</body>
</html>