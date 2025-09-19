<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Berhasil Dikirim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <div class="card card-glass p-4 p-md-5 mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <div class="mb-4">
                    <i class="fas fa-check-circle fa-4x text-success"></i>
                </div>
                <h1 class="card-title fw-bold mb-3">Terima Kasih!</h1>
                <p class="card-text text-muted">
                    Formulir Anda telah berhasil dikirim. Tim kami akan segera meninjau data yang Anda berikan.
                </p>
                {{-- <a href="{{ url('/') }}" class="btn btn-primary mt-4">Kembali ke Halaman Utama</a> --}}
            </div>
        </div>
    </div>
</body>
</html>
