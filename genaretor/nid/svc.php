<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Server Copy — Repdoc</title>
    <meta name="robots" content="noindex">
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        .container {
            position: relative;
        }

        .bgImg {
            width: 210mm;
            height: 297mm;
        }

        .avatar {
            width: 130px;
            height: 151px;
            position: absolute;
            top: 187px;
            left: 333px;
            background: red;
            border-radius: 16px;
        }

        p {
            font-size: 15px;
        }

        p.inLeft {
            position: absolute;
            left: 110px;
            opacity: 0.9;
        }

        p.relagionKey.inLeft {
            top: 790px;
        }

        p.mobileKey.inLeft {
            top: 817px;
        }

        p.inRight {
            max-height: 0.393in;
            max-width: 6.33in;
        }

        .inRight {
            position: absolute;
            left: 264px;
        }

        p.nid.inRight {
            top: 400px;
        }

        p.pin.inRight {
            top: 428px;
        }

        p.formno.inRight {
            top: 456px;
        }

        p.voterno.inRight {
            top: 484px;
        }

        p.vArea.inRight {
            top: 510px;
        }

        p.nameBn.inRight {
            top: 567px;
            font-weight: bold;
        }

        p.nameEn.inRight {
            top: 595px;
        }

        p.dob.inRight {
            top: 623px;
        }

        p.fName.inRight {
            top: 649px;
        }

        p.mName.inRight {
            top: 677px;
        }

        p.husWif.inRight {
            top: 703px;
        }

        p.gender.inRight {
            top: 762px;
        }

        p.phone.inRight {
            top: 819px;
        }

        p.relagion.inRight {
            top: 791px;
        }

        p.birthPlace.inRight {
            top: 845px;
        }

        p.address {
            max-width: 575px;
            position: absolute;
            left: 110px;
            font-size: 12px;
            line-height: 18px;
        }

        .presentAddr {
            top: 902px;
        }

        .permanentAddr {
            top: 975px;
        }

        button.PrintBtn {
            width: 150px;
            background: #8a00ff;
            padding: 10px;
            font-weight: bold;
            cursor: pointer;
            display: block;
            margin: auto;
            margin-bottom: 100px;
            border-radius: 6px;
            color: #fff;
            font-size: 16px;
        }

        @media print {
            @page {
                size: auto;
                margin: 0;
            }

            button.PrintBtn {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <img class="bgImg" src="https://raw.githubusercontent.com/emonbaluahat/Download-Win/refs/heads/main/server-new.png" alt="">
        <img src="https://api.blackfiretools.my.id/server/image/6023633537.png" alt="" class="avatar">
        <p class="relagionKey inLeft">ধর্ম</p>
        <p class="mobileKey inLeft">মোবাইল নম্বর</p>
        <p class="nid inRight">6023633537</p>
        <p class="pin inRight">20020695113000114</p>
        <p class="formno inRight">n/a</p>
        <p class="voterno inRight">060354399242</p>
        <p class="vArea inRight">বাহেরচর</p>
        <p class="nameBn inRight">লামিয়া বেগম</p>
        <p class="nameEn inRight">LAMIA BEGUM</p>
        <p class="dob inRight">2002-11-10</p>
        <p class="fName inRight">মোঃ জালাল আকন</p>
        <p class="mName inRight">ফাতেমা বেগম</p>
        <p class="husWif inRight">মোঃ আল আমিন</p>
        <p class="gender inRight">female</p>
        <p class="relagion inRight">Islam</p>
        <p class="phone inRight">01831875819</p>
        <p class="birthPlace inRight">বরিশাল</p>
        <p class="address presentAddr">বাসা/হোল্ডিং: ৯৭৫, গ্রাম/রাস্তা: দক্ষিন আলেকান্দা, খান সড়ক, মৌজা/মহল্লা: বগুড়া আলেকান্দা, ইউনিয়ন ওয়ার্ড: ওয়ার্ড নং-১৩, ডাকঘর: বরিশাল সদর - ৮২০০, উপজিলা: বরিশাল সদর, জেলা: বরিশাল, বিভাগ: বরিশাল</p>
        <p class="address permanentAddr">বাসা/হোল্ডিং: -, গ্রাম/রাস্তা: বাহেরচর, মৌজা/মহল্লা: বাহেরচর, ইউনিয়ন ওয়ার্ড: শ্রীপুর, ডাকঘর: শ্রীপুর - ৮২৭৪, উপজিলা: মেহেন্দীগঞ্জ, জেলা: বরিশাল, বিভাগ: বরিশাল</p>
    </div>
    <button class="PrintBtn" onclick="window.print()">Print</button>
</body>
</html>
