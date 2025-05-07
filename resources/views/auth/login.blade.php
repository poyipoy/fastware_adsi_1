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
            <form method="POST" action="{{ route('login_post') }}" novalidate>
                @csrf
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


{{-- <div class="container">
    <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-center pt-3">
                                <img src="{{ asset('assets/img/logo-login.png') }}" alt=""
                                    style="height: 50px; animation: fadeIn 1s ease-in-out;">
                                <a href="" class="logo d-flex align-items-center w-auto">
                                    <span class="d-none d-lg-block"></span>
                                </a>
                            </div>
                            <div class="pt-1 pb-2">
                                <h5 class="card-title text-center pb-0 fs-4"
                                    style="animation: slideInDown 1s ease-in-out;">Selamat Datang</h5>
                                <p class="text-center small" style="animation: slideInDown 1.5s ease-in-out;">
                                    Masukan Username dan Password</p>
                            </div>
                            @if (session('error'))
                            <p style="color: red; animation: shake 0.5s;">{{ session('error') }}</p>
                            @endif
                            <form class="row g-3 needs-validation" method="POST" action="{{ route('login_post') }}"
                                novalidate>
                                @csrf
                                <div class="col-12">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group has-validation" style="animation: fadeIn 2s ease-in-out;">
                                        <span class="input-group-text" id="inputGroupPrepend">@</span>
                                        <input type="text" name="username" class="form-control" id="username" required>
                                        <div class="invalid-feedback">Silahkan Masukan Username.</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" id="password" required
                                        style="animation: fadeIn 2.5s ease-in-out;">
                                    <div class="invalid-feedback">Silahkan Masukan Password!</div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary w-100" type="submit"
                                        style="animation: bounceIn 3s ease-in-out;">Login</button>
                                </div>
                            </form>
                        </div>

                        <style>
                            @keyframes bounceIn {

                                from,
                                20%,
                                40%,
                                60%,
                                80%,
                                to {
                                    animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
                                }

                                0% {
                                    opacity: 0;
                                    transform: scale3d(.3, .3, .3);
                                }

                                20% {
                                    transform: scale3d(1.1, 1.1, 1.1);
                                }

                                40% {
                                    transform: scale3d(.9, .9, .9);
                                }

                                60% {
                                    opacity: 1;
                                    transform: scale3d(1.03, 1.03, 1.03);
                                }

                                80% {
                                    transform: scale3d(.97, .97, .97);
                                }

                                to {
                                    opacity: 1;
                                    transform: scale3d(1, 1, 1);
                                }
                            }

                            input.form-control {
                                transition: all 0.3s ease;
                            }

                            input.form-control:focus {
                                border-color: #5cabf5;
                                box-shadow: 0 0 10px rgba(0, 167, 245, 0.5);
                            }

                            button.btn-primary {
                                transition: background-color 0.3s ease, transform 0.3s ease;
                            }

                            button.btn-primary:hover {
                                background-color: #00c2fd;
                                transform: scale(1.05);
                            }
                        </style>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div> --}}