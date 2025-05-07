<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Klaim Proses Handling</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding: 20px 0;
            background-color: #f44336;
            color: #ffffff;
            border-radius: 8px 8px 0 0;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 20px;
        }

        .content p {
            font-size: 16px;
            color: #333333;
        }

        .content ul {
            list-style-type: none;
            padding: 0;
        }

        .content ul li {
            background-color: #fdecea;
            margin: 5px 0;
            padding: 10px;
            border-radius: 4px;
            font-size: 16px;
            color: #333333;
        }

        .button-container {
            text-align: center;
            margin: 20px 0;
        }

        .button-container a {
            background-color: #f44336;
            color: #ffffff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
        }

        .button-container a:hover {
            background-color: #e53935;
        }

        .footer {
            text-align: center;
            padding: 10px;
            background-color: #fdecea;
            border-radius: 0 0 8px 8px;
            font-size: 14px;
            color: #666666;
        }

        @media screen and (max-width: 600px) {
            .email-container {
                width: 100%;
                padding: 10px;
            }

            .header h1 {
                font-size: 20px;
            }

            .content p,
            .content ul li {
                font-size: 14px;
            }

            .footer {
                font-size: 12px;
            }

            .button-container a {
                font-size: 14px;
                padding: 8px 16px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <h1>Konfirmasi Klaim Proses Handling</h1>
        </div>
        <div class="content">
            <p>Proses handling dengan rincian berikut telah diklaim:</p>
            <ul>
                <li><strong>No WO:</strong> {{ $handling->no_wo }}</li>
                <li><strong>Catatan Hasil:</strong> {{ $scheduleVisit->results }}</li>
                <li><strong>Jadwal Kunjungan:</strong> {{ $scheduleVisit->schedule }}</li>
            </ul>
            <div class="button-container">
                <a href="https://fastware.adasi.co.id/" target="_blank">Buka Fastware</a>
            </div>
        </div>
        <div class="footer">
            <p>Fastware - Astra Daido Steel Indonesia (ADASI)</p>
        </div>
</body>

</html>
