<?php
/**
 * Automated Backup Script
 * Runs daily at 19:00 WITA
 */
require_once __DIR__ . '/../config/helpers.php';

// Only allow execution via lazy cron or CLI (optional security)
// For now, it will be called from header.php

function run_auto_backup() {
    $conn = db();
    $db_name = DB_NAME;
    $backup_dir = __DIR__ . '/../backups/';
    
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }

    $filename = 'auto_backup_' . date('Ymd_His') . '.sql';
    $filepath = $backup_dir . $filename;
    
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) $tables[] = $row[0];
    
    $sqlDump = "-- Auto Backup: " . date('Y-m-d H:i:s') . "\n";
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        $row2 = $conn->query("SHOW CREATE TABLE $table")->fetch_row();
        $sqlDump .= "\n\n" . $row2[1] . ";\n\n";
        
        $result = $conn->query("SELECT * FROM $table");
        $num_fields = $result->field_count;
        
        while ($row = $result->fetch_row()) {
            $sqlDump .= "INSERT INTO $table VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                $val = $row[$j];
                if ($val === null) {
                    $sqlDump .= 'NULL';
                } else {
                    $val = addslashes($val);
                    $val = str_replace("\n", "\\n", $val);
                    $sqlDump .= '"' . $val . '"';
                }
                if ($j < ($num_fields - 1)) $sqlDump .= ',';
            }
            $sqlDump .= ");\n";
        }
    }
    $sqlDump .= "\nSET FOREIGN_KEY_CHECKS=1;";
    
    file_put_contents($filepath, $sqlDump);
    
    // Clean up old backups (older than 7 days)
    $files = glob($backup_dir . 'auto_backup_*.sql');
    $now = time();
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > (7 * 24 * 60 * 60)) {
            unlink($file);
        }
    }

    // Update last run
    file_put_contents(__DIR__ . '/../config/last_auto_backup.txt', date('Y-m-d'));
    
    log_audit('AUTO_BACKUP_DATABASE', null, null, "Internal auto backup created: $filename");
}

run_auto_backup();
?>
