-- Auto Backup: 2026-04-18 22:33:50
SET FOREIGN_KEY_CHECKS=0;



CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=351 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO audit_log VALUES("1","1","BACKUP_DATABASE",NULL,NULL,"Generated backup file: backup_pengumpulan_zis_20260405_035101.sql","::1","2026-04-05 02:51:01");
INSERT INTO audit_log VALUES("2","1","UPDATE_JENIS_PEMBAYARAN","jenis_pembayaran","6","Nama: Zakat Maal","::1","2026-04-05 03:01:23");
INSERT INTO audit_log VALUES("3","1","UPDATE_JENIS_PEMBAYARAN","jenis_pembayaran","6","Nama: Zakat","::1","2026-04-05 03:02:50");
INSERT INTO audit_log VALUES("4","1","UPDATE_MUZAKI","muzaki","104","Nama: A. KARIM SALEH, KTP: 7854","::1","2026-04-05 03:06:02");
INSERT INTO audit_log VALUES("5","1","UPDATE_MUZAKI","muzaki","104","Nama: A. KARIM SALEH, KTP: 7854","::1","2026-04-05 03:06:18");
INSERT INTO audit_log VALUES("6","1","BACKUP_DATABASE",NULL,NULL,"Generated backup file: manual_backup_pengumpulan_zis_20260405_041259.sql","::1","2026-04-05 03:12:59");
INSERT INTO audit_log VALUES("7","1","UPDATE_UPZ","upz","151","Nama UPZ: UPZ | DESA KELUNGKUNG","::1","2026-04-05 03:20:46");
INSERT INTO audit_log VALUES("8","1","SAVE_TARGET_JENIS","target_pengumpulan_per_jenis","7","Tahun 2026 jenis 7 Rp 3000000000","::1","2026-04-05 11:57:50");
INSERT INTO audit_log VALUES("9","1","SAVE_TARGET_JENIS","target_pengumpulan_per_jenis","6","Tahun 2026 jenis 6 Rp 2000000000","::1","2026-04-05 11:58:11");
INSERT INTO audit_log VALUES("10","1","SAVE_TARGET_JENIS","target_pengumpulan_per_jenis","9","Tahun 2026 jenis 9 Rp 0","::1","2026-04-05 11:58:17");
INSERT INTO audit_log VALUES("11","1","SAVE_TARGET_JENIS","target_pengumpulan_per_jenis","10","Tahun 2026 jenis 10 Rp 0","::1","2026-04-05 11:58:23");
INSERT INTO audit_log VALUES("12","1","SAVE_TARGET_JENIS","target_pengumpulan_per_jenis","11","Tahun 2026 jenis 11 Rp 10000000","::1","2026-04-05 11:58:31");
INSERT INTO audit_log VALUES("13","1","SAVE_TARGET_JENIS","target_pengumpulan_per_jenis","8","Tahun 2026 jenis 8 Rp 10000000","::1","2026-04-05 11:58:41");
INSERT INTO audit_log VALUES("14","1","SAVE_TARGET_TAHUNAN","target_pengumpulan_tahunan","2026","Target Rp 6500000000 tahun 2026","::1","2026-04-05 11:59:12");
INSERT INTO audit_log VALUES("15","1","SAVE_TARGET_TAHUNAN","target_pengumpulan_tahunan","2026","Target Rp 0 tahun 2026","::1","2026-04-05 12:00:26");
INSERT INTO audit_log VALUES("16","1","DELETE_TARGET_JENIS","target_pengumpulan_per_jenis","3","Hapus target per jenis","::1","2026-04-05 12:00:32");
INSERT INTO audit_log VALUES("17","1","DELETE_TARGET_JENIS","target_pengumpulan_per_jenis","4","Hapus target per jenis","::1","2026-04-05 12:00:34");
INSERT INTO audit_log VALUES("18","1","DELETE_TARGET_JENIS","target_pengumpulan_per_jenis","5","Hapus target per jenis","::1","2026-04-05 12:00:36");
INSERT INTO audit_log VALUES("19","1","DELETE_TARGET_JENIS","target_pengumpulan_per_jenis","6","Hapus target per jenis","::1","2026-04-05 12:00:39");
INSERT INTO audit_log VALUES("20","1","DELETE_TARGET_JENIS","target_pengumpulan_per_jenis","1","Hapus target per jenis","::1","2026-04-05 12:00:41");
INSERT INTO audit_log VALUES("21","1","DELETE_TARGET_JENIS","target_pengumpulan_per_jenis","2","Hapus target per jenis","::1","2026-04-05 12:00:44");
INSERT INTO audit_log VALUES("22","1","BACKUP_DATABASE",NULL,NULL,"Generated backup file: manual_backup_pengumpulan_zis_20260405_131222.sql","::1","2026-04-05 12:12:22");
INSERT INTO audit_log VALUES("23","1","DOWNLOAD_BACKUP_FILE",NULL,NULL,"Downloaded: manual_backup_pengumpulan_zis_20260405_041259.sql","2001:448a:5142:7175:2d2f:6dcb:c904:ed20","2026-04-05 12:36:57");
INSERT INTO audit_log VALUES("24","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:2d2f:6dcb:c904:ed20","2026-04-05 12:42:12");
INSERT INTO audit_log VALUES("25","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:2d2f:6dcb:c904:ed20","2026-04-05 12:57:10");
INSERT INTO audit_log VALUES("26","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-05 12:58:34");
INSERT INTO audit_log VALUES("27","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:2d2f:6dcb:c904:ed20","2026-04-05 14:02:09");
INSERT INTO audit_log VALUES("28","1","SAVE_TARGET_TAHUNAN","target_pengumpulan_tahunan","2026","Target Rp 6500000000 tahun 2026","2001:448a:5142:7175:2d2f:6dcb:c904:ed20","2026-04-05 14:02:54");
INSERT INTO audit_log VALUES("29","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:2d2f:6dcb:c904:ed20","2026-04-05 14:21:26");
INSERT INTO audit_log VALUES("30","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-05 14:23:50");
INSERT INTO audit_log VALUES("31","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-05 14:24:28");
INSERT INTO audit_log VALUES("32","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-05 14:26:13");
INSERT INTO audit_log VALUES("33","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-05 14:33:14");
INSERT INTO audit_log VALUES("34","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:2d2f:6dcb:c904:ed20","2026-04-05 14:42:28");
INSERT INTO audit_log VALUES("35","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:447b:46b7:88be:4c31","2026-04-05 14:48:26");
INSERT INTO audit_log VALUES("36","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:9399:7211:2819:ebdb","2026-04-05 18:24:57");
INSERT INTO audit_log VALUES("37","1","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260405_192457.sql","2001:448a:5142:7175:9399:7211:2819:ebdb","2026-04-05 18:24:57");
INSERT INTO audit_log VALUES("38","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:7ae7:99bb:1337:dc70","2026-04-05 19:26:40");
INSERT INTO audit_log VALUES("39","1","LOGIN","users","1","User logged in successfully","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 07:31:51");
INSERT INTO audit_log VALUES("40","1","DELETE_BACKUP_FILE",NULL,NULL,"Deleted backup file: manual_backup_pengumpulan_zis_20260405_041259.sql","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:01:45");
INSERT INTO audit_log VALUES("41","1","DELETE_BACKUP_FILE",NULL,NULL,"Deleted backup file: manual_backup_pengumpulan_zis_20260405_131222.sql","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:01:49");
INSERT INTO audit_log VALUES("42","1","BACKUP_DATABASE",NULL,NULL,"Generated backup file: manual_backup_n1579664_xPgbaZtas7iajQGtgfZis_20260406_090157.sql","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:01:57");
INSERT INTO audit_log VALUES("43","1","SAVE_TARGET_TAHUNAN","target_pengumpulan_tahunan","2026","Target Rp 7641000000 tahun 2026","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:08:28");
INSERT INTO audit_log VALUES("50","1","CREATE_TRANSAKSI","transaksi","782","Jumlah: 1642000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:30:33");
INSERT INTO audit_log VALUES("51","1","CREATE_TRANSAKSI","transaksi","783","Jumlah: 50000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:31:39");
INSERT INTO audit_log VALUES("53","4","LOGIN","users","4","User logged in successfully","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:34:33");
INSERT INTO audit_log VALUES("54","3","LOGIN","users","3","User logged in successfully","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:34:53");
INSERT INTO audit_log VALUES("55","3","CREATE_TRANSAKSI","transaksi","785","Jumlah: 450000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:35:37");
INSERT INTO audit_log VALUES("56","4","LOGIN","users","4","User logged in successfully","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:38:54");
INSERT INTO audit_log VALUES("57","4","CREATE_TRANSAKSI","transaksi","786","Jumlah: 320000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:40:12");
INSERT INTO audit_log VALUES("58","4","CREATE_TRANSAKSI","transaksi","787","Jumlah: 380000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:41:40");
INSERT INTO audit_log VALUES("59","3","CREATE_TRANSAKSI","transaksi","788","Jumlah: 80000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:41:54");
INSERT INTO audit_log VALUES("60","4","CREATE_TRANSAKSI","transaksi","789","Jumlah: 1500000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:42:30");
INSERT INTO audit_log VALUES("61","3","CREATE_TRANSAKSI","transaksi","790","Jumlah: 60000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:45:02");
INSERT INTO audit_log VALUES("62","3","UPDATE_TRANSAKSI","transaksi","783","Jumlah: 50000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:45:23");
INSERT INTO audit_log VALUES("63","4","CREATE_TRANSAKSI","transaksi","791","Jumlah: 80000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:46:24");
INSERT INTO audit_log VALUES("64","3","UPDATE_TRANSAKSI","transaksi","788","Jumlah: 80000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:46:27");
INSERT INTO audit_log VALUES("65","4","CREATE_TRANSAKSI","transaksi","792","Jumlah: 200000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:47:21");
INSERT INTO audit_log VALUES("66","3","CREATE_TRANSAKSI","transaksi","793","Jumlah: 120000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:48:24");
INSERT INTO audit_log VALUES("67","4","CREATE_TRANSAKSI","transaksi","794","Jumlah: 150000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:48:55");
INSERT INTO audit_log VALUES("68","4","CREATE_TRANSAKSI","transaksi","795","Jumlah: 1810000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:50:06");
INSERT INTO audit_log VALUES("69","3","CREATE_TRANSAKSI","transaksi","796","Jumlah: 30000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:50:22");
INSERT INTO audit_log VALUES("70","4","CREATE_TRANSAKSI","transaksi","797","Jumlah: 1030000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:51:27");
INSERT INTO audit_log VALUES("71","4","CREATE_TRANSAKSI","transaksi","798","Jumlah: 50000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:52:35");
INSERT INTO audit_log VALUES("72","4","CREATE_TRANSAKSI","transaksi","799","Jumlah: 2630000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:53:23");
INSERT INTO audit_log VALUES("73","4","CREATE_TRANSAKSI","transaksi","800","Jumlah: 260000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:54:21");
INSERT INTO audit_log VALUES("74","4","CREATE_TRANSAKSI","transaksi","801","Jumlah: 30000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:55:27");
INSERT INTO audit_log VALUES("75","3","CREATE_TRANSAKSI","transaksi","802","Jumlah: 560000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:55:33");
INSERT INTO audit_log VALUES("76","4","CREATE_TRANSAKSI","transaksi","803","Jumlah: 2570000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:57:03");
INSERT INTO audit_log VALUES("77","4","CREATE_TRANSAKSI","transaksi","804","Jumlah: 780000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:58:44");
INSERT INTO audit_log VALUES("78","3","CREATE_TRANSAKSI","transaksi","805","Jumlah: 630000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 08:59:32");
INSERT INTO audit_log VALUES("79","4","CREATE_TRANSAKSI","transaksi","806","Jumlah: 770000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 08:59:35");
INSERT INTO audit_log VALUES("80","3","CREATE_TRANSAKSI","transaksi","807","Jumlah: 730000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:00:46");
INSERT INTO audit_log VALUES("81","3","CREATE_TRANSAKSI","transaksi","808","Jumlah: 530000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:02:29");
INSERT INTO audit_log VALUES("82","3","CREATE_TRANSAKSI","transaksi","809","Jumlah: 580000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:03:18");
INSERT INTO audit_log VALUES("83","3","CREATE_TRANSAKSI","transaksi","810","Jumlah: 60000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:04:59");
INSERT INTO audit_log VALUES("84","3","CREATE_TRANSAKSI","transaksi","811","Jumlah: 740000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:09:57");
INSERT INTO audit_log VALUES("85","3","CREATE_TRANSAKSI","transaksi","812","Jumlah: 3540000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:12:51");
INSERT INTO audit_log VALUES("86","4","CREATE_TRANSAKSI","transaksi","813","Jumlah: 2820000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 09:12:56");
INSERT INTO audit_log VALUES("87","4","CREATE_TRANSAKSI","transaksi","814","Jumlah: 2920000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 09:13:34");
INSERT INTO audit_log VALUES("88","3","CREATE_TRANSAKSI","transaksi","815","Jumlah: 60000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:14:01");
INSERT INTO audit_log VALUES("89","4","CREATE_TRANSAKSI","transaksi","816","Jumlah: 340000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 09:14:30");
INSERT INTO audit_log VALUES("90","4","CREATE_TRANSAKSI","transaksi","817","Jumlah: 3370000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 09:15:21");
INSERT INTO audit_log VALUES("91","3","CREATE_TRANSAKSI","transaksi","818","Jumlah: 4830000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:15:53");
INSERT INTO audit_log VALUES("92","4","CREATE_TRANSAKSI","transaksi","819","Jumlah: 960000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 09:16:08");
INSERT INTO audit_log VALUES("93","1","LOGIN","users","1","User logged in successfully","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 09:16:34");
INSERT INTO audit_log VALUES("94","3","CREATE_TRANSAKSI","transaksi","820","Jumlah: 90000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:17:26");
INSERT INTO audit_log VALUES("95","3","CREATE_TRANSAKSI","transaksi","821","Jumlah: 60000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:18:04");
INSERT INTO audit_log VALUES("96","4","LOGIN","users","4","User logged in successfully","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:18:32");
INSERT INTO audit_log VALUES("97","3","CREATE_TRANSAKSI","transaksi","822","Jumlah: 90000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:18:44");
INSERT INTO audit_log VALUES("98","3","CREATE_TRANSAKSI","transaksi","823","Jumlah: 50000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:19:30");
INSERT INTO audit_log VALUES("99","4","CREATE_TRANSAKSI","transaksi","824","Jumlah: 6670000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:20:02");
INSERT INTO audit_log VALUES("100","3","CREATE_TRANSAKSI","transaksi","825","Jumlah: 1420000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:20:09");
INSERT INTO audit_log VALUES("101","4","CREATE_TRANSAKSI","transaksi","826","Jumlah: 390000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:20:49");
INSERT INTO audit_log VALUES("102","3","CREATE_TRANSAKSI","transaksi","827","Jumlah: 30000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:21:31");
INSERT INTO audit_log VALUES("103","4","CREATE_TRANSAKSI","transaksi","828","Jumlah: 2627000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:21:51");
INSERT INTO audit_log VALUES("104","3","CREATE_TRANSAKSI","transaksi","829","Jumlah: 3980000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:22:18");
INSERT INTO audit_log VALUES("105","3","CREATE_TRANSAKSI","transaksi","830","Jumlah: 500000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:23:10");
INSERT INTO audit_log VALUES("106","4","CREATE_TRANSAKSI","transaksi","831","Jumlah: 1030000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:23:17");
INSERT INTO audit_log VALUES("107","4","CREATE_TRANSAKSI","transaksi","832","Jumlah: 2290000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:24:11");
INSERT INTO audit_log VALUES("108","3","CREATE_TRANSAKSI","transaksi","833","Jumlah: 550000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:24:15");
INSERT INTO audit_log VALUES("109","3","CREATE_TRANSAKSI","transaksi","834","Jumlah: 110000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:24:45");
INSERT INTO audit_log VALUES("110","4","CREATE_TRANSAKSI","transaksi","835","Jumlah: 420000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:25:06");
INSERT INTO audit_log VALUES("111","3","CREATE_TRANSAKSI","transaksi","836","Jumlah: 920000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:25:24");
INSERT INTO audit_log VALUES("112","4","CREATE_TRANSAKSI","transaksi","837","Jumlah: 450000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:26:17");
INSERT INTO audit_log VALUES("113","3","CREATE_TRANSAKSI","transaksi","838","Jumlah: 1950000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:27:06");
INSERT INTO audit_log VALUES("114","4","CREATE_TRANSAKSI","transaksi","839","Jumlah: 1000000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:27:28");
INSERT INTO audit_log VALUES("115","3","CREATE_TRANSAKSI","transaksi","840","Jumlah: 810000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:27:37");
INSERT INTO audit_log VALUES("116","3","CREATE_TRANSAKSI","transaksi","841","Jumlah: 280000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:28:13");
INSERT INTO audit_log VALUES("117","4","CREATE_TRANSAKSI","transaksi","842","Jumlah: 60000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:29:31");
INSERT INTO audit_log VALUES("118","3","CREATE_TRANSAKSI","transaksi","843","Jumlah: 1940000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:34:31");
INSERT INTO audit_log VALUES("119","4","CREATE_TRANSAKSI","transaksi","844","Jumlah: 60000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:35:04");
INSERT INTO audit_log VALUES("120","3","CREATE_TRANSAKSI","transaksi","845","Jumlah: 60000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:35:29");
INSERT INTO audit_log VALUES("121","4","CREATE_TRANSAKSI","transaksi","846","Jumlah: 180000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:36:15");
INSERT INTO audit_log VALUES("122","4","CREATE_TRANSAKSI","transaksi","847","Jumlah: 90000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:37:29");
INSERT INTO audit_log VALUES("123","4","CREATE_TRANSAKSI","transaksi","848","Jumlah: 200000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:38:03");
INSERT INTO audit_log VALUES("124","4","CREATE_TRANSAKSI","transaksi","849","Jumlah: 360000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:38:54");
INSERT INTO audit_log VALUES("125","3","CREATE_TRANSAKSI","transaksi","850","Jumlah: 2630000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 09:39:19");
INSERT INTO audit_log VALUES("126","4","CREATE_TRANSAKSI","transaksi","851","Jumlah: 1470000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:39:58");
INSERT INTO audit_log VALUES("127","4","CREATE_TRANSAKSI","transaksi","852","Jumlah: 60000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:41:30");
INSERT INTO audit_log VALUES("128","4","CREATE_TRANSAKSI","transaksi","853","Jumlah: 17940000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:43:39");
INSERT INTO audit_log VALUES("129","4","CREATE_TRANSAKSI","transaksi","854","Jumlah: 145770000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:44:50");
INSERT INTO audit_log VALUES("130","4","CREATE_TRANSAKSI","transaksi","855","Jumlah: 160360000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:46:07");
INSERT INTO audit_log VALUES("131","4","CREATE_TRANSAKSI","transaksi","856","Jumlah: 61590000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:48:24");
INSERT INTO audit_log VALUES("132","4","CREATE_TRANSAKSI","transaksi","857","Jumlah: 2000000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:50:36");
INSERT INTO audit_log VALUES("133","4","CREATE_TRANSAKSI","transaksi","858","Jumlah: 640000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:51:33");
INSERT INTO audit_log VALUES("134","4","CREATE_TRANSAKSI","transaksi","859","Jumlah: 90000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:52:43");
INSERT INTO audit_log VALUES("135","4","CREATE_TRANSAKSI","transaksi","860","Jumlah: 440000, Muzaki ID: ","2001:448a:c140:3f5:a568:2d45:5c60:8c71","2026-04-06 09:53:32");
INSERT INTO audit_log VALUES("136","1","BACKUP_DATABASE",NULL,NULL,"Generated backup file: manual_backup_n1579664_xPgbaZtas7iajQGtgfZis_20260406_105952.sql","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 09:59:52");
INSERT INTO audit_log VALUES("137","1","DELETE_SUB_JENIS_PEMBAYARAN","sub_jenis_pembayaran","25","Deleted ID: 25","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 10:12:39");
INSERT INTO audit_log VALUES("138","1","DELETE_SUB_JENIS_PEMBAYARAN","sub_jenis_pembayaran","24","Deleted ID: 24","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 10:12:45");
INSERT INTO audit_log VALUES("139","1","DELETE_JENIS_PEMBAYARAN","jenis_pembayaran","10","Deleted ID: 10","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 10:13:00");
INSERT INTO audit_log VALUES("140","1","DELETE_JENIS_PEMBAYARAN","jenis_pembayaran","9","Deleted ID: 9","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 10:13:05");
INSERT INTO audit_log VALUES("141","1","LOGIN","users","1","User logged in successfully","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 11:50:51");
INSERT INTO audit_log VALUES("142","3","LOGIN","users","3","User logged in successfully","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 12:02:10");
INSERT INTO audit_log VALUES("143","3","CREATE_UPZ","upz","155","Nama UPZ: GAPENSI","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 12:03:56");
INSERT INTO audit_log VALUES("144","3","CREATE_TRANSAKSI","transaksi","861","Jumlah: 300000, Muzaki ID: ","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 12:05:13");
INSERT INTO audit_log VALUES("145","3","CREATE_MUZAKI","muzaki","132","Nama: NANANG NASIRUDDIN, KTP: 5208","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 12:09:49");
INSERT INTO audit_log VALUES("146","3","CREATE_TRANSAKSI","transaksi","862","Jumlah: 1000000, Muzaki ID: 132","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 12:10:52");
INSERT INTO audit_log VALUES("147","1","LOGIN","users","1","User logged in successfully","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 13:43:32");
INSERT INTO audit_log VALUES("148","1","UPDATE_TRANSAKSI","transaksi","861","Jumlah: 300000, Muzaki ID: ","2001:448a:c140:3f5:8412:724a:61ad:3858","2026-04-06 13:46:46");
INSERT INTO audit_log VALUES("149","1","LOGIN","users","1","User logged in successfully","2001:448a:c140:3f5:5de5:99c0:6fe:2f78","2026-04-06 15:01:39");
INSERT INTO audit_log VALUES("150","4","LOGIN","users","4","User logged in successfully","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-06 15:12:14");
INSERT INTO audit_log VALUES("151","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:ca08:6872:ee86:a901","2026-04-06 16:25:52");
INSERT INTO audit_log VALUES("152","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:ca08:6872:ee86:a901","2026-04-06 17:07:18");
INSERT INTO audit_log VALUES("153","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:c24e:f227:e62:446e:ad5f","2026-04-06 19:09:24");
INSERT INTO audit_log VALUES("154","1","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260406_200924.sql","2001:448a:5142:c24e:f227:e62:446e:ad5f","2026-04-06 19:09:24");
INSERT INTO audit_log VALUES("155","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:c24e:f227:e62:446e:ad5f","2026-04-06 19:16:50");
INSERT INTO audit_log VALUES("156","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:6f7a:44f4:1a81:d78a","2026-04-06 19:38:20");
INSERT INTO audit_log VALUES("157","4","LOGIN","users","4","User logged in successfully","2001:448a:c140:3f5:a428:2aaf:4106:aec6","2026-04-07 07:06:15");
INSERT INTO audit_log VALUES("158","1","LOGIN","users","1","User logged in successfully","2001:448a:c140:3f5:91a7:3b62:e168:723d","2026-04-07 07:10:56");
INSERT INTO audit_log VALUES("159","1","UPDATE_REKENING_KORAN","rekening_koran","15","Bulan: 2026-02, Kredit: 70.008","2001:448a:c140:3f5:91a7:3b62:e168:723d","2026-04-07 07:19:05");
INSERT INTO audit_log VALUES("160","1","UPDATE_REKENING_KORAN","rekening_koran","15","Bulan: 2026-02, Kredit: 70.008","2001:448a:c140:3f5:91a7:3b62:e168:723d","2026-04-07 07:19:40");
INSERT INTO audit_log VALUES("161","1","LOGIN","users","1","User logged in successfully","2001:448a:c140:3f5:91a7:3b62:e168:723d","2026-04-07 08:37:59");
INSERT INTO audit_log VALUES("162","4","LOGIN","users","4","User logged in successfully","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","2026-04-07 09:05:05");
INSERT INTO audit_log VALUES("163","4","UPDATE_TRANSAKSI","transaksi","860","Jumlah: 440000, Muzaki ID: ","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","2026-04-07 09:05:47");
INSERT INTO audit_log VALUES("164","4","UPDATE_TRANSAKSI","transaksi","859","Jumlah: 90000, Muzaki ID: ","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","2026-04-07 09:06:03");
INSERT INTO audit_log VALUES("165","4","CREATE_TRANSAKSI","transaksi","863","Jumlah: 5000, Muzaki ID: 6","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","2026-04-07 09:27:25");
INSERT INTO audit_log VALUES("169","4","LOGIN","users","4","User logged in successfully","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","2026-04-07 11:01:08");
INSERT INTO audit_log VALUES("170","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:6f7a:44f4:1a81:d78a","2026-04-07 17:12:18");
INSERT INTO audit_log VALUES("171","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:6f7a:44f4:1a81:d78a","2026-04-07 17:23:09");
INSERT INTO audit_log VALUES("172","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:6f7a:44f4:1a81:d78a","2026-04-07 18:16:33");
INSERT INTO audit_log VALUES("173","1","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260407_191634.sql","2001:448a:5142:7175:6f7a:44f4:1a81:d78a","2026-04-07 18:16:34");
INSERT INTO audit_log VALUES("174","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:29b9:5379:d55f:cd8a","2026-04-07 19:58:41");
INSERT INTO audit_log VALUES("175","1","LOGIN","users","1","User logged in successfully","2001:448a:5142:7175:29b9:5379:d55f:cd8a","2026-04-07 20:46:33");
INSERT INTO audit_log VALUES("176","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-07 20:54:55");
INSERT INTO audit_log VALUES("177","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-07 20:55:10");
INSERT INTO audit_log VALUES("178","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-07 21:01:25");
INSERT INTO audit_log VALUES("179","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-07 21:02:08");
INSERT INTO audit_log VALUES("180","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-07 21:02:20");
INSERT INTO audit_log VALUES("181","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-07 21:46:33");
INSERT INTO audit_log VALUES("182","1","BACKUP_DATABASE",NULL,NULL,"Generated backup file: manual_backup_n1579664_xPgbaZtas7iajQGtgfZis_20260407_225423.sql","125.166.125.232","2026-04-07 21:54:23");
INSERT INTO audit_log VALUES("183","1","UPDATE_REKENING_KORAN","rekening_koran","15","Bulan: 2026-02, Kredit: 70000000","125.166.125.232","2026-04-07 21:55:22");
INSERT INTO audit_log VALUES("184","1","UPDATE_MUZAKI","muzaki","94","Nama: PAHRIYADI 1, KTP: 08515","125.166.125.232","2026-04-07 21:56:11");
INSERT INTO audit_log VALUES("185","1","UPDATE_MUZAKI","muzaki","94","Nama: PAHRIYADI, KTP: 08515","125.166.125.232","2026-04-07 21:56:23");
INSERT INTO audit_log VALUES("186","1","CREATE_MUZAKI","muzaki","134","Nama: PAHRIYADI 2, KTP: 085158777","125.166.125.232","2026-04-07 21:56:59");
INSERT INTO audit_log VALUES("187","1","DELETE_MUZAKI","muzaki","134","Deleted muzaki ID: 134","125.166.125.232","2026-04-07 21:57:11");
INSERT INTO audit_log VALUES("188","1","UPDATE_UPZ","upz","57","Nama UPZ: BADAN KESBANGPOL | 8.01.0.00.0.00.01.0000","125.166.125.232","2026-04-07 21:57:35");
INSERT INTO audit_log VALUES("189","1","UPDATE_UPZ","upz","57","Nama UPZ: BADAN KESBANGPOL | 8.01.0.00.0.00.01.0000","125.166.125.232","2026-04-07 21:57:44");
INSERT INTO audit_log VALUES("190","1","UPDATE_JENIS_PEMBAYARAN","jenis_pembayaran","11","Nama: CSR 1","125.166.125.232","2026-04-07 21:59:05");
INSERT INTO audit_log VALUES("191","1","UPDATE_JENIS_PEMBAYARAN","jenis_pembayaran","11","Nama: CSR","125.166.125.232","2026-04-07 21:59:14");
INSERT INTO audit_log VALUES("192","1","CREATE_JENIS_PEMBAYARAN","jenis_pembayaran","12","Nama: APBD","125.166.125.232","2026-04-07 21:59:23");
INSERT INTO audit_log VALUES("193","1","DELETE_JENIS_PEMBAYARAN","jenis_pembayaran","12","Deleted ID: 12","125.166.125.232","2026-04-07 21:59:29");
INSERT INTO audit_log VALUES("194","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-07 22:46:23");
INSERT INTO audit_log VALUES("195","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-07 23:20:54");
INSERT INTO audit_log VALUES("196","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-08 00:01:52");
INSERT INTO audit_log VALUES("197","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-08 00:03:47");
INSERT INTO audit_log VALUES("198","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-08 00:16:41");
INSERT INTO audit_log VALUES("199","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-08 07:15:15");
INSERT INTO audit_log VALUES("200","4","CREATE_MUZAKI","muzaki","135","Nama: DADANG PUTRAWANSYAH, KTP: 62378428","36.84.34.227","2026-04-08 07:20:43");
INSERT INTO audit_log VALUES("201","4","CREATE_MUZAKI","muzaki","136","Nama: RAHMATULLAH, KTP: 645645","36.84.34.227","2026-04-08 07:21:36");
INSERT INTO audit_log VALUES("202","4","CREATE_TRANSAKSI","transaksi","866","Jumlah: 100000, Muzaki ID: 136","36.84.34.227","2026-04-08 07:23:28");
INSERT INTO audit_log VALUES("203","4","CREATE_TRANSAKSI","transaksi","867","Jumlah: 100000, Muzaki ID: 135","36.84.34.227","2026-04-08 07:24:01");
INSERT INTO audit_log VALUES("204","4","CREATE_TRANSAKSI","transaksi","868","Jumlah: 300000, Muzaki ID: ","36.84.34.227","2026-04-08 07:24:51");
INSERT INTO audit_log VALUES("205","4","UPDATE_UPZ","upz","155","Nama UPZ: GAPENSI KAB. SUMBAWA","36.84.34.227","2026-04-08 07:25:47");
INSERT INTO audit_log VALUES("206","4","UPDATE_UPZ","upz","108","Nama UPZ: SDN 7 ALAS","36.84.34.227","2026-04-08 07:26:11");
INSERT INTO audit_log VALUES("207","4","UPDATE_UPZ","upz","98","Nama UPZ: SDN BUKIT PERMAI","36.84.34.227","2026-04-08 07:26:36");
INSERT INTO audit_log VALUES("208","4","UPDATE_UPZ","upz","73","Nama UPZ: SDN LOPOK BERU","36.84.34.227","2026-04-08 07:26:54");
INSERT INTO audit_log VALUES("209","4","CREATE_TRANSAKSI","transaksi","869","Jumlah: 5000, Muzaki ID: 6","36.84.34.227","2026-04-08 07:30:29");
INSERT INTO audit_log VALUES("210","4","CREATE_TRANSAKSI","transaksi","870","Jumlah: 3776, Muzaki ID: 6","36.84.34.227","2026-04-08 07:38:07");
INSERT INTO audit_log VALUES("211","4","CREATE_MUZAKI","muzaki","137","Nama: SITI SALEHA 2, KTP: 5454","36.84.34.227","2026-04-08 07:40:33");
INSERT INTO audit_log VALUES("212","4","CREATE_TRANSAKSI","transaksi","871","Jumlah: 300000, Muzaki ID: 137","36.84.34.227","2026-04-08 07:41:24");
INSERT INTO audit_log VALUES("213","1","LOGIN","users","1","User logged in successfully","112.215.221.235","2026-04-08 08:37:17");
INSERT INTO audit_log VALUES("214","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-08 09:57:46");
INSERT INTO audit_log VALUES("215","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-08 10:28:12");
INSERT INTO audit_log VALUES("216","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-08 11:07:55");
INSERT INTO audit_log VALUES("217","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-08 11:19:53");
INSERT INTO audit_log VALUES("218","1","UPDATE_USER","users","5","Username: Supriyadi, Role: admin","36.84.34.227","2026-04-08 11:47:19");
INSERT INTO audit_log VALUES("219","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-08 12:35:06");
INSERT INTO audit_log VALUES("220","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-08 12:43:35");
INSERT INTO audit_log VALUES("221","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-08 13:56:23");
INSERT INTO audit_log VALUES("222","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-08 14:31:38");
INSERT INTO audit_log VALUES("223","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-08 21:24:30");
INSERT INTO audit_log VALUES("224","1","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260408_222430.sql","125.166.125.232","2026-04-08 21:24:30");
INSERT INTO audit_log VALUES("225","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-09 01:03:15");
INSERT INTO audit_log VALUES("226","1","LOGIN","users","1","User logged in successfully","125.166.125.232","2026-04-09 01:58:52");
INSERT INTO audit_log VALUES("227","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-09 07:36:10");
INSERT INTO audit_log VALUES("228","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-09 07:52:47");
INSERT INTO audit_log VALUES("229","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-09 08:30:01");
INSERT INTO audit_log VALUES("230","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-09 08:59:22");
INSERT INTO audit_log VALUES("231","4","UPDATE_TRANSAKSI","transaksi","769","Jumlah: 2765000, Muzaki ID: 117","36.84.34.227","2026-04-09 09:07:54");
INSERT INTO audit_log VALUES("232","4","UPDATE_TRANSAKSI","transaksi","766","Jumlah: 2170000, Muzaki ID: ","36.84.34.227","2026-04-09 09:08:43");
INSERT INTO audit_log VALUES("233","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-09 10:14:19");
INSERT INTO audit_log VALUES("234","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-09 10:27:16");
INSERT INTO audit_log VALUES("235","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-09 11:41:17");
INSERT INTO audit_log VALUES("236","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-09 12:15:47");
INSERT INTO audit_log VALUES("237","4","UPDATE_REKENING_KORAN","rekening_koran","15","Bulan: 2026-02, Kredit: 70008118","36.84.34.227","2026-04-09 12:17:12");
INSERT INTO audit_log VALUES("238","4","CREATE_REKENING_KORAN","rekening_koran","24","Bulan: 2026-03, Kredit: 456684","36.84.34.227","2026-04-09 12:23:35");
INSERT INTO audit_log VALUES("239","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-09 13:03:24");
INSERT INTO audit_log VALUES("240","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-09 13:10:24");
INSERT INTO audit_log VALUES("241","1","LOGIN","users","1","User logged in successfully","125.166.124.198","2026-04-09 22:01:20");
INSERT INTO audit_log VALUES("242","1","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260409_230121.sql","125.166.124.198","2026-04-09 22:01:21");
INSERT INTO audit_log VALUES("243","1","LOGIN","users","1","User logged in successfully","140.213.126.34","2026-04-10 10:52:32");
INSERT INTO audit_log VALUES("244","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-10 17:08:45");
INSERT INTO audit_log VALUES("245","4","CREATE_TRANSAKSI","transaksi","872","Jumlah: 60000, Muzaki ID: ","36.84.34.227","2026-04-10 17:11:20");
INSERT INTO audit_log VALUES("246","4","CREATE_TRANSAKSI","transaksi","873","Jumlah: 711000, Muzaki ID: ","36.84.34.227","2026-04-10 17:13:15");
INSERT INTO audit_log VALUES("247","4","CREATE_TRANSAKSI","transaksi","874","Jumlah: 1910000, Muzaki ID: ","36.84.34.227","2026-04-10 17:15:30");
INSERT INTO audit_log VALUES("248","4","CREATE_TRANSAKSI","transaksi","875","Jumlah: 150000, Muzaki ID: ","36.84.34.227","2026-04-10 17:18:53");
INSERT INTO audit_log VALUES("249","4","CREATE_TRANSAKSI","transaksi","876","Jumlah: 5000, Muzaki ID: 6","36.84.34.227","2026-04-10 17:21:10");
INSERT INTO audit_log VALUES("250","4","CREATE_TRANSAKSI","transaksi","877","Jumlah: 152000, Muzaki ID: ","36.84.34.227","2026-04-10 17:23:43");
INSERT INTO audit_log VALUES("251","4","CREATE_UPZ","upz","156","Nama UPZ: SDN LITO JAM KEC. MOYO HULU","36.84.34.227","2026-04-10 17:25:15");
INSERT INTO audit_log VALUES("252","4","CREATE_TRANSAKSI","transaksi","878","Jumlah: 156000, Muzaki ID: ","36.84.34.227","2026-04-10 17:26:29");
INSERT INTO audit_log VALUES("253","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-10 18:00:52");
INSERT INTO audit_log VALUES("254","4","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260410_190052.sql","36.84.34.227","2026-04-10 18:00:52");
INSERT INTO audit_log VALUES("255","1","LOGIN","users","1","User logged in successfully","125.166.124.198","2026-04-10 21:20:27");
INSERT INTO audit_log VALUES("256","1","LOGIN","users","1","User logged in successfully","125.166.124.198","2026-04-11 10:40:55");
INSERT INTO audit_log VALUES("257","1","LOGIN","users","1","User logged in successfully","125.166.124.198","2026-04-11 21:16:19");
INSERT INTO audit_log VALUES("258","1","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260411_221619.sql","125.166.124.198","2026-04-11 21:16:19");
INSERT INTO audit_log VALUES("259","1","LOGIN","users","1","User logged in successfully","125.166.124.198","2026-04-11 22:53:16");
INSERT INTO audit_log VALUES("260","1","LOGIN","users","1","User logged in successfully","112.215.64.100","2026-04-12 21:30:17");
INSERT INTO audit_log VALUES("261","1","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260412_223017.sql","112.215.64.100","2026-04-12 21:30:17");
INSERT INTO audit_log VALUES("262","1","LOGIN","users","1","User logged in successfully","140.213.127.92","2026-04-12 21:36:41");
INSERT INTO audit_log VALUES("263","1","LOGIN","users","1","User logged in successfully","125.166.126.150","2026-04-12 22:46:40");
INSERT INTO audit_log VALUES("264","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-13 07:26:46");
INSERT INTO audit_log VALUES("265","4","CREATE_TRANSAKSI","transaksi","879","Jumlah: 680000, Muzaki ID: ","36.84.34.227","2026-04-13 07:36:09");
INSERT INTO audit_log VALUES("266","4","CREATE_TRANSAKSI","transaksi","880","Jumlah: 90000, Muzaki ID: ","36.84.34.227","2026-04-13 07:37:10");
INSERT INTO audit_log VALUES("267","4","CREATE_TRANSAKSI","transaksi","881","Jumlah: 630000, Muzaki ID: ","36.84.34.227","2026-04-13 07:38:21");
INSERT INTO audit_log VALUES("268","4","CREATE_TRANSAKSI","transaksi","882","Jumlah: 60000, Muzaki ID: ","36.84.34.227","2026-04-13 07:42:03");
INSERT INTO audit_log VALUES("269","4","CREATE_TRANSAKSI","transaksi","883","Jumlah: 60000, Muzaki ID: ","36.84.34.227","2026-04-13 07:43:05");
INSERT INTO audit_log VALUES("270","4","CREATE_TRANSAKSI","transaksi","884","Jumlah: 30000, Muzaki ID: ","36.84.34.227","2026-04-13 07:44:01");
INSERT INTO audit_log VALUES("271","4","CREATE_TRANSAKSI","transaksi","885","Jumlah: 490000, Muzaki ID: ","36.84.34.227","2026-04-13 07:44:59");
INSERT INTO audit_log VALUES("272","4","CREATE_TRANSAKSI","transaksi","886","Jumlah: 120000, Muzaki ID: ","36.84.34.227","2026-04-13 07:59:05");
INSERT INTO audit_log VALUES("273","3","LOGIN","users","3","User logged in successfully","36.84.34.227","2026-04-13 08:22:35");
INSERT INTO audit_log VALUES("274","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-13 09:27:43");
INSERT INTO audit_log VALUES("275","4","CREATE_TRANSAKSI","transaksi","887","Jumlah: 1350000, Muzaki ID: ","36.84.34.227","2026-04-13 09:28:25");
INSERT INTO audit_log VALUES("276","4","CREATE_TRANSAKSI","transaksi","888","Jumlah: 1560000, Muzaki ID: ","36.84.34.227","2026-04-13 09:29:08");
INSERT INTO audit_log VALUES("277","4","CREATE_TRANSAKSI","transaksi","889","Jumlah: 500000, Muzaki ID: ","36.84.34.227","2026-04-13 09:30:10");
INSERT INTO audit_log VALUES("278","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-13 13:15:43");
INSERT INTO audit_log VALUES("279","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-13 13:22:25");
INSERT INTO audit_log VALUES("280","4","CREATE_TRANSAKSI","transaksi","890","Jumlah: 440000, Muzaki ID: ","36.84.34.227","2026-04-13 13:23:24");
INSERT INTO audit_log VALUES("281","4","CREATE_TRANSAKSI","transaksi","891","Jumlah: 5000, Muzaki ID: 6","36.84.34.227","2026-04-13 13:26:09");
INSERT INTO audit_log VALUES("282","4","CREATE_TRANSAKSI","transaksi","892","Jumlah: 5000, Muzaki ID: 6","36.84.34.227","2026-04-13 13:26:51");
INSERT INTO audit_log VALUES("283","4","CREATE_TRANSAKSI","transaksi","893","Jumlah: 50000, Muzaki ID: 6","36.84.34.227","2026-04-13 13:27:39");
INSERT INTO audit_log VALUES("284","4","CREATE_MUZAKI","muzaki","139","Nama: A. LATIEF MAJID, KTP: 520408632","36.84.34.227","2026-04-13 13:35:18");
INSERT INTO audit_log VALUES("285","4","CREATE_TRANSAKSI","transaksi","894","Jumlah: 8190000, Muzaki ID: 139","36.84.34.227","2026-04-13 13:37:53");
INSERT INTO audit_log VALUES("286","1","LOGIN","users","1","User logged in successfully","125.166.126.123","2026-04-13 18:08:04");
INSERT INTO audit_log VALUES("287","1","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260413_190804.sql","125.166.126.123","2026-04-13 18:08:04");
INSERT INTO audit_log VALUES("288","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-14 06:35:17");
INSERT INTO audit_log VALUES("289","1","LOGIN","users","1","User logged in successfully","125.166.126.123","2026-04-14 06:36:04");
INSERT INTO audit_log VALUES("290","4","CREATE_TRANSAKSI","transaksi","895","Jumlah: 60000, Muzaki ID: ","36.84.34.227","2026-04-14 06:40:06");
INSERT INTO audit_log VALUES("291","4","CREATE_TRANSAKSI","transaksi","896","Jumlah: 40000, Muzaki ID: ","36.84.34.227","2026-04-14 06:40:55");
INSERT INTO audit_log VALUES("292","4","CREATE_TRANSAKSI","transaksi","897","Jumlah: 10000, Muzaki ID: ","36.84.34.227","2026-04-14 06:41:58");
INSERT INTO audit_log VALUES("293","4","CREATE_TRANSAKSI","transaksi","898","Jumlah: 250000, Muzaki ID: ","36.84.34.227","2026-04-14 06:42:51");
INSERT INTO audit_log VALUES("294","4","CREATE_TRANSAKSI","transaksi","899","Jumlah: 3100000, Muzaki ID: ","36.84.34.227","2026-04-14 06:43:38");
INSERT INTO audit_log VALUES("295","4","CREATE_TRANSAKSI","transaksi","900","Jumlah: 460000, Muzaki ID: ","36.84.34.227","2026-04-14 06:44:23");
INSERT INTO audit_log VALUES("296","4","CREATE_TRANSAKSI","transaksi","901","Jumlah: 5000, Muzaki ID: 6","36.84.34.227","2026-04-14 06:45:08");
INSERT INTO audit_log VALUES("297","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-14 07:13:53");
INSERT INTO audit_log VALUES("298","3","LOGIN","users","3","User logged in successfully","36.84.34.227","2026-04-14 09:29:30");
INSERT INTO audit_log VALUES("299","3","CREATE_TRANSAKSI","transaksi","902","Jumlah: 300000, Muzaki ID: ","36.84.34.227","2026-04-14 09:30:39");
INSERT INTO audit_log VALUES("300","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-14 12:36:12");
INSERT INTO audit_log VALUES("301","3","LOGIN","users","3","User logged in successfully","36.84.34.227","2026-04-14 13:22:45");
INSERT INTO audit_log VALUES("302","3","CREATE_TRANSAKSI","transaksi","903","Jumlah: 50000, Muzaki ID: ","36.84.34.227","2026-04-14 13:23:25");
INSERT INTO audit_log VALUES("303","3","CREATE_TRANSAKSI","transaksi","904","Jumlah: 30000, Muzaki ID: ","36.84.34.227","2026-04-14 13:24:10");
INSERT INTO audit_log VALUES("304","3","UPDATE_TRANSAKSI","transaksi","904","Jumlah: 30000, Muzaki ID: ","36.84.34.227","2026-04-14 13:24:28");
INSERT INTO audit_log VALUES("305","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-14 13:40:16");
INSERT INTO audit_log VALUES("306","1","LOGIN","users","1","User logged in successfully","125.166.126.123","2026-04-14 23:25:50");
INSERT INTO audit_log VALUES("307","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-15 07:28:08");
INSERT INTO audit_log VALUES("308","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-15 08:13:19");
INSERT INTO audit_log VALUES("309","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-15 08:54:05");
INSERT INTO audit_log VALUES("310","4","CREATE_MUZAKI","muzaki","140","Nama: KAMARIAH, KTP: 520000","36.84.34.227","2026-04-15 08:55:48");
INSERT INTO audit_log VALUES("311","4","DELETE_MUZAKI","muzaki","140","Deleted muzaki ID: 140","36.84.34.227","2026-04-15 08:57:46");
INSERT INTO audit_log VALUES("312","4","CREATE_TRANSAKSI","transaksi","905","Jumlah: 100000, Muzaki ID: 63","36.84.34.227","2026-04-15 08:58:36");
INSERT INTO audit_log VALUES("313","4","CREATE_TRANSAKSI","transaksi","906","Jumlah: 1220000, Muzaki ID: ","36.84.34.227","2026-04-15 09:00:08");
INSERT INTO audit_log VALUES("314","4","CREATE_TRANSAKSI","transaksi","907","Jumlah: 1000, Muzaki ID: 6","36.84.34.227","2026-04-15 09:01:06");
INSERT INTO audit_log VALUES("315","4","CREATE_TRANSAKSI","transaksi","908","Jumlah: 1000, Muzaki ID: 6","36.84.34.227","2026-04-15 09:03:10");
INSERT INTO audit_log VALUES("316","4","CREATE_MUZAKI","muzaki","141","Nama: MUSTAMI, KTP: 4156485","36.84.34.227","2026-04-15 09:06:42");
INSERT INTO audit_log VALUES("317","4","CREATE_TRANSAKSI","transaksi","909","Jumlah: 500000, Muzaki ID: 141","36.84.34.227","2026-04-15 09:07:54");
INSERT INTO audit_log VALUES("318","4","CREATE_TRANSAKSI","transaksi","910","Jumlah: 200000, Muzaki ID: 14","36.84.34.227","2026-04-15 09:09:31");
INSERT INTO audit_log VALUES("319","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-15 09:17:01");
INSERT INTO audit_log VALUES("320","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-15 11:06:58");
INSERT INTO audit_log VALUES("321","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-15 11:07:40");
INSERT INTO audit_log VALUES("322","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-15 12:50:50");
INSERT INTO audit_log VALUES("323","4","CREATE_TRANSAKSI","transaksi","911","Jumlah: 1000, Muzaki ID: 6","36.84.34.227","2026-04-15 12:52:53");
INSERT INTO audit_log VALUES("324","4","CREATE_TRANSAKSI","transaksi","912","Jumlah: 1000, Muzaki ID: 6","36.84.34.227","2026-04-15 12:53:23");
INSERT INTO audit_log VALUES("325","4","CREATE_TRANSAKSI","transaksi","913","Jumlah: 1000, Muzaki ID: 6","36.84.34.227","2026-04-15 12:54:02");
INSERT INTO audit_log VALUES("326","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-15 13:34:24");
INSERT INTO audit_log VALUES("327","4","BACKUP_DATABASE",NULL,NULL,"Generated backup file: manual_backup_n1579664_xPgbaZtas7iajQGtgfZis_20260415_151035.sql","36.84.34.227","2026-04-15 14:10:35");
INSERT INTO audit_log VALUES("328","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-16 07:14:06");
INSERT INTO audit_log VALUES("329","4","CREATE_TRANSAKSI","transaksi","914","Jumlah: 20000, Muzaki ID: 6","36.84.34.227","2026-04-16 07:16:20");
INSERT INTO audit_log VALUES("330","4","CREATE_TRANSAKSI","transaksi","915","Jumlah: 60000, Muzaki ID: ","36.84.34.227","2026-04-16 07:18:24");
INSERT INTO audit_log VALUES("331","4","CREATE_TRANSAKSI","transaksi","916","Jumlah: 2970000, Muzaki ID: ","36.84.34.227","2026-04-16 07:19:16");
INSERT INTO audit_log VALUES("332","4","CREATE_TRANSAKSI","transaksi","917","Jumlah: 5000, Muzaki ID: 6","36.84.34.227","2026-04-16 07:19:50");
INSERT INTO audit_log VALUES("333","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-16 09:29:44");
INSERT INTO audit_log VALUES("334","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-16 12:10:50");
INSERT INTO audit_log VALUES("335","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-16 12:11:47");
INSERT INTO audit_log VALUES("336","4","CREATE_TRANSAKSI","transaksi","918","Jumlah: 1000, Muzaki ID: 6","36.84.34.227","2026-04-16 12:12:28");
INSERT INTO audit_log VALUES("337","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-16 14:03:55");
INSERT INTO audit_log VALUES("338","1","LOGIN","users","1","User logged in successfully","125.166.127.80","2026-04-16 21:42:43");
INSERT INTO audit_log VALUES("339","1","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260416_224243.sql","125.166.127.80","2026-04-16 21:42:43");
INSERT INTO audit_log VALUES("340","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-17 06:57:22");
INSERT INTO audit_log VALUES("341","4","CREATE_TRANSAKSI","transaksi","919","Jumlah: 5000, Muzaki ID: 6","36.84.34.227","2026-04-17 06:59:06");
INSERT INTO audit_log VALUES("342","4","LOGIN","users","4","User logged in successfully","36.84.34.227","2026-04-17 08:17:42");
INSERT INTO audit_log VALUES("343","4","CREATE_TRANSAKSI","transaksi","920","Jumlah: 100000, Muzaki ID: ","36.84.34.227","2026-04-17 08:20:44");
INSERT INTO audit_log VALUES("344","4","CREATE_TRANSAKSI","transaksi","921","Jumlah: 460000, Muzaki ID: ","36.84.34.227","2026-04-17 09:18:23");
INSERT INTO audit_log VALUES("345","4","UPDATE_TRANSAKSI","transaksi","921","Jumlah: 460000, Muzaki ID: ","36.84.34.227","2026-04-17 09:19:04");
INSERT INTO audit_log VALUES("346","4","CREATE_MUZAKI","muzaki","144","Nama: KARTIKA SARI, KTP: 123456","36.84.34.227","2026-04-17 09:22:22");
INSERT INTO audit_log VALUES("347","4","CREATE_TRANSAKSI","transaksi","922","Jumlah: 10000, Muzaki ID: 144","36.84.34.227","2026-04-17 09:23:39");
INSERT INTO audit_log VALUES("348","1","LOGIN","users","1","User logged in successfully","36.84.34.227","2026-04-17 20:05:52");
INSERT INTO audit_log VALUES("349","1","AUTO_BACKUP_DATABASE",NULL,NULL,"Internal auto backup created: auto_backup_20260417_210552.sql","36.84.34.227","2026-04-17 20:05:52");
INSERT INTO audit_log VALUES("350","1","LOGIN","users","1","User logged in successfully","36.84.29.19","2026-04-18 21:33:50");


