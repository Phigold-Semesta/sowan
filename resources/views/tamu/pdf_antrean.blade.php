<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Antrean SOWAN V2</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #111;
            margin: 0;
            padding: 20px;
            background: #fff;
        }
        .ticket-box {
            max-width: 400px;
            margin: 0 auto;
            border: 2px dashed #046A38;
            border-radius: 20px;
            padding: 30px;
            background: #f9fbf9;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #046A38;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #046A38;
            font-size: 24px;
            text-transform: uppercase;
            font-weight: 900;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .content {
            text-align: center;
            margin-bottom: 25px;
        }
        .antrean-label {
            font-size: 10px;
            font-weight: bold;
            color: #046A38;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 5px;
        }
        .antrean-number {
            font-size: 56px;
            font-weight: 900;
            color: #022c22;
            margin: 0;
        }
        .details {
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details td {
            padding: 6px 0;
            border-bottom: 1px solid #eee;
        }
        .details td.label {
            font-weight: bold;
            color: #555;
            width: 40%;
        }
        .details td.value {
            color: #111;
            font-weight: 600;
            text-align: right;
        }
        .footer {
            text-align: center;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
    </style>
</head>
<body>

    <div class="ticket-box">
        <div class="header">
            <h1>SOWAN V2</h1>
            <p>LPSE Kabupaten Karawang</p>
        </div>

        <div class="content">
            <div class="antrean-label">Nomor Antrean Anda</div>
            <h2 class="antrean-number">{{ $antrean ?? '-' }}</h2>
        </div>

        <div class="details">
            <table>
                <tr>
                    <td class="label">Nama Tamu</td>
                    <td class="value">{{ $nama_tamu ?? 'Tamu' }}</td>
                </tr>
                <tr>
                    <td class="label">Instansi</td>
                    <td class="value">{{ $instansi ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Waktu Catat</td>
                    <td class="value">{{ now()->format('d-m-Y H:i') }}</td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="value" style="color: #046A38;">TERVERIFIKASI</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Simpan bukti ini sebagai tiket kunjungan resmi Anda.<br>Terima kasih telah berkunjung ke LPSE Karawang.</p>
        </div>
    </div>

</body>
</html>