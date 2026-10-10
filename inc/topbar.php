<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= url('index.php') ?>" class="nav-link">Beranda</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a class="nav-link text-success font-weight-bold" href="#" data-toggle="modal" data-target="#whatsNewModal" style="cursor: pointer;">
                <i class="fas fa-bullhorn mr-1 text-success"></i>Apa yang Baru?
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a class="nav-link text-dark font-weight-bold" href="javascript:void(0)" onclick="toggleAiCopilot()" title="Tanya Amil Virtual (Alt + A)" style="cursor: pointer;">
                <i class="fas fa-robot mr-1 text-success"></i>Tanya AI
            </a>
        </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <?php
        // Fetch active reminders and notes count for the logged-in user
        $topbarConn = db();
        $topbarCurrentUser = current_user();
        $topbarUserId = (int)($topbarCurrentUser['id'] ?? 0);
        
        $activeReminders = [];
        $activeNotes = [];
        
        if ($topbarUserId > 0) {
            // Reminders (type = 'reminder' and is_completed = 0)
            $remRes = $topbarConn->query("SELECT * FROM reminders WHERE user_id = {$topbarUserId} AND type = 'reminder' AND is_completed = 0 ORDER BY due_date ASC, id DESC LIMIT 5");
            if ($remRes) {
                while ($r = $remRes->fetch_assoc()) {
                    $activeReminders[] = $r;
                }
            }
            
            // Notes (type = 'note' and is_completed = 0)
            $noteRes = $topbarConn->query("SELECT * FROM reminders WHERE user_id = {$topbarUserId} AND type = 'note' AND is_completed = 0 ORDER BY id DESC LIMIT 5");
            if ($noteRes) {
                while ($n = $noteRes->fetch_assoc()) {
                    $activeNotes[] = $n;
                }
            }
            
            // Get total count of uncompleted items
            $totalActiveRemindersCount = $topbarConn->query("SELECT COUNT(*) FROM reminders WHERE user_id = {$topbarUserId} AND type = 'reminder' AND is_completed = 0")->fetch_row()[0] ?? 0;
            $totalActiveNotesCount = $topbarConn->query("SELECT COUNT(*) FROM reminders WHERE user_id = {$topbarUserId} AND type = 'note' AND is_completed = 0")->fetch_row()[0] ?? 0;
        } else {
            $totalActiveRemindersCount = 0;
            $totalActiveNotesCount = 0;
        }
        ?>

        <!-- Dropdown Catatan (Notes) -->
        <li class="nav-item dropdown mr-1">
            <a class="nav-link" data-toggle="dropdown" href="#" title="Catatan">
                <i class="far fa-sticky-note" style="font-size: 1.1rem;"></i>
                <?php if ($totalActiveNotesCount > 0): ?>
                    <span class="badge badge-success navbar-badge" style="font-size: 0.6rem; top: 4px; right: 2px; padding: 2px 4px; border-radius: 50%;"><?= $totalActiveNotesCount ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0" style="border: 1px solid #b8b8b8 !important; box-shadow: none !important; border-radius: 4px; width: 320px;">
                <div class="dropdown-header font-weight-bold text-dark text-left border-bottom py-2 px-3">
                    <i class="far fa-sticky-note mr-2 text-success"></i><?= $totalActiveNotesCount ?> Catatan Aktif
                </div>
                <div style="max-height: 250px; overflow-y: auto;">
                    <?php if (empty($activeNotes)): ?>
                        <div class="text-center py-4 text-muted small">Tidak ada catatan aktif.</div>
                    <?php else: ?>
                        <?php foreach ($activeNotes as $note): ?>
                            <div class="dropdown-item d-flex align-items-start py-2 px-3 border-bottom" style="white-space: normal; background-color: #ffffff;">
                                <div class="custom-control custom-checkbox mr-2 pt-1">
                                    <input type="checkbox" class="custom-control-input" id="topbarNoteCheck<?= $note['id'] ?>" onclick="toggleReminder(<?= $note['id'] ?>, this.checked); event.stopPropagation();">
                                    <label class="custom-control-label" for="topbarNoteCheck<?= $note['id'] ?>"></label>
                                </div>
                                <div class="flex-grow-1 pr-2">
                                    <div class="text-dark font-weight-bold" style="font-size: 0.85rem; line-height: 1.2;"><?= clean($note['title']) ?></div>
                                    <?php if (!empty($note['description'])): ?>
                                        <div class="text-muted mt-1" style="font-size: 0.75rem; line-height: 1.3;"><?= clean($note['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-link text-danger btn-xs p-0 ml-auto align-self-center" onclick="deleteReminder(<?= $note['id'] ?>); event.stopPropagation();" style="font-size: 0.85rem;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="#" class="dropdown-item dropdown-footer text-success text-center font-weight-bold py-2 border-top" onclick="showAddNoteModal(); event.preventDefault(); event.stopPropagation();" style="font-size: 0.85rem; background-color: #f8f9fa;">
                    <i class="fas fa-plus mr-1"></i>Tambah Catatan Baru
                </a>
            </div>
        </li>

        <!-- Dropdown Pengingat (Reminders) -->
        <li class="nav-item dropdown mr-2">
            <a class="nav-link" data-toggle="dropdown" href="#" title="Pengingat">
                <i class="far fa-bell" style="font-size: 1.1rem;"></i>
                <?php if ($totalActiveRemindersCount > 0): ?>
                    <span class="badge badge-warning navbar-badge" style="font-size: 0.6rem; top: 4px; right: 2px; padding: 2px 4px; border-radius: 50%; color: #333; background-color: #ffc107;"><?= $totalActiveRemindersCount ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0" style="border: 1px solid #b8b8b8 !important; box-shadow: none !important; border-radius: 4px; width: 320px;">
                <div class="dropdown-header font-weight-bold text-dark text-left border-bottom py-2 px-3">
                    <i class="far fa-bell mr-2 text-warning"></i><?= $totalActiveRemindersCount ?> Pengingat Aktif
                </div>
                <div style="max-height: 250px; overflow-y: auto;">
                    <?php if (empty($activeReminders)): ?>
                        <div class="text-center py-4 text-muted small">Tidak ada pengingat aktif.</div>
                    <?php else: ?>
                        <?php foreach ($activeReminders as $rem): ?>
                            <?php
                            $isOverdue = false;
                            if ($rem['due_date']) {
                                $isOverdue = (strtotime($rem['due_date']) < strtotime(date('Y-m-d')));
                            }
                            ?>
                                <div class="dropdown-item d-flex align-items-start py-2 px-3 border-bottom" style="white-space: normal;">
                                <div class="custom-control custom-checkbox mr-2 pt-1">
                                    <input type="checkbox" class="custom-control-input" id="topbarRemCheck<?= $rem['id'] ?>" onclick="toggleReminder(<?= $rem['id'] ?>, this.checked); event.stopPropagation();">
                                    <label class="custom-control-label" for="topbarRemCheck<?= $rem['id'] ?>"></label>
                                </div>
                                <div class="flex-grow-1 pr-2">
                                    <div class="text-dark font-weight-bold" style="font-size: 0.85rem; line-height: 1.2;"><?= clean($rem['title']) ?></div>
                                    <?php if (!empty($rem['description'])): ?>
                                        <div class="text-muted mt-1" style="font-size: 0.75rem; line-height: 1.3;"><?= clean($rem['description']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($rem['due_date']): ?>
                                        <div class="mt-1 <?= $isOverdue ? 'text-danger font-weight-bold' : 'text-muted' ?>" style="font-size: 0.75rem;">
                                            <i class="far fa-calendar-alt mr-1"></i>Jatuh tempo: <?= date('d/m/Y', strtotime($rem['due_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-link text-danger btn-xs p-0 ml-auto align-self-center" onclick="deleteReminder(<?= $rem['id'] ?>); event.stopPropagation();" style="font-size: 0.85rem;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="#" class="dropdown-item dropdown-footer text-warning text-center font-weight-bold py-2 border-top" onclick="showAddReminderModal(); event.preventDefault(); event.stopPropagation();" style="font-size: 0.85rem;">
                    <i class="fas fa-plus mr-1"></i>Tambah Pengingat Baru
                </a>
            </div>
        </li>

        <!-- Mode Gelap / Terang (Dark / Light Mode) -->
        <li class="nav-item mr-1">
            <a class="nav-link" href="javascript:void(0)" id="btn-theme-toggle" onclick="toggleDarkMode()" title="Ganti Mode Gelap / Terang" style="cursor: pointer;">
                <i class="fas fa-moon" id="theme-toggle-icon" style="font-size: 1.1rem;"></i>
            </a>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-user-circle mr-1"></i>
                <?= htmlspecialchars($user['full_name'] ?? 'Pengguna') ?>
                <small class="badge badge-primary ml-1"><?= htmlspecialchars(strtoupper($user['role'] ?? '')) ?></small>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a href="<?= url('pages/profile/index.php') ?>" class="dropdown-item">
                    <i class="fas fa-user mr-2 text-primary"></i>Profil Saya
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= url('logout.php') ?>" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt mr-2"></i>Keluar
                </a>
            </div>
        </li>
    </ul>
</nav>

<!-- Modal Apa yang Baru? -->
<div class="modal fade" id="whatsNewModal" tabindex="-1" role="dialog" aria-labelledby="whatsNewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="whatsNewModalLabel">
                    <i class="fas fa-bullhorn mr-2 text-success"></i>Apa yang Baru di Aplikasi?
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                <?php
                $dbConn = db();
                $changelogs = [];
                $clRes = $dbConn->query("SELECT * FROM system_changelog ORDER BY tanggal DESC, id DESC LIMIT 15");
                if ($clRes) {
                    while ($clRow = $clRes->fetch_assoc()) {
                        $changelogs[] = $clRow;
                    }
                }
                ?>
                
                <?php if (empty($changelogs)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-bullhorn fa-2x mb-3 text-muted" style="opacity: 0.5;"></i>
                        <p class="mb-0 font-weight-bold">Belum ada catatan log perubahan baru.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline-update">
                        <?php 
                        $currentDate = ''; 
                        foreach ($changelogs as $log): 
                            $dateStr = date('d F Y', strtotime($log['tanggal']));
                            $months = [
                                'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
                                'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
                                'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
                                'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
                            ];
                            $dateStr = strtr($dateStr, $months);
                        ?>
                            <?php if ($currentDate !== $log['tanggal']): $currentDate = $log['tanggal']; ?>
                                <h6 class="font-weight-bold text-success border-bottom pb-2 mt-4">
                                    <i class="fas fa-calendar-alt mr-2 text-success"></i><?= htmlspecialchars($dateStr) ?>
                                </h6>
                            <?php endif; ?>
                            
                            <div class="mb-3 pl-2">
                                <h6 class="font-weight-bold mb-1 text-dark d-flex align-items-center">
                                    <?php
                                    $badgeClass = 'badge-info';
                                    if ($log['kategori'] === 'Fitur Baru') $badgeClass = 'badge-success';
                                    elseif ($log['kategori'] === 'Pembaruan Desain') $badgeClass = 'badge-primary';
                                    elseif ($log['kategori'] === 'Perbaikan Bug') $badgeClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?= $badgeClass ?> mr-2" style="font-size: 0.7rem; padding: 0.3em 0.6em;"><?= htmlspecialchars($log['kategori']) ?></span>
                                    <?= htmlspecialchars($log['judul']) ?>
                                </h6>
                                <div class="small text-muted pl-3 border-left" style="border-width: 3px !important; border-color: #28a745 !important; line-height: 1.6;">
                                    <?= $log['deskripsi'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