CREATE TABLE `jenis_pembayaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_jenis` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_jenis` (`nama_jenis`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO jenis_pembayaran VALUES("6","Zakat","2026-01-12 14:08:44","2026-01-12 14:08:44");
INSERT INTO jenis_pembayaran VALUES("7","Infak dan sedekah","2026-01-12 14:08:53","2026-01-12 14:08:53");
INSERT INTO jenis_pembayaran VALUES("8","DSKL","2026-01-13 11:00:30","2026-01-13 11:00:30");
INSERT INTO jenis_pembayaran VALUES("11","CSR","2026-03-10 11:30:53","2026-04-07 21:59:14");


CREATE TABLE `kategori_dana` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_kategori` (`nama_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO kategori_dana VALUES("2","Pengumpulan","2026-01-04 22:31:54","2026-01-04 22:31:54");
INSERT INTO kategori_dana VALUES("3","Penerimaan","2026-01-12 14:08:12","2026-01-12 14:08:12");


CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `attempt_time` datetime NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `block_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_username_ip` (`username`,`ip_address`),
  KEY `idx_attempt_time` (`attempt_time`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB AUTO_INCREMENT=281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO login_attempts VALUES("1","admin","127.0.0.1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36","2026-02-03 21:22:57","1",NULL);
INSERT INTO login_attempts VALUES("2","admin","127.0.0.1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36","2026-02-03 20:22:57","0",NULL);
INSERT INTO login_attempts VALUES("3","testuser","192.168.1.100","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36","2026-02-03 19:22:57","0",NULL);
INSERT INTO login_attempts VALUES("4","pahriyadi","125.166.124.153","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-03 21:26:28","1",NULL);
INSERT INTO login_attempts VALUES("5","pahriyadi","125.166.124.153","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-03 22:13:53","1",NULL);
INSERT INTO login_attempts VALUES("6","Fira Aulia","125.166.125.192","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-05 07:14:24","1",NULL);
INSERT INTO login_attempts VALUES("7","pahriyadi","125.166.125.192","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-05 07:41:23","1",NULL);
INSERT INTO login_attempts VALUES("8","pahriyadi","125.166.127.40","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-05 09:33:31","1",NULL);
INSERT INTO login_attempts VALUES("9","Erwin Mahendra","125.166.127.40","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-05 09:38:15","1",NULL);
INSERT INTO login_attempts VALUES("10","Fira Aulia","125.166.127.40","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-05 13:21:50","1",NULL);
INSERT INTO login_attempts VALUES("11","pahriyadi","36.84.183.223","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-05 18:32:34","1",NULL);
INSERT INTO login_attempts VALUES("12","Fira Aulia","125.166.127.40","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-06 14:10:14","1",NULL);
INSERT INTO login_attempts VALUES("13","Fira Aulia","125.166.126.111","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-09 07:24:18","1",NULL);
INSERT INTO login_attempts VALUES("14","pahriyadi","125.166.126.111","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-09 07:54:06","1",NULL);
INSERT INTO login_attempts VALUES("15","Pahriyadi","36.84.240.13","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36","2026-02-09 09:19:02","1",NULL);
INSERT INTO login_attempts VALUES("16","pahriyadi","125.166.124.246","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-09 10:50:23","1",NULL);
INSERT INTO login_attempts VALUES("17","pahriyadi","125.166.124.32","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-09 12:48:15","1",NULL);
INSERT INTO login_attempts VALUES("18","Fira Aulia","125.166.124.32","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-09 13:09:47","1",NULL);
INSERT INTO login_attempts VALUES("19","pahriyadi","125.166.124.32","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-09 14:37:57","1",NULL);
INSERT INTO login_attempts VALUES("20","pahriyadi","125.166.124.32","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-10 07:29:38","1",NULL);
INSERT INTO login_attempts VALUES("21","Fira Aulia","125.166.124.32","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-10 07:38:45","1",NULL);
INSERT INTO login_attempts VALUES("22","Fira Aulia","36.84.70.90","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-10 09:07:29","1",NULL);
INSERT INTO login_attempts VALUES("23","pahriyadi","36.84.70.90","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-10 20:05:34","1",NULL);
INSERT INTO login_attempts VALUES("24","Fira Aulia","36.84.70.90","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-11 13:20:15","1",NULL);
INSERT INTO login_attempts VALUES("25","admin","36.84.70.90","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-11 14:07:06","0",NULL);
INSERT INTO login_attempts VALUES("26","erwin mahendra","36.84.70.90","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-11 14:07:36","0",NULL);
INSERT INTO login_attempts VALUES("27","erwinmahendra","36.84.70.90","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-11 14:07:55","0",NULL);
INSERT INTO login_attempts VALUES("28","erwinmahendra02","36.84.70.90","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-11 14:08:07","0",NULL);
INSERT INTO login_attempts VALUES("29","erwinmahendra02","36.84.70.90","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-11 14:08:17","0",NULL);
INSERT INTO login_attempts VALUES("30","pahriyadi","36.84.70.90","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 07:36:24","1",NULL);
INSERT INTO login_attempts VALUES("31","pahriyadi","36.84.70.90","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 09:08:26","1",NULL);
INSERT INTO login_attempts VALUES("32","tezar16","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 12:31:37","0",NULL);
INSERT INTO login_attempts VALUES("33","erwinmahendra02","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 12:32:05","0",NULL);
INSERT INTO login_attempts VALUES("34","erwinmahendra02","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 12:33:43","0",NULL);
INSERT INTO login_attempts VALUES("35","erwinmahendra02","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 12:33:55","0",NULL);
INSERT INTO login_attempts VALUES("36","Erwinmahendra02","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 12:34:40","0","2026-02-12 13:51:21");
INSERT INTO login_attempts VALUES("37","admin","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 12:36:21","0","2026-02-12 13:52:22");
INSERT INTO login_attempts VALUES("38","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 12:37:22","0","2026-02-12 14:06:47");
INSERT INTO login_attempts VALUES("39","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 12:51:47","0","2026-02-12 14:07:12");
INSERT INTO login_attempts VALUES("40","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 12:52:12","0","2026-02-12 14:08:00");
INSERT INTO login_attempts VALUES("41","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 12:53:00","0","2026-02-12 14:24:21");
INSERT INTO login_attempts VALUES("42","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 13:09:21","0","2026-02-12 14:25:03");
INSERT INTO login_attempts VALUES("43","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 13:10:03","0","2026-02-12 14:25:54");
INSERT INTO login_attempts VALUES("44","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:10:54","0","2026-02-12 14:26:12");
INSERT INTO login_attempts VALUES("45","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:11:12","0","2026-02-12 14:26:20");
INSERT INTO login_attempts VALUES("46","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:11:20","0","2026-02-12 14:26:30");
INSERT INTO login_attempts VALUES("47","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:11:30","0","2026-02-12 14:26:50");
INSERT INTO login_attempts VALUES("48","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:11:50","0","2026-02-12 14:26:55");
INSERT INTO login_attempts VALUES("49","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:11:55","0","2026-02-12 14:33:58");
INSERT INTO login_attempts VALUES("50","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 13:18:58","0","2026-02-12 14:34:54");
INSERT INTO login_attempts VALUES("51","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:19:54","0","2026-02-12 14:34:56");
INSERT INTO login_attempts VALUES("52","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:19:56","0","2026-02-12 14:42:54");
INSERT INTO login_attempts VALUES("53","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 13:27:54","0","2026-02-12 14:54:14");
INSERT INTO login_attempts VALUES("54","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 13:39:14","0","2026-02-12 14:55:21");
INSERT INTO login_attempts VALUES("55","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:40:21","0","2026-02-12 14:55:22");
INSERT INTO login_attempts VALUES("56","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:40:22","0","2026-02-12 14:58:32");
INSERT INTO login_attempts VALUES("57","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 13:43:32","0","2026-02-12 15:02:29");
INSERT INTO login_attempts VALUES("58","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:47:29","0","2026-02-12 15:03:38");
INSERT INTO login_attempts VALUES("59","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 13:48:38","0","2026-02-12 15:06:57");
INSERT INTO login_attempts VALUES("60","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 13:51:57","0","2026-02-12 15:11:08");
INSERT INTO login_attempts VALUES("61","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 13:56:08","0","2026-02-12 15:23:51");
INSERT INTO login_attempts VALUES("62","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 14:08:51","0","2026-02-12 15:24:13");
INSERT INTO login_attempts VALUES("63","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 14:09:13","0","2026-02-12 15:30:24");
INSERT INTO login_attempts VALUES("64","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 14:15:24","0","2026-02-12 15:30:50");
INSERT INTO login_attempts VALUES("65","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 14:15:50","0","2026-02-12 15:41:41");
INSERT INTO login_attempts VALUES("66","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 14:26:41","0","2026-02-12 15:41:59");
INSERT INTO login_attempts VALUES("67","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 14:26:59","0","2026-02-12 15:42:15");
INSERT INTO login_attempts VALUES("68","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 14:27:15","0","2026-02-12 15:42:25");
INSERT INTO login_attempts VALUES("69","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 14:27:25","0","2026-02-12 15:42:57");
INSERT INTO login_attempts VALUES("70","erwinmahendra02","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 14:27:57","0","2026-02-12 15:43:15");
INSERT INTO login_attempts VALUES("71","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 14:28:15","0","2026-02-12 15:43:19");
INSERT INTO login_attempts VALUES("72","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 14:28:19","0","2026-02-12 15:43:26");
INSERT INTO login_attempts VALUES("73","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 14:28:26","0","2026-02-12 15:43:28");
INSERT INTO login_attempts VALUES("74","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 14:28:28","0","2026-02-12 15:43:42");
INSERT INTO login_attempts VALUES("75","pahriyadi","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 14:28:42","0","2026-02-12 16:18:57");
INSERT INTO login_attempts VALUES("76","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 15:03:57","0",NULL);
INSERT INTO login_attempts VALUES("77","Fira Aulia","125.166.127.29","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 15:39:47","1",NULL);
INSERT INTO login_attempts VALUES("78","pahriyadi","125.166.126.78","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 22:18:27","1",NULL);
INSERT INTO login_attempts VALUES("79","pahriyadi","125.166.126.78","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-12 23:30:13","1",NULL);
INSERT INTO login_attempts VALUES("80","pahryadi","125.166.126.78","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 23:56:19","0",NULL);
INSERT INTO login_attempts VALUES("81","pahriyadi","125.166.126.78","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 23:56:35","1",NULL);
INSERT INTO login_attempts VALUES("82","pahriyadi","125.166.126.78","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-12 23:59:12","1",NULL);
INSERT INTO login_attempts VALUES("83","pahriyadi","125.166.126.78","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-02-13 00:26:07","1",NULL);
INSERT INTO login_attempts VALUES("84","pahriyadi","125.166.126.230","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-13 07:43:07","1",NULL);
INSERT INTO login_attempts VALUES("85","pahriyadi","125.166.126.230","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-13 07:43:34","1",NULL);
INSERT INTO login_attempts VALUES("86","Fira Aulia","125.166.126.230","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-13 07:58:38","1",NULL);
INSERT INTO login_attempts VALUES("87","Fira Aulia","36.84.71.115","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-13 09:32:04","1",NULL);
INSERT INTO login_attempts VALUES("88","pahriyadi","36.84.71.115","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-13 09:37:19","1",NULL);
INSERT INTO login_attempts VALUES("89","pahriyadi","36.84.71.115","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-13 12:00:40","1",NULL);
INSERT INTO login_attempts VALUES("90","Fira Aulia","36.84.71.115","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-13 12:04:31","1",NULL);
INSERT INTO login_attempts VALUES("91","Erwin Mahendra","36.84.71.115","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-13 12:17:41","1",NULL);
INSERT INTO login_attempts VALUES("92","Supriyadi","36.84.71.115","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-13 12:17:52","1",NULL);
INSERT INTO login_attempts VALUES("93","Supriyadi","36.84.71.115","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-13 12:25:34","1",NULL);
INSERT INTO login_attempts VALUES("94","pahriyadi","36.84.71.115","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-13 14:50:00","1",NULL);
INSERT INTO login_attempts VALUES("95","pahriyadi","140.213.126.254","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-02-13 17:12:49","1",NULL);
INSERT INTO login_attempts VALUES("96","Pahriyadi","125.166.126.78","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36","2026-02-13 23:29:15","1",NULL);
INSERT INTO login_attempts VALUES("97","Fira Aulia","202.65.234.227","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-14 12:01:29","1",NULL);
INSERT INTO login_attempts VALUES("98","pahriyadi","125.166.125.39","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-02-14 12:34:09","1",NULL);
INSERT INTO login_attempts VALUES("99","Fira Aulia","36.84.67.142","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-15 06:09:40","1",NULL);
INSERT INTO login_attempts VALUES("100","Erwin Mahendra","36.84.48.62","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36","2026-02-16 04:36:36","0",NULL);
INSERT INTO login_attempts VALUES("101","Erwin Mahendra","36.84.48.62","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36","2026-02-16 04:38:29","1",NULL);
INSERT INTO login_attempts VALUES("102","pahriyadi","125.166.124.137","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-16 13:20:55","1",NULL);
INSERT INTO login_attempts VALUES("103","Fira Aulia","125.166.127.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-18 07:24:32","1",NULL);
INSERT INTO login_attempts VALUES("104","Erwin Mahendra","125.166.127.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-18 07:34:00","1",NULL);
INSERT INTO login_attempts VALUES("105","pahriyadi","125.166.127.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-18 08:11:56","1",NULL);
INSERT INTO login_attempts VALUES("106","pahriyadi","125.166.124.110","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-18 11:17:14","1",NULL);
INSERT INTO login_attempts VALUES("107","pahriyadi","125.166.124.227","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0","2026-02-18 21:56:07","1",NULL);
INSERT INTO login_attempts VALUES("108","Pahriyadi","125.166.124.227","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36","2026-02-19 00:46:00","1",NULL);
INSERT INTO login_attempts VALUES("109","Fira Aulia","2001:448a:5142:6297:112b:b9c2:4301:f2f9","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-19 07:55:53","1",NULL);
INSERT INTO login_attempts VALUES("110","Fira Aulia","2001:448a:5142:8181:255f:5917:31e2:371f","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-19 09:19:09","1",NULL);
INSERT INTO login_attempts VALUES("111","pahriyadi","2001:448a:5142:8181:5503:c468:cb3c:2fa","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-19 09:46:29","1",NULL);
INSERT INTO login_attempts VALUES("112","Fira Aulia","2001:448a:5142:8181:9809:a6cf:5ae9:83a4","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-19 11:11:41","1",NULL);
INSERT INTO login_attempts VALUES("113","Fira Aulia","2001:448a:5142:8181:9809:a6cf:5ae9:83a4","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36","2026-02-19 13:29:49","1",NULL);
INSERT INTO login_attempts VALUES("114","Fira Aulia","2001:448a:5142:8181:9809:a6cf:5ae9:83a4","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-20 08:13:45","1",NULL);
INSERT INTO login_attempts VALUES("115","pahriyadi","125.166.125.99","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-20 09:39:24","1",NULL);
INSERT INTO login_attempts VALUES("116","Fira Aulia","125.166.125.99","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-23 07:38:18","1",NULL);
INSERT INTO login_attempts VALUES("117","pahriyadi","125.166.125.99","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-23 10:24:39","1",NULL);
INSERT INTO login_attempts VALUES("118","Fira Aulia","125.166.125.99","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-23 11:14:54","1",NULL);
INSERT INTO login_attempts VALUES("119","pahriyadi","125.166.125.99","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-23 12:02:33","1",NULL);
INSERT INTO login_attempts VALUES("120","pahriyadi","2001:448a:5142:410f:b41d:a334:cbfd:823","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-02-23 16:09:45","1",NULL);
INSERT INTO login_attempts VALUES("121","pahriyadi","2001:448a:5142:410f:99d4:7699:315d:ccff","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-23 23:42:54","1",NULL);
INSERT INTO login_attempts VALUES("122","Pahriyadi","2001:448a:5142:3316:66af:e541:8c8e:6638","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36","2026-02-24 00:49:04","1",NULL);
INSERT INTO login_attempts VALUES("123","Fira Aulia","125.166.125.99","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-24 07:48:02","1",NULL);
INSERT INTO login_attempts VALUES("124","pahriyadi","125.166.125.99","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-24 08:07:34","1",NULL);
INSERT INTO login_attempts VALUES("125","pahriyadi","2001:448a:5142:5942:d899:ec4b:3874:2888","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-24 13:30:34","1",NULL);
INSERT INTO login_attempts VALUES("126","Pahriyadi","2001:448a:5142:3316:5a95:70fb:b6e8:ee6a","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36","2026-02-24 23:15:25","1",NULL);
INSERT INTO login_attempts VALUES("127","Erwin Mahendra","2001:448a:5142:1175:a558:7bf7:afb4:46a7","Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-25 08:40:17","1",NULL);
INSERT INTO login_attempts VALUES("128","Fira Aulia","2001:448a:5142:1175:ede6:1c33:cea0:dccf","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-25 09:19:40","1",NULL);
INSERT INTO login_attempts VALUES("129","pahriyadi","2001:448a:5142:1175:1c1a:6638:b5e9:196a","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-25 11:08:22","1",NULL);
INSERT INTO login_attempts VALUES("130","pahriyadi","2001:448a:5142:1cb4:dca:5323:4bc2:ad39","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-26 07:31:51","1",NULL);
INSERT INTO login_attempts VALUES("131","Erwin Mahendra","2001:448a:5142:1cb4:c460:c09a:d99f:6365","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36","2026-02-26 07:49:53","1",NULL);
INSERT INTO login_attempts VALUES("132","Fira Aulia","2001:448a:5142:1cb4:480:d428:549c:639f","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-26 07:59:52","1",NULL);
INSERT INTO login_attempts VALUES("133","pahriyadi","2001:448a:5142:8661:3c23:3be7:3739:a1e0","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-26 08:17:57","1",NULL);
INSERT INTO login_attempts VALUES("134","Fira Aulia","2001:448a:5142:88c2:f9ec:7259:cfb8:a04e","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-26 10:23:09","1",NULL);
INSERT INTO login_attempts VALUES("135","pahriyadi","2001:448a:5142:9159:810:9c59:6dcd:ab11","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-26 10:52:07","1",NULL);
INSERT INTO login_attempts VALUES("136","pahriyadi","2001:448a:5142:9159:810:9c59:6dcd:ab11","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-26 10:54:31","1",NULL);
INSERT INTO login_attempts VALUES("137","Erwin Mahendra","2001:448a:5142:9159:8de2:1cc:e7f4:afb3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36","2026-02-26 10:57:04","1",NULL);
INSERT INTO login_attempts VALUES("138","pahriyadi","125.166.124.248","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-26 12:23:20","1",NULL);
INSERT INTO login_attempts VALUES("139","pahriyadi","2001:448a:5142:43f8:707e:6f7b:3d61:d1bb","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-02-27 04:46:59","1",NULL);
INSERT INTO login_attempts VALUES("140","Erwin Mahendra","2001:448a:5142:9a52:90b5:aa2d:df3a:d063","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36","2026-02-27 09:02:42","1",NULL);
INSERT INTO login_attempts VALUES("141","Fira Aulia","2001:448a:5142:9a52:3120:df5e:5985:179e","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-27 09:13:50","1",NULL);
INSERT INTO login_attempts VALUES("142","Piyaaaaa","2001:448a:5142:2632:302c:d70c:3444:e1af","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-27 10:46:57","0",NULL);
INSERT INTO login_attempts VALUES("143","Fira Aulia","2001:448a:5142:2632:302c:d70c:3444:e1af","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-02-27 10:47:02","1",NULL);
INSERT INTO login_attempts VALUES("144","Pahriyadi","2001:448a:5142:2632:c3e2:6f31:3dcc:8146","Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36","2026-02-27 12:14:16","1",NULL);
INSERT INTO login_attempts VALUES("145","Pahriyadi","2001:448a:5142:222c:e4b9:ebca:66b3:18e9","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36","2026-03-01 23:35:01","1",NULL);
INSERT INTO login_attempts VALUES("146","Fira Aulia","2001:448a:5142:92cf:4d72:9e20:a83c:99d4","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-02 07:33:04","1",NULL);
INSERT INTO login_attempts VALUES("147","pahriyadi","125.166.127.23","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-03-02 08:03:01","1",NULL);
INSERT INTO login_attempts VALUES("148","pahriyadi","2001:448a:5142:95d6:69f2:d1c3:75f4:1f1a","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-02 12:15:49","1",NULL);
INSERT INTO login_attempts VALUES("149","pahriyadi","2001:448a:5142:95d6:69f2:d1c3:75f4:1f1a","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-02 13:05:50","1",NULL);
INSERT INTO login_attempts VALUES("150","Pahriyadi","2001:448a:5142:222c:5d95:9e06:1a61:3feb","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36","2026-03-02 23:22:38","1",NULL);
INSERT INTO login_attempts VALUES("151","Fira Aulia","2001:448a:5142:95d6:9d09:a4ae:6a10:5423","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-03 07:59:56","1",NULL);
INSERT INTO login_attempts VALUES("152","admin","2001:448a:5142:95d6:39d7:3718:f6eb:ddd8","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-03 08:10:20","0",NULL);
INSERT INTO login_attempts VALUES("153","pahriyadi","112.215.219.97","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-03-03 10:14:08","1",NULL);
INSERT INTO login_attempts VALUES("154","Erwin Mahendra","2001:448a:5142:95d6:39d7:3718:f6eb:ddd8","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-03 12:09:44","1",NULL);
INSERT INTO login_attempts VALUES("155","pahriyadi","2400:9800:866:7363:4974:7864:dd70:4949","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-03-03 17:30:31","1",NULL);
INSERT INTO login_attempts VALUES("156","pahriyadi","203.78.114.9","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-03-03 19:31:33","1",NULL);
INSERT INTO login_attempts VALUES("157","Erwin Mahendra","2001:448a:5142:95d6:39d7:3718:f6eb:ddd8","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-04 07:07:47","0",NULL);
INSERT INTO login_attempts VALUES("158","Erwin Mahendra","2001:448a:5142:95d6:39d7:3718:f6eb:ddd8","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-04 07:08:00","0",NULL);
INSERT INTO login_attempts VALUES("159","Erwin Mahendra","2001:448a:5142:95d6:39d7:3718:f6eb:ddd8","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-04 07:08:51","0",NULL);
INSERT INTO login_attempts VALUES("160","Piyaaaaa","2001:448a:5142:95d6:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-04 07:12:44","0",NULL);
INSERT INTO login_attempts VALUES("161","Fira Aulia","2001:448a:5142:95d6:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-04 07:12:54","1",NULL);
INSERT INTO login_attempts VALUES("162","Erwin Mahendra","2001:448a:5142:95d6:39d7:3718:f6eb:ddd8","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-04 07:16:33","1",NULL);
INSERT INTO login_attempts VALUES("163","Fira Aulia","2001:448a:5142:95d6:d9ea:e0bb:97a8:8ae2","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-04 08:03:17","1",NULL);
INSERT INTO login_attempts VALUES("164","pahriyadi","2001:448a:5142:95d6:8d19:9f4d:2d29:674b","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-04 08:06:47","1",NULL);
INSERT INTO login_attempts VALUES("165","pahriyadi","2001:448a:5142:16cc:bd4a:a14b:4eaa:14ca","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-04 09:09:39","1",NULL);
INSERT INTO login_attempts VALUES("166","Fira Aulia","2001:448a:5142:16cc:8c31:a32:57b2:4120","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-04 09:15:33","1",NULL);
INSERT INTO login_attempts VALUES("167","Fira Aulia","2001:448a:5142:2a19:34b8:9d46:b44f:4c70","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-04 12:47:19","1",NULL);
INSERT INTO login_attempts VALUES("168","pahriyadi","2001:448a:5142:2a19:6594:a908:8a81:8489","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-04 12:51:26","1",NULL);
INSERT INTO login_attempts VALUES("169","Fira Aulia","36.84.224.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-05 07:51:40","1",NULL);
INSERT INTO login_attempts VALUES("170","Erwin Mahendra","36.84.224.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-05 09:22:15","1",NULL);
INSERT INTO login_attempts VALUES("171","pahriyadi","36.84.224.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-05 09:32:04","1",NULL);
INSERT INTO login_attempts VALUES("172","pahriyadi","36.84.224.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-05 10:34:01","1",NULL);
INSERT INTO login_attempts VALUES("173","Erwin Mahendra","36.84.224.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-05 13:45:27","1",NULL);
INSERT INTO login_attempts VALUES("174","Erwin Mahendra","36.84.224.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-06 08:21:25","1",NULL);
INSERT INTO login_attempts VALUES("175","Fira Aulia","36.84.224.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-06 10:12:52","1",NULL);
INSERT INTO login_attempts VALUES("176","pahriyadi","36.84.224.169","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-06 11:36:21","1",NULL);
INSERT INTO login_attempts VALUES("177","pahriyadi","2001:448a:5142:e45:d878:ba82:2421:6533","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-07 08:48:25","1",NULL);
INSERT INTO login_attempts VALUES("178","supriyadi","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-08 13:25:35","0",NULL);
INSERT INTO login_attempts VALUES("179","supriyadi","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-08 13:25:53","0",NULL);
INSERT INTO login_attempts VALUES("180","supriyadi","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-08 13:25:55","0",NULL);
INSERT INTO login_attempts VALUES("181","pahriyadi","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-08 14:26:28","1",NULL);
INSERT INTO login_attempts VALUES("182","Pahriyadi","2001:448a:5142:3ec9:1935:b364:b050:fcba","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36","2026-03-09 00:23:01","1",NULL);
INSERT INTO login_attempts VALUES("183","Fira Aulia","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-09 07:17:53","1",NULL);
INSERT INTO login_attempts VALUES("184","pahriyadi","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-09 08:12:25","1",NULL);
INSERT INTO login_attempts VALUES("185","pahriyadi","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-09 08:33:42","1",NULL);
INSERT INTO login_attempts VALUES("186","Fira Aulia","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-09 10:21:25","1",NULL);
INSERT INTO login_attempts VALUES("187","pahriyadi","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-09 11:23:47","1",NULL);
INSERT INTO login_attempts VALUES("188","Fira Aulia","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-09 13:22:29","1",NULL);
INSERT INTO login_attempts VALUES("189","pahriyadi","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-09 14:41:59","1",NULL);
INSERT INTO login_attempts VALUES("190","pahriyadi","125.166.126.65","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-03-09 16:21:18","1",NULL);
INSERT INTO login_attempts VALUES("191","Pahriyadi","2001:448a:5142:3ec9:314e:36b1:6b10:3ab4","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36","2026-03-10 03:33:39","1",NULL);
INSERT INTO login_attempts VALUES("192","Fira Aulia","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-10 08:04:49","1",NULL);
INSERT INTO login_attempts VALUES("193","pahriyadi","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-10 08:41:07","1",NULL);
INSERT INTO login_attempts VALUES("194","pahriyadi","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-10 11:36:34","1",NULL);
INSERT INTO login_attempts VALUES("195","Fira Aulia","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-10 12:12:54","1",NULL);
INSERT INTO login_attempts VALUES("196","pahriyadi","125.166.126.65","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-03-10 23:27:30","1",NULL);
INSERT INTO login_attempts VALUES("197","Fira Aulia","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-11 07:07:26","1",NULL);
INSERT INTO login_attempts VALUES("198","Erwin Mahendra","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36","2026-03-11 08:06:11","0",NULL);
INSERT INTO login_attempts VALUES("199","Erwin Mahendra","36.84.34.251","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36","2026-03-11 08:06:24","1",NULL);
INSERT INTO login_attempts VALUES("200","Fira Aulia","2001:448a:c140:3f5:9112:74f4:d980:6285","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-12 07:43:48","1",NULL);
INSERT INTO login_attempts VALUES("201","pahriyadi","2001:448a:c140:3f5:d00d:c06b:25be:36bd","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-12 13:02:04","1",NULL);
INSERT INTO login_attempts VALUES("202","Fira Aulia","2001:448a:c140:3f5:9112:74f4:d980:6285","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-12 14:03:38","1",NULL);
INSERT INTO login_attempts VALUES("203","admin","2001:448a:c140:3f5:15c1:d330:bf33:bc34","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-12 14:56:39","0",NULL);
INSERT INTO login_attempts VALUES("204","Erwin Mahendra","2001:448a:c140:3f5:15c1:d330:bf33:bc34","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-12 14:56:57","0",NULL);
INSERT INTO login_attempts VALUES("205","Erwin Mahendra","2001:448a:c140:3f5:15c1:d330:bf33:bc34","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-12 14:57:11","0",NULL);
INSERT INTO login_attempts VALUES("206","Fira Aulia","2001:448a:c140:3f5:9112:74f4:d980:6285","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-12 15:37:31","1",NULL);
INSERT INTO login_attempts VALUES("207","Fira Aulia","2001:448a:c140:3f5:14fd:a071:3813:4345","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-13 11:40:57","1",NULL);
INSERT INTO login_attempts VALUES("208","Fira Aulia","2001:448a:c140:3f5:14fd:a071:3813:4345","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-13 14:33:15","1",NULL);
INSERT INTO login_attempts VALUES("209","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-14 08:38:49","1",NULL);
INSERT INTO login_attempts VALUES("210","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-16 07:08:54","1",NULL);
INSERT INTO login_attempts VALUES("211","pahriyadi","2001:448a:c140:3f5:44ee:f6be:3554:8903","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-16 08:32:41","1",NULL);
INSERT INTO login_attempts VALUES("212","pahriyadi","2001:448a:c140:3f5:44ee:f6be:3554:8903","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-16 10:13:27","1",NULL);
INSERT INTO login_attempts VALUES("213","pahriyadi","2001:448a:c140:3f5:44ee:f6be:3554:8903","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0","2026-03-16 12:12:27","1",NULL);
INSERT INTO login_attempts VALUES("214","Fira Aulia","2001:448a:c140:3f5:de7:b293:e1f9:c088","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-17 08:04:34","1",NULL);
INSERT INTO login_attempts VALUES("215","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-17 12:57:38","1",NULL);
INSERT INTO login_attempts VALUES("216","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36","2026-03-18 07:12:18","1",NULL);
INSERT INTO login_attempts VALUES("217","upz yayasan mnj","114.10.156.243","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36","2026-03-19 12:38:29","0",NULL);
INSERT INTO login_attempts VALUES("218","Pahriyadi","2001:448a:5142:3e6b:ffcc:3ef7:2575:b1d3","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36","2026-03-20 03:39:55","1",NULL);
INSERT INTO login_attempts VALUES("219","Supriyadi","114.122.150.197","Mozilla/5.0 (Linux; U; Android 12; in-id; CPH2325 Build/SP1A.210812.016) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.72 Mobile Safari/537.36 HeyTapBrowser/45.13.8.1","2026-03-22 18:29:42","0",NULL);
INSERT INTO login_attempts VALUES("220","supriyadi","114.122.150.197","Mozilla/5.0 (Linux; U; Android 12; in-id; CPH2325 Build/SP1A.210812.016) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.72 Mobile Safari/537.36 HeyTapBrowser/45.13.8.1","2026-03-22 18:30:00","0",NULL);
INSERT INTO login_attempts VALUES("221","pahriyadi","125.166.126.177","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-03-23 16:30:49","1",NULL);
INSERT INTO login_attempts VALUES("222","Fira Aulia","2001:448a:50d0:41f4:a4cf:73bd:9152:6177","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-23 16:51:29","1",NULL);
INSERT INTO login_attempts VALUES("223","Erwin Mahendra","202.65.234.205","Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-23 16:54:24","0",NULL);
INSERT INTO login_attempts VALUES("224","Erwin Mahendra","202.65.234.205","Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-23 16:54:26","0",NULL);
INSERT INTO login_attempts VALUES("225","Erwin Mahendra","202.65.234.205","Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-23 16:54:26","0",NULL);
INSERT INTO login_attempts VALUES("226","Erwin Mahendra","202.65.234.205","Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-23 16:54:43","0",NULL);
INSERT INTO login_attempts VALUES("227","pahriyadi","112.215.220.112","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1","2026-03-23 17:29:48","1",NULL);
INSERT INTO login_attempts VALUES("228","Fira Aulia","202.65.234.205","Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-23 18:10:26","1",NULL);
INSERT INTO login_attempts VALUES("229","Fira Aulia","202.65.233.53","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36","2026-03-23 18:32:48","1",NULL);
INSERT INTO login_attempts VALUES("230","Pahriyadi","2001:448a:5142:82e:e235:2144:f47f:cf07","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36","2026-03-23 19:00:53","1",NULL);
INSERT INTO login_attempts VALUES("231","Pahriyadi","2001:448a:5142:82e:e235:2144:f47f:cf07","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36","2026-03-23 19:14:53","1",NULL);
INSERT INTO login_attempts VALUES("232","pahriyadi","2001:448a:5142:82e:491b:af5c:3ca8:e97b","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.14 Safari/605.1.15","2026-03-24 01:38:09","1",NULL);
INSERT INTO login_attempts VALUES("233","Fira Aulia","112.215.220.98","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.151 Mobile/15E148 Safari/604.1","2026-03-24 07:35:23","1",NULL);
INSERT INTO login_attempts VALUES("234","pahriyadi","2001:448a:5142:82e:30ff:3e80:e4a3:71cc","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-03-25 01:58:36","1",NULL);
INSERT INTO login_attempts VALUES("235","pahriyadi","2001:448a:5142:1425:d15b:3855:853c:1d7d","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-03-27 07:17:21","1",NULL);
INSERT INTO login_attempts VALUES("236","Fira Aulia","2001:448a:c140:3f5:152e:54b8:b346:2263","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-30 07:14:28","1",NULL);
INSERT INTO login_attempts VALUES("237","Fira Aulia","2001:448a:c140:3f5:152e:54b8:b346:2263","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-30 10:16:34","1",NULL);
INSERT INTO login_attempts VALUES("238","pahriyadi","2001:448a:c140:3f5:40eb:26b1:c326:6dcf","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-03-30 12:26:47","1",NULL);
INSERT INTO login_attempts VALUES("239","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-31 07:19:10","1",NULL);
INSERT INTO login_attempts VALUES("240","Erwin Mahendra","2001:448a:c140:3f5:658c:8b3b:1669:203b","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36","2026-03-31 07:24:36","1",NULL);
INSERT INTO login_attempts VALUES("241","pahriyadi","2001:448a:c140:3f5:3952:a60e:4bb7:751c","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-03-31 08:53:17","1",NULL);
INSERT INTO login_attempts VALUES("242","admin","2001:448a:c140:3f5:bc7b:cef:5639:94f8","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-31 08:54:01","0",NULL);
INSERT INTO login_attempts VALUES("243","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-31 09:00:20","1",NULL);
INSERT INTO login_attempts VALUES("244","pahriyadi","2001:448a:c140:3f5:3952:a60e:4bb7:751c","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-03-31 10:48:46","1",NULL);
INSERT INTO login_attempts VALUES("245","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-31 13:33:40","1",NULL);
INSERT INTO login_attempts VALUES("246","pahriyadi","2001:448a:c140:3f5:3952:a60e:4bb7:751c","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-03-31 14:46:24","1",NULL);
INSERT INTO login_attempts VALUES("247","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-03-31 14:46:44","1",NULL);
INSERT INTO login_attempts VALUES("248","Pahriyadi","2001:448a:5142:1425:1e42:50df:6a71:7e22","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36","2026-03-31 15:38:17","1",NULL);
INSERT INTO login_attempts VALUES("249","erwinmahendra","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 07:25:38","1",NULL);
INSERT INTO login_attempts VALUES("250","pahriyadi","2001:448a:c140:3f5:5139:fb84:4649:fb6c","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-04-01 07:25:58","1",NULL);
INSERT INTO login_attempts VALUES("251","Erwin Mahendra","36.84.34.227","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 07:30:34","0",NULL);
INSERT INTO login_attempts VALUES("252","Fira Aulia","36.84.34.227","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 07:30:49","1",NULL);
INSERT INTO login_attempts VALUES("253","erwinmahendra","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 07:37:10","1",NULL);
INSERT INTO login_attempts VALUES("254","Fira Aulia","36.84.34.227","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 08:04:40","1",NULL);
INSERT INTO login_attempts VALUES("255","erwinmahendra","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 08:07:21","1",NULL);
INSERT INTO login_attempts VALUES("256","erwinmahendra","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 08:07:36","0",NULL);
INSERT INTO login_attempts VALUES("257","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 08:07:55","1",NULL);
INSERT INTO login_attempts VALUES("258","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 08:08:06","0",NULL);
INSERT INTO login_attempts VALUES("259","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 08:08:14","1",NULL);
INSERT INTO login_attempts VALUES("260","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 08:09:14","1",NULL);
INSERT INTO login_attempts VALUES("261","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 08:09:48","0",NULL);
INSERT INTO login_attempts VALUES("262","Fira Aulia","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 08:09:56","1",NULL);
INSERT INTO login_attempts VALUES("263","erwinmahendra","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 09:56:15","1",NULL);
INSERT INTO login_attempts VALUES("264","pahriyadi","2001:448a:c140:3f5:a1b2:8e85:4d4c:90eb","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-04-01 10:03:14","1",NULL);
INSERT INTO login_attempts VALUES("265","Fira Aulia","36.84.34.227","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 10:19:26","1",NULL);
INSERT INTO login_attempts VALUES("266","pahriyadi","2001:448a:c140:3f5:a1b2:8e85:4d4c:90eb","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-04-01 12:23:38","1",NULL);
INSERT INTO login_attempts VALUES("267","erwinmahendra","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-01 14:19:46","1",NULL);
INSERT INTO login_attempts VALUES("268","Fira Aulia","36.84.34.227","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-02 08:32:02","1",NULL);
INSERT INTO login_attempts VALUES("269","erwinmahendra","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-02 10:22:23","1",NULL);
INSERT INTO login_attempts VALUES("270","pahriyadi","2001:448a:c140:3f5:9095:5eb:fa3a:12a7","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-04-02 10:26:21","1",NULL);
INSERT INTO login_attempts VALUES("271","pahriyadi","2001:448a:c140:3f5:a19c:c287:eeda:d20","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-04-02 11:16:01","1",NULL);
INSERT INTO login_attempts VALUES("272","erwinmahendra","2001:448a:c140:3f5:6c2a:ce6b:ccf4:92d3","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-02 13:35:04","1",NULL);
INSERT INTO login_attempts VALUES("273","Fira Aulia","36.84.34.227","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36","2026-04-02 14:52:41","1",NULL);
INSERT INTO login_attempts VALUES("274","Pahriyadi","2001:448a:5142:7175:7201:fb72:d3b8:da3f","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36","2026-04-02 22:50:46","1",NULL);
INSERT INTO login_attempts VALUES("275","pahriyadi","125.166.125.232","Mozilla/5.0 (iPad; CPU OS 17_7_10 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.151 Mobile/15E148 Safari/604.1","2026-04-02 23:17:00","1",NULL);
INSERT INTO login_attempts VALUES("276","Pahriyadi","2001:448a:5142:7175:7201:fb72:d3b8:da3f","Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36","2026-04-03 00:23:46","1",NULL);
INSERT INTO login_attempts VALUES("277","Pahriyadi","125.166.125.232","Mozilla/5.0 (iPad; CPU OS 17_7_10 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/146.0.7680.151 Mobile/15E148 Safari/604.1","2026-04-03 05:30:46","1",NULL);
INSERT INTO login_attempts VALUES("278","pahriyadi","112.215.220.161","Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Mobile/15E148 Safari/604.1","2026-04-04 19:14:00","1",NULL);
INSERT INTO login_attempts VALUES("279","pahriyadi","2001:448a:5142:7175:2d2f:6dcb:c904:ed20","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-04-05 00:30:30","1",NULL);
INSERT INTO login_attempts VALUES("280","pahriyadi","2001:448a:5142:7175:2d2f:6dcb:c904:ed20","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0","2026-04-05 02:15:49","1",NULL);


CREATE TABLE `metode_pembayaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_metode` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_metode` (`nama_metode`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO metode_pembayaran VALUES("4","Transfer Bank","2026-01-12 14:08:23","2026-01-12 14:08:23");
INSERT INTO metode_pembayaran VALUES("5","Tunai","2026-01-12 14:08:31","2026-01-12 14:08:31");


CREATE TABLE `muzaki` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_muzaki` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `no_ktp` varchar(20) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `id_status_muzaki` int(11) DEFAULT NULL,
  `id_upz_sekarang` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_ktp` (`no_ktp`),
  KEY `id_status_muzaki` (`id_status_muzaki`),
  KEY `id_upz_sekarang` (`id_upz_sekarang`),
  CONSTRAINT `muzaki_ibfk_1` FOREIGN KEY (`id_status_muzaki`) REFERENCES `status_muzaki` (`id`),
  CONSTRAINT `muzaki_ibfk_2` FOREIGN KEY (`id_upz_sekarang`) REFERENCES `upz` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO muzaki VALUES("6","MUZAKI TRANSFER","SUMBAWA","0","0","10",NULL,"2026-01-13 13:28:06","2026-01-13 13:28:06");
INSERT INTO muzaki VALUES("9","DEDY HERIWIBOWO","SUMBAWA","00","08","6","28","2026-01-13 13:43:12","2026-01-15 13:59:49");
INSERT INTO muzaki VALUES("10","ARIE BUDIMAN","SUMBAWA","000","000","10",NULL,"2026-01-13 15:14:30","2026-01-13 15:14:30");
INSERT INTO muzaki VALUES("11","MUTASIN ANWAR","SUMBAWA","0000","0000","6","60","2026-01-13 15:18:39","2026-01-23 07:34:56");
INSERT INTO muzaki VALUES("14","KHAIRUDDIN","SUMBAWA","1","1","10",NULL,"2026-01-20 12:59:08","2026-01-20 12:59:08");
INSERT INTO muzaki VALUES("17","SRI IRIANTI","SUMBAWA","08","08","10",NULL,"2026-01-29 10:44:23","2026-01-29 10:44:23");
INSERT INTO muzaki VALUES("18","DARMAWAN","SUMBAWA","111","111","10",NULL,"2026-02-02 13:49:25","2026-02-02 13:49:25");
INSERT INTO muzaki VALUES("19","ARMAWANSYAH","SUMBAWA","01","01","10",NULL,"2026-02-02 13:51:16","2026-02-02 13:51:16");
INSERT INTO muzaki VALUES("23","MOHAMMAD HUSNUL ALWAN","SUMBAWA","212","212","10",NULL,"2026-02-05 07:39:49","2026-02-05 07:39:49");
INSERT INTO muzaki VALUES("24","HASIRUDDIN","SUMBAWA","121","121","10",NULL,"2026-02-05 07:42:03","2026-02-05 07:42:03");
INSERT INTO muzaki VALUES("25","BAZRI","SUMBAWA","131","131","10",NULL,"2026-02-05 07:44:19","2026-02-05 07:44:19");
INSERT INTO muzaki VALUES("26","ROHANA","SUMBAWA","141","141","10",NULL,"2026-02-05 07:45:37","2026-02-05 07:45:37");
INSERT INTO muzaki VALUES("27","IDA NURSANTI","SUMBAWA","151","151","10",NULL,"2026-02-05 07:47:39","2026-02-05 07:47:39");
INSERT INTO muzaki VALUES("28","MAZNAH","SUMBAWA","161","161","10",NULL,"2026-02-05 07:51:39","2026-02-05 07:51:39");
INSERT INTO muzaki VALUES("29","ABDUL GAFUR","SUMBAWA","171","171","10",NULL,"2026-02-05 07:53:04","2026-02-05 07:53:04");
INSERT INTO muzaki VALUES("30","PITRI RATMI YULIANTI","SUMBAWA","181","181","10",NULL,"2026-02-05 07:54:57","2026-02-05 07:54:57");
INSERT INTO muzaki VALUES("31","SAFARUDDIN AR","SUMBAWA","191","191","10",NULL,"2026-02-05 07:56:50","2026-02-05 07:56:50");
INSERT INTO muzaki VALUES("33","MURSYID","SUMBAWA","242","242","10",NULL,"2026-02-05 07:58:40","2026-02-05 07:58:40");
INSERT INTO muzaki VALUES("34","NIRLA KOMALA","SUMBAWA","252","252","10",NULL,"2026-02-05 08:00:29","2026-02-05 08:00:29");
INSERT INTO muzaki VALUES("35","INDRA LESMANA","SUMBAWA","262","262","10",NULL,"2026-02-05 08:03:24","2026-02-05 08:03:24");
INSERT INTO muzaki VALUES("36","KARTINI","SUMBAWA","282","282","10",NULL,"2026-02-05 08:06:34","2026-02-05 08:06:34");
INSERT INTO muzaki VALUES("37","MUSTAKIM","SUMBAWA","258","258","10",NULL,"2026-02-05 09:39:40","2026-02-05 09:39:40");
INSERT INTO muzaki VALUES("38","NUR AINI FURQAN","SUMBAWA","456","456","10",NULL,"2026-02-05 09:42:26","2026-02-05 09:42:26");
INSERT INTO muzaki VALUES("40","MAUDARA","SUMBAWA","369","369","10",NULL,"2026-02-05 09:46:50","2026-02-05 09:46:50");
INSERT INTO muzaki VALUES("41","JOHAN ROSIHAN","SUMBAWA","147","147","10",NULL,"2026-02-05 09:54:42","2026-02-05 09:54:42");
INSERT INTO muzaki VALUES("42","HJ. SUDARTI MOHAMMAD ANSORI","SUMBAWA","2123","2123","10",NULL,"2026-02-05 13:16:20","2026-02-05 13:16:20");
INSERT INTO muzaki VALUES("46","NANI ARIFAH","SUMBAWA","9874","9874","10",NULL,"2026-02-06 14:25:14","2026-02-06 14:25:14");
INSERT INTO muzaki VALUES("47","SUDRAJAT MARTADINATA","SUMBAWA","6547","6547","10",NULL,"2026-02-06 14:27:08","2026-02-06 14:27:08");
INSERT INTO muzaki VALUES("48","Ir.H.SYARAFUDDIN JAROT, M.P","SUMBAWA","3214","3214","10",NULL,"2026-02-11 13:24:55","2026-02-11 13:24:55");
INSERT INTO muzaki VALUES("49","LALU SUHARMAJI KERTAWIJAYA","SUMBAWA","6978","6978","10",NULL,"2026-02-11 13:29:43","2026-02-11 13:29:43");
INSERT INTO muzaki VALUES("50","H AHMAD SH","SUMBAWA","2587","2587","10",NULL,"2026-02-11 13:34:34","2026-02-11 13:34:34");
INSERT INTO muzaki VALUES("52","USMA","SUMBAWA","9856","9856","10",NULL,"2026-02-11 13:36:58","2026-02-11 13:36:58");
INSERT INTO muzaki VALUES("55","ERMAYANTI S.Pd","SUMBAWA","99","08","10",NULL,"2026-02-14 12:21:29","2026-02-14 12:21:29");
INSERT INTO muzaki VALUES("56","ABDUL RAHMAN","SUMBAWA","008","081","10",NULL,"2026-02-19 09:13:08","2026-02-19 09:13:08");
INSERT INTO muzaki VALUES("58","AMINAH (KUP MAWAR)","SUMBAWA","0088","081","10",NULL,"2026-02-19 09:23:50","2026-02-19 09:23:50");
INSERT INTO muzaki VALUES("59","RITA LISNAWATI","SUMBAWA","65489","6548","10",NULL,"2026-02-19 11:23:02","2026-02-19 11:23:02");
INSERT INTO muzaki VALUES("63","KAMARIAH","SUMBAWA","052","0812","10",NULL,"2026-03-02 07:17:17","2026-03-02 07:17:17");
INSERT INTO muzaki VALUES("66","BHASIL BERSIH TAB - 0042221520018","SUMBAWA","0524","08","10",NULL,"2026-03-03 11:14:40","2026-03-03 11:14:40");
INSERT INTO muzaki VALUES("67","YULIANA","SUMBAWA","2546584","2546584","10",NULL,"2026-03-06 08:52:37","2026-03-06 08:52:37");
INSERT INTO muzaki VALUES("68","PUTRI RIZQIYA HIKMATIN","SUMBAWA","0521","085","10",NULL,"2026-03-09 08:52:24","2026-03-09 08:52:24");
INSERT INTO muzaki VALUES("69","DWI MARDIA","SUMBAWA","5456454","456556","10",NULL,"2026-03-09 10:22:03","2026-03-09 10:22:03");
INSERT INTO muzaki VALUES("70","INA RAMADHANI RAHMATIN","SUMBAWA","521","081","10",NULL,"2026-03-09 12:13:24","2026-03-09 12:13:24");
INSERT INTO muzaki VALUES("72","H.FATAHOLLAH BA","SUMBAWA","4449646","9646498","10",NULL,"2026-03-09 12:53:37","2026-03-09 12:53:37");
INSERT INTO muzaki VALUES("73","ASIYAH","SUMBAWA","45644","456467","10",NULL,"2026-03-09 12:54:10","2026-03-09 12:54:10");
INSERT INTO muzaki VALUES("74","M.ISNAINI","SUMBAWA","167497","1549","10",NULL,"2026-03-09 12:55:14","2026-03-09 12:55:14");
INSERT INTO muzaki VALUES("75","APRINDA DWI SAFITRI","SUMBAWA","05154","085","10",NULL,"2026-03-09 13:32:25","2026-03-09 13:32:25");
INSERT INTO muzaki VALUES("76","EFIN JULINDRA","SUMBAWA","05151","085","10",NULL,"2026-03-10 08:40:30","2026-03-10 08:40:30");
INSERT INTO muzaki VALUES("77","H.MANZUR","SUMBAWA","5244","14564979","10",NULL,"2026-03-11 09:56:16","2026-03-11 09:56:16");
INSERT INTO muzaki VALUES("78","NINDIA YULIANA","SUMBAWA","528","085","10",NULL,"2026-03-12 12:37:01","2026-03-12 12:37:01");
INSERT INTO muzaki VALUES("81","ZAINAL ASIKIN","SUMBAWA","085","085","10",NULL,"2026-03-13 12:37:06","2026-03-13 12:37:06");
INSERT INTO muzaki VALUES("84","SARIATI ASSATARI","SUMBAWA","087","0869","10",NULL,"2026-03-13 12:38:55","2026-03-13 12:38:55");
INSERT INTO muzaki VALUES("87","RAMLAH","SUMBAWA","0852","6931","10",NULL,"2026-03-13 15:01:50","2026-03-13 15:01:50");
INSERT INTO muzaki VALUES("89","Drs. H. MOHAMMAD ANSORI","SUMBAWA","154545","0852","10",NULL,"2026-03-13 15:16:21","2026-03-13 15:16:21");
INSERT INTO muzaki VALUES("92","ANIS MERIATI","SUMBAWA","856321","0856","10",NULL,"2026-03-16 07:33:52","2026-03-16 07:33:52");
INSERT INTO muzaki VALUES("93","APRIK LISMUHAR JUAND","SUMBAWA","15451","085","10",NULL,"2026-03-16 07:53:34","2026-03-16 07:53:34");
INSERT INTO muzaki VALUES("94","PAHRIYADI","SUMBAWA","08515","085","10",NULL,"2026-03-16 09:26:39","2026-04-07 21:56:23");
INSERT INTO muzaki VALUES("95","NITA UTAMI","SUMBAWA","464616","45548","10",NULL,"2026-03-16 12:04:05","2026-03-16 12:04:05");
INSERT INTO muzaki VALUES("96","Dr. BUDI PRASETIYO","SUMBAWA","44846","085","6",NULL,"2026-03-17 12:24:35","2026-03-17 12:24:35");
INSERT INTO muzaki VALUES("97","MU\'AINI","SUMBAWA","5632","085","10",NULL,"2026-03-17 12:38:41","2026-03-17 12:38:41");
INSERT INTO muzaki VALUES("98","THARIK AKBAR","SUMBAWA","5326","085","10",NULL,"2026-03-17 12:39:50","2026-03-17 12:39:50");
INSERT INTO muzaki VALUES("99","TENRI","SUMBAWA","8563","085","10",NULL,"2026-03-17 12:42:35","2026-03-17 12:42:35");
INSERT INTO muzaki VALUES("100","M. HUSNI","SUMBAWA","0859","085","10",NULL,"2026-03-17 12:44:01","2026-03-17 12:44:01");
INSERT INTO muzaki VALUES("103","M. SALEH","SUMBAWA","7856","0832","10",NULL,"2026-03-17 12:48:45","2026-03-17 12:48:45");
INSERT INTO muzaki VALUES("104","A. KARIM SALEH","BRANG BARA","7854","086","10",NULL,"2026-03-17 12:52:06","2026-03-17 12:52:06");
INSERT INTO muzaki VALUES("105","RIYANTO","SUMBAWA","41646","1245","10","147","2026-03-18 10:08:41","2026-03-18 10:08:41");
INSERT INTO muzaki VALUES("106","ALVIN HOSTIADI SAPUTRA","SUMBAWA","9762","023","10",NULL,"2026-03-23 17:12:07","2026-03-23 17:12:07");
INSERT INTO muzaki VALUES("107","NURHIDAYAH SARI","SUMBAWA","87654","076","10",NULL,"2026-03-23 17:20:58","2026-03-23 17:20:58");
INSERT INTO muzaki VALUES("109","BEKTI BANGUN NURSANT","SUMBAWA","764","345","10",NULL,"2026-03-23 17:24:50","2026-03-23 17:24:50");
INSERT INTO muzaki VALUES("111","NURLAILAH","SUMBAWA","520","065","10",NULL,"2026-03-23 17:26:38","2026-03-23 17:26:38");
INSERT INTO muzaki VALUES("114","JULMANSYAH","SUMBAWA","1234","764","10",NULL,"2026-03-23 17:47:50","2026-03-23 17:47:50");
INSERT INTO muzaki VALUES("117","ZAKAT FITRAH RAMADHAN","SUMBAWA","5206","081","10",NULL,"2026-03-30 12:31:02","2026-03-30 12:31:02");
INSERT INTO muzaki VALUES("118","KAHARUDIN","SUMBAWA","14646","453534","10",NULL,"2026-04-01 11:48:26","2026-04-01 11:48:26");
INSERT INTO muzaki VALUES("119","DWI MARDHIA","SUMBAWA","5204","0877","10",NULL,"2026-04-02 08:44:37","2026-04-02 08:44:37");
INSERT INTO muzaki VALUES("120","SITI SALEHA","SUMBAWA","52045","0877","10",NULL,"2026-04-02 08:51:00","2026-04-02 08:51:00");
INSERT INTO muzaki VALUES("121","MUHAMMAD KHAERUL AKBAR","SUMBAWA","52048","0877","10",NULL,"2026-04-02 10:50:19","2026-04-02 10:50:19");
INSERT INTO muzaki VALUES("122","ROSITA M YASIN","SUMBAWA","520408","0877","10",NULL,"2026-04-02 10:56:41","2026-04-02 10:56:41");
INSERT INTO muzaki VALUES("123","H.MANZUR","SUMBAWA","5456","46746","10",NULL,"2026-04-02 11:06:44","2026-04-02 11:06:44");
INSERT INTO muzaki VALUES("124","SAPARUDDIN ","SUMBAWA","454686","4567874","10",NULL,"2026-04-02 11:43:21","2026-04-02 11:43:48");
INSERT INTO muzaki VALUES("125","MONITA NASRIANI","SUMBAWA","54454","55646874","10",NULL,"2026-04-02 11:48:39","2026-04-02 11:48:39");
INSERT INTO muzaki VALUES("126","AHMAD YANI","SUMBAWA","553456456","45564564","10",NULL,"2026-04-02 12:25:17","2026-04-02 12:25:17");
INSERT INTO muzaki VALUES("127","FITRAH RAMADHAN","SUMBAWA","89787","080989","10",NULL,"2026-04-02 14:23:02","2026-04-02 14:23:02");
INSERT INTO muzaki VALUES("132","NANANG NASIRUDDIN","SUMBAWA","5208","0875","10",NULL,"2026-04-06 12:09:49","2026-04-06 12:09:49");
INSERT INTO muzaki VALUES("135","DADANG PUTRAWANSYAH","SUMBAWA","62378428","235913","10",NULL,"2026-04-08 07:20:43","2026-04-08 07:20:43");
INSERT INTO muzaki VALUES("136","RAHMATULLAH","SUMBAWA","645645","154154","10",NULL,"2026-04-08 07:21:36","2026-04-08 07:21:36");
INSERT INTO muzaki VALUES("137","SITI SALEHA 2","SUMBAWA","5454","245984","10",NULL,"2026-04-08 07:40:33","2026-04-08 07:40:33");
INSERT INTO muzaki VALUES("139","A. LATIEF MAJID","SUMBAWA","520408632","0852","10",NULL,"2026-04-13 13:35:18","2026-04-13 13:35:18");
INSERT INTO muzaki VALUES("141","MUSTAMI","SUMBAWA","4156485","0555","10",NULL,"2026-04-15 09:06:42","2026-04-15 09:06:42");
INSERT INTO muzaki VALUES("144","KARTIKA SARI","SUMBAWA","123456","0822","10",NULL,"2026-04-17 09:22:22","2026-04-17 09:22:22");


CREATE TABLE `muzaki_upz_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_muzaki` int(11) NOT NULL,
  `id_upz` int(11) NOT NULL,
  `tanggal_bergabung` date NOT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_muzaki` (`id_muzaki`),
  KEY `id_upz` (`id_upz`),
  CONSTRAINT `muzaki_upz_history_ibfk_1` FOREIGN KEY (`id_muzaki`) REFERENCES `muzaki` (`id`) ON DELETE CASCADE,
  CONSTRAINT `muzaki_upz_history_ibfk_2` FOREIGN KEY (`id_upz`) REFERENCES `upz` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `rekening_koran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sumber_rekening` int(11) NOT NULL,
  `bulan` varchar(7) NOT NULL,
  `jumlah_kredit` decimal(15,2) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `nama_file_original` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sumber_bulan` (`id_sumber_rekening`,`bulan`),
  KEY `idx_sumber_rekening` (`id_sumber_rekening`),
  KEY `idx_bulan` (`bulan`),
  CONSTRAINT `fk_rekening_koran_sumber` FOREIGN KEY (`id_sumber_rekening`) REFERENCES `sumber_rekening` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO rekening_koran VALUES("3","1","2026-01","46248895.00","assets/uploads/rekening_koran/rekening_koran_1_2026_01_1770000022.pdf","Mutasi Rekening Zakat Januari 2026.pdf","2026-02-02 09:40:22","2026-02-02 09:40:22");
INSERT INTO rekening_koran VALUES("7","6","2026-01","278486.00","assets/uploads/rekening_koran/rekening_koran_6_2026_01_1770017276.pdf","Mutasi Rekening Zakat 019 Januari 2026.pdf","2026-02-02 14:27:56","2026-02-02 14:27:56");
INSERT INTO rekening_koran VALUES("8","10","2026-01","530000.00","assets/uploads/rekening_koran/rekening_koran_10_2026_01_1770017482.pdf","Mutasi Rekening BRI Januari 2026_.pdf","2026-02-02 14:31:22","2026-02-02 14:31:22");
INSERT INTO rekening_koran VALUES("12","5","2026-02","428396001.97","assets/uploads/rekening_koran/rekening_koran_5_2026_02_1772513197.pdf","Mutasi Rekening Infak Februari 2026.pdf","2026-03-03 11:46:37","2026-03-03 11:46:37");
INSERT INTO rekening_koran VALUES("14","6","2026-02","545418.26","assets/uploads/rekening_koran/rekening_koran_6_2026_02_1772513515.pdf","Mutasi Rekening 019 Februari 2026.pdf","2026-03-03 11:51:55","2026-03-03 11:51:55");
INSERT INTO rekening_koran VALUES("15","1","2026-02","70008118.00","assets/uploads/rekening_koran/rekening_koran_1_2026_02_1772513985.pdf","Mutasi Rekening Zakat Februari 2026.pdf","2026-03-03 11:59:45","2026-04-09 12:17:12");
INSERT INTO rekening_koran VALUES("17","7","2026-02","8.69","assets/uploads/rekening_koran/rekening_koran_7_2026_02_1772516483.pdf","Mutasi Rekening 018 Februari 2026.pdf","2026-03-03 12:41:23","2026-03-03 12:41:23");
INSERT INTO rekening_koran VALUES("18","7","2026-01","9.65","assets/uploads/rekening_koran/rekening_koran_7_2026_01_1772516730.pdf","Mutasi Rekening 018 Januari 2026.pdf","2026-03-03 12:45:30","2026-03-03 12:45:30");
INSERT INTO rekening_koran VALUES("21","5","2026-01","431777930.00","assets/uploads/rekening_koran/rekening_koran_5_2026_01_1775014826.pdf","Mutasi Rekening infak Januari 2026.pdf","2026-04-01 10:40:26","2026-04-01 10:40:26");
INSERT INTO rekening_koran VALUES("22","1","2026-03","81122519.00","assets/uploads/rekening_koran/rekening_koran_1_2026_03_1775100551.pdf","Mutasi Rekening bulan maret 006.pdf","2026-04-02 10:29:11","2026-04-02 10:29:11");
INSERT INTO rekening_koran VALUES("23","5","2026-03","517389883.00","assets/uploads/rekening_koran/rekening_koran_5_2026_03_1775116393.pdf","Mutasi Rekening infak maret 2026.pdf","2026-04-02 14:53:13","2026-04-02 14:53:13");
INSERT INTO rekening_koran VALUES("24","6","2026-03","456684.00","assets/uploads/rekening_koran/RK_6_202603_1775712215.pdf","Mutasi Rekening Bulan Maret 019.pdf","2026-04-09 12:23:35","2026-04-09 12:23:35");


CREATE TABLE `status_muzaki` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_status` (`nama_status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO status_muzaki VALUES("6","ASN","2026-01-12 14:10:04","2026-01-12 14:10:04");
INSERT INTO status_muzaki VALUES("7","Swasta","2026-01-12 14:10:13","2026-01-12 14:10:13");
INSERT INTO status_muzaki VALUES("8","Pedagang","2026-01-12 14:10:21","2026-01-12 14:10:21");
INSERT INTO status_muzaki VALUES("9","Pengusaha","2026-01-12 14:10:25","2026-01-12 14:10:25");
INSERT INTO status_muzaki VALUES("10","Tidak Diketahui","2026-01-13 13:26:47","2026-01-13 13:27:03");


CREATE TABLE `sub_jenis_pembayaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_jenis_pembayaran` int(11) NOT NULL,
  `nama_sub_jenis` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_jenis_pembayaran` (`id_jenis_pembayaran`),
  CONSTRAINT `sub_jenis_pembayaran_ibfk_1` FOREIGN KEY (`id_jenis_pembayaran`) REFERENCES `jenis_pembayaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sub_jenis_pembayaran VALUES("10","6","Zakat Maal Perorangan","2026-01-12 14:09:10","2026-01-12 14:09:10");
INSERT INTO sub_jenis_pembayaran VALUES("11","6","Zakat Maal Perorangan Via UPZ","2026-01-12 14:09:41","2026-01-12 14:09:41");
INSERT INTO sub_jenis_pembayaran VALUES("12","6","Zakat Maal Badan","2026-01-12 17:36:02","2026-01-12 17:36:02");
INSERT INTO sub_jenis_pembayaran VALUES("13","6","Zakat Fitrah","2026-01-12 17:36:19","2026-01-12 17:36:19");
INSERT INTO sub_jenis_pembayaran VALUES("14","6","Zakat Fitrah Via UPZ","2026-01-12 17:36:51","2026-01-12 17:36:51");
INSERT INTO sub_jenis_pembayaran VALUES("15","6","Dana Titipan (Transfer Dana BAZNAS Provinsi/pusat)","2026-01-12 17:38:31","2026-01-12 17:38:31");
INSERT INTO sub_jenis_pembayaran VALUES("16","6","Dana Bagi Hasil BAZNAS Prov. NTB","2026-01-12 17:39:13","2026-01-12 17:39:13");
INSERT INTO sub_jenis_pembayaran VALUES("17","6","Dana Bagi Hasil Bank","2026-01-12 17:39:42","2026-01-12 17:39:42");
INSERT INTO sub_jenis_pembayaran VALUES("18","7","Infak dan Sedekah Terikat","2026-01-13 10:53:42","2026-01-13 10:53:42");
INSERT INTO sub_jenis_pembayaran VALUES("19","7","Infak dan Sedekah Tidak Terikat","2026-01-13 10:55:09","2026-01-13 10:55:09");
INSERT INTO sub_jenis_pembayaran VALUES("20","7","Infak dan Sedekah Tidak Terikat Via UPZ","2026-01-13 10:56:22","2026-01-13 10:56:22");
INSERT INTO sub_jenis_pembayaran VALUES("21","7","Infak Penyaluran (Titipan)","2026-01-13 10:56:51","2026-01-13 10:56:51");
INSERT INTO sub_jenis_pembayaran VALUES("22","7","Infak Operasional","2026-01-13 10:57:01","2026-01-13 10:57:01");
INSERT INTO sub_jenis_pembayaran VALUES("23","7","Dana Bagi Hasil Bank","2026-01-13 10:58:08","2026-01-13 10:58:08");
INSERT INTO sub_jenis_pembayaran VALUES("26","8","FIDYAH","2026-03-09 09:29:18","2026-03-09 09:29:18");
INSERT INTO sub_jenis_pembayaran VALUES("27","11","PAKET BERKAH RAMADHAN","2026-03-10 11:31:14","2026-03-10 11:31:14");


CREATE TABLE `sumber_rekening` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_sumber` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_sumber` (`nama_sumber`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sumber_rekening VALUES("1","BANK NTB SYARIAH ZAKAT | 0042100208006","2026-01-04 22:31:54","2026-01-13 11:10:26");
INSERT INTO sumber_rekening VALUES("5","BANK NTB SYARIAH INFAK | 0042220955015","2026-01-13 11:11:18","2026-01-13 11:11:18");
INSERT INTO sumber_rekening VALUES("6","BANK NTB SYARIAH ZAKAT | 5090301611019","2026-01-13 11:12:35","2026-01-13 11:12:35");
INSERT INTO sumber_rekening VALUES("7","BANK NTB SYARIAH APBD |  0042221520018","2026-01-13 11:16:17","2026-01-13 11:21:45");
INSERT INTO sumber_rekening VALUES("8","BANK MANDIRI INFAK | 1610009519518","2026-01-13 11:17:57","2026-01-13 11:21:19");
INSERT INTO sumber_rekening VALUES("9","BANK MANDIRI ZAKAT | 1610008518511","2026-01-13 11:23:03","2026-01-13 11:23:03");
INSERT INTO sumber_rekening VALUES("10","BANK BRI ZAKAT | 183501000953534","2026-01-13 11:24:21","2026-02-02 09:48:15");
INSERT INTO sumber_rekening VALUES("11","BUKU PENGUMPULAN","2026-03-10 11:31:32","2026-03-10 11:31:32");


CREATE TABLE `target_pengumpulan_per_jenis` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tahun` year(4) NOT NULL,
  `id_jenis_pembayaran` int(11) NOT NULL,
  `nominal_target` decimal(18,2) NOT NULL DEFAULT 0.00,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tahun_jenis` (`tahun`,`id_jenis_pembayaran`),
  KEY `idx_jenis` (`id_jenis_pembayaran`),
  CONSTRAINT `fk_target_p_jenis` FOREIGN KEY (`id_jenis_pembayaran`) REFERENCES `jenis_pembayaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `target_pengumpulan_tahunan` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tahun` year(4) NOT NULL,
  `nominal_target` decimal(18,2) NOT NULL DEFAULT 0.00,
  `keterangan` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_target_tahun` (`tahun`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO target_pengumpulan_tahunan VALUES("1","2026","7641000000.00","terget Pengumpulan RKAT 2026","2026-04-05 14:02:54","2026-04-06 08:08:28");


CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal_transaksi` date NOT NULL,
  `id_muzaki` int(11) DEFAULT NULL,
  `id_upz` int(11) DEFAULT NULL,
  `id_jenis_pembayaran` int(11) NOT NULL,
  `id_sub_jenis_pembayaran` int(11) DEFAULT NULL,
  `id_metode_pembayaran` int(11) NOT NULL,
  `id_kategori_dana` varchar(255) NOT NULL,
  `id_sumber_rekening` int(11) NOT NULL,
  `jumlah_pembayaran` decimal(15,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_muzaki` (`id_muzaki`),
  KEY `id_upz` (`id_upz`),
  KEY `id_jenis_pembayaran` (`id_jenis_pembayaran`),
  KEY `id_sub_jenis_pembayaran` (`id_sub_jenis_pembayaran`),
  KEY `id_metode_pembayaran` (`id_metode_pembayaran`),
  KEY `id_kategori_dana` (`id_kategori_dana`),
  KEY `id_sumber_rekening` (`id_sumber_rekening`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_muzaki`) REFERENCES `muzaki` (`id`),
  CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`id_upz`) REFERENCES `upz` (`id`),
  CONSTRAINT `transaksi_ibfk_3` FOREIGN KEY (`id_jenis_pembayaran`) REFERENCES `jenis_pembayaran` (`id`),
  CONSTRAINT `transaksi_ibfk_4` FOREIGN KEY (`id_sub_jenis_pembayaran`) REFERENCES `sub_jenis_pembayaran` (`id`),
  CONSTRAINT `transaksi_ibfk_5` FOREIGN KEY (`id_metode_pembayaran`) REFERENCES `metode_pembayaran` (`id`),
  CONSTRAINT `transaksi_ibfk_7` FOREIGN KEY (`id_sumber_rekening`) REFERENCES `sumber_rekening` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=923 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO transaksi VALUES("6","2026-01-07",NULL,"18","7","20","4","[3,2]","5","1420000.00","","2026-01-13 13:24:39","2026-01-13 13:24:39");
INSERT INTO transaksi VALUES("9","2026-01-08","9",NULL,"7","19","4","[3,2]","5","1250000.00","","2026-01-13 13:44:09","2026-01-13 13:44:09");
INSERT INTO transaksi VALUES("10","2026-01-08",NULL,"39","7","20","4","[3,2]","5","580000.00","","2026-01-13 13:45:54","2026-01-18 19:39:52");
INSERT INTO transaksi VALUES("12","2026-01-08",NULL,"39","7","20","4","[3,2]","5","60000.00","","2026-01-13 13:48:38","2026-01-18 19:40:17");
INSERT INTO transaksi VALUES("13","2026-01-08",NULL,"3","7","20","4","[3,2]","5","145670000.00","","2026-01-13 13:50:26","2026-01-13 13:50:26");
INSERT INTO transaksi VALUES("14","2026-01-08",NULL,"3","7","20","4","[3,2]","5","160880000.00","","2026-01-13 13:51:28","2026-01-13 13:51:28");
INSERT INTO transaksi VALUES("15","2026-01-08",NULL,"15","7","20","4","[3,2]","5","1990000.00","","2026-01-13 13:55:00","2026-01-13 13:58:44");
INSERT INTO transaksi VALUES("16","2026-01-08",NULL,"57","7","20","4","[3,2]","5","780000.00","","2026-01-13 13:58:07","2026-01-13 13:58:07");
INSERT INTO transaksi VALUES("17","2026-01-09",NULL,"25","7","20","4","[3,2]","5","5080000.00","","2026-01-13 13:59:31","2026-01-13 13:59:31");
INSERT INTO transaksi VALUES("18","2026-01-09",NULL,"25","7","20","4","[3,2]","5","630000.00","","2026-01-13 14:01:41","2026-01-13 14:01:41");
INSERT INTO transaksi VALUES("19","2026-01-09",NULL,"24","7","20","4","[3,2]","5","2820000.00","","2026-01-13 14:02:50","2026-01-13 14:02:50");
INSERT INTO transaksi VALUES("21","2026-01-12",NULL,"16","7","20","4","[3,2]","5","110000.00","","2026-01-13 14:06:25","2026-01-13 14:08:03");
INSERT INTO transaksi VALUES("22","2026-01-08",NULL,"58","7","20","4","[3,2]","5","2630000.00","","2026-01-13 14:16:21","2026-01-13 14:16:21");
INSERT INTO transaksi VALUES("23","2026-01-07","6",NULL,"6","10","4","[3,2]","1","100000.00","","2026-01-13 14:37:41","2026-01-13 14:43:57");
INSERT INTO transaksi VALUES("24","2026-01-07","6",NULL,"6","10","4","[3,2]","1","100000.00","","2026-01-13 14:38:28","2026-01-13 14:44:17");
INSERT INTO transaksi VALUES("25","2026-01-07",NULL,"58","6","11","4","[3,2]","1","7630000.00","","2026-01-13 14:41:56","2026-01-13 14:41:56");
INSERT INTO transaksi VALUES("26","2026-01-07",NULL,"58","6","11","4","[3,2]","1","1989000.00","","2026-01-13 14:45:20","2026-01-13 14:45:20");
INSERT INTO transaksi VALUES("27","2026-01-08",NULL,"32","6","11","4","[3,2]","1","3980000.00","","2026-01-13 14:47:48","2026-01-13 14:47:48");
INSERT INTO transaksi VALUES("30","2026-01-08",NULL,"22","6","11","4","[3,2]","1","1950000.00","","2026-01-13 14:53:23","2026-01-13 14:53:23");
INSERT INTO transaksi VALUES("31","2026-01-08",NULL,"56","6","11","4","[3,2]","1","420000.00","","2026-01-13 14:56:19","2026-01-13 14:56:19");
INSERT INTO transaksi VALUES("32","2026-01-08",NULL,"30","6","11","4","[3,2]","1","2627000.00","","2026-01-13 14:57:21","2026-01-13 14:57:21");
INSERT INTO transaksi VALUES("34","2026-01-09",NULL,"14","6","11","4","[3,2]","1","770000.00","","2026-01-13 14:59:44","2026-01-13 14:59:44");
INSERT INTO transaksi VALUES("35","2026-01-09",NULL,"56","6","11","4","[3,2]","1","120000.00","","2026-01-13 15:00:42","2026-01-13 15:00:42");
INSERT INTO transaksi VALUES("36","2026-01-09",NULL,"41","6","11","4","[3,2]","1","500000.00","","2026-01-13 15:01:58","2026-01-13 15:01:58");
INSERT INTO transaksi VALUES("37","2026-01-09",NULL,"41","6","11","4","[3,2]","1","60000.00","","2026-01-13 15:02:37","2026-01-13 15:02:37");
INSERT INTO transaksi VALUES("38","2026-01-09",NULL,"23","6","11","4","[3,2]","1","340000.00","","2026-01-13 15:03:41","2026-01-13 15:03:41");
INSERT INTO transaksi VALUES("39","2026-01-09",NULL,"23","6","11","4","[3,2]","1","2900000.00","","2026-01-13 15:04:50","2026-01-13 15:04:50");
INSERT INTO transaksi VALUES("41","2026-01-12",NULL,"28","6","11","4","[3,2]","1","180000.00","","2026-01-13 15:06:26","2026-01-13 15:06:26");
INSERT INTO transaksi VALUES("42","2026-01-12",NULL,"5","6","11","4","[3,2]","1","50000.00","","2026-01-13 15:07:18","2026-01-13 15:07:18");
INSERT INTO transaksi VALUES("43","2026-01-12",NULL,"5","6","11","4","[3,2]","1","3540000.00","","2026-01-13 15:07:58","2026-01-18 18:50:48");
INSERT INTO transaksi VALUES("47","2026-01-12",NULL,"22","6","11","4","[3,2]","1","80000.00","","2026-01-13 15:12:20","2026-01-13 15:12:20");
INSERT INTO transaksi VALUES("48","2026-01-12","10",NULL,"6","10","4","[3,2]","1","640000.00","","2026-01-13 15:15:26","2026-01-13 15:15:26");
INSERT INTO transaksi VALUES("49","2026-01-12",NULL,"14","6","11","4","[3,2]","1","2850000.00","","2026-01-13 15:16:13","2026-01-13 15:16:13");
INSERT INTO transaksi VALUES("50","2026-01-13",NULL,"31","6","11","4","[3,2]","1","2850000.00","","2026-01-13 15:17:09","2026-01-13 15:17:09");
INSERT INTO transaksi VALUES("51","2026-01-13","11",NULL,"6","10","4","[3,2]","1","300000.00","","2026-01-13 15:19:15","2026-01-13 15:19:15");
INSERT INTO transaksi VALUES("53","2026-01-13",NULL,"19","7","20","4","[3,2]","5","380000.00","","2026-01-14 08:01:23","2026-01-14 08:01:23");
INSERT INTO transaksi VALUES("54","2026-01-13",NULL,"45","7","20","4","[3,2]","5","60000.00","","2026-01-14 08:03:25","2026-01-14 08:03:25");
INSERT INTO transaksi VALUES("55","2026-01-13",NULL,"45","7","20","4","[3,2]","5","1030000.00","","2026-01-14 08:03:51","2026-01-14 08:03:51");
INSERT INTO transaksi VALUES("56","2026-01-13",NULL,"4","7","20","4","[3,2]","5","17970000.00","","2026-01-14 08:05:47","2026-01-14 08:05:47");
INSERT INTO transaksi VALUES("57","2026-01-07",NULL,"29","7","20","4","[3,2]","5","3320000.00","","2026-01-14 15:49:36","2026-01-14 15:49:36");
INSERT INTO transaksi VALUES("58","2026-01-14",NULL,"20","7","20","4","[3,2]","5","1730000.00","","2026-01-15 09:54:43","2026-01-15 09:54:43");
INSERT INTO transaksi VALUES("59","2026-01-15",NULL,"43","7","20","4","[3,2]","5","550000.00","","2026-01-15 09:56:40","2026-01-15 09:56:40");
INSERT INTO transaksi VALUES("62","2026-01-19",NULL,"11","7","20","4","[3,2]","5","1050000.00","","2026-01-20 11:43:32","2026-01-20 11:43:32");
INSERT INTO transaksi VALUES("63","2026-01-19","6",NULL,"7","19","4","[3,2]","5","1600000.00","","2026-01-20 11:49:34","2026-01-20 11:58:11");
INSERT INTO transaksi VALUES("64","2026-01-20","6",NULL,"7","19","4","[3,2]","5","1200000.00","","2026-01-20 12:00:55","2026-03-31 09:18:57");
INSERT INTO transaksi VALUES("66","2026-01-20","14",NULL,"6","10","4","[3,2]","1","200000.00","","2026-01-20 13:00:05","2026-01-20 13:00:05");
INSERT INTO transaksi VALUES("68","2026-01-20",NULL,"59","7","18","4","[3,2]","5","11755000.00","BANTUAN BENCANA SUMATERA","2026-01-21 13:03:43","2026-01-21 13:03:43");
INSERT INTO transaksi VALUES("70","2026-01-22",NULL,"60","6","11","4","[3,2]","1","2375000.00","","2026-01-22 13:13:07","2026-01-22 13:13:07");
INSERT INTO transaksi VALUES("71","2026-01-26",NULL,"61","7","20","4","[3,2]","5","1485919.00","","2026-01-26 10:16:38","2026-01-26 11:23:04");
INSERT INTO transaksi VALUES("72","2026-01-26",NULL,"62","7","20","4","[3,2]","5","100000.00","","2026-01-26 14:09:49","2026-01-26 14:09:49");
INSERT INTO transaksi VALUES("73","2026-01-26",NULL,"63","7","20","4","[3,2]","5","301706.00","","2026-01-26 14:10:18","2026-01-26 14:10:18");
INSERT INTO transaksi VALUES("75","2026-01-27",NULL,"64","7","20","4","[3,2]","5","279000.00","","2026-01-27 10:43:43","2026-01-27 10:43:43");
INSERT INTO transaksi VALUES("77","2026-01-29",NULL,"44","7","20","4","[3,2]","5","480000.00","","2026-01-29 10:43:41","2026-01-29 10:43:41");
INSERT INTO transaksi VALUES("78","2026-01-29","17",NULL,"7","19","4","[3,2]","5","300000.00","","2026-01-29 10:45:18","2026-01-29 10:45:18");
INSERT INTO transaksi VALUES("80","2026-01-27",NULL,"66","6","11","4","[3,2]","6","277322.00","","2026-01-30 08:05:17","2026-01-30 08:13:42");
INSERT INTO transaksi VALUES("81","2026-01-30",NULL,"67","6","11","4","[3,2]","1","1775000.00","PEMBAYARAN  ZIS BAZNAS KAB. SUMBAWA","2026-01-30 11:01:39","2026-01-30 11:01:39");
INSERT INTO transaksi VALUES("172","2026-02-03",NULL,"37","7","20","4","[3,2]","5","60000.00","","2026-02-05 07:27:33","2026-02-05 07:27:33");
INSERT INTO transaksi VALUES("173","2026-02-03",NULL,"50","7","20","4","[3,2]","5","90000.00","","2026-02-05 07:28:30","2026-02-05 07:28:30");
INSERT INTO transaksi VALUES("175","2026-02-03",NULL,"26","7","20","4","[3,2]","5","960000.00","","2026-02-05 07:30:40","2026-02-05 07:30:40");
INSERT INTO transaksi VALUES("177","2026-02-03",NULL,"37","7","20","4","[3,2]","5","570000.00","","2026-02-05 07:32:57","2026-02-05 07:32:57");
INSERT INTO transaksi VALUES("178","2026-02-04",NULL,"42","7","20","4","[3,2]","5","60000.00","","2026-02-05 07:34:43","2026-02-05 07:34:43");
INSERT INTO transaksi VALUES("180","2026-02-04","6",NULL,"7","19","4","[3,2]","5","100000.00","","2026-02-05 07:38:06","2026-02-10 07:56:31");
INSERT INTO transaksi VALUES("181","2026-02-04","23",NULL,"7","19","4","[3,2]","5","100000.00","","2026-02-05 07:40:30","2026-02-05 07:40:30");
INSERT INTO transaksi VALUES("183","2026-02-04","24",NULL,"7","19","4","[3,2]","5","5000.00","","2026-02-05 07:43:04","2026-02-05 07:43:04");
INSERT INTO transaksi VALUES("184","2026-02-04","25",NULL,"7","19","4","[3,2]","5","100000.00","","2026-02-05 07:44:56","2026-02-05 07:44:56");
INSERT INTO transaksi VALUES("185","2026-02-04","26",NULL,"7","19","4","[3,2]","5","20000.00","","2026-02-05 07:46:46","2026-02-05 07:46:46");
INSERT INTO transaksi VALUES("187","2026-02-04","28",NULL,"7","19","4","[3,2]","5","25000.00","","2026-02-05 07:52:16","2026-02-05 07:52:16");
INSERT INTO transaksi VALUES("188","2026-02-04","29",NULL,"7","19","4","[3,2]","5","50000.00","","2026-02-05 07:53:28","2026-02-05 07:53:28");
INSERT INTO transaksi VALUES("189","2026-02-04","30",NULL,"7","19","4","[3,2]","5","20000.00","","2026-02-05 07:55:33","2026-02-05 07:55:33");
INSERT INTO transaksi VALUES("190","2026-02-05","36",NULL,"7","19","4","[3,2]","5","100000.00","","2026-02-05 07:57:08","2026-03-31 10:23:00");
INSERT INTO transaksi VALUES("193","2026-02-04",NULL,"4","7","20","4","[3,2]","5","17940000.00","","2026-02-05 08:02:18","2026-02-05 08:02:18");
INSERT INTO transaksi VALUES("195","2026-02-04","35",NULL,"7","19","4","[3,2]","5","10000.00","","2026-02-05 08:05:09","2026-03-31 14:00:39");
INSERT INTO transaksi VALUES("196","2026-02-04",NULL,"3","7","20","4","[3,2]","5","161670000.00","","2026-02-05 08:05:52","2026-02-05 08:05:52");
INSERT INTO transaksi VALUES("198","2026-02-04","37",NULL,"6","10","4","[3,2]","1","100000.00","","2026-02-05 09:40:50","2026-02-05 09:51:16");
INSERT INTO transaksi VALUES("199","2026-02-04","38",NULL,"6","10","4","[3,2]","1","50000.00","","2026-02-05 09:45:04","2026-02-05 09:51:49");
INSERT INTO transaksi VALUES("200","2026-02-04","40",NULL,"6","10","4","[3,2]","1","100000.00","","2026-02-05 09:53:54","2026-02-05 09:53:54");
INSERT INTO transaksi VALUES("201","2026-02-04","41",NULL,"6","10","4","[3,2]","1","1700000.00","","2026-02-05 09:55:21","2026-02-05 09:55:21");
INSERT INTO transaksi VALUES("202","2026-02-04",NULL,"4","6","11","4","[3,2]","1","61620000.00","","2026-02-05 09:57:02","2026-02-05 09:57:02");
INSERT INTO transaksi VALUES("204","2026-02-05","42",NULL,"6","10","4","[3,2]","1","2500000.00","","2026-02-05 13:17:09","2026-02-05 13:17:09");
INSERT INTO transaksi VALUES("205","2026-02-06","24",NULL,"7","19","4","[3,2]","5","1000.00","","2026-02-06 14:17:04","2026-02-06 14:17:04");
INSERT INTO transaksi VALUES("206","2026-02-06",NULL,"72","7","20","4","[3,2]","5","64000.00","","2026-02-06 14:19:01","2026-02-06 14:22:14");
INSERT INTO transaksi VALUES("207","2026-02-06",NULL,"97","7","20","4","[3,2]","5","100000.00","","2026-02-06 14:21:50","2026-03-09 09:58:02");
INSERT INTO transaksi VALUES("209","2026-02-06","47",NULL,"6","10","4","[3,2]","1","100000.00","","2026-02-06 14:27:40","2026-02-06 14:27:40");
INSERT INTO transaksi VALUES("212","2026-02-11","49",NULL,"7","19","4","[3,2]","5","500000.00","","2026-02-11 13:31:11","2026-02-11 13:31:11");
INSERT INTO transaksi VALUES("213","2026-02-10","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-02-11 13:33:30","2026-02-11 13:33:30");
INSERT INTO transaksi VALUES("214","2026-02-11","50",NULL,"7","19","4","[3,2]","5","100000.00","","2026-02-11 13:35:19","2026-02-11 13:35:19");
INSERT INTO transaksi VALUES("215","2026-02-11","52",NULL,"7","19","4","[3,2]","5","200000.00","","2026-02-11 13:37:26","2026-02-11 13:37:26");
INSERT INTO transaksi VALUES("216","2026-02-11","6",NULL,"7","19","4","[3,2]","5","10000.00","","2026-02-11 13:38:12","2026-02-11 13:38:12");
INSERT INTO transaksi VALUES("217","2026-02-13","24",NULL,"7","19","4","[3,2]","5","100000.00","","2026-02-13 08:21:48","2026-02-13 08:21:48");
INSERT INTO transaksi VALUES("219","2026-02-14",NULL,"74","7","20","4","[3,2]","5","330000.00","","2026-02-14 12:10:05","2026-02-14 12:10:05");
INSERT INTO transaksi VALUES("220","2026-02-14",NULL,"75","6","11","4","[3,2]","1","270000.00","","2026-02-14 12:15:25","2026-02-14 12:15:25");
INSERT INTO transaksi VALUES("221","2026-02-14","55",NULL,"6","10","4","[3,2]","1","200000.00","","2026-02-14 12:22:49","2026-03-16 08:05:39");
INSERT INTO transaksi VALUES("222","2026-02-14",NULL,"76","7","20","4","[3,2]","5","495000.00","","2026-02-15 06:12:41","2026-02-15 06:12:41");
INSERT INTO transaksi VALUES("223","2026-02-17","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-02-18 07:27:58","2026-02-18 07:27:58");
INSERT INTO transaksi VALUES("224","2026-02-18","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-02-18 07:28:22","2026-02-18 07:28:22");
INSERT INTO transaksi VALUES("225","2026-02-19","6",NULL,"7","19","4","[3,2]","5","10000.00","","2026-02-19 07:56:39","2026-02-19 07:56:39");
INSERT INTO transaksi VALUES("226","2026-02-19","56",NULL,"7","19","4","[3,2]","5","100000.00","","2026-02-19 09:14:13","2026-02-19 09:14:13");
INSERT INTO transaksi VALUES("227","2026-02-19","58",NULL,"7","19","4","[3,2]","5","50000.00","","2026-02-19 09:25:00","2026-02-19 09:25:00");
INSERT INTO transaksi VALUES("229","2026-02-19","59",NULL,"7","19","4","[3,2]","5","100000.00","","2026-02-19 11:24:15","2026-02-19 11:24:42");
INSERT INTO transaksi VALUES("231","2026-02-23",NULL,"54","7","20","4","[3,2]","5","90000.00","","2026-02-24 07:54:14","2026-02-24 07:54:14");
INSERT INTO transaksi VALUES("234","2026-02-23",NULL,"27","7","20","4","[3,2]","5","1560000.00","","2026-02-24 08:00:47","2026-02-24 08:00:47");
INSERT INTO transaksi VALUES("235","2026-02-23",NULL,"42","7","20","4","[3,2]","5","680000.00","","2026-02-24 08:02:33","2026-02-24 08:02:33");
INSERT INTO transaksi VALUES("236","2026-02-23",NULL,"40","7","20","4","[3,2]","5","500000.00","","2026-02-24 08:04:26","2026-02-24 08:04:26");
INSERT INTO transaksi VALUES("237","2026-02-23",NULL,"36","7","20","4","[3,2]","5","711000.00","","2026-02-24 08:05:23","2026-02-24 08:05:23");
INSERT INTO transaksi VALUES("238","2026-02-23",NULL,"54","7","20","4","[3,2]","5","680000.00","","2026-02-24 08:06:24","2026-02-24 08:06:24");
INSERT INTO transaksi VALUES("240","2026-02-24","26",NULL,"6","10","4","[3,2]","1","600000.00","","2026-02-25 09:42:07","2026-02-25 09:43:41");
INSERT INTO transaksi VALUES("241","2026-02-25",NULL,"64","7","20","4","[3,2]","5","254000.00","","2026-02-26 08:22:13","2026-02-26 08:22:13");
INSERT INTO transaksi VALUES("242","2026-02-25",NULL,"12","7","20","4","[3,2]","5","60000.00","","2026-02-26 08:25:15","2026-02-26 08:25:15");
INSERT INTO transaksi VALUES("243","2026-02-25",NULL,"62","7","20","4","[3,2]","5","100000.00","","2026-02-26 08:27:33","2026-02-26 08:27:33");
INSERT INTO transaksi VALUES("244","2026-02-25",NULL,"63","7","20","4","[3,2]","5","586753.00","","2026-02-26 08:28:27","2026-02-26 08:28:27");
INSERT INTO transaksi VALUES("247","2026-02-25",NULL,"11","7","20","4","[3,2]","5","150000.00","","2026-02-26 08:49:55","2026-02-26 08:49:55");
INSERT INTO transaksi VALUES("248","2026-02-25",NULL,"61","7","20","4","[3,2]","5","1413419.00","","2026-02-26 08:50:37","2026-02-26 08:50:37");
INSERT INTO transaksi VALUES("249","2026-02-25",NULL,"51","7","20","4","[3,2]","5","10000.00","","2026-02-26 08:51:41","2026-02-26 08:51:41");
INSERT INTO transaksi VALUES("250","2026-02-25",NULL,"51","7","20","4","[3,2]","5","3100000.00","","2026-02-26 08:52:07","2026-02-26 08:52:07");
INSERT INTO transaksi VALUES("251","2026-02-26","6",NULL,"7","19","4","[3,2]","5","15000.00","","2026-02-26 08:53:09","2026-02-26 08:53:09");
INSERT INTO transaksi VALUES("253","2026-02-25",NULL,"66","6","11","4","[3,2]","6","544261.00","","2026-02-26 09:13:31","2026-02-26 09:13:31");
INSERT INTO transaksi VALUES("254","2026-02-26",NULL,"18","7","20","4","[3,2]","5","60000.00","","2026-02-27 09:23:21","2026-02-27 09:23:21");
INSERT INTO transaksi VALUES("255","2026-02-26",NULL,"9","7","20","4","[3,2]","5","50000.00","","2026-02-27 09:24:09","2026-02-27 09:24:09");
INSERT INTO transaksi VALUES("258","2026-02-27",NULL,"67","6","11","4","[3,2]","1","1805000.00","","2026-02-27 09:37:57","2026-02-27 09:37:57");
INSERT INTO transaksi VALUES("259","2026-02-27","11",NULL,"6","10","4","[3,2]","1","300000.00","","2026-02-27 09:39:11","2026-02-27 09:39:11");
INSERT INTO transaksi VALUES("261","2026-02-27","63",NULL,"7","19","4","[3,2]","5","100000.00","","2026-03-02 07:17:59","2026-03-02 07:17:59");
INSERT INTO transaksi VALUES("267","2026-03-02",NULL,"10","7","20","4","[3,2]","5","750000.00","","2026-03-03 08:04:56","2026-03-03 08:04:56");
INSERT INTO transaksi VALUES("268","2026-03-02",NULL,"16","7","20","4","[3,2]","5","1420000.00","","2026-03-03 08:05:48","2026-03-03 08:05:48");
INSERT INTO transaksi VALUES("269","2026-03-02",NULL,"53","7","20","4","[3,2]","5","710000.00","","2026-03-03 08:06:15","2026-03-03 08:06:15");
INSERT INTO transaksi VALUES("270","2026-03-02",NULL,"53","7","20","4","[3,2]","5","60000.00","","2026-03-03 08:06:44","2026-03-03 08:06:44");
INSERT INTO transaksi VALUES("271","2026-03-02",NULL,"17","7","20","4","[3,2]","5","1940000.00","","2026-03-03 08:07:33","2026-03-03 08:07:33");
INSERT INTO transaksi VALUES("273","2026-03-02",NULL,"6","7","20","4","[3,2]","5","1500000.00","","2026-03-03 08:08:28","2026-03-03 08:08:28");
INSERT INTO transaksi VALUES("274","2026-03-02",NULL,"29","7","20","4","[3,2]","5","3370000.00","","2026-03-03 08:09:12","2026-03-03 08:09:12");
INSERT INTO transaksi VALUES("276","2026-03-02",NULL,"5","7","20","4","[3,2]","5","50000.00","","2026-03-03 08:10:12","2026-03-03 08:10:12");
INSERT INTO transaksi VALUES("278","2026-03-02",NULL,"4","7","20","4","[3,2]","5","17940000.00","","2026-03-03 08:11:11","2026-03-03 08:11:11");
INSERT INTO transaksi VALUES("279","2026-03-02",NULL,"22","7","20","4","[3,2]","5","1850000.00","","2026-03-03 08:11:39","2026-03-03 08:11:39");
INSERT INTO transaksi VALUES("280","2026-03-02",NULL,"30","7","20","4","[3,2]","5","2627000.00","","2026-03-03 08:12:16","2026-03-03 08:12:16");
INSERT INTO transaksi VALUES("281","2026-03-02",NULL,"34","7","20","4","[3,2]","5","730000.00","","2026-03-03 08:13:03","2026-03-03 08:13:03");
INSERT INTO transaksi VALUES("282","2026-03-02",NULL,"39","7","20","4","[3,2]","5","60000.00","","2026-03-03 08:13:26","2026-03-03 08:13:26");
INSERT INTO transaksi VALUES("284","2026-03-02",NULL,"49","7","20","4","[3,2]","5","450000.00","","2026-03-03 08:14:44","2026-03-03 08:14:44");
INSERT INTO transaksi VALUES("285","2026-03-02",NULL,"5","7","20","4","[3,2]","5","3540000.00","","2026-03-03 08:15:12","2026-03-03 08:15:12");
INSERT INTO transaksi VALUES("287","2026-03-02",NULL,"16","7","20","4","[3,2]","5","110000.00","","2026-03-03 08:15:59","2026-03-03 08:15:59");
INSERT INTO transaksi VALUES("288","2026-03-02",NULL,"7","7","20","4","[3,2]","5","2000000.00","","2026-03-03 08:16:54","2026-03-03 08:16:54");
INSERT INTO transaksi VALUES("289","2026-03-02",NULL,"22","7","20","4","[3,2]","5","80000.00","","2026-03-03 08:17:19","2026-03-03 08:17:19");
INSERT INTO transaksi VALUES("290","2026-03-02",NULL,"25","7","20","4","[3,2]","5","630000.00","","2026-03-03 08:18:01","2026-03-03 08:18:01");
INSERT INTO transaksi VALUES("291","2026-03-02",NULL,"34","7","20","4","[3,2]","5","60000.00","","2026-03-03 08:24:09","2026-03-03 08:24:09");
INSERT INTO transaksi VALUES("292","2026-03-02",NULL,"35","7","20","4","[3,2]","5","530000.00","","2026-03-03 08:25:01","2026-03-03 08:25:01");
INSERT INTO transaksi VALUES("294","2026-03-02",NULL,"4","7","20","4","[3,2]","5","61620000.00","","2026-03-03 08:27:13","2026-03-03 08:27:13");
INSERT INTO transaksi VALUES("295","2026-03-02",NULL,"45","7","20","4","[3,2]","5","1030000.00","","2026-03-03 08:27:47","2026-03-03 08:27:47");
INSERT INTO transaksi VALUES("296","2026-03-02",NULL,"48","7","20","4","[3,2]","5","550000.00","","2026-03-03 08:28:28","2026-03-03 08:28:28");
INSERT INTO transaksi VALUES("297","2026-03-02",NULL,"49","7","20","4","[3,2]","5","120000.00","","2026-03-03 08:28:54","2026-03-03 08:28:54");
INSERT INTO transaksi VALUES("300","2026-03-02",NULL,"25","7","20","4","[3,2]","5","4830000.00","","2026-03-03 09:51:19","2026-03-03 09:51:19");
INSERT INTO transaksi VALUES("302","2026-03-02",NULL,"28","7","20","4","[3,2]","5","2290000.00","","2026-03-03 09:52:54","2026-03-03 09:52:54");
INSERT INTO transaksi VALUES("304","2026-03-02",NULL,"48","7","20","4","[3,2]","5","90000.00","","2026-03-03 09:54:30","2026-03-03 09:54:30");
INSERT INTO transaksi VALUES("305","2026-03-02",NULL,"19","7","20","4","[3,2]","5","380000.00","","2026-03-03 09:54:53","2026-03-03 09:54:53");
INSERT INTO transaksi VALUES("306","2026-03-02",NULL,"28","7","20","4","[3,2]","5","180000.00","","2026-03-03 09:55:19","2026-03-03 09:55:19");
INSERT INTO transaksi VALUES("307","2026-03-02",NULL,"20","7","20","4","[3,2]","5","1810000.00","","2026-03-03 09:55:45","2026-03-03 09:55:45");
INSERT INTO transaksi VALUES("309","2026-03-02",NULL,"44","7","20","4","[3,2]","5","90000.00","","2026-03-03 09:56:31","2026-03-03 09:56:31");
INSERT INTO transaksi VALUES("310","2026-03-02",NULL,"46","7","20","4","[3,2]","5","810000.00","","2026-03-03 09:56:56","2026-03-03 09:56:56");
INSERT INTO transaksi VALUES("311","2026-03-02",NULL,"20","7","20","4","[3,2]","5","200000.00","","2026-03-03 09:57:21","2026-03-03 09:57:21");
INSERT INTO transaksi VALUES("313","2026-03-02",NULL,"46","7","20","4","[3,2]","5","60000.00","","2026-03-03 09:58:24","2026-03-03 09:58:24");
INSERT INTO transaksi VALUES("316","2026-03-02",NULL,"14","7","20","4","[3,2]","5","770000.00","","2026-03-03 10:00:35","2026-03-03 10:00:35");
INSERT INTO transaksi VALUES("317","2026-03-02",NULL,"24","7","20","4","[3,2]","5","2650000.00","","2026-03-03 10:01:08","2026-03-03 10:01:08");
INSERT INTO transaksi VALUES("318","2026-03-02",NULL,"24","7","20","4","[3,2]","5","50000.00","","2026-03-03 10:01:37","2026-03-03 10:01:37");
INSERT INTO transaksi VALUES("319","2026-03-02",NULL,"31","7","20","4","[3,2]","5","2590000.00","","2026-03-03 10:02:15","2026-03-03 10:02:15");
INSERT INTO transaksi VALUES("320","2026-03-02",NULL,"31","7","20","4","[3,2]","5","260000.00","","2026-03-03 10:02:42","2026-03-03 10:02:42");
INSERT INTO transaksi VALUES("321","2026-03-02",NULL,"23","7","20","4","[3,2]","5","340000.00","","2026-03-03 10:03:06","2026-03-03 10:03:06");
INSERT INTO transaksi VALUES("322","2026-03-02",NULL,"23","7","20","4","[3,2]","5","2920000.00","","2026-03-03 10:03:28","2026-03-03 10:03:28");
INSERT INTO transaksi VALUES("323","2026-03-02",NULL,"27","7","20","4","[3,2]","5","120000.00","","2026-03-03 10:08:29","2026-03-03 10:08:29");
INSERT INTO transaksi VALUES("324","2026-03-02",NULL,"26","7","20","4","[3,2]","5","6670000.00","","2026-03-03 10:08:57","2026-03-03 10:08:57");
INSERT INTO transaksi VALUES("326","2026-03-02",NULL,"79","7","20","4","[3,2]","5","250000.00","","2026-03-03 10:13:39","2026-03-03 10:13:39");
INSERT INTO transaksi VALUES("327","2026-03-02",NULL,"80","7","20","4","[3,2]","5","200000.00","","2026-03-03 10:14:02","2026-03-03 10:14:02");
INSERT INTO transaksi VALUES("328","2026-03-02",NULL,"81","7","20","4","[3,2]","5","150000.00","","2026-03-03 10:14:27","2026-03-03 10:14:27");
INSERT INTO transaksi VALUES("332","2026-03-02",NULL,"50","7","20","4","[3,2]","5","440000.00","","2026-03-03 10:18:17","2026-03-03 10:18:17");
INSERT INTO transaksi VALUES("333","2026-03-02",NULL,"8","7","20","4","[3,2]","5","1020000.00","","2026-03-03 10:26:53","2026-03-03 10:26:53");
INSERT INTO transaksi VALUES("334","2026-03-02",NULL,"26","7","20","4","[3,2]","5","960000.00","","2026-03-03 10:27:20","2026-03-03 10:27:20");
INSERT INTO transaksi VALUES("335","2026-03-02",NULL,"21","7","20","4","[3,2]","5","150000.00","","2026-03-03 10:27:52","2026-03-03 10:27:52");
INSERT INTO transaksi VALUES("336","2026-03-02",NULL,"52","7","20","4","[3,2]","5","30000.00","","2026-03-03 10:28:20","2026-03-03 10:28:20");
INSERT INTO transaksi VALUES("337","2026-03-02",NULL,"15","7","20","4","[3,2]","5","280000.00","","2026-03-03 10:30:51","2026-03-03 10:30:51");
INSERT INTO transaksi VALUES("339","2026-03-02",NULL,"15","7","20","4","[3,2]","5","1970000.00","","2026-03-03 10:31:46","2026-03-03 10:31:46");
INSERT INTO transaksi VALUES("340","2026-03-02",NULL,"21","7","20","4","[3,2]","5","1100000.00","","2026-03-03 10:32:19","2026-03-03 10:32:19");
INSERT INTO transaksi VALUES("342","2026-03-02",NULL,"38","7","20","4","[3,2]","5","920000.00","","2026-03-03 10:33:17","2026-03-03 10:33:17");
INSERT INTO transaksi VALUES("344","2026-03-02",NULL,"3","7","20","4","[3,2]","5","145770000.00","","2026-03-03 10:35:23","2026-03-03 10:35:23");
INSERT INTO transaksi VALUES("345","2026-03-02",NULL,"8","7","20","4","[3,2]","5","470000.00","","2026-03-03 10:35:53","2026-03-03 10:35:53");
INSERT INTO transaksi VALUES("349","2026-02-27",NULL,"78","7","20","4","[3,2]","5","153000.00","","2026-03-03 12:27:39","2026-03-03 12:27:39");
INSERT INTO transaksi VALUES("350","2026-03-03",NULL,"84","7","20","4","[3,2]","5","255000.00","","2026-03-04 08:09:33","2026-03-04 08:09:33");
INSERT INTO transaksi VALUES("351","2026-03-03",NULL,"33","7","20","4","[3,2]","5","60000.00","","2026-03-04 08:13:26","2026-03-04 08:13:26");
INSERT INTO transaksi VALUES("352","2026-03-03",NULL,"47","7","20","4","[3,2]","5","440000.00","","2026-03-04 08:14:05","2026-03-04 08:14:05");
INSERT INTO transaksi VALUES("354","2026-03-04",NULL,"85","7","20","4","[3,2]","5","230000.00","","2026-03-05 07:56:49","2026-03-05 07:56:49");
INSERT INTO transaksi VALUES("355","2026-03-04",NULL,"50","7","20","4","[3,2]","5","90000.00","","2026-03-05 07:57:24","2026-03-05 07:57:24");
INSERT INTO transaksi VALUES("356","2026-03-05",NULL,"86","7","20","4","[3,2]","5","304000.00","","2026-03-05 09:25:43","2026-03-05 09:25:43");
INSERT INTO transaksi VALUES("358","2026-03-05",NULL,"88","6","11","4","[3,2]","10","1410000.00","","2026-03-05 10:32:58","2026-03-05 10:32:58");
INSERT INTO transaksi VALUES("359","2026-03-05",NULL,"88","6","11","4","[3,2]","10","1360000.00","","2026-03-05 10:33:49","2026-03-05 10:33:49");
INSERT INTO transaksi VALUES("360","2026-03-05",NULL,"54","7","20","4","[3,2]","5","90000.00","","2026-03-06 08:29:02","2026-03-06 08:29:02");
INSERT INTO transaksi VALUES("362","2026-03-05",NULL,"36","7","20","4","[3,2]","5","60000.00","","2026-03-06 08:31:37","2026-03-06 08:31:37");
INSERT INTO transaksi VALUES("363","2026-03-06",NULL,"54","7","20","4","[3,2]","5","680000.00","","2026-03-06 08:32:40","2026-03-06 08:32:40");
INSERT INTO transaksi VALUES("365","2026-03-06","67",NULL,"6","10","4","[3,2]","1","20000.00","","2026-03-06 08:53:24","2026-03-06 08:53:24");
INSERT INTO transaksi VALUES("369","2026-03-06",NULL,"60","6","11","4","[3,2]","1","2325000.00","","2026-03-06 10:38:33","2026-03-06 10:38:33");
INSERT INTO transaksi VALUES("370","2026-03-06",NULL,"90","7","20","4","[3,2]","5","318000.00","","2026-03-09 07:25:37","2026-03-09 07:26:04");
INSERT INTO transaksi VALUES("371","2026-03-06",NULL,"18","7","20","4","[3,2]","5","60000.00","","2026-03-09 07:27:31","2026-03-09 07:27:31");
INSERT INTO transaksi VALUES("373","2026-03-06",NULL,"42","7","20","4","[3,2]","5","60000.00","","2026-03-09 07:36:19","2026-03-09 07:36:19");
INSERT INTO transaksi VALUES("374","2026-03-06",NULL,"55","7","20","4","[3,2]","5","60000.00","","2026-03-09 07:36:56","2026-03-09 07:36:56");
INSERT INTO transaksi VALUES("375","2026-03-06",NULL,"57","7","20","4","[3,2]","5","780000.00","","2026-03-09 07:38:48","2026-03-09 07:38:48");
INSERT INTO transaksi VALUES("376","2026-03-06",NULL,"18","7","20","4","[3,2]","5","1420000.00","","2026-03-09 07:41:20","2026-03-09 07:41:20");
INSERT INTO transaksi VALUES("377","2026-03-06",NULL,"55","7","20","4","[3,2]","5","460000.00","","2026-03-09 07:42:51","2026-03-09 07:42:51");
INSERT INTO transaksi VALUES("378","2026-03-06",NULL,"88","7","20","4","[3,2]","5","1350000.00","","2026-03-09 07:44:06","2026-03-09 07:44:06");
INSERT INTO transaksi VALUES("379","2026-03-07","6",NULL,"7","19","4","[3,2]","5","10000.00","","2026-03-09 07:49:54","2026-03-09 07:49:54");
INSERT INTO transaksi VALUES("380","2026-03-07",NULL,"91","7","20","4","[3,2]","5","183000.00","","2026-03-09 08:33:23","2026-03-09 08:33:23");
INSERT INTO transaksi VALUES("381","2026-03-09",NULL,"92","7","20","4","[3,2]","5","253750.00","","2026-03-09 08:35:44","2026-03-09 08:35:44");
INSERT INTO transaksi VALUES("384","2026-03-09",NULL,"94","7","20","4","[3,2]","5","200000.00","","2026-03-09 08:43:31","2026-03-09 08:43:31");
INSERT INTO transaksi VALUES("385","2026-03-09",NULL,"95","7","20","4","[3,2]","5","110000.00","","2026-03-09 08:44:49","2026-03-09 08:44:49");
INSERT INTO transaksi VALUES("386","2026-03-07",NULL,"92","6","11","4","[3,2]","1","708840.00","","2026-03-09 08:51:38","2026-04-01 13:50:59");
INSERT INTO transaksi VALUES("387","2026-03-09","68",NULL,"6","10","4","[3,2]","1","30000.00","","2026-03-09 08:53:06","2026-03-09 08:53:06");
INSERT INTO transaksi VALUES("388","2026-03-09","68",NULL,"6","10","4","[3,2]","1","30000.00","","2026-03-09 08:53:17","2026-03-09 08:53:17");
INSERT INTO transaksi VALUES("389","2026-03-09","68",NULL,"6","10","4","[3,2]","1","30000.00","","2026-03-09 08:53:27","2026-03-09 08:53:27");
INSERT INTO transaksi VALUES("390","2026-03-09",NULL,"96","6","11","4","[3,2]","1","220000.00","","2026-03-09 08:55:13","2026-03-09 13:55:59");
INSERT INTO transaksi VALUES("392","2026-03-09","70",NULL,"7","19","4","[3,2]","5","10000.00","","2026-03-09 12:16:52","2026-03-09 12:16:52");
INSERT INTO transaksi VALUES("393","2026-03-09",NULL,"101","7","20","4","[3,2]","5","286000.00","","2026-03-09 12:19:27","2026-03-10 14:51:31");
INSERT INTO transaksi VALUES("394","2026-03-09","72",NULL,"8","26","4","[3,2]","1","600000.00","","2026-03-09 12:56:32","2026-03-09 12:56:32");
INSERT INTO transaksi VALUES("395","2026-03-09","73",NULL,"8","26","4","[3,2]","1","900000.00","","2026-03-09 12:57:12","2026-03-09 12:57:12");
INSERT INTO transaksi VALUES("396","2026-03-09","74",NULL,"8","26","4","[3,2]","1","450000.00","","2026-03-09 12:57:52","2026-03-09 12:57:52");
INSERT INTO transaksi VALUES("397","2026-03-09",NULL,"73","7","20","4","[3,2]","5","117000.00","","2026-03-09 13:02:22","2026-03-09 13:02:22");
INSERT INTO transaksi VALUES("400","2026-03-09","75",NULL,"6","10","4","[3,2]","1","200000.00","","2026-03-09 13:33:50","2026-03-09 13:33:50");
INSERT INTO transaksi VALUES("401","2026-03-09",NULL,"11","7","20","4","[3,2]","5","150000.00","","2026-03-09 14:59:37","2026-03-09 14:59:37");
INSERT INTO transaksi VALUES("404","2026-03-09",NULL,"51","7","20","4","[3,2]","5","3100000.00","","2026-03-10 08:36:21","2026-03-10 08:36:21");
INSERT INTO transaksi VALUES("405","2026-03-10","76",NULL,"6","10","4","[3,2]","1","2000.00","","2026-03-10 08:40:47","2026-03-10 08:40:47");
INSERT INTO transaksi VALUES("406","2026-03-09",NULL,"100","11","27","5","[3,2]","11","20000000.00","","2026-03-10 11:32:24","2026-03-10 11:32:24");
INSERT INTO transaksi VALUES("407","2026-03-10",NULL,"12","7","20","4","[3,2]","5","60000.00","","2026-03-11 07:11:14","2026-03-11 07:11:14");
INSERT INTO transaksi VALUES("409","2026-03-10",NULL,"42","7","20","4","[3,2]","5","680000.00","","2026-03-11 07:12:59","2026-03-11 07:12:59");
INSERT INTO transaksi VALUES("411","2026-03-10",NULL,"43","7","20","4","[3,2]","5","490000.00","","2026-03-11 07:18:14","2026-03-11 07:31:52");
INSERT INTO transaksi VALUES("413","2026-03-11",NULL,"103","7","20","4","[3,2]","5","200000.00","","2026-03-11 07:35:27","2026-03-11 07:35:27");
INSERT INTO transaksi VALUES("415","2026-03-11",NULL,"105","7","20","4","[3,2]","5","100000.00","","2026-03-11 07:45:07","2026-03-11 07:45:07");
INSERT INTO transaksi VALUES("416","2026-03-11",NULL,"106","6","11","4","[3,2]","1","100000.00","","2026-03-11 08:17:42","2026-03-11 08:17:42");
INSERT INTO transaksi VALUES("419","2026-03-11",NULL,"40","6","11","4","[3,2]","1","500000.00","","2026-03-11 12:33:57","2026-03-11 12:33:57");
INSERT INTO transaksi VALUES("421","2026-03-11",NULL,"108","6","11","4","[3,2]","1","300000.00","","2026-03-11 12:37:47","2026-03-11 12:37:47");
INSERT INTO transaksi VALUES("422","2026-03-11",NULL,"47","7","20","4","[3,2]","5","40000.00","","2026-03-12 07:43:05","2026-03-12 07:43:05");
INSERT INTO transaksi VALUES("425","2026-03-13",NULL,"109","11","27","4","[3,2]","5","1000000.00","","2026-03-12 08:02:46","2026-03-13 14:38:18");
INSERT INTO transaksi VALUES("426","2026-03-12","6",NULL,"7","19","4","[3,2]","5","50000.00","","2026-03-12 09:54:21","2026-04-02 14:49:19");
INSERT INTO transaksi VALUES("429","2026-03-12",NULL,"110","6","11","4","[3,2]","1","166000.00","","2026-03-12 12:06:11","2026-03-12 12:06:11");
INSERT INTO transaksi VALUES("431","2026-03-12",NULL,"111","7","20","4","[3,2]","5","1050000.00","","2026-03-12 14:32:21","2026-03-12 14:32:21");
INSERT INTO transaksi VALUES("433","2026-03-12",NULL,"47","7","20","4","[3,2]","5","440000.00","","2026-03-13 11:50:57","2026-03-13 11:50:57");
INSERT INTO transaksi VALUES("434","2026-03-13",NULL,"112","7","20","4","[3,2]","5","150000.00","","2026-03-13 11:52:20","2026-03-13 11:52:20");
INSERT INTO transaksi VALUES("435","2026-03-13",NULL,"113","7","20","4","[3,2]","5","200000.00","","2026-03-13 11:53:42","2026-03-13 11:53:42");
INSERT INTO transaksi VALUES("436","2026-03-13",NULL,"114","7","20","4","[3,2]","5","200000.00","","2026-03-13 11:55:17","2026-03-13 11:55:17");
INSERT INTO transaksi VALUES("437","2026-03-13",NULL,"115","7","20","4","[3,2]","5","300000.00","","2026-03-13 11:56:31","2026-03-13 11:56:31");
INSERT INTO transaksi VALUES("438","2026-03-13",NULL,"116","7","20","4","[3,2]","5","300000.00","Kepala Desa dan BPD","2026-03-13 12:05:47","2026-03-13 14:54:51");
INSERT INTO transaksi VALUES("439","2026-03-13",NULL,"116","7","20","4","[3,2]","5","140000.00","BPD Langam","2026-03-13 12:06:21","2026-03-13 12:06:21");
INSERT INTO transaksi VALUES("440","2026-03-13",NULL,"135","7","20","4","[3,2]","5","330000.00","","2026-03-13 12:09:06","2026-03-16 09:44:04");
INSERT INTO transaksi VALUES("442","2026-03-13","81",NULL,"6","10","4","[3,2]","1","200000.00","","2026-03-13 12:39:51","2026-03-13 12:39:51");
INSERT INTO transaksi VALUES("444","2026-03-13",NULL,"118","11","27","4","[3,2]","5","2000000.00","","2026-03-13 14:39:35","2026-03-13 14:39:35");
INSERT INTO transaksi VALUES("445","2026-03-13",NULL,"119","7","20","4","[3,2]","5","4015500.00","","2026-03-13 14:45:43","2026-03-13 14:45:43");
INSERT INTO transaksi VALUES("446","2026-03-13",NULL,"27","7","20","4","[3,2]","5","120000.00","","2026-03-13 14:46:23","2026-03-13 14:46:23");
INSERT INTO transaksi VALUES("447","2026-03-13","87",NULL,"6","10","4","[3,2]","1","5000000.00","","2026-03-13 15:08:47","2026-03-13 15:08:47");
INSERT INTO transaksi VALUES("449","2026-03-13","48",NULL,"6","10","4","[3,2]","1","12000000.00","","2026-03-13 15:14:21","2026-03-13 15:14:21");
INSERT INTO transaksi VALUES("450","2026-03-13","89",NULL,"6","10","4","[3,2]","1","25000000.00","","2026-03-13 15:17:21","2026-03-13 15:17:21");
INSERT INTO transaksi VALUES("451","2026-03-13",NULL,"27","7","20","4","[3,2]","5","1560000.00","","2026-03-14 08:45:53","2026-03-14 08:45:53");
INSERT INTO transaksi VALUES("453","2026-03-14",NULL,"120","7","20","4","[3,2]","5","50000.00","","2026-03-14 09:12:33","2026-03-14 10:11:59");
INSERT INTO transaksi VALUES("456","2026-03-14",NULL,"123","7","20","4","[3,2]","5","150000.00","","2026-03-14 10:17:00","2026-03-14 10:17:00");
INSERT INTO transaksi VALUES("459","2026-03-14",NULL,"124","7","20","4","[3,2]","5","180000.00","","2026-03-14 10:59:30","2026-03-14 10:59:30");
INSERT INTO transaksi VALUES("460","2026-03-14",NULL,"125","7","20","4","[3,2]","5","350000.00","","2026-03-14 11:01:30","2026-03-14 11:01:30");
INSERT INTO transaksi VALUES("461","2026-03-14",NULL,"126","7","20","4","[3,2]","5","250000.00","","2026-03-14 11:05:44","2026-03-14 11:05:44");
INSERT INTO transaksi VALUES("462","2026-03-14",NULL,"127","7","20","4","[3,2]","5","150000.00","","2026-03-14 11:06:21","2026-03-14 11:06:21");
INSERT INTO transaksi VALUES("463","2026-03-14",NULL,"128","7","20","4","[3,2]","5","500000.00","","2026-03-16 07:13:00","2026-03-16 07:13:00");
INSERT INTO transaksi VALUES("464","2026-03-14",NULL,"129","7","20","4","[3,2]","5","350000.00","","2026-03-16 07:15:57","2026-03-16 07:15:57");
INSERT INTO transaksi VALUES("467","2026-03-16",NULL,"132","7","20","4","[3,2]","5","2590000.00","","2026-03-16 07:24:49","2026-03-16 07:24:49");
INSERT INTO transaksi VALUES("468","2026-03-16","92",NULL,"7","19","4","[3,2]","5","500000.00","","2026-03-16 07:34:14","2026-03-16 07:34:14");
INSERT INTO transaksi VALUES("469","2026-03-14","93",NULL,"6","10","4","[3,2]","1","2100000.00","","2026-03-16 07:54:46","2026-03-16 07:54:46");
INSERT INTO transaksi VALUES("470","2026-03-14",NULL,"133","6","11","4","[3,2]","1","250000.00","","2026-03-16 08:02:53","2026-03-16 08:02:53");
INSERT INTO transaksi VALUES("472","2026-03-16",NULL,"85","7","20","4","[3,2]","5","220000.00","","2026-03-16 09:24:04","2026-03-16 09:24:04");
INSERT INTO transaksi VALUES("473","2026-03-16",NULL,"85","7","20","4","[3,2]","5","20000.00","","2026-03-16 09:24:17","2026-03-16 09:24:17");
INSERT INTO transaksi VALUES("474","2026-03-16","94",NULL,"7","19","4","[3,2]","5","15000.00","","2026-03-16 09:30:57","2026-03-16 09:30:57");
INSERT INTO transaksi VALUES("476","2026-03-16",NULL,"136","7","20","4","[3,2]","5","400000.00","","2026-03-16 11:55:01","2026-03-16 11:55:01");
INSERT INTO transaksi VALUES("477","2026-03-16","11",NULL,"6","10","4","[3,2]","1","500000.00","","2026-03-16 12:02:57","2026-03-16 12:02:57");
INSERT INTO transaksi VALUES("483","2026-03-17",NULL,"140","7","20","4","[3,2]","5","200000.00","","2026-03-17 12:23:36","2026-03-17 12:23:36");
INSERT INTO transaksi VALUES("486","2026-03-17","97",NULL,"7","19","4","[3,2]","5","35000.00","","2026-03-17 12:39:21","2026-03-17 12:53:12");
INSERT INTO transaksi VALUES("489","2026-03-17","100",NULL,"7","19","4","[3,2]","5","70000.00","","2026-03-17 12:44:59","2026-03-17 12:54:15");
INSERT INTO transaksi VALUES("490","2026-03-17","103",NULL,"7","19","4","[3,2]","5","90000.00","","2026-03-17 12:49:57","2026-03-17 12:54:35");
INSERT INTO transaksi VALUES("491","2026-03-17","104",NULL,"7","19","4","[3,2]","5","35000.00","","2026-03-17 12:52:34","2026-03-17 12:54:58");
INSERT INTO transaksi VALUES("492","2026-03-17",NULL,"143","7","20","4","[3,2]","5","1020000.00","","2026-03-17 12:56:05","2026-03-17 12:56:05");
INSERT INTO transaksi VALUES("493","2026-03-17",NULL,"144","7","20","4","[3,2]","5","120000.00","","2026-03-18 07:20:51","2026-03-18 07:20:51");
INSERT INTO transaksi VALUES("494","2026-03-17",NULL,"145","7","20","4","[3,2]","5","600000.00","","2026-03-18 07:25:05","2026-03-18 07:25:05");
INSERT INTO transaksi VALUES("495","2026-03-17","41",NULL,"6","10","4","[3,2]","1","1700000.00","","2026-03-18 07:28:20","2026-03-18 07:28:20");
INSERT INTO transaksi VALUES("496","2026-03-17",NULL,"146","6","11","4","[3,2]","1","520000.00","","2026-03-18 07:31:42","2026-03-18 07:31:42");
INSERT INTO transaksi VALUES("497","2026-03-18","105",NULL,"6","10","4","[3,2]","1","20000.00","","2026-03-18 10:09:57","2026-03-18 10:09:57");
INSERT INTO transaksi VALUES("498","2026-03-18",NULL,"148","7","20","4","[3,2]","5","100000.00","","2026-03-18 10:12:46","2026-03-18 10:12:46");
INSERT INTO transaksi VALUES("500","2026-03-19",NULL,"149","7","20","4","[3,2]","5","300000.00","","2026-03-23 17:01:44","2026-03-23 17:01:44");
INSERT INTO transaksi VALUES("502","2026-03-20",NULL,"140","7","20","4","[3,2]","5","400000.00","","2026-03-23 17:05:36","2026-03-23 17:05:36");
INSERT INTO transaksi VALUES("504","2026-03-20","6",NULL,"7","19","4","[3,2]","5","100000.00","","2026-03-23 17:18:47","2026-03-23 17:18:47");
INSERT INTO transaksi VALUES("505","2026-03-20","6",NULL,"7","19","4","[3,2]","5","100000.00","","2026-03-23 17:19:40","2026-03-23 17:19:40");
INSERT INTO transaksi VALUES("510","2026-03-22","111",NULL,"7","18","4","[3,2]","5","1000000.00","","2026-03-23 17:27:16","2026-03-23 17:27:16");
INSERT INTO transaksi VALUES("511","2026-03-22","106",NULL,"7","18","4","[3,2]","5","150000.00","","2026-03-23 17:28:10","2026-03-23 17:28:10");
INSERT INTO transaksi VALUES("513","2026-03-18","114",NULL,"6","10","4","[3,2]","9","4000000.00","","2026-03-23 17:50:39","2026-03-23 17:50:39");
INSERT INTO transaksi VALUES("515","2026-03-25",NULL,"61","7","20","4","[3,2]","5","1493236.00","","2026-03-30 07:26:21","2026-03-30 07:26:21");
INSERT INTO transaksi VALUES("516","2026-03-25",NULL,"64","7","20","4","[3,2]","5","254000.00","","2026-03-30 07:27:37","2026-03-30 07:27:37");
INSERT INTO transaksi VALUES("517","2026-03-25",NULL,"63","7","20","4","[3,2]","5","549253.00","","2026-03-30 07:28:44","2026-03-30 07:28:44");
INSERT INTO transaksi VALUES("518","2026-03-25",NULL,"62","7","20","4","[3,2]","5","100000.00","","2026-03-30 07:30:00","2026-03-30 07:30:00");
INSERT INTO transaksi VALUES("519","2026-03-25",NULL,"65","7","20","4","[3,2]","5","953247.00","","2026-03-30 07:31:23","2026-03-30 07:31:23");
INSERT INTO transaksi VALUES("521","2026-03-30",NULL,"151","7","20","4","[3,2]","5","1650000.00","","2026-03-31 07:22:50","2026-03-31 07:22:50");
INSERT INTO transaksi VALUES("522","2026-01-10","6",NULL,"7","19","4","[3,2]","5","10000.00","","2026-03-31 09:24:35","2026-03-31 09:24:35");
INSERT INTO transaksi VALUES("523","2026-01-13",NULL,"4","7","20","4","[3,2]","5","61690000.00","","2026-03-31 09:26:14","2026-03-31 09:26:14");
INSERT INTO transaksi VALUES("524","2026-01-22",NULL,"60","7","20","4","[3,2]","5","445000.00","","2026-03-31 09:27:29","2026-03-31 09:27:29");
INSERT INTO transaksi VALUES("525","2026-01-26",NULL,"65","7","20","4","[3,2]","5","1033064.00","","2026-03-31 09:28:10","2026-03-31 09:28:10");
INSERT INTO transaksi VALUES("526","2026-01-29",NULL,"21","7","20","4","[3,2]","5","757000.00","","2026-03-31 09:28:58","2026-03-31 09:28:58");
INSERT INTO transaksi VALUES("528","2026-01-08",NULL,"7","6","11","4","[3,2]","1","1470000.00","","2026-03-31 09:42:58","2026-03-31 09:42:58");
INSERT INTO transaksi VALUES("529","2026-01-12",NULL,"28","6","11","4","[3,2]","1","2340000.00","","2026-03-31 09:43:48","2026-03-31 09:43:48");
INSERT INTO transaksi VALUES("530","2026-01-12",NULL,"38","6","11","4","[3,2]","1","920000.00","","2026-03-31 09:44:28","2026-03-31 09:48:57");
INSERT INTO transaksi VALUES("545","2026-02-03",NULL,"17","7","20","4","[3,2]","5","320000.00","","2026-03-31 13:42:04","2026-03-31 13:42:04");
INSERT INTO transaksi VALUES("546","2026-02-04",NULL,"41","7","20","4","[3,2]","5","60000.00","","2026-03-31 13:51:57","2026-03-31 13:51:57");
INSERT INTO transaksi VALUES("547","2026-02-04","27",NULL,"7","19","4","[3,2]","5","20000.00","","2026-03-31 13:58:14","2026-04-02 11:24:42");
INSERT INTO transaksi VALUES("548","2026-02-05",NULL,"71","7","20","4","[3,2]","5","1000000.00","","2026-03-31 14:04:58","2026-03-31 14:04:58");
INSERT INTO transaksi VALUES("549","2026-02-09",NULL,"73","7","20","4","[3,2]","5","326000.00","","2026-03-31 14:08:04","2026-03-31 14:08:04");
INSERT INTO transaksi VALUES("551","2026-02-23",NULL,"40","7","20","4","[3,2]","5","60000.00","","2026-03-31 14:17:43","2026-03-31 14:17:43");
INSERT INTO transaksi VALUES("552","2026-02-24",NULL,"11","7","20","4","[3,2]","5","2160000.00","","2026-03-31 14:18:43","2026-03-31 14:19:33");
INSERT INTO transaksi VALUES("553","2026-02-26",NULL,"9","7","20","4","[3,2]","5","1220000.00","","2026-03-31 14:20:46","2026-03-31 14:20:46");
INSERT INTO transaksi VALUES("554","2026-02-25",NULL,"65","7","20","4","[3,2]","5","1033064.00","","2026-03-31 14:22:50","2026-03-31 14:22:50");
INSERT INTO transaksi VALUES("556","2026-02-11","48",NULL,"7","19","4","[3,2]","5","500000.00","","2026-03-31 14:44:37","2026-04-02 11:23:26");
INSERT INTO transaksi VALUES("558","2026-02-04","35",NULL,"7","19","4","[3,2]","5","10000.00","","2026-03-31 14:52:21","2026-04-02 11:23:01");
INSERT INTO transaksi VALUES("559","2026-01-15",NULL,"15","7","20","4","[3,2]","5","280000.00","","2026-04-01 09:57:52","2026-04-01 09:57:52");
INSERT INTO transaksi VALUES("560","2026-01-27","6",NULL,"7","19","4","[3,2]","5","20000.00","","2026-04-01 09:59:44","2026-04-01 09:59:44");
INSERT INTO transaksi VALUES("561","2026-01-05","6",NULL,"7","19","4","[3,2]","5","1.00","","2026-04-01 10:17:39","2026-04-01 10:17:39");
INSERT INTO transaksi VALUES("562","2026-01-20","6",NULL,"6","10","4","[3,2]","10","530000.00","","2026-04-01 10:48:47","2026-04-01 11:19:51");
INSERT INTO transaksi VALUES("563","2026-01-08",NULL,"17","6","11","4","[3,2]","1","320000.00","","2026-04-01 10:58:07","2026-04-01 10:58:07");
INSERT INTO transaksi VALUES("564","2026-01-08",NULL,"7","6","11","4","[3,2]","1","2000000.00","","2026-04-01 11:03:07","2026-04-01 11:03:07");
INSERT INTO transaksi VALUES("565","2026-01-12",NULL,"38","6","11","4","[3,2]","1","30000.00","","2026-04-01 11:08:01","2026-04-01 11:08:01");
INSERT INTO transaksi VALUES("566","2026-01-12",NULL,"14","6","11","4","[3,2]","1","770000.00","","2026-04-01 11:09:04","2026-04-01 11:20:51");
INSERT INTO transaksi VALUES("577","2026-02-04","31",NULL,"7","19","4","[3,2]","5","100000.00","","2026-04-01 11:43:44","2026-04-01 12:38:55");
INSERT INTO transaksi VALUES("578","2026-02-04","33",NULL,"7","19","4","[3,2]","5","100000.00","","2026-04-01 11:44:07","2026-04-01 12:39:19");
INSERT INTO transaksi VALUES("579","2026-02-04","34",NULL,"7","19","4","[3,2]","5","50000.00","","2026-04-01 11:45:01","2026-04-01 12:40:04");
INSERT INTO transaksi VALUES("580","2026-02-13","118",NULL,"7","19","4","[3,2]","5","100000.00","","2026-04-01 11:49:02","2026-04-01 12:31:05");
INSERT INTO transaksi VALUES("581","2026-02-23",NULL,"77","7","20","4","[3,2]","5","320000.00","","2026-04-01 11:51:05","2026-04-01 12:32:13");
INSERT INTO transaksi VALUES("582","2026-02-23",NULL,"36","7","20","4","[3,2]","5","60000.00","","2026-04-01 11:52:44","2026-04-01 12:33:28");
INSERT INTO transaksi VALUES("583","2026-02-25",NULL,"12","7","20","4","[3,2]","5","2970000.00","","2026-04-01 11:53:32","2026-04-01 12:34:18");
INSERT INTO transaksi VALUES("584","2026-02-26",NULL,"18","7","20","4","[3,2]","5","1420000.00","","2026-04-01 11:54:19","2026-04-01 12:35:41");
INSERT INTO transaksi VALUES("588","2026-02-03",NULL,"48","7","20","4","[3,2]","5","550000.00","","2026-04-01 12:43:09","2026-04-01 12:43:09");
INSERT INTO transaksi VALUES("591","2026-03-02",NULL,"17","7","20","4","[3,2]","5","320000.00","","2026-04-01 13:53:41","2026-04-01 13:53:41");
INSERT INTO transaksi VALUES("592","2026-03-02",NULL,"32","7","20","4","[3,2]","5","3980000.00","","2026-04-01 13:54:36","2026-04-01 13:54:36");
INSERT INTO transaksi VALUES("593","2026-03-02",NULL,"13","7","20","4","[3,2]","5","1642000.00","","2026-04-01 13:55:19","2026-04-01 13:55:19");
INSERT INTO transaksi VALUES("594","2026-03-02",NULL,"45","7","20","4","[3,2]","5","60000.00","","2026-04-01 14:00:10","2026-04-01 14:00:10");
INSERT INTO transaksi VALUES("595","2026-03-02",NULL,"13","7","20","4","[3,2]","5","50000.00","","2026-04-01 14:01:04","2026-04-01 14:01:04");
INSERT INTO transaksi VALUES("596","2026-03-02",NULL,"39","7","20","4","[3,2]","5","580000.00","","2026-04-01 14:02:00","2026-04-01 14:02:00");
INSERT INTO transaksi VALUES("597","2026-03-02",NULL,"7","7","20","4","[3,2]","5","1470000.00","","2026-04-01 14:03:49","2026-04-01 14:03:49");
INSERT INTO transaksi VALUES("598","2026-03-02",NULL,"19","7","20","4","[3,2]","5","2630000.00","","2026-04-01 14:04:33","2026-04-01 14:04:33");
INSERT INTO transaksi VALUES("599","2026-03-02",NULL,"35","7","20","4","[3,2]","5","90000.00","","2026-04-01 14:05:44","2026-04-01 14:05:44");
INSERT INTO transaksi VALUES("600","2026-02-06","46",NULL,"6","10","4","[3,2]","1","100000.00","","2026-04-01 14:06:20","2026-04-01 14:06:20");
INSERT INTO transaksi VALUES("601","2026-03-02",NULL,"41","7","20","4","[3,2]","5","500000.00","","2026-04-01 14:06:33","2026-04-01 14:06:33");
INSERT INTO transaksi VALUES("602","2026-03-02",NULL,"41","7","20","4","[3,2]","5","60000.00","","2026-04-01 14:14:02","2026-04-01 14:14:02");
INSERT INTO transaksi VALUES("603","2026-02-19","6",NULL,"6","10","4","[3,2]","1","200000.00","","2026-04-01 14:14:27","2026-04-01 14:14:27");
INSERT INTO transaksi VALUES("604","2026-02-28",NULL,"83","6","11","4","[3,2]","1","330000.00","","2026-04-01 14:16:17","2026-04-01 14:16:17");
INSERT INTO transaksi VALUES("605","2026-03-02",NULL,"6","7","20","4","[3,2]","5","80000.00","","2026-04-01 14:16:25","2026-04-01 14:16:25");
INSERT INTO transaksi VALUES("606","2026-03-02",NULL,"14","7","20","4","[3,2]","5","2840000.00","","2026-04-01 14:17:02","2026-04-01 14:17:02");
INSERT INTO transaksi VALUES("607","2026-03-02",NULL,"3","7","20","4","[3,2]","5","161010000.00","","2026-04-01 14:18:30","2026-04-01 14:18:30");
INSERT INTO transaksi VALUES("608","2026-03-02",NULL,"56","7","20","4","[3,2]","5","120000.00","","2026-04-01 14:21:26","2026-04-01 14:21:26");
INSERT INTO transaksi VALUES("609","2026-03-02",NULL,"37","7","20","4","[3,2]","5","60000.00","","2026-04-01 14:22:08","2026-04-01 14:22:08");
INSERT INTO transaksi VALUES("610","2026-03-02",NULL,"38","7","20","4","[3,2]","5","30000.00","","2026-04-01 14:22:40","2026-04-01 14:22:40");
INSERT INTO transaksi VALUES("611","2026-03-02",NULL,"56","7","20","4","[3,2]","5","420000.00","","2026-04-01 14:27:27","2026-04-01 14:27:27");
INSERT INTO transaksi VALUES("612","2026-03-02",NULL,"37","7","20","4","[3,2]","5","640000.00","","2026-04-01 14:28:14","2026-04-01 14:28:14");
INSERT INTO transaksi VALUES("613","2026-03-03",NULL,"33","7","20","4","[3,2]","5","390000.00","","2026-04-01 14:29:04","2026-04-01 14:29:04");
INSERT INTO transaksi VALUES("614","2026-03-05",NULL,"87","7","20","4","[3,2]","5","200000.00","","2026-04-01 14:30:47","2026-04-01 14:30:47");
INSERT INTO transaksi VALUES("615","2026-03-05",NULL,"36","7","20","4","[3,2]","5","711000.00","","2026-04-01 14:31:31","2026-04-01 14:31:31");
INSERT INTO transaksi VALUES("616","2026-03-06",NULL,"57","7","20","4","[3,2]","5","30000.00","","2026-04-01 14:32:35","2026-04-01 14:32:35");
INSERT INTO transaksi VALUES("617","2026-03-07",NULL,"93","7","20","4","[3,2]","5","300000.00","","2026-04-01 14:35:29","2026-04-01 14:35:29");
INSERT INTO transaksi VALUES("618","2026-03-07",NULL,"77","7","20","4","[3,2]","5","279000.00","","2026-04-01 14:36:04","2026-04-01 14:36:04");
INSERT INTO transaksi VALUES("619","2026-03-09",NULL,"98","7","20","4","[3,2]","5","300000.00","","2026-04-01 14:41:43","2026-04-01 14:41:43");
INSERT INTO transaksi VALUES("620","2026-03-09",NULL,"51","7","20","4","[3,2]","5","10000.00","","2026-04-01 14:43:21","2026-04-01 14:43:21");
INSERT INTO transaksi VALUES("621","2026-03-09",NULL,"11","7","20","4","[3,2]","5","2010000.00","","2026-04-01 14:44:03","2026-04-01 14:44:03");
INSERT INTO transaksi VALUES("622","2026-03-10",NULL,"43","7","20","4","[3,2]","5","30000.00","","2026-04-01 14:45:35","2026-04-01 14:45:35");
INSERT INTO transaksi VALUES("623","2026-03-10",NULL,"12","7","20","4","[3,2]","5","2970000.00","","2026-04-01 14:46:22","2026-04-01 14:46:22");
INSERT INTO transaksi VALUES("624","2026-02-02","19",NULL,"7","19","4","[3,2]","5","200000.00","","2026-04-02 08:31:46","2026-04-02 08:31:46");
INSERT INTO transaksi VALUES("625","2026-02-02","18",NULL,"7","19","4","[3,2]","5","200000.00","","2026-04-02 08:32:14","2026-04-02 08:32:14");
INSERT INTO transaksi VALUES("626","2026-02-02",NULL,"32","7","20","4","[3,2]","5","3980000.00","","2026-04-02 08:38:40","2026-04-02 08:38:40");
INSERT INTO transaksi VALUES("627","2026-02-02",NULL,"15","7","20","4","[3,2]","5","1990000.00","","2026-04-02 08:40:18","2026-04-02 08:40:18");
INSERT INTO transaksi VALUES("628","2026-02-02",NULL,"15","7","20","4","[3,2]","5","280000.00","","2026-04-02 08:40:45","2026-04-02 08:40:45");
INSERT INTO transaksi VALUES("629","2026-02-02",NULL,"29","7","20","4","[3,2]","5","3370000.00","","2026-04-02 08:41:16","2026-04-02 08:41:16");
INSERT INTO transaksi VALUES("630","2026-02-02",NULL,"22","7","20","4","[3,2]","5","1950000.00","","2026-04-02 08:41:57","2026-04-02 08:41:57");
INSERT INTO transaksi VALUES("631","2026-02-02",NULL,"31","7","20","4","[3,2]","5","2640000.00","","2026-04-02 08:42:23","2026-04-02 08:42:23");
INSERT INTO transaksi VALUES("632","2026-02-02",NULL,"22","7","20","4","[3,2]","5","80000.00","","2026-04-02 08:43:04","2026-04-02 08:43:04");
INSERT INTO transaksi VALUES("633","2026-02-02",NULL,"21","7","20","4","[3,2]","5","1100000.00","","2026-04-02 08:44:05","2026-04-02 08:44:05");
INSERT INTO transaksi VALUES("634","2026-02-02",NULL,"21","7","20","4","[3,2]","5","150000.00","","2026-04-02 08:44:37","2026-04-02 08:44:37");
INSERT INTO transaksi VALUES("635","2026-02-02",NULL,"20","7","20","4","[3,2]","5","1810000.00","","2026-04-02 08:45:33","2026-04-02 08:45:33");
INSERT INTO transaksi VALUES("636","2026-02-02",NULL,"20","7","20","4","[3,2]","5","200000.00","","2026-04-02 08:45:56","2026-04-02 08:45:56");
INSERT INTO transaksi VALUES("637","2026-03-09","119",NULL,"8","26","4","[3,2]","1","900000.00","","2026-04-02 08:46:01","2026-04-02 08:46:01");
INSERT INTO transaksi VALUES("638","2026-02-02",NULL,"30","7","20","4","[3,2]","5","2627000.00","","2026-04-02 08:46:29","2026-04-02 08:46:29");
INSERT INTO transaksi VALUES("639","2026-02-02",NULL,"57","7","20","4","[3,2]","5","30000.00","","2026-04-02 08:46:52","2026-04-02 08:46:52");
INSERT INTO transaksi VALUES("640","2026-03-12","78",NULL,"8","26","4","[3,2]","1","900000.00","","2026-04-02 08:47:22","2026-04-02 08:47:22");
INSERT INTO transaksi VALUES("641","2026-02-02",NULL,"6","7","20","4","[3,2]","5","80000.00","","2026-04-02 08:47:26","2026-04-02 08:47:26");
INSERT INTO transaksi VALUES("642","2026-02-02",NULL,"43","7","20","4","[3,2]","5","30000.00","","2026-04-02 08:51:04","2026-04-02 08:51:04");
INSERT INTO transaksi VALUES("643","2026-02-02",NULL,"24","7","20","4","[3,2]","5","50000.00","","2026-04-02 08:51:32","2026-04-02 08:51:32");
INSERT INTO transaksi VALUES("644","2026-02-02",NULL,"52","7","20","4","[3,2]","5","30000.00","","2026-04-02 08:51:50","2026-04-02 08:51:50");
INSERT INTO transaksi VALUES("645","2026-02-02",NULL,"38","7","20","4","[3,2]","5","30000.00","","2026-04-02 08:52:08","2026-04-02 08:52:08");
INSERT INTO transaksi VALUES("646","2026-02-02",NULL,"33","7","20","4","[3,2]","5","120000.00","","2026-04-02 08:52:46","2026-04-02 08:52:46");
INSERT INTO transaksi VALUES("647","2026-03-13","120",NULL,"8","26","4","[3,2]","1","300000.00","","2026-04-02 08:53:20","2026-04-02 08:53:20");
INSERT INTO transaksi VALUES("648","2026-02-02",NULL,"50","7","20","4","[3,2]","5","440000.00","","2026-04-02 08:53:27","2026-04-02 08:53:27");
INSERT INTO transaksi VALUES("649","2026-02-02",NULL,"44","7","20","4","[3,2]","5","90000.00","","2026-04-02 08:53:53","2026-04-02 08:53:53");
INSERT INTO transaksi VALUES("650","2026-02-02",NULL,"45","7","20","4","[3,2]","5","60000.00","","2026-04-02 08:54:11","2026-04-02 08:54:11");
INSERT INTO transaksi VALUES("651","2026-02-02",NULL,"39","7","20","4","[3,2]","5","60000.00","","2026-04-02 08:54:33","2026-04-02 08:54:33");
INSERT INTO transaksi VALUES("652","2026-02-02",NULL,"46","7","20","4","[3,2]","5","60000.00","","2026-04-02 08:54:56","2026-04-02 08:54:56");
INSERT INTO transaksi VALUES("653","2026-02-02",NULL,"53","7","20","4","[3,2]","5","60000.00","","2026-04-02 08:55:16","2026-04-02 08:55:16");
INSERT INTO transaksi VALUES("654","2026-02-02",NULL,"55","7","20","4","[3,2]","5","60000.00","","2026-04-02 08:55:42","2026-04-02 08:55:42");
INSERT INTO transaksi VALUES("655","2026-02-02",NULL,"28","7","20","4","[3,2]","5","180000.00","","2026-04-02 08:56:41","2026-04-02 08:56:41");
INSERT INTO transaksi VALUES("656","2026-02-02",NULL,"35","7","20","4","[3,2]","5","90000.00","","2026-04-02 08:57:07","2026-04-02 08:57:07");
INSERT INTO transaksi VALUES("657","2026-02-02",NULL,"49","7","20","4","[3,2]","5","120000.00","","2026-04-02 08:57:38","2026-04-02 08:57:38");
INSERT INTO transaksi VALUES("658","2026-02-02",NULL,"48","7","20","4","[3,2]","5","90000.00","","2026-04-02 08:57:54","2026-04-02 08:57:54");
INSERT INTO transaksi VALUES("659","2026-02-02",NULL,"5","7","20","4","[3,2]","5","50000.00","","2026-04-02 08:58:31","2026-04-02 08:58:31");
INSERT INTO transaksi VALUES("660","2026-02-02",NULL,"16","7","20","4","[3,2]","5","110000.00","","2026-04-02 08:59:34","2026-04-02 08:59:34");
INSERT INTO transaksi VALUES("661","2026-02-02",NULL,"34","7","20","4","[3,2]","5","60000.00","","2026-04-02 09:02:23","2026-04-02 09:02:23");
INSERT INTO transaksi VALUES("662","2026-02-02",NULL,"31","7","20","4","[3,2]","5","260000.00","","2026-04-02 09:03:04","2026-04-02 09:03:04");
INSERT INTO transaksi VALUES("663","2026-02-02",NULL,"44","7","20","4","[3,2]","5","390000.00","","2026-04-02 09:03:30","2026-04-02 09:03:30");
INSERT INTO transaksi VALUES("664","2026-02-02",NULL,"39","7","20","4","[3,2]","5","580000.00","","2026-04-02 09:04:02","2026-04-02 09:04:02");
INSERT INTO transaksi VALUES("665","2026-02-02",NULL,"14","7","20","4","[3,2]","5","750000.00","","2026-04-02 09:05:28","2026-04-02 09:05:28");
INSERT INTO transaksi VALUES("666","2026-02-02",NULL,"14","7","20","4","[3,2]","5","2800000.00","","2026-04-02 09:06:12","2026-04-02 09:06:12");
INSERT INTO transaksi VALUES("667","2026-02-02",NULL,"38","7","20","4","[3,2]","5","920000.00","","2026-04-02 09:07:39","2026-04-02 09:07:39");
INSERT INTO transaksi VALUES("668","2026-02-02",NULL,"16","7","20","4","[3,2]","5","1420000.00","","2026-04-02 09:08:18","2026-04-02 09:08:18");
INSERT INTO transaksi VALUES("669","2026-02-02",NULL,"17","7","20","4","[3,2]","5","1940000.00","","2026-04-02 09:08:57","2026-04-02 09:08:57");
INSERT INTO transaksi VALUES("670","2026-02-02",NULL,"46","7","20","4","[3,2]","5","810000.00","","2026-04-02 09:09:29","2026-04-02 09:09:29");
INSERT INTO transaksi VALUES("671","2026-02-02",NULL,"52","7","20","4","[3,2]","5","740000.00","","2026-04-02 09:09:54","2026-04-02 09:09:54");
INSERT INTO transaksi VALUES("672","2026-02-02",NULL,"53","7","20","4","[3,2]","5","710000.00","","2026-04-02 09:10:24","2026-04-02 09:10:24");
INSERT INTO transaksi VALUES("673","2026-02-02",NULL,"41","7","20","4","[3,2]","5","500000.00","","2026-04-02 09:10:46","2026-04-02 09:10:46");
INSERT INTO transaksi VALUES("674","2026-02-02",NULL,"5","7","20","4","[3,2]","5","3540000.00","","2026-04-02 09:11:35","2026-04-02 09:11:35");
INSERT INTO transaksi VALUES("675","2026-02-02",NULL,"7","7","20","4","[3,2]","5","2000000.00","","2026-04-02 09:13:01","2026-04-02 09:13:01");
INSERT INTO transaksi VALUES("676","2026-02-02",NULL,"7","7","20","4","[3,2]","5","1470000.00","","2026-04-02 09:13:20","2026-04-02 09:13:20");
INSERT INTO transaksi VALUES("677","2026-02-02",NULL,"33","7","20","4","[3,2]","5","720000.00","","2026-04-02 09:13:42","2026-04-02 09:13:42");
INSERT INTO transaksi VALUES("678","2026-02-02",NULL,"10","7","20","4","[3,2]","5","750000.00","","2026-04-02 09:14:20","2026-04-02 09:14:20");
INSERT INTO transaksi VALUES("679","2026-02-02",NULL,"13","7","20","4","[3,2]","5","1642000.00","","2026-04-02 09:15:07","2026-04-02 09:15:07");
INSERT INTO transaksi VALUES("680","2026-02-02",NULL,"35","7","20","4","[3,2]","5","530000.00","","2026-04-02 09:15:29","2026-04-02 09:15:29");
INSERT INTO transaksi VALUES("681","2026-02-02",NULL,"23","7","20","4","[3,2]","5","2920000.00","","2026-04-02 09:15:58","2026-04-02 09:15:58");
INSERT INTO transaksi VALUES("682","2026-02-02",NULL,"24","7","20","4","[3,2]","5","2630000.00","","2026-04-02 09:16:25","2026-04-02 09:16:25");
INSERT INTO transaksi VALUES("683","2026-02-02",NULL,"25","7","20","4","[3,2]","5","630000.00","","2026-04-02 09:17:04","2026-04-02 09:17:04");
INSERT INTO transaksi VALUES("684","2026-02-02",NULL,"34","7","20","4","[3,2]","5","730000.00","","2026-04-02 09:17:41","2026-04-02 09:17:41");
INSERT INTO transaksi VALUES("685","2026-02-02",NULL,"45","7","20","4","[3,2]","5","1030000.00","","2026-04-02 09:18:07","2026-04-02 09:18:07");
INSERT INTO transaksi VALUES("686","2026-02-02",NULL,"56","7","20","4","[3,2]","5","420000.00","","2026-04-02 09:18:28","2026-04-02 09:18:28");
INSERT INTO transaksi VALUES("687","2026-02-02",NULL,"8","7","20","4","[3,2]","5","470000.00","","2026-04-02 09:18:54","2026-04-02 09:18:54");
INSERT INTO transaksi VALUES("688","2026-02-02",NULL,"19","7","20","4","[3,2]","5","2630000.00","","2026-04-02 09:19:29","2026-04-02 09:19:29");
INSERT INTO transaksi VALUES("689","2026-02-02",NULL,"23","7","20","4","[3,2]","5","340000.00","","2026-04-02 09:19:58","2026-04-02 09:19:58");
INSERT INTO transaksi VALUES("690","2026-02-02",NULL,"25","7","20","4","[3,2]","5","4980000.00","","2026-04-02 09:20:29","2026-04-02 09:20:29");
INSERT INTO transaksi VALUES("691","2026-02-02",NULL,"28","7","20","4","[3,2]","5","2290000.00","","2026-04-02 09:20:56","2026-04-02 09:20:56");
INSERT INTO transaksi VALUES("692","2026-02-02",NULL,"43","7","20","4","[3,2]","5","490000.00","","2026-04-02 09:21:15","2026-04-02 09:21:15");
INSERT INTO transaksi VALUES("693","2026-02-02",NULL,"49","7","20","4","[3,2]","5","450000.00","","2026-04-02 09:21:42","2026-04-02 09:21:42");
INSERT INTO transaksi VALUES("694","2026-02-02",NULL,"55","7","20","4","[3,2]","5","460000.00","","2026-04-02 09:22:16","2026-04-02 09:22:16");
INSERT INTO transaksi VALUES("695","2026-02-02",NULL,"56","7","20","4","[3,2]","5","120000.00","","2026-04-02 09:22:47","2026-04-02 09:22:47");
INSERT INTO transaksi VALUES("696","2026-02-02",NULL,"6","7","20","4","[3,2]","5","1500000.00","","2026-04-02 09:23:09","2026-04-02 09:23:09");
INSERT INTO transaksi VALUES("697","2026-02-02",NULL,"8","7","20","4","[3,2]","5","1020000.00","","2026-04-02 09:23:40","2026-04-02 09:23:40");
INSERT INTO transaksi VALUES("698","2026-02-02",NULL,"19","7","20","4","[3,2]","5","380000.00","","2026-04-02 09:24:17","2026-04-02 09:24:17");
INSERT INTO transaksi VALUES("699","2026-02-02",NULL,"57","7","20","4","[3,2]","5","780000.00","","2026-04-02 09:24:46","2026-04-02 09:24:46");
INSERT INTO transaksi VALUES("700","2026-02-02",NULL,"26","7","20","4","[3,2]","5","6670000.00","","2026-04-02 09:25:58","2026-04-02 09:25:58");
INSERT INTO transaksi VALUES("701","2026-02-02",NULL,"3","7","20","4","[3,2]","5","145770000.00","","2026-04-02 09:26:31","2026-04-02 09:26:31");
INSERT INTO transaksi VALUES("707","2026-03-02",NULL,"44","7","20","4","[3,2]","5","390000.00","","2026-04-02 10:27:06","2026-04-02 10:27:06");
INSERT INTO transaksi VALUES("708","2026-03-02",NULL,"52","7","20","4","[3,2]","5","740000.00","","2026-04-02 10:30:08","2026-04-02 10:30:08");
INSERT INTO transaksi VALUES("709","2026-03-06",NULL,"60","7","20","4","[3,2]","5","445000.00","","2026-04-02 10:31:20","2026-04-02 10:31:20");
INSERT INTO transaksi VALUES("710","2026-03-06",NULL,"60","7","20","4","[3,2]","5","445000.00","","2026-04-02 10:31:43","2026-04-02 10:31:43");
INSERT INTO transaksi VALUES("711","2026-03-11",NULL,"102","7","20","4","[3,2]","5","200000.00","","2026-04-02 10:32:54","2026-04-02 10:32:54");
INSERT INTO transaksi VALUES("712","2026-03-11",NULL,"104","7","20","4","[3,2]","5","100000.00","","2026-04-02 10:33:26","2026-04-02 10:33:26");
INSERT INTO transaksi VALUES("713","2026-03-11",NULL,"107","7","20","4","[3,2]","5","300000.00","","2026-04-02 10:34:02","2026-04-02 10:34:02");
INSERT INTO transaksi VALUES("714","2026-03-11",NULL,"9","7","20","4","[3,2]","5","50000.00","","2026-04-02 10:34:42","2026-04-02 10:34:42");
INSERT INTO transaksi VALUES("715","2026-03-11",NULL,"9","7","20","4","[3,2]","5","1220000.00","","2026-04-02 10:35:39","2026-04-02 10:35:39");
INSERT INTO transaksi VALUES("716","2026-03-12",NULL,"47","7","20","4","[3,2]","5","40000.00","","2026-04-02 10:36:07","2026-04-02 10:36:07");
INSERT INTO transaksi VALUES("717","2026-03-14",NULL,"121","7","20","4","[3,2]","5","225000.00","","2026-04-02 10:37:39","2026-04-02 10:37:39");
INSERT INTO transaksi VALUES("718","2026-03-14",NULL,"122","7","20","4","[3,2]","5","225000.00","","2026-04-02 10:38:05","2026-04-02 10:38:05");
INSERT INTO transaksi VALUES("719","2026-03-14",NULL,"120","7","20","4","[3,2]","5","450000.00","","2026-04-02 10:40:33","2026-04-02 10:40:33");
INSERT INTO transaksi VALUES("720","2026-03-17",NULL,"142","7","20","4","[3,2]","5","100000.00","","2026-04-02 10:44:10","2026-04-02 10:44:10");
INSERT INTO transaksi VALUES("721","2026-03-17","98",NULL,"7","19","4","[3,2]","5","45000.00","","2026-04-02 10:45:05","2026-04-02 10:45:05");
INSERT INTO transaksi VALUES("722","2026-03-17","99",NULL,"7","19","4","[3,2]","5","1000000.00","","2026-04-02 10:46:24","2026-04-02 10:46:24");
INSERT INTO transaksi VALUES("723","2026-03-19","6",NULL,"7","19","4","[3,2]","5","270000.00","","2026-04-02 10:47:10","2026-04-02 10:47:10");
INSERT INTO transaksi VALUES("725","2026-03-20","6",NULL,"7","19","4","[3,2]","5","100000.00","","2026-04-02 10:48:14","2026-04-02 10:48:14");
INSERT INTO transaksi VALUES("726","2026-03-22","107",NULL,"7","18","4","[3,2]","5","500000.00","","2026-04-02 10:49:32","2026-04-02 10:51:17");
INSERT INTO transaksi VALUES("747","2026-03-16",NULL,"137","6","11","4","[3,2]","1","1380000.00","","2026-04-02 12:15:49","2026-04-02 12:15:49");
INSERT INTO transaksi VALUES("748","2026-03-17",NULL,"138","6","11","4","[3,2]","1","250000.00","","2026-04-02 12:21:00","2026-04-02 12:21:00");
INSERT INTO transaksi VALUES("750","2026-03-17","96",NULL,"6","10","4","[3,2]","1","9000000.00","","2026-04-02 12:23:54","2026-04-02 12:23:54");
INSERT INTO transaksi VALUES("751","2026-03-19",NULL,"150","6","11","4","[3,2]","1","250000.00","","2026-04-02 12:24:38","2026-04-02 12:24:38");
INSERT INTO transaksi VALUES("752","2026-03-31","126",NULL,"6","10","4","[3,2]","1","1000000.00","","2026-04-02 12:25:44","2026-04-02 12:25:44");
INSERT INTO transaksi VALUES("753","2026-03-31",NULL,"67","6","11","4","[3,2]","1","1805000.00","","2026-04-02 12:27:29","2026-04-02 12:27:29");
INSERT INTO transaksi VALUES("754","2026-03-06",NULL,"89","6","11","4","[3,2]","1","270000.00","","2026-04-02 13:07:34","2026-04-02 13:07:34");
INSERT INTO transaksi VALUES("755","2026-03-06",NULL,"60","6","11","4","[3,2]","1","2325000.00","","2026-04-02 13:08:14","2026-04-02 13:08:14");
INSERT INTO transaksi VALUES("756","2026-03-11","123",NULL,"6","10","4","[3,2]","1","5100000.00","","2026-04-02 13:09:26","2026-04-02 13:09:26");
INSERT INTO transaksi VALUES("757","2026-03-11",NULL,"40","6","11","4","[3,2]","1","60000.00","","2026-04-02 13:10:16","2026-04-02 13:10:16");
INSERT INTO transaksi VALUES("758","2026-03-13",NULL,"117","6","11","4","[3,2]","1","250000.00","","2026-04-02 13:11:12","2026-04-02 13:11:12");
INSERT INTO transaksi VALUES("759","2026-03-13","84",NULL,"6","10","4","[3,2]","1","120000.00","","2026-04-02 13:12:10","2026-04-02 13:12:10");
INSERT INTO transaksi VALUES("760","2026-03-14",NULL,"75","6","11","4","[3,2]","1","250000.00","","2026-04-02 13:12:51","2026-04-02 14:44:51");
INSERT INTO transaksi VALUES("763","2026-03-17",NULL,"139","6","11","4","[3,2]","1","455000.00","","2026-04-02 13:16:02","2026-04-02 13:16:02");
INSERT INTO transaksi VALUES("764","2026-03-16","95",NULL,"6","10","4","[3,2]","1","2345000.00","","2026-04-02 13:59:53","2026-04-02 13:59:53");
INSERT INTO transaksi VALUES("765","2026-03-16",NULL,"134","6","11","4","[3,2]","1","240000.00","","2026-04-02 14:02:14","2026-04-02 14:02:14");
INSERT INTO transaksi VALUES("766","2026-03-18",NULL,"154","6","14","5","[3,2]","11","2170000.00","https://docs.google.com/spreadsheets/d/11US3mJONgtiwqGFv4QxPeEmB7oC6zrRUUJ-Gwff5HFs/edit?usp=sharing","2026-04-02 14:21:24","2026-04-09 09:08:43");
INSERT INTO transaksi VALUES("767","2026-03-08",NULL,"99","6","11","4","[3,2]","10","200000.00","","2026-04-02 14:23:15","2026-04-02 14:23:15");
INSERT INTO transaksi VALUES("769","2026-03-18","117",NULL,"6","13","5","[3,2]","11","2765000.00","https://docs.google.com/spreadsheets/d/11US3mJONgtiwqGFv4QxPeEmB7oC6zrRUUJ-Gwff5HFs/edit?usp=sharing","2026-04-02 14:24:59","2026-04-09 09:07:54");
INSERT INTO transaksi VALUES("770","2026-03-16",NULL,"99","6","11","4","[3,2]","10","20000.00","","2026-04-02 14:27:00","2026-04-02 14:27:00");
INSERT INTO transaksi VALUES("771","2026-03-23",NULL,"46","7","18","5","[3,2]","11","2400000.00","Diberikan dalam bentuk beras, tolong nanti dirubah","2026-04-02 14:33:26","2026-04-02 14:33:54");
INSERT INTO transaksi VALUES("772","2026-03-15",NULL,"130","7","20","4","[3,2]","5","1000000.00","","2026-04-02 14:42:01","2026-04-02 14:42:01");
INSERT INTO transaksi VALUES("773","2026-03-15",NULL,"131","7","20","4","[3,2]","5","1000000.00","","2026-04-02 14:42:23","2026-04-02 14:42:23");
INSERT INTO transaksi VALUES("774","2026-03-20",NULL,"93","7","20","4","[3,2]","5","150000.00","","2026-04-02 14:43:15","2026-04-02 14:43:15");
INSERT INTO transaksi VALUES("775","2026-03-19","6",NULL,"7","19","4","[3,2]","5","50000.00","","2026-04-02 14:44:05","2026-04-02 14:50:55");
INSERT INTO transaksi VALUES("776","2026-03-22","121",NULL,"7","18","4","[3,2]","5","100000.00","","2026-04-02 14:44:38","2026-04-02 14:44:38");
INSERT INTO transaksi VALUES("777","2026-03-22","109",NULL,"7","18","4","[3,2]","5","100000.00","","2026-04-02 14:45:07","2026-04-02 14:45:07");
INSERT INTO transaksi VALUES("778","2026-03-31",NULL,"152","7","20","4","[3,2]","5","100000.00","","2026-04-02 14:45:38","2026-04-02 14:45:38");
INSERT INTO transaksi VALUES("781","2026-04-01",NULL,"10","7","20","4","[3,2]","5","750000.00","","2026-04-06 08:25:30","2026-04-06 08:26:49");
INSERT INTO transaksi VALUES("782","2026-04-01",NULL,"13","7","20","4","[3,2]","5","1642000.00","","2026-04-06 08:30:33","2026-04-06 08:30:33");
INSERT INTO transaksi VALUES("783","2026-04-01",NULL,"13","7","20","4","[3,2]","5","50000.00","zakat","2026-04-06 08:31:39","2026-04-06 08:45:23");
INSERT INTO transaksi VALUES("784","2026-04-01",NULL,"22","7","20","4","[3,2]","5","1850000.00","","2026-04-06 08:33:00","2026-04-06 08:33:00");
INSERT INTO transaksi VALUES("785","2026-04-01",NULL,"49","7","20","4","[3,2]","5","450000.00","","2026-04-06 08:35:37","2026-04-06 08:35:37");
INSERT INTO transaksi VALUES("786","2026-04-01",NULL,"17","7","20","4","[3,2]","5","320000.00","","2026-04-06 08:40:12","2026-04-06 08:40:12");
INSERT INTO transaksi VALUES("787","2026-04-01",NULL,"19","7","20","4","[3,2]","5","380000.00","","2026-04-06 08:41:40","2026-04-06 08:41:40");
INSERT INTO transaksi VALUES("788","2026-04-01",NULL,"22","7","20","4","[3,2]","5","80000.00","zakat","2026-04-06 08:41:54","2026-04-06 08:46:27");
INSERT INTO transaksi VALUES("789","2026-04-01",NULL,"6","7","20","4","[3,2]","5","1500000.00","","2026-04-06 08:42:30","2026-04-06 08:42:30");
INSERT INTO transaksi VALUES("790","2026-04-01",NULL,"39","7","20","4","[3,2]","5","60000.00","zakat","2026-04-06 08:45:02","2026-04-06 08:45:02");
INSERT INTO transaksi VALUES("791","2026-04-01",NULL,"6","7","20","4","[3,2]","5","80000.00","zakat","2026-04-06 08:46:24","2026-04-06 08:46:24");
INSERT INTO transaksi VALUES("792","2026-04-01",NULL,"20","7","20","4","[3,2]","5","200000.00","","2026-04-06 08:47:21","2026-04-06 08:47:21");
INSERT INTO transaksi VALUES("793","2026-04-01",NULL,"49","7","20","4","[3,2]","5","120000.00","","2026-04-06 08:48:24","2026-04-06 08:48:24");
INSERT INTO transaksi VALUES("794","2026-04-01",NULL,"21","7","20","4","[3,2]","5","150000.00","","2026-04-06 08:48:55","2026-04-06 08:48:55");
INSERT INTO transaksi VALUES("795","2026-04-01",NULL,"20","7","20","4","[3,2]","5","1810000.00","","2026-04-06 08:50:06","2026-04-06 08:50:06");
INSERT INTO transaksi VALUES("796","2026-04-01",NULL,"52","7","20","4","[3,2]","5","30000.00","zakat","2026-04-06 08:50:22","2026-04-06 08:50:22");
INSERT INTO transaksi VALUES("797","2026-04-01",NULL,"21","7","20","4","[3,2]","5","1030000.00","","2026-04-06 08:51:27","2026-04-06 08:51:27");
INSERT INTO transaksi VALUES("798","2026-04-01",NULL,"24","7","20","4","[3,2]","5","50000.00","zakat","2026-04-06 08:52:35","2026-04-06 08:52:35");
INSERT INTO transaksi VALUES("799","2026-04-01",NULL,"24","7","20","4","[3,2]","5","2630000.00","","2026-04-06 08:53:23","2026-04-06 08:53:23");
INSERT INTO transaksi VALUES("800","2026-04-01",NULL,"31","7","20","4","[3,2]","5","260000.00","","2026-04-06 08:54:21","2026-04-06 08:54:21");
INSERT INTO transaksi VALUES("801","2026-04-01",NULL,"57","7","20","4","[3,2]","5","30000.00","zakat","2026-04-06 08:55:27","2026-04-06 08:55:27");
INSERT INTO transaksi VALUES("802","2026-04-01",NULL,"53","7","20","4","[3,2]","5","560000.00","","2026-04-06 08:55:33","2026-04-06 08:55:33");
INSERT INTO transaksi VALUES("803","2026-04-01",NULL,"31","7","20","4","[3,2]","5","2570000.00","","2026-04-06 08:57:03","2026-04-06 08:57:03");
INSERT INTO transaksi VALUES("804","2026-04-01",NULL,"57","7","20","4","[3,2]","5","780000.00","","2026-04-06 08:58:44","2026-04-06 08:58:44");
INSERT INTO transaksi VALUES("805","2026-04-01",NULL,"25","7","20","4","[3,2]","5","630000.00","","2026-04-06 08:59:32","2026-04-06 08:59:32");
INSERT INTO transaksi VALUES("806","2026-04-01",NULL,"14","7","20","4","[3,2]","5","770000.00","","2026-04-06 08:59:35","2026-04-06 08:59:35");
INSERT INTO transaksi VALUES("807","2026-04-01",NULL,"34","7","20","4","[3,2]","5","730000.00","","2026-04-06 09:00:46","2026-04-06 09:00:46");
INSERT INTO transaksi VALUES("808","2026-04-01",NULL,"35","7","20","4","[3,2]","5","530000.00","","2026-04-06 09:02:29","2026-04-06 09:02:29");
INSERT INTO transaksi VALUES("809","2026-04-01",NULL,"39","7","20","4","[3,2]","5","580000.00","","2026-04-06 09:03:18","2026-04-06 09:03:18");
INSERT INTO transaksi VALUES("810","2026-04-01",NULL,"53","7","20","4","[3,2]","5","60000.00","zakat","2026-04-06 09:04:59","2026-04-06 09:04:59");
INSERT INTO transaksi VALUES("811","2026-04-01",NULL,"52","7","20","4","[3,2]","5","740000.00","","2026-04-06 09:09:57","2026-04-06 09:09:57");
INSERT INTO transaksi VALUES("812","2026-04-01",NULL,"5","7","20","4","[3,2]","5","3540000.00","","2026-04-06 09:12:51","2026-04-06 09:12:51");
INSERT INTO transaksi VALUES("813","2026-04-01",NULL,"14","7","20","4","[3,2]","5","2820000.00","","2026-04-06 09:12:56","2026-04-06 09:12:56");
INSERT INTO transaksi VALUES("814","2026-04-01",NULL,"23","7","20","4","[3,2]","5","2920000.00","","2026-04-06 09:13:34","2026-04-06 09:13:34");
INSERT INTO transaksi VALUES("815","2026-04-01",NULL,"34","7","20","4","[3,2]","5","60000.00","zakat","2026-04-06 09:14:01","2026-04-06 09:14:01");
INSERT INTO transaksi VALUES("816","2026-04-01",NULL,"23","7","20","4","[3,2]","5","340000.00","zakat","2026-04-06 09:14:30","2026-04-06 09:14:30");
INSERT INTO transaksi VALUES("817","2026-04-01",NULL,"29","7","20","4","[3,2]","5","3370000.00","","2026-04-06 09:15:21","2026-04-06 09:15:21");
INSERT INTO transaksi VALUES("818","2026-04-01",NULL,"25","7","20","4","[3,2]","5","4830000.00","","2026-04-06 09:15:53","2026-04-06 09:15:53");
INSERT INTO transaksi VALUES("819","2026-04-01",NULL,"26","7","20","4","[3,2]","5","960000.00","","2026-04-06 09:16:08","2026-04-06 09:16:08");
INSERT INTO transaksi VALUES("820","2026-04-01",NULL,"35","7","20","4","[3,2]","5","90000.00","zakat","2026-04-06 09:17:26","2026-04-06 09:17:26");
INSERT INTO transaksi VALUES("821","2026-04-01",NULL,"41","7","20","4","[3,2]","5","60000.00","zakat","2026-04-06 09:18:04","2026-04-06 09:18:04");
INSERT INTO transaksi VALUES("822","2026-04-01",NULL,"48","7","20","4","[3,2]","5","90000.00","zakat","2026-04-06 09:18:44","2026-04-06 09:18:44");
INSERT INTO transaksi VALUES("823","2026-04-01",NULL,"5","7","20","4","[3,2]","5","50000.00","zakat","2026-04-06 09:19:30","2026-04-06 09:19:30");
INSERT INTO transaksi VALUES("824","2026-04-01",NULL,"26","7","20","4","[3,2]","5","6670000.00","","2026-04-06 09:20:02","2026-04-06 09:20:02");
INSERT INTO transaksi VALUES("825","2026-04-01",NULL,"16","7","20","4","[3,2]","5","1420000.00","","2026-04-06 09:20:09","2026-04-06 09:20:09");
INSERT INTO transaksi VALUES("826","2026-04-01",NULL,"44","7","20","4","[3,2]","5","390000.00","","2026-04-06 09:20:49","2026-04-06 09:20:49");
INSERT INTO transaksi VALUES("827","2026-04-01",NULL,"38","7","20","4","[3,2]","5","30000.00","zakat","2026-04-06 09:21:31","2026-04-06 09:21:31");
INSERT INTO transaksi VALUES("828","2026-04-01",NULL,"30","7","20","4","[3,2]","5","2627000.00","","2026-04-06 09:21:51","2026-04-06 09:21:51");
INSERT INTO transaksi VALUES("829","2026-04-01",NULL,"32","7","20","4","[3,2]","5","3980000.00","","2026-04-06 09:22:18","2026-04-06 09:22:18");
INSERT INTO transaksi VALUES("830","2026-04-01",NULL,"41","7","20","4","[3,2]","5","500000.00","","2026-04-06 09:23:10","2026-04-06 09:23:10");
INSERT INTO transaksi VALUES("831","2026-04-01",NULL,"45","7","20","4","[3,2]","5","1030000.00","","2026-04-06 09:23:17","2026-04-06 09:23:17");
INSERT INTO transaksi VALUES("832","2026-04-01",NULL,"28","7","20","4","[3,2]","5","2290000.00","","2026-04-06 09:24:11","2026-04-06 09:24:11");
INSERT INTO transaksi VALUES("833","2026-04-01",NULL,"48","7","20","4","[3,2]","5","550000.00","","2026-04-06 09:24:15","2026-04-06 09:24:15");
INSERT INTO transaksi VALUES("834","2026-04-01",NULL,"16","7","20","4","[3,2]","5","110000.00","","2026-04-06 09:24:45","2026-04-06 09:24:45");
INSERT INTO transaksi VALUES("835","2026-04-01",NULL,"56","7","20","4","[3,2]","5","420000.00","","2026-04-06 09:25:06","2026-04-06 09:25:06");
INSERT INTO transaksi VALUES("836","2026-04-01",NULL,"38","7","20","4","[3,2]","5","920000.00","","2026-04-06 09:25:24","2026-04-06 09:25:24");
INSERT INTO transaksi VALUES("837","2026-04-01",NULL,"8","7","20","4","[3,2]","5","450000.00","","2026-04-06 09:26:17","2026-04-06 09:26:17");
INSERT INTO transaksi VALUES("838","2026-04-01",NULL,"15","7","20","4","[3,2]","5","1950000.00","","2026-04-06 09:27:06","2026-04-06 09:27:06");
INSERT INTO transaksi VALUES("839","2026-04-01",NULL,"8","7","20","4","[3,2]","5","1000000.00","","2026-04-06 09:27:28","2026-04-06 09:27:28");
INSERT INTO transaksi VALUES("840","2026-04-01",NULL,"46","7","20","4","[3,2]","5","810000.00","","2026-04-06 09:27:37","2026-04-06 09:27:37");
INSERT INTO transaksi VALUES("841","2026-04-01",NULL,"15","7","20","4","[3,2]","5","280000.00","","2026-04-06 09:28:12","2026-04-06 09:28:12");
INSERT INTO transaksi VALUES("842","2026-04-01",NULL,"45","7","20","4","[3,2]","5","60000.00","zakat","2026-04-06 09:29:31","2026-04-06 09:29:31");
INSERT INTO transaksi VALUES("843","2026-04-01",NULL,"17","7","20","4","[3,2]","5","1940000.00","","2026-04-06 09:34:31","2026-04-06 09:34:31");
INSERT INTO transaksi VALUES("844","2026-04-01",NULL,"33","7","20","4","[3,2]","5","60000.00","zakat","2026-04-06 09:35:04","2026-04-06 09:35:04");
INSERT INTO transaksi VALUES("845","2026-04-01",NULL,"46","7","20","4","[3,2]","5","60000.00","zakat","2026-04-06 09:35:29","2026-04-06 09:35:29");
INSERT INTO transaksi VALUES("846","2026-04-01",NULL,"28","7","20","4","[3,2]","5","180000.00","","2026-04-06 09:36:15","2026-04-06 09:36:15");
INSERT INTO transaksi VALUES("847","2026-04-01",NULL,"44","7","20","4","[3,2]","5","90000.00","zakat","2026-04-06 09:37:29","2026-04-06 09:37:29");
INSERT INTO transaksi VALUES("848","2026-04-01",NULL,"56","7","20","4","[3,2]","5","200000.00","","2026-04-06 09:38:03","2026-04-06 09:38:03");
INSERT INTO transaksi VALUES("849","2026-04-01",NULL,"33","7","20","4","[3,2]","5","360000.00","","2026-04-06 09:38:54","2026-04-06 09:38:54");
INSERT INTO transaksi VALUES("850","2026-04-01",NULL,"19","7","20","4","[3,2]","5","2630000.00","","2026-04-06 09:39:19","2026-04-06 09:39:19");
INSERT INTO transaksi VALUES("851","2026-04-01",NULL,"7","7","20","4","[3,2]","5","1470000.00","","2026-04-06 09:39:58","2026-04-06 09:39:58");
INSERT INTO transaksi VALUES("852","2026-04-01",NULL,"37","7","20","4","[3,2]","5","60000.00","zakat","2026-04-06 09:41:30","2026-04-06 09:41:30");
INSERT INTO transaksi VALUES("853","2026-04-01",NULL,"4","7","20","4","[3,2]","5","17940000.00","","2026-04-06 09:43:39","2026-04-06 09:43:39");
INSERT INTO transaksi VALUES("854","2026-04-01",NULL,"3","7","20","4","[3,2]","5","145770000.00","","2026-04-06 09:44:50","2026-04-06 09:44:50");
INSERT INTO transaksi VALUES("855","2026-04-01",NULL,"3","7","20","4","[3,2]","5","160360000.00","","2026-04-06 09:46:07","2026-04-06 09:46:07");
INSERT INTO transaksi VALUES("856","2026-04-01",NULL,"4","7","20","4","[3,2]","5","61590000.00","","2026-04-06 09:48:24","2026-04-06 09:48:24");
INSERT INTO transaksi VALUES("857","2026-04-01",NULL,"7","7","20","4","[3,2]","5","2000000.00","","2026-04-06 09:50:36","2026-04-06 09:50:36");
INSERT INTO transaksi VALUES("858","2026-04-01",NULL,"37","7","20","4","[3,2]","5","640000.00","","2026-04-06 09:51:33","2026-04-06 09:51:33");
INSERT INTO transaksi VALUES("859","2026-04-02",NULL,"50","7","20","4","[3,2]","5","90000.00","zakat","2026-04-06 09:52:43","2026-04-07 09:06:03");
INSERT INTO transaksi VALUES("860","2026-04-02",NULL,"50","7","20","4","[3,2]","5","440000.00","","2026-04-06 09:53:32","2026-04-07 09:05:47");
INSERT INTO transaksi VALUES("861","2026-04-06",NULL,"155","6","11","4","[3,2]","1","300000.00","","2026-04-06 12:05:13","2026-04-06 13:46:46");
INSERT INTO transaksi VALUES("862","2026-04-06","132",NULL,"6","10","4","[3,2]","1","1000000.00","","2026-04-06 12:10:52","2026-04-06 12:10:52");
INSERT INTO transaksi VALUES("863","2026-03-24","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-04-07 09:27:25","2026-04-07 09:27:25");
INSERT INTO transaksi VALUES("864","2026-03-27",NULL,"66","6","11","4","[3,2]","6","455221.00","","2026-04-07 10:56:17","2026-04-07 10:56:17");
INSERT INTO transaksi VALUES("866","2026-04-07","136",NULL,"7","19","4","[3,2]","5","100000.00","","2026-04-08 07:23:28","2026-04-08 07:23:28");
INSERT INTO transaksi VALUES("867","2026-04-07","135",NULL,"7","19","4","[3,2]","5","100000.00","","2026-04-08 07:24:01","2026-04-08 07:24:01");
INSERT INTO transaksi VALUES("868","2026-04-07",NULL,"155","7","20","4","[3,2]","5","300000.00","","2026-04-08 07:24:51","2026-04-08 07:24:51");
INSERT INTO transaksi VALUES("869","2026-04-08","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-04-08 07:30:29","2026-04-08 07:30:29");
INSERT INTO transaksi VALUES("870","2026-04-06","6",NULL,"6","10","4","[3,2]","1","3776.00","","2026-04-08 07:38:07","2026-04-08 07:38:07");
INSERT INTO transaksi VALUES("871","2026-04-07","137",NULL,"8","26","4","[3,2]","1","300000.00","","2026-04-08 07:41:24","2026-04-08 07:41:24");
INSERT INTO transaksi VALUES("872","2026-04-09",NULL,"36","7","20","4","[3,2]","5","60000.00","Zakat","2026-04-10 17:11:20","2026-04-10 17:11:20");
INSERT INTO transaksi VALUES("873","2026-04-09",NULL,"36","7","20","4","[3,2]","5","711000.00","","2026-04-10 17:13:15","2026-04-10 17:13:15");
INSERT INTO transaksi VALUES("874","2026-04-09",NULL,"11","7","20","4","[3,2]","5","1910000.00","","2026-04-10 17:15:30","2026-04-10 17:15:30");
INSERT INTO transaksi VALUES("875","2026-04-09",NULL,"11","7","20","4","[3,2]","5","150000.00","","2026-04-10 17:18:53","2026-04-10 17:18:53");
INSERT INTO transaksi VALUES("876","2026-04-10","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-04-10 17:21:10","2026-04-10 17:21:10");
INSERT INTO transaksi VALUES("877","2026-04-10",NULL,"78","7","20","4","[3,2]","5","152000.00","","2026-04-10 17:23:43","2026-04-10 17:23:43");
INSERT INTO transaksi VALUES("878","2026-04-10",NULL,"156","7","20","4","[3,2]","5","156000.00","","2026-04-10 17:26:29","2026-04-10 17:26:29");
INSERT INTO transaksi VALUES("879","2026-04-10",NULL,"54","7","20","4","[3,2]","5","680000.00","","2026-04-13 07:36:09","2026-04-13 07:36:09");
INSERT INTO transaksi VALUES("880","2026-04-10",NULL,"54","7","20","4","[3,2]","5","90000.00","zakat","2026-04-13 07:37:10","2026-04-13 07:37:10");
INSERT INTO transaksi VALUES("881","2026-04-10",NULL,"42","7","20","4","[3,2]","5","630000.00","","2026-04-13 07:38:21","2026-04-13 07:38:21");
INSERT INTO transaksi VALUES("882","2026-04-10",NULL,"42","7","20","4","[3,2]","5","60000.00","zakat","2026-04-13 07:42:03","2026-04-13 07:42:03");
INSERT INTO transaksi VALUES("883","2026-04-10",NULL,"18","7","20","4","[3,2]","5","60000.00","zakat","2026-04-13 07:43:05","2026-04-13 07:43:05");
INSERT INTO transaksi VALUES("884","2026-04-10",NULL,"43","7","20","4","[3,2]","5","30000.00","zakat","2026-04-13 07:44:01","2026-04-13 07:44:01");
INSERT INTO transaksi VALUES("885","2026-04-10",NULL,"43","7","20","4","[3,2]","5","490000.00","","2026-04-13 07:44:59","2026-04-13 07:44:59");
INSERT INTO transaksi VALUES("886","2026-04-10",NULL,"27","7","20","4","[3,2]","5","120000.00","","2026-04-13 07:59:05","2026-04-13 07:59:05");
INSERT INTO transaksi VALUES("887","2026-04-10",NULL,"18","7","20","4","[3,2]","5","1350000.00","","2026-04-13 09:28:25","2026-04-13 09:28:25");
INSERT INTO transaksi VALUES("888","2026-04-10",NULL,"27","7","20","4","[3,2]","5","1560000.00","","2026-04-13 09:29:08","2026-04-13 09:29:08");
INSERT INTO transaksi VALUES("889","2026-04-10",NULL,"40","7","20","4","[3,2]","5","500000.00","","2026-04-13 09:30:10","2026-04-13 09:30:10");
INSERT INTO transaksi VALUES("890","2026-04-10",NULL,"47","7","20","4","[3,2]","5","440000.00","","2026-04-13 13:23:24","2026-04-13 13:23:24");
INSERT INTO transaksi VALUES("891","2026-04-11","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-04-13 13:26:09","2026-04-13 13:26:09");
INSERT INTO transaksi VALUES("892","2026-04-11","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-04-13 13:26:51","2026-04-13 13:26:51");
INSERT INTO transaksi VALUES("893","2026-04-13","6",NULL,"7","18","4","[3,2]","5","50000.00","","2026-04-13 13:27:39","2026-04-13 13:27:39");
INSERT INTO transaksi VALUES("894","2026-04-13","139",NULL,"6","10","4","[3,2]","1","8190000.00","","2026-04-13 13:37:53","2026-04-13 13:37:53");
INSERT INTO transaksi VALUES("895","2026-04-13",NULL,"55","7","20","4","[3,2]","5","60000.00","","2026-04-14 06:40:06","2026-04-14 06:40:06");
INSERT INTO transaksi VALUES("896","2026-04-13",NULL,"47","7","20","4","[3,2]","5","40000.00","","2026-04-14 06:40:55","2026-04-14 06:40:55");
INSERT INTO transaksi VALUES("897","2026-04-13",NULL,"51","7","20","4","[3,2]","5","10000.00","","2026-04-14 06:41:58","2026-04-14 06:41:58");
INSERT INTO transaksi VALUES("898","2026-04-13",NULL,"75","7","20","4","[3,2]","5","250000.00","","2026-04-14 06:42:51","2026-04-14 06:42:51");
INSERT INTO transaksi VALUES("899","2026-04-13",NULL,"51","7","20","4","[3,2]","5","3100000.00","","2026-04-14 06:43:38","2026-04-14 06:43:38");
INSERT INTO transaksi VALUES("900","2026-04-13",NULL,"55","7","20","4","[3,2]","5","460000.00","","2026-04-14 06:44:23","2026-04-14 06:44:23");
INSERT INTO transaksi VALUES("901","2026-04-14","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-04-14 06:45:08","2026-04-14 06:45:08");
INSERT INTO transaksi VALUES("902","2026-04-14",NULL,"108","6","11","4","[3,2]","1","300000.00","","2026-04-14 09:30:39","2026-04-14 09:30:39");
INSERT INTO transaksi VALUES("903","2026-04-14",NULL,"9","7","20","4","[3,2]","5","50000.00","","2026-04-14 13:23:25","2026-04-14 13:23:25");
INSERT INTO transaksi VALUES("904","2026-04-14",NULL,"40","7","20","4","[3,2]","5","30000.00","","2026-04-14 13:24:10","2026-04-14 13:24:28");
INSERT INTO transaksi VALUES("905","2026-04-14","63",NULL,"7","19","4","[3,2]","5","100000.00","","2026-04-15 08:58:36","2026-04-15 08:58:36");
INSERT INTO transaksi VALUES("906","2026-04-14",NULL,"9","7","20","4","[3,2]","5","1220000.00","","2026-04-15 09:00:08","2026-04-15 09:00:08");
INSERT INTO transaksi VALUES("907","2026-04-15","6",NULL,"7","19","4","[3,2]","5","1000.00","","2026-04-15 09:01:05","2026-04-15 09:01:05");
INSERT INTO transaksi VALUES("908","2026-04-15","6",NULL,"7","19","4","[3,2]","5","1000.00","","2026-04-15 09:03:10","2026-04-15 09:03:10");
INSERT INTO transaksi VALUES("909","2026-04-14","141",NULL,"6","10","4","[3,2]","1","500000.00","","2026-04-15 09:07:54","2026-04-15 09:07:54");
INSERT INTO transaksi VALUES("910","2026-04-14","14",NULL,"6","10","4","[3,2]","1","200000.00","","2026-04-15 09:09:31","2026-04-15 09:09:31");
INSERT INTO transaksi VALUES("911","2026-04-15","6",NULL,"7","19","4","[3,2]","5","1000.00","","2026-04-15 12:52:53","2026-04-15 12:52:53");
INSERT INTO transaksi VALUES("912","2026-04-15","6",NULL,"7","19","4","[3,2]","5","1000.00","","2026-04-15 12:53:23","2026-04-15 12:53:23");
INSERT INTO transaksi VALUES("913","2026-04-15","6",NULL,"7","19","4","[3,2]","5","1000.00","","2026-04-15 12:54:02","2026-04-15 12:54:02");
INSERT INTO transaksi VALUES("914","2026-04-15","6",NULL,"7","19","4","[3,2]","5","20000.00","","2026-04-16 07:16:20","2026-04-16 07:16:20");
INSERT INTO transaksi VALUES("915","2026-04-15",NULL,"12","7","20","4","[3,2]","5","60000.00","zakat","2026-04-16 07:18:24","2026-04-16 07:18:24");
INSERT INTO transaksi VALUES("916","2026-04-15",NULL,"12","7","20","4","[3,2]","5","2970000.00","","2026-04-16 07:19:16","2026-04-16 07:19:16");
INSERT INTO transaksi VALUES("917","2026-04-16","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-04-16 07:19:50","2026-04-16 07:19:50");
INSERT INTO transaksi VALUES("918","2026-04-16","6",NULL,"7","19","4","[3,2]","5","1000.00","","2026-04-16 12:12:28","2026-04-16 12:12:28");
INSERT INTO transaksi VALUES("919","2026-04-17","6",NULL,"7","19","4","[3,2]","5","5000.00","","2026-04-17 06:59:06","2026-04-17 06:59:06");
INSERT INTO transaksi VALUES("920","2026-04-17",NULL,"97","7","20","4","[3,2]","5","100000.00","","2026-04-17 08:20:44","2026-04-17 08:20:44");
INSERT INTO transaksi VALUES("921","2026-04-17",NULL,"137","6","11","4","[3,2]","1","460000.00","","2026-04-17 09:18:23","2026-04-17 09:18:23");
INSERT INTO transaksi VALUES("922","2026-04-17","144",NULL,"7","19","4","[3,2]","5","10000.00","","2026-04-17 09:23:39","2026-04-17 09:23:39");


CREATE TABLE `upz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_upz` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `kontak` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO upz VALUES("3","DINAS DIKBUD | 1.01.2.22.0.00.01.0000","SUMBAWA","0","2026-01-13 08:13:39","2026-01-13 08:13:39");
INSERT INTO upz VALUES("4","DINAS KESEHATAN| 1.02.0.00.0.00.02.0000","SUMBAWA","0","2026-01-13 09:45:37","2026-01-13 09:45:37");
INSERT INTO upz VALUES("5","DINAS PUPR | 1.03.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 09:46:32","2026-01-13 09:46:32");
INSERT INTO upz VALUES("6","DINAS PRKP | 1.04.2.10.0.00.02.0000","SUMBAWA","0","2026-01-13 09:48:40","2026-01-13 09:48:40");
INSERT INTO upz VALUES("7","SATPOL PP | 1.05.0.00.0.00.01.0000 ","SUMBAWA","0","2026-01-13 09:49:30","2026-01-13 09:49:30");
INSERT INTO upz VALUES("8","DINAS DAMKAR | 1.05.0.00.0.00.02.0000","SUMBAWA","0","2026-01-13 09:52:16","2026-01-13 09:52:16");
INSERT INTO upz VALUES("9","BPBD | 1.05.0.00.0.00.04.0000","\n\n\n\n\n\n\nSUMBAWA\n\n","0","2026-01-13 09:53:10","2026-01-13 09:54:04");
INSERT INTO upz VALUES("10","DINAS SOSIAL | 1.06.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 09:55:04","2026-01-13 09:55:04");
INSERT INTO upz VALUES("11","DISNAKERTRANS | 2.07.3.32.0.00.02.0000","SUMBAWA","0","2026-01-13 09:56:20","2026-01-13 09:56:20");
INSERT INTO upz VALUES("12","DINAS P2KBP3A | 2.08.2.14.0.00.02.0000","SUMBAWA\n","0","2026-01-13 09:58:03","2026-01-13 09:58:03");
INSERT INTO upz VALUES("13","DINAS KETAHANAN PANGAN | 2.09.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 09:59:53","2026-01-13 09:59:53");
INSERT INTO upz VALUES("14","DINAS LH | 2.11.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:00:51","2026-01-13 10:00:51");
INSERT INTO upz VALUES("15","DINAS DUKCAPIL | 2.12.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:02:30","2026-01-13 10:02:30");
INSERT INTO upz VALUES("16","DINAS PMD | 2.13.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:03:06","2026-01-13 10:03:06");
INSERT INTO upz VALUES("17","DINAS PERHUBUNGAN | 2.15.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:04:16","2026-01-13 10:04:16");
INSERT INTO upz VALUES("18","DINAS KOMINFOTIKSANDI | 2.16.2.20.2.21.04.0000","SUMBAWA","0","2026-01-13 10:05:17","2026-01-13 10:05:17");
INSERT INTO upz VALUES("19","DISKOPERINDAG | 2.17.3.31.3.30.07.0000","SUMBAWA","0","2026-01-13 10:06:35","2026-01-13 10:06:35");
INSERT INTO upz VALUES("20","DINAS PMPTSP | 2.18.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:07:34","2026-01-13 10:07:34");
INSERT INTO upz VALUES("21","DISPOPAR | 2.19.3.26.0.00.02.0000","SUMBAWA","0","2026-01-13 10:08:46","2026-01-13 10:08:46");
INSERT INTO upz VALUES("22","DISPERSIP | 2.23.2.24.0.00.01.0000","SUMBAWA","0","2026-01-13 10:10:04","2026-01-13 10:10:04");
INSERT INTO upz VALUES("23","DISLUTKAN | 3.25.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:11:09","2026-01-13 10:11:09");
INSERT INTO upz VALUES("24","DINAS PERTANIAN | 3.27.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:13:28","2026-01-13 10:13:28");
INSERT INTO upz VALUES("25","DISNAKESWAN | 3.27.0.00.0.00.02.0000","SUMBAWA","0","2026-01-13 10:14:54","2026-01-13 10:14:54");
INSERT INTO upz VALUES("26","SEKRETARIAT DAERAH | 4.01.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:16:06","2026-01-13 10:16:06");
INSERT INTO upz VALUES("27","SEKRETARIAT DPRD | 4.02.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:17:13","2026-01-13 10:17:13");
INSERT INTO upz VALUES("28","BAPPERIDA | 5.01.5.05.0.00.02.0000","SUMBAWA","0","2026-01-13 10:19:32","2026-01-13 10:19:32");
INSERT INTO upz VALUES("29","BKAD | 5.02.0.00.0.00.02.0000","SUMBAWA","0","2026-01-13 10:20:27","2026-01-13 10:20:27");
INSERT INTO upz VALUES("30","BAPENDA | 5.02.0.00.0.00.03.0000","SUMBAWA","0","2026-01-13 10:21:19","2026-01-13 10:21:19");
INSERT INTO upz VALUES("31","BKPSDM | 5.03.5.04.0.00.01.0000","SUMBAWA\n","0","2026-01-13 10:22:06","2026-01-13 10:22:06");
INSERT INTO upz VALUES("32","INSPEKTORAT | 6.01.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:22:39","2026-01-13 10:22:39");
INSERT INTO upz VALUES("33","KECAMATAN ALAS | 7.01.0.00.0.00.02.0000","SUMBAWA","0","2026-01-13 10:23:43","2026-01-13 10:23:43");
INSERT INTO upz VALUES("34","KECAMATAN BATULANTEH | 7.01.0.00.0.00.03.0000","SUMBAWA","0","2026-01-13 10:27:24","2026-01-13 10:27:24");
INSERT INTO upz VALUES("35","KECAMATAN BUER | 7.01.0.00.0.00.04.0000","SUMBAWA","0","2026-01-13 10:29:04","2026-01-13 10:29:04");
INSERT INTO upz VALUES("36","KECAMATAN TARANO | 7.01.0.00.0.00.05.0000","SUMBAWA\n","0","2026-01-13 10:29:56","2026-01-13 10:29:56");
INSERT INTO upz VALUES("37","KECAMATAN LABANGKA | 7.01.0.00.0.00.06.0000","SUMBAWA","0","2026-01-13 10:31:08","2026-01-13 10:31:08");
INSERT INTO upz VALUES("38","KECAMATAN LAB.BADAS | 7.01.0.00.0.00.07.0000","SUMBAWA","0","2026-01-13 10:31:56","2026-01-13 10:31:56");
INSERT INTO upz VALUES("39","KECAMATAN LANTUNG | 7.01.0.00.0.00.08.0000","SUMBAWA","0","2026-01-13 10:32:27","2026-01-13 10:32:27");
INSERT INTO upz VALUES("40","KECAMATAN LAPE | 7.01.0.00.0.00.09.0000","SUMBAWA","0","2026-01-13 10:32:53","2026-01-13 10:32:53");
INSERT INTO upz VALUES("41","KECAMATAN LENANGGUAR | 7.01.0.00.0.00.10.0000","SUMBAWA","0","2026-01-13 10:33:24","2026-01-13 10:33:24");
INSERT INTO upz VALUES("42","KECAMATAN LOPOK | 7.01.0.00.0.00.11.0000","SUMBAWA","0","2026-01-13 10:33:59","2026-01-13 10:33:59");
INSERT INTO upz VALUES("43","KECAMATAN LUNYUK | 7.01.0.00.0.00.12.0000","SUMBAWA","0","2026-01-13 10:36:16","2026-01-13 10:36:16");
INSERT INTO upz VALUES("44","KECAMATAN MARONGE | 7.01.0.00.0.00.13.0000","SUMBAWA","0","2026-01-13 10:37:57","2026-01-13 10:37:57");
INSERT INTO upz VALUES("45","KECAMATAN MOYO HILIR | 7.01.0.00.0.00.14.0000","SUMBAWA","0","2026-01-13 10:38:53","2026-01-13 10:38:53");
INSERT INTO upz VALUES("46","KECAMATAN MOYO UTARA | 7.01.0.00.0.00.16.0000","SUMBAWA","0","2026-01-13 10:41:18","2026-01-13 10:41:18");
INSERT INTO upz VALUES("47","KECAMATAN ORONG TELU | 7.01.0.00.0.00.17.0000","SUMBAWA","0","2026-01-13 10:41:57","2026-01-13 10:41:57");
INSERT INTO upz VALUES("48","KECAMATAN PLAMPANG | 7.01.0.00.0.00.18.0000","SUMBAWA","0","2026-01-13 10:43:10","2026-01-13 10:43:10");
INSERT INTO upz VALUES("49","KECAMATAN RHEE | 7.01.0.00.0.00.19.0000","SUMBAWA","0","2026-01-13 10:43:41","2026-01-13 10:43:41");
INSERT INTO upz VALUES("50","KECAMATAN ROPANG | 7.01.0.00.0.00.20.0000","SUMBAWA","0","2026-01-13 10:44:16","2026-01-13 10:44:16");
INSERT INTO upz VALUES("51","KECAMATAN SUMBAWA | 7.01.0.00.0.00.21.0000","SUMBAWA","0","2026-01-13 10:44:55","2026-01-13 10:44:55");
INSERT INTO upz VALUES("52","KECAMATAN UNTER IWES| 7.01.0.00.0.00.22.0000","SUMBAWA","0","2026-01-13 10:45:28","2026-01-13 10:45:28");
INSERT INTO upz VALUES("53","KECAMATAN UTAN | 7.01.0.00.0.00.23.0000","SUMBAWA","0","2026-01-13 10:45:55","2026-01-13 10:45:55");
INSERT INTO upz VALUES("54","KECAMATAN ALAS BARAT|7.01.0.00.0.00.25.0000","SUMBAWA","0","2026-01-13 10:46:34","2026-01-13 10:46:34");
INSERT INTO upz VALUES("55","KECAMATAN EMPANG | 7.01.0.00.0.00.27.0000","SUMBAWA","0","2026-01-13 10:47:02","2026-01-13 10:47:02");
INSERT INTO upz VALUES("56","KECAMATAN MOYO HULU|7.01.0.00.0.00.28.0000","SUMBAWA","0","2026-01-13 10:47:35","2026-01-13 10:47:35");
INSERT INTO upz VALUES("57","BADAN KESBANGPOL | 8.01.0.00.0.00.01.0000","SUMBAWA","0","2026-01-13 10:48:05","2026-04-07 21:57:44");
INSERT INTO upz VALUES("58","UPZ ANONIM","SUMBAWA","0","2026-01-13 14:15:20","2026-01-13 14:15:20");
INSERT INTO upz VALUES("59","MASJID AGUNG NURUL HUDA KABUPATEN SUMBAWA","SUMBAWA","0","2026-01-21 13:01:39","2026-01-21 13:01:39");
INSERT INTO upz VALUES("60","MTs 1 SUMBAWA","SUMBAWA","","2026-01-22 13:06:38","2026-01-22 13:06:38");
INSERT INTO upz VALUES("61","BANK NTB SYARIAH CAB SUMBAWA","SUMBAWA","","2026-01-26 10:15:06","2026-01-26 10:15:06");
INSERT INTO upz VALUES("62","BANK NTB SYARIAH CAB LUNYUK","SUMBAWA","","2026-01-26 14:07:48","2026-01-26 14:07:48");
INSERT INTO upz VALUES("63","BANK NTB SYARIAH CAB LOPOK","SUMBAWA","","2026-01-26 14:08:38","2026-01-26 14:08:38");
INSERT INTO upz VALUES("64","BANK NTB SYARIAH CAB PLAMPANG","SUMBAWA","","2026-01-27 10:43:11","2026-01-27 10:43:11");
INSERT INTO upz VALUES("65","BANK NTB SYRIAH CAB ALAS","SUMBAWA","","2026-01-27 10:46:27","2026-01-27 10:46:27");
INSERT INTO upz VALUES("66","BANK NTB SYARIAH CAB UTAN","SUMBAWA","","2026-01-30 08:03:22","2026-01-30 08:03:22");
INSERT INTO upz VALUES("67","BAZNAS KAB. SUMBAWA","SUMBAWA","","2026-01-30 10:59:12","2026-01-30 10:59:12");
INSERT INTO upz VALUES("71","SDN 1 BATULANTEH","SUMBAWA","00","2026-02-05 13:11:57","2026-02-14 12:30:37");
INSERT INTO upz VALUES("72","SDN 1 SEBEOK","SUMBAWA","","2026-02-06 14:18:19","2026-02-14 12:31:01");
INSERT INTO upz VALUES("73","SDN LOPOK BERU","SUMBAWA","","2026-02-09 10:31:39","2026-04-08 07:26:54");
INSERT INTO upz VALUES("74","SDN 1 UTAN","SUMBAWA","00","2026-02-14 12:08:49","2026-02-14 12:08:49");
INSERT INTO upz VALUES("75","SDN 7 SUMBAWA","SUMBAWA","00","2026-02-14 12:12:08","2026-02-14 12:12:08");
INSERT INTO upz VALUES("76","SDN 1 PLAMPANG","SUMBAWA","08","2026-02-15 06:11:30","2026-02-15 06:11:30");
INSERT INTO upz VALUES("77","SDN BRANG PELAT","SUMBAWA","08","2026-02-23 11:19:48","2026-02-23 11:19:48");
INSERT INTO upz VALUES("78","SDN 10 SUMBAWA","SUMBAWA","","2026-02-27 10:49:03","2026-02-27 10:50:56");
INSERT INTO upz VALUES("79","SDN 2 SUMBAWA","SUMBAWA","081","2026-03-03 10:12:27","2026-03-03 10:12:27");
INSERT INTO upz VALUES("80","SDN KEBAYAN SUMBAWA","SUMBAWA","081","2026-03-03 10:12:45","2026-03-03 10:12:45");
INSERT INTO upz VALUES("81","SDN 9 SUMBAWA","SUMBAWA","00","2026-03-03 10:12:57","2026-03-03 10:12:57");
INSERT INTO upz VALUES("83","SDN 1 SUMBAWA","SUMBAWA","082","2026-03-03 12:28:19","2026-03-03 12:28:19");
INSERT INTO upz VALUES("84","SDN JOMPONG ","SUMBAWA","085","2026-03-04 08:08:12","2026-03-04 08:08:12");
INSERT INTO upz VALUES("85","UPZ DESA BRANGKOLONG","SUMBAWA","085","2026-03-05 07:56:07","2026-03-16 09:15:25");
INSERT INTO upz VALUES("86","SDN 2 LANGAM","SUMBAWA","0812","2026-03-05 09:23:58","2026-03-05 09:23:58");
INSERT INTO upz VALUES("87","SDN TATEDE DALAM","SUMBAWA","085","2026-03-05 09:24:13","2026-03-05 09:24:13");
INSERT INTO upz VALUES("88","KEMENAG KAB.SUMBAWA","SUMBAWA","083","2026-03-05 10:30:35","2026-03-05 10:30:35");
INSERT INTO upz VALUES("89","SDN 1 MAPIN KEBAK ","SUMBAWA","","2026-03-06 08:49:03","2026-03-06 08:49:03");
INSERT INTO upz VALUES("90","SDN 6 SUMBAWA","SUMBAWA","","2026-03-09 07:24:38","2026-03-09 07:24:38");
INSERT INTO upz VALUES("91","SDN MALALO ","SUMBAWA","085","2026-03-09 08:32:59","2026-03-09 08:32:59");
INSERT INTO upz VALUES("92","SDN SERING","SUMBAWA","086","2026-03-09 08:34:38","2026-03-09 08:34:38");
INSERT INTO upz VALUES("93","SDN POTO","SUMBAWA","082","2026-03-09 08:37:12","2026-03-09 08:37:12");
INSERT INTO upz VALUES("94","SDN SEMANGAT BARU ALAS BARAT","SUMBAWA","085","2026-03-09 08:42:27","2026-03-09 09:51:03");
INSERT INTO upz VALUES("95","SDN MOKONG","SUMBAWA","084","2026-03-09 08:44:03","2026-03-09 08:44:03");
INSERT INTO upz VALUES("96","SDN 4 ALAS","SUMBAWA","085","2026-03-09 08:54:31","2026-03-09 08:54:31");
INSERT INTO upz VALUES("97","SDN 1 LAPE","SUMBAWA","081","2026-03-09 09:56:33","2026-03-09 09:56:33");
INSERT INTO upz VALUES("98","SDN BUKIT PERMAI","SUMBAWA","","2026-03-09 13:03:42","2026-04-08 07:26:36");
INSERT INTO upz VALUES("99","SDN SUKAMAJU ","SUMBAWA","0852","2026-03-09 13:25:29","2026-03-09 13:25:29");
INSERT INTO upz VALUES("100","BANK BNI CAB.SUMBAWA","SUMBAWA","087","2026-03-10 11:29:34","2026-03-10 11:29:34");
INSERT INTO upz VALUES("101","SDN 8 SUMBAWA","SUMBAWA","052","2026-03-10 14:49:16","2026-03-10 14:49:16");
INSERT INTO upz VALUES("102","SDN PELITA ","SUMBAWA","085","2026-03-11 07:32:43","2026-03-11 07:32:43");
INSERT INTO upz VALUES("103","SDN MAMAN","SUMBAWA","085","2026-03-11 07:33:03","2026-03-11 07:33:03");
INSERT INTO upz VALUES("104","SDN KARYA","SUMBAWA","085","2026-03-11 07:33:24","2026-03-11 07:33:24");
INSERT INTO upz VALUES("105","SDN 1 SEMAMUNG","SUMBAWA","082","2026-03-11 07:44:41","2026-03-17 12:33:42");
INSERT INTO upz VALUES("106","SDN PENEBIS LAPE","SUMBAWA","084","2026-03-11 08:14:11","2026-03-11 08:14:11");
INSERT INTO upz VALUES("107","SDN 3 LAPE","SUMBAWA","085","2026-03-11 08:51:00","2026-03-11 08:51:00");
INSERT INTO upz VALUES("108","SDN 7 ALAS","SUMBAWA","","2026-03-11 12:36:58","2026-04-08 07:26:11");
INSERT INTO upz VALUES("109","BANK BSI CAB.SUMBAWA","SUMBAWA","087","2026-03-12 08:02:11","2026-03-12 08:02:11");
INSERT INTO upz VALUES("110","SMPN 1 LENANGGUAR","SUMBAWA","085","2026-03-12 12:00:51","2026-03-12 12:00:51");
INSERT INTO upz VALUES("111","BPJS KETENAGAKERJAAN","SUMBAWA","085","2026-03-12 14:25:29","2026-03-12 14:25:29");
INSERT INTO upz VALUES("112","SDN UNTER MALANG","SUMBAWA","085","2026-03-13 11:51:39","2026-03-13 11:51:39");
INSERT INTO upz VALUES("113","SDN 2 ALAS","SUMBAWA","082","2026-03-13 11:53:07","2026-03-13 11:53:07");
INSERT INTO upz VALUES("114","SDN BARU MOYO UTARA","SUMBAWA","085","2026-03-13 11:54:30","2026-03-13 11:54:30");
INSERT INTO upz VALUES("115","SDN 1 PUNGKIT ","SUMBAWA","085","2026-03-13 11:56:04","2026-03-13 11:56:04");
INSERT INTO upz VALUES("116","UPZ DESA LANGAM","LANGAM","085","2026-03-13 12:01:50","2026-03-13 12:01:50");
INSERT INTO upz VALUES("117","SDN 8 ALAS","SUMBAWA","085","2026-03-13 12:13:38","2026-03-13 12:13:38");
INSERT INTO upz VALUES("118","BANK BRI CAB. SUMBAWA","SUMBAWA","085","2026-03-13 14:38:53","2026-03-13 14:38:53");
INSERT INTO upz VALUES("119","PAUD TAAMASA","SUMBAWA","0852","2026-03-13 14:41:22","2026-03-13 14:41:22");
INSERT INTO upz VALUES("120","SDN LABUHAN ALAS","SUMBAWA","","2026-03-14 09:11:38","2026-03-14 09:11:38");
INSERT INTO upz VALUES("121","SDN BINAMARGA UTAN","SUMBAWA","","2026-03-14 10:07:39","2026-03-14 10:09:45");
INSERT INTO upz VALUES("122","SMPN 3 SATAP UTAN","SUMBAWA","","2026-03-14 10:09:01","2026-03-14 10:09:01");
INSERT INTO upz VALUES("123","SDN 2 SARANGE","SUMBAWA","","2026-03-14 10:16:17","2026-03-14 10:16:17");
INSERT INTO upz VALUES("124","SDN 2 MAPIN KEBAK","SUMBAWA","","2026-03-14 10:58:58","2026-03-14 10:58:58");
INSERT INTO upz VALUES("125","SDN BRANGKOLONG","SUMBAWA","","2026-03-14 11:00:39","2026-03-14 11:00:39");
INSERT INTO upz VALUES("126","SDN JOROK","SUMBAWA","","2026-03-14 11:04:55","2026-03-14 11:04:55");
INSERT INTO upz VALUES("127","SDN SEMONGKAT","SUMBAWA","","2026-03-14 11:05:14","2026-03-14 11:05:14");
INSERT INTO upz VALUES("128","SDN 4 EMPANG","SUMBAWA","","2026-03-16 07:11:59","2026-03-16 07:11:59");
INSERT INTO upz VALUES("129","SDN KUKIN MOYO UTARA","SUMBAWA","","2026-03-16 07:15:07","2026-03-16 07:15:07");
INSERT INTO upz VALUES("130","SDN BAO DESA","SUMBAWA","","2026-03-16 07:17:14","2026-03-16 07:17:14");
INSERT INTO upz VALUES("131","SMPN 6 SATAP KEC. BATULANTEH","SUMBAWA","","2026-03-16 07:17:44","2026-03-16 07:22:50");
INSERT INTO upz VALUES("132","SDN LABUHAN IJUK","SUMBAWA","","2026-03-16 07:19:53","2026-03-16 07:19:53");
INSERT INTO upz VALUES("133","SDN NIJANG","SUMBAWA","085","2026-03-16 08:02:06","2026-03-16 08:02:06");
INSERT INTO upz VALUES("134","UPZ DESA BARU TAHAN","MOYO UTARA","0852","2026-03-16 09:37:08","2026-03-16 09:37:08");
INSERT INTO upz VALUES("135","UPZ DESA BERANG REA","MOYO HULU","085","2026-03-16 09:43:27","2026-03-16 09:43:27");
INSERT INTO upz VALUES("136","SDN LADAN KEC. BATULANTEH","SUMBAWA","","2026-03-16 11:54:10","2026-03-16 11:54:10");
INSERT INTO upz VALUES("137","DESA LESENG KECAMATAN MOYO HULU","SUMBAWA","","2026-03-17 08:12:31","2026-03-17 08:12:31");
INSERT INTO upz VALUES("138","DESA JOROK KECAMATAN UNTER IWES","UNTER IWES","0852","2026-03-17 11:29:07","2026-03-17 11:29:07");
INSERT INTO upz VALUES("139","DESA POTO KECAMATAN MOYO HILIR","MOYO HILIR","0852","2026-03-17 11:32:33","2026-03-17 11:32:33");
INSERT INTO upz VALUES("140","DESA SEPUKUR","LANTUNG","085","2026-03-17 12:21:45","2026-03-17 12:21:45");
INSERT INTO upz VALUES("142","SDN 2 SEMAMUNG","SEMAMUNG","085","2026-03-17 12:34:11","2026-03-17 12:34:11");
INSERT INTO upz VALUES("143","UPZ DESA BATU BULAN","BATU BULAN","085","2026-03-17 12:55:34","2026-03-17 12:55:34");
INSERT INTO upz VALUES("144","SDN KUANG AMO KEC. MOYO HULU","SUMBAWA","","2026-03-18 07:20:16","2026-03-18 07:20:16");
INSERT INTO upz VALUES("145","SMPN 2 BATULANTEH","SUMBAWA","","2026-03-18 07:24:20","2026-03-18 07:24:20");
INSERT INTO upz VALUES("146","UPZ DESA LOPOK","SUMBAWA","","2026-03-18 07:30:09","2026-03-18 07:30:09");
INSERT INTO upz VALUES("147","UPZ DESA KERATO","SUMBAWA","","2026-03-18 10:07:56","2026-03-18 10:09:07");
INSERT INTO upz VALUES("148","UPZ DESA EMPANG ATAS ","SUMBAWA","","2026-03-18 10:11:44","2026-03-18 10:11:44");
INSERT INTO upz VALUES("149","UPZ DESA GONTAR BARU","ALAS BARAT","089","2026-03-23 17:00:43","2026-03-23 17:00:43");
INSERT INTO upz VALUES("150","UPZ DESA SEBEWE","SEBEWE","097","2026-03-23 17:44:22","2026-03-23 17:44:22");
INSERT INTO upz VALUES("151","DESA KELUNGKUNG","SUMBAWA","","2026-03-31 07:22:04","2026-03-31 07:22:04");
INSERT INTO upz VALUES("152","SDIT ASA","SUMBAWA","087","2026-04-02 11:32:50","2026-04-02 11:32:50");
INSERT INTO upz VALUES("154","UPZ MUHALLA ALAM KERATO ASRI","SUMBAWA","9089080980","2026-04-02 14:19:39","2026-04-02 14:19:39");
INSERT INTO upz VALUES("155","GAPENSI KAB. SUMBAWA","SUMBAWA","0877","2026-04-06 12:03:56","2026-04-08 07:25:47");
INSERT INTO upz VALUES("156","SDN LITO JAM KEC. MOYO HULU","SUMBAWA","","2026-04-10 17:25:15","2026-04-10 17:25:15");


CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO users VALUES("1","pahriyadi","$2y$10$ZgdMhP1aauWquzrXF5FDwOycdctHoFBq8ol8KoRzdtxPcSI/Rv4B2","admin","Pahriyadi","admin@example.com","1","2026-01-11 22:13:50","2026-01-12 14:43:31");
INSERT INTO users VALUES("3","Fira Aulia","$2y$10$MKQPMr45FtNQbLVmGDrt2uTEHwHyyvKvDpyPEhs9iffYhHc31k1ge","admin","Fira Aulia Rahmadani","Firaaulia@gmail.com","1","2026-01-12 14:45:14","2026-01-12 14:45:14");
INSERT INTO users VALUES("4","erwinmahendra","$2y$10$cMNHj7HsBENVVGEWPpXi2OxGWoQX.Mysxi8fF2yu4MD9HS3oQaMXi","admin","ERWIN MAHENDRA","erwinmahendra02@gmail.com","1","2026-01-12 14:49:10","2026-03-31 15:22:42");
INSERT INTO users VALUES("5","Supriyadi","$2y$10$x2NKTa0OqN6tNab6PZ52oO9tY6VBq5u/J40jFBz9neW.PBeVjR07m","admin","Supriyadi","supriyadishi@yahoo.co.id","0","2026-02-13 12:17:27","2026-04-08 11:47:19");


CREATE TABLE `website_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO website_settings VALUES("1","site_title","Sistem Pengumpulan ZIS BAZNAS Kab. Sumbawa","2026-01-12 14:21:05","2026-02-05 10:00:30");
INSERT INTO website_settings VALUES("2","site_description","Sistem Manajemen Pengumpulan Zakat, Infak, dan Sedekah BAZNAS Kab. Sumbawa","2026-01-12 14:21:05","2026-02-05 10:00:30");
INSERT INTO website_settings VALUES("3","site_keywords","zakat, infak, sedekah, zis, pengumpulan, BAZNAS Kab. Sumbawa","2026-01-12 14:21:05","2026-02-05 10:00:30");
INSERT INTO website_settings VALUES("4","admin_email","fahri181095@gmail.com","2026-01-12 14:21:05","2026-02-05 10:00:30");
INSERT INTO website_settings VALUES("5","timezone","Asia/Makassar","2026-01-12 14:21:05","2026-01-12 14:27:19");
INSERT INTO website_settings VALUES("6","maintenance_mode","0","2026-01-12 14:21:05","2026-01-12 14:21:05");
INSERT INTO website_settings VALUES("7","favicon_path","assets/uploads/favicon/favicon_1768464926.png","2026-01-12 14:27:19","2026-01-15 15:15:26");

SET FOREIGN_KEY_CHECKS=1;