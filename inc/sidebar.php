<aside class="main-sidebar sidebar-light-success border-right elevation-0">
    <a href="<?= url('index.php') ?>" class="brand-link">
        <img src="<?= get_favicon_url() ?>" alt="Logo" class="brand-image img-circle elevation-0" style="opacity: .9; width: 33px; height: 33px; object-fit: cover;">
        <span class="brand-text font-weight-light">SIM Pengumpulan ZIS</span>
    </a>
    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <?php
            $photoUrl = !empty($user['photo']) ? base_url($user['photo']) : asset('adminlte/dist/img/user2-160x160.jpg');
            ?>
            <div class="image">
                <img src="<?= $photoUrl ?>" class="img-circle elevation-2" alt="User" style="width: 33px; height: 33px; object-fit: cover;">
            </div>
            <div class="info">
                <a href="<?= url('pages/profile/index.php') ?>" class="d-block"><?= htmlspecialchars($user['full_name'] ?? 'Pengguna') ?></a>
                <small class="text-muted"><?= htmlspecialchars(strtoupper($user['role'] ?? '')) ?></small>
            </div>
        </div>
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <!-- DASHBOARD -->
                <li class="nav-item">
                    <a href="<?= url('index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'dashboard' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p><strong>Dashboard</strong></p>
                    </a>
                </li>

                <!-- ASISTEN AI INTELIJEN -->
                <li class="nav-item">
                    <a href="<?= url('pages/ai_asisten/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'ai_asisten' ? ' active' : '' ?>" style="border-left: 3px solid #0d9f4f;">
                        <i class="nav-icon fas fa-robot text-success"></i>
                        <p>
                            <strong>Asisten AI</strong>
                            <span class="badge badge-success right" style="font-size: 0.68rem; padding: 2px 6px;">Intelijen</span>
                        </p>
                    </a>
                </li>

                <!-- 1. DATA MUZAKI & UPZ -->
                <li class="nav-header text-uppercase" style="color: #4ade80; font-weight: bold;">
                    <i class="fas fa-users mr-1"></i>Data Muzaki & UPZ
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/muzaki/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'muzaki' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-user-tie"></i>
                        <p>Data Muzaki</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/upz/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'upz' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-building"></i>
                        <p>Data UPZ</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/muzaki_upz_history/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'muzaki_upz_history' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Riwayat UPZ Muzaki</p>
                    </a>
                </li>

                <!-- 2. PENCATATAN TRANSAKSI (ON BALANCE, OFF BALANCE, SKZ & REKENING KORAN) -->
                <li class="nav-header text-uppercase" style="color: #f59e0b; font-weight: bold;">
                    <i class="fas fa-exchange-alt mr-1"></i>Pencatatan Transaksi
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/transaksi/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'transaksi' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-cash-register"></i>
                        <p>Transaksi On Balance</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/off_balance/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'off_balance' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>Transaksi Off Balance</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/skz/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'skz' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-file-contract"></i>
                        <p><span class="badge badge-success right">SKZ</span>SKZ & BSZIS Proyek</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/rekening_koran/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'rekening_koran' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-university"></i>
                        <p>Rekening Koran Bank</p>
                    </a>
                </li>

                <!-- 3. LAPORAN & REKAPITULASI (ON BALANCE, OFF BALANCE, SKZ & AUDIT) -->
                <li class="nav-header text-uppercase" style="color: #3b82f6; font-weight: bold;">
                    <i class="fas fa-file-invoice-dollar mr-1"></i>Laporan & Rekapitulasi
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/laporan/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'laporan' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Laporan On Balance</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/laporan_rekap/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'laporan_rekap' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>Rekap Bulanan</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/laporan_tahunan/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'laporan_tahunan' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>Laporan Tahunan</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/off_balance/laporan.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'off_balance_laporan' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-clipboard-check"></i>
                        <p>Laporan Off Balance</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/skz/laporan.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'skz_laporan' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-file-invoice"></i>
                        <p>Laporan SKZ & BSZIS</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/laporan_audit/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'laporan_audit' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-shield-alt"></i>
                        <p><span class="badge badge-success right">Audit</span>Laporan Audit</p>
                    </a>
                </li>

                <!-- 4. ANALISIS & KINERJA PENGUMPULAN -->
                <li class="nav-header text-uppercase" style="color: #10b981; font-weight: bold;">
                    <i class="fas fa-bullseye mr-1"></i>Kinerja & Analisis
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/kinerja/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'kinerja' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-bullseye"></i>
                        <p>Target & Kinerja (RKAT)</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/analisis/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'analisis' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-chart-pie"></i>
                        <p>Analisis Data ZIS</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/analisis/perbandingan.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'analisis_perbandingan' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-balance-scale"></i>
                        <p>Perbandingan Periode</p>
                    </a>
                </li>

                <!-- 5. DOKUMEN BANTUAN / PENERIMA MANFAAT -->
                <li class="nav-header text-uppercase" style="color: #06b6d4; font-weight: bold;">
                    <i class="fas fa-folder-open mr-1"></i>Dokumen Bantuan
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/dokumen_program/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'dokumen_program' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Lembar Penerima</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('pages/dokumen_kategori/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'dokumen_kategori' ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-sliders-h"></i>
                        <p>Kategori & Kolom</p>
                    </a>
                </li>

                <!-- 6. MASTER DATA -->
                <?php
                $masterMenus = ['jenis_pembayaran', 'sub_jenis_pembayaran', 'kategori_dana', 'metode_pembayaran', 'status_muzaki', 'sumber_rekening', 'kategori_upz'];
                $isMasterActive = in_array($activeMenu ?? '', $masterMenus, true);
                ?>
                <li class="nav-header text-uppercase" style="color: #8b5cf6; font-weight: bold;">
                    <i class="fas fa-database mr-1"></i>Master Data
                </li>
                <li class="nav-item<?= $isMasterActive ? ' menu-open' : '' ?>">
                    <a href="#" class="nav-link<?= $isMasterActive ? ' active' : '' ?>">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                            <strong>Referensi Data</strong>
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= url('pages/jenis_pembayaran/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'jenis_pembayaran' ? ' active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Jenis Pembayaran</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= url('pages/sub_jenis_pembayaran/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'sub_jenis_pembayaran' ? ' active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Sub Jenis Pembayaran</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= url('pages/kategori_dana/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'kategori_dana' ? ' active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Kategori Dana</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= url('pages/metode_pembayaran/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'metode_pembayaran' ? ' active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Metode Pembayaran</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= url('pages/status_muzaki/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'status_muzaki' ? ' active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Status Muzaki</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= url('pages/kategori_upz/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'kategori_upz' ? ' active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Kategori UPZ</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= url('pages/sumber_rekening/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'sumber_rekening' ? ' active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Sumber Rekening</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 7. PENGATURAN & PEMELIHARAAN SISTEM -->
                <?php if (has_role('admin')): ?>
                    <li class="nav-header text-uppercase" style="color: #ef4444; font-weight: bold;">
                        <i class="fas fa-cog mr-1"></i>Pengaturan Sistem
                    </li>
                    <li class="nav-item">
                        <a href="<?= url('pages/users/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'users' ? ' active' : '' ?>">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Manajemen Pengguna</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= url('pages/settings/website/index.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'website_settings' ? ' active' : '' ?>">
                            <i class="nav-icon fas fa-globe"></i>
                            <p>Pengaturan Website</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= url('pages/settings/backup.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'settings' ? ' active' : '' ?>">
                            <i class="nav-icon fas fa-database"></i>
                            <p>Backup & Restore</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= url('pages/settings/diagnostics.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'diagnostics' ? ' active' : '' ?>">
                            <i class="nav-icon fas fa-stethoscope text-success"></i>
                            <p>Dokter Sistem & DB</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= url('pages/settings/audit_log.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'audit_log' ? ' active' : '' ?>">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Audit Trail</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= url('pages/settings/changelog.php') ?>" class="nav-link<?= ($activeMenu ?? '') === 'changelog' ? ' active' : '' ?>">
                            <i class="nav-icon fas fa-bullhorn"></i>
                            <p>Log Perubahan</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- LOGOUT -->
                <li class="nav-header" style="border-top: 2px solid #4b5563; margin-top: 20px; padding-top: 10px;"></li>
                <li class="nav-item">
                    <a href="<?= url('logout.php') ?>" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p><strong>Keluar Sistem</strong></p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
