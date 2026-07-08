<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ATC — Digital Attendance System</title>

    <link rel="preload" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet"></noscript>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="ega-body">

    {{-- Main Header --}}
    <header class="ega-header">
        <div class="container-fluid px-3 px-md-5">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <a href="{{ route('home') }}" class="ega-brand">
                    <img src="{{ asset('images/logo.png') }}" alt="ATC Logo" class="ega-brand-img">
                    <div>
                        <div class="ega-brand-name">ATC Attendance Portal</div>
                        <div class="ega-brand-sub">Digital Attendance System</div>
                    </div>
                </a>
                <nav class="d-flex align-items-center gap-2">
                    <a href="{{ route('login') }}" class="ega-nav-outline">
                        <i class='bx bx-log-in'></i> Login
                    </a>
                    <a href="{{ route('register') }}" class="ega-nav-primary">
                        <i class='bx bx-user-plus'></i> Register
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        {{-- Hero --}}
        <section class="ega-hero ega-hero--img">
            <div class="container-fluid px-3 px-md-5">
                <div class="row align-items-center gy-5">

                    {{-- Copy --}}
                    <div class="col-lg-9 col-xl-8 mx-auto text-center">
                        <div class="ega-badge ega-badge--light mb-4">
                            <i class='bx bx-badge-check'></i>
                            Official ATC Digital System
                        </div>

                        <h1 class="ega-title ega-title--white mb-2">
                            Biometric Attendance<br>
                             System for arusha technical college
                        </h1>
                        <p class="ega-title-en ega-title-en--light mb-4">Student Attendance Management System</p>

                        <p class="ega-copy ega-copy--white mb-5">
                            Record attendance, manage biometric devices, and view reports —
                            all in one secure system built for students, lecturers, and administrators.
                        </p>

                        <div class="ega-actions mb-5 justify-content-center">
                            <a href="{{ route('login') }}" class="ega-btn-main ega-btn-white">
                                <i class='bx bx-log-in'></i>
                                Log In
                            </a>
                            <a href="{{ route('register') }}" class="ega-btn-ghost ega-btn-ghost--white">
                                <i class='bx bx-user-plus'></i>
                                Register Now
                            </a>
                        </div>

                        <div class="ega-pills justify-content-center">
                            <div class="ega-pill">
                                <div class="ega-pill-icon ega-pill--white">
                                    <i class='bx bx-fingerprint'></i>
                                </div>
                                <div>
                                    <div class="ega-pill-title ega-pill-title--white">Biometric</div>
                                    <div class="ega-pill-sub ega-pill-sub--white">Fingerprint scan</div>
                                </div>
                            </div>
                            <div class="ega-pill">
                                <div class="ega-pill-icon ega-pill--white">
                                    <i class='bx bx-bar-chart-alt-2'></i>
                                </div>
                                <div>
                                    <div class="ega-pill-title ega-pill-title--white">Live Reports</div>
                                    <div class="ega-pill-sub ega-pill-sub--white">Real-time data</div>
                                </div>
                            </div>
                            <div class="ega-pill">
                                <div class="ega-pill-icon ega-pill--white">
                                    <i class='bx bx-shield-alt-2'></i>
                                </div>
                                <div>
                                    <div class="ega-pill-title ega-pill-title--white">High Security</div>
                                    <div class="ega-pill-sub ega-pill-sub--white">Role-based access</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- Services Cards --}}
        <section class="ega-services">
            <div class="container-fluid px-3 px-md-5">
                <div class="text-center mb-5">
                    <div class="ega-section-tag">System Services</div>
                    <h2 class="ega-section-title">Built for every user</h2>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="ega-card">
                            <div class="ega-card-icon">
                                <i class='bx bx-user-check'></i>
                            </div>
                            <h3 class="ega-card-title">Students</h3>
                            <p class="ega-card-desc">View your attendance, class schedule, and exam results with ease.</p>
                            <a href="{{ route('login') }}" class="ega-card-link">
                                Login <i class='bx bx-right-arrow-alt'></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ega-card">
                            <div class="ega-card-icon">
                                <i class='bx bx-chalkboard'></i>
                            </div>
                            <h3 class="ega-card-title">Lecturers</h3>
                            <p class="ega-card-desc">Manage classes, record attendance with ease, and generate detailed student reports.</p>
                            <a href="{{ route('login') }}" class="ega-card-link">
                                Login <i class='bx bx-right-arrow-alt'></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ega-card">
                            <div class="ega-card-icon">
                                <i class='bx bx-shield-quarter'></i>
                            </div>
                            <h3 class="ega-card-title">Administrators</h3>
                            <p class="ega-card-desc">Track attendance trends, manage users, and oversee biometric devices efficiently.</p>
                            <a href="{{ route('login') }}" class="ega-card-link">
                                Login <i class='bx bx-right-arrow-alt'></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="ega-footer">
        <div class="container-fluid px-3 px-md-5">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-3">
                <div class="ega-footer-left">
                    <img src="{{ asset('images/logo.png') }}" alt="ATC" class="ega-footer-logo">
                    <span>ATC Attendance Portal &copy; {{ date('Y') }}</span>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
