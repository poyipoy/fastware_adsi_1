<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Astra Daido - Halaman Masuk</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="assets/img/logo-menu.png" rel="icon">
    <link href="assets/img/logo-menu.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: rgba(233, 233, 233, 0.9);
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
            /* Transparan */
            padding: 2rem;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
            box-shadow: inset 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .welcome-text {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 20px;
            margin-bottom: 15px;
            font-family: 'Cambria', serif;
            box-shadow: 0 10px 30px rgba(32, 32, 32, 0.1);
        }


        .btn-custom {
            border-radius: 20px;
            background-color: #007bff;
            color: white;
            width: 100%;
            font-family: 'Cambria', serif;
        }

        .btn-custom:hover {
            background-color: #00043f;
            color: rgb(255, 255, 255);
            font-weight: bold;
            font-family: 'Cambria', serif;
        }

        /* Animasi untuk efek masuk */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s;
        }
    </style>
</head>

<body>
    <main>
        <div class="login-container fade-in">
            <i class="ri-bubble-chart-fill fs-2"></i>
            <h2 class="welcome-text fw-bold text-primary">Hello Users!</h2>
            <form method="POST" action="<?php echo e(route('login_post')); ?>" novalidate>
                <?php echo csrf_field(); ?>
                <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
                <input type="password" name="password" class="form-control" id="password" placeholder="Password"
                    required>
                <button class="btn btn-custom btn-sm mt-3" type="submit">Login</button>
            </form>
        </div>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>
    <!-- Vendor JS Files -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>

</body>


<?php /**PATH C:\laragon\www\fastware_adsi_1\resources\views/auth/login.blade.php ENDPATH**/ ?>