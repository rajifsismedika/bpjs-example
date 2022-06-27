<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Config Database HIS */
define("DB_HIS_HOST", "localhost");
define("DB_HIS_USER", "root");
define("DB_HIS_PASS", "toor");
define("DB_HIS_NAME", "pgicikini21");
/* Config Database Tokens */
define("DB_AUTH_HOST", "localhost");
define("DB_AUTH_USER", "root");
define("DB_AUTH_PASS", "toor");
define("DB_AUTH_NAME", "hisjkn");
/* Config Antrian2019 */
define('DB_ANTRIAN_HOST', 'localhost');
define('DB_ANTRIAN_USER', 'root');
define('DB_ANTRIAN_PASS', 'toor');
define('DB_ANTRIAN_NAME', 'antrian2019');

include 'Models/LogWaktuAntrian.php';
include 'Models/AntrianPrc.php';
include 'Logger/Logger.php';
require_once 'BPJS/BpjsAntreanService.php';

/*************** DATABASE CONNECTION SETUP ***************/
try {
    $HISJKN = new PDO("mysql:host=".DB_AUTH_HOST.";dbname=".DB_AUTH_NAME, DB_AUTH_USER, DB_AUTH_PASS);
    $HISJKN->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(\PDOException $e) {
    throw new Exception("Connection failed: " . $e->getMessage());
}

try {
    $HIS = new PDO("mysql:host=".DB_HIS_HOST.";dbname=".DB_HIS_NAME, DB_HIS_USER, DB_HIS_PASS);
    $HIS->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(\PDOException $e) {
    throw new Exception("Connection failed: " . $e->getMessage());
}

try {
    $ANTRIAN = new PDO("mysql:host=".DB_ANTRIAN_HOST.";dbname=".DB_ANTRIAN_NAME, DB_ANTRIAN_USER, DB_ANTRIAN_PASS);
    $ANTRIAN->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $antreanKey = [
      'cons_id'		=> 'xxxxx', 
      'secret_key'	=> 'xxxxxxxxxxx', 
      'user_key'		=> 'xxxxxxxxxxxxxxxxxxxxxxxx',
      'base_url'		=> 'https://apijkn.bpjs-kesehatan.go.id',
      'service_name'	=> 'antreanrs'
    ];
    $antreanBPJS = new BpjsAntreanService($antreanKey);
} catch(\PDOException $e) {
    throw new Exception("Connection failed: " . $e->getMessage());
}
/*************** END DATABASE CONNECTION SETUP ***************/


// Helpers
function getAll($connection, $query, $params = []) {
  $res = $connection->prepare($query);
  $res->execute($params);

  return $res->fetchAll(PDO::FETCH_ASSOC);
}

function makeResponse($message = "Ok", $code = 200, $response_data = null) {
  http_response_code($code);
  $response = [
      "metadata" => [
          "message"   => $message,
          "code"      => $code,
      ]
  ];
  if ($response_data) {
      $response['response'] = $response_data;
  }

  return json_encode($response, true); 
}



/*************** DATABASE LOGGING SETUP ***************/
// ONLY NEED TO CALLED ONCE

$logWaktuAntrian = new LogWaktuAntrian(DB_AUTH_HOST, DB_AUTH_USER, DB_AUTH_PASS, DB_AUTH_NAME);
$logWaktuAntrian->migrate();

function migrationSyncLog() {
    global $HISJKN;
    
    $check = getAll($HISJKN, "SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = '".DB_AUTH_NAME."' AND table_name = 'regpas_antrian_sync_history' LIMIT 1;");

    if (!count($check)) {
        $HISJKN->query("
        CREATE TABLE `regpas_antrian_sync_history` ( 
            `id` INT NOT NULL AUTO_INCREMENT , 
            `TanggalStart` DATETIME, 
            `TanggalEnd` DATETIME, 
            `Total` INT NULL,
            `TanggalBuat` TIMESTAMP NULL DEFAULT NOW(), 
            
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB;
        ");
    }
}
migrationSyncLog();

$antrianPrc = new AntrianPrc(DB_AUTH_HOST, DB_AUTH_USER, DB_AUTH_PASS, DB_AUTH_NAME);
$antrianPrc->migrate();
/*************** DATABASE LOGGING SETUP END ***************/




//************************START POPULATE************************ */
// Buat Data log_waktu_antrian dari Regpas
function processRegpas($regpas) {
    global $HIS;
    global $HISJKN;
    global $ANTRIAN;
    $data_log = [];

    $antrian = getAll($HIS, "SELECT AntrianID, KodeBooking FROM antrian WHERE RegPasID = ? AND NA = 'N' AND KodeBooking != '' LIMIT 1;", [
        $regpas['RegID']
    ]);

    if (count($antrian)) {
        $antrian = $antrian[0];
        $data_log = [
            'RegID' => $regpas['RegID'],
            'AntrianID' => $antrian['AntrianID'],
            'KodeBooking' => $antrian['KodeBooking'],
            'tunggu_admisi' => $regpas['TanggalBuat'],
            'layanan_admisi' => $regpas['TanggalBuat'],
            'tunggu_poli' => $regpas['TanggalBuat'],
            'layanan_poli' => null,
            'tunggu_farmasi' => null,
            'layanan_farmasi' => null,
            'selesai' => null
        ];

        $isLogAlreadyExist = getAll($HISJKN, "SELECT * FROM log_waktu_antrian WHERE KodeBooking = ? LIMIT 1;", [
            $antrian['KodeBooking']
        ]);

        if (count($isLogAlreadyExist) == 0) {
            $stmt = $HISJKN->prepare("INSERT INTO log_waktu_antrian
                (RegID,
                AntrianID,
                KodeBooking,
                tunggu_admisi,
                layanan_admisi,
                tunggu_poli,
                layanan_poli,
                tunggu_farmasi,
                layanan_farmasi,
                selesai,
                TanggalBuat) VALUES (?,?,?,?,?,?,?,?,?,?, now());");
            $stmt->execute([
                $data_log['RegID'],
                $data_log['AntrianID'],
                $data_log['KodeBooking'],
                $data_log['tunggu_admisi'],
                $data_log['layanan_admisi'],
                $data_log['tunggu_poli'],
                $data_log['layanan_poli'],
                $data_log['tunggu_farmasi'],
                $data_log['layanan_farmasi'],
                $data_log['selesai']
            ]);
            $waktu_antrian_id = $HISJKN->lastInsertId();

            $stmt = $HISJKN->prepare("INSERT INTO antrian_prc
                (waktu_antrian_id) VALUES (?);");
            $stmt->execute([
                $waktu_antrian_id
            ]);
        } else {
            echo 'Booking :'.$data_log['KodeBooking'].' Already Exist'. PHP_EOL;
        }
    }
}

// UNTUK MENGAWASI REGPAS YANG SUDAH DIPROSES SEHINGGA TIDAK DIPROSES BERULANG ULANG
function getFromRegpas($limit = 10) {
    global $HIS;
    global $HISJKN;

    $lastSync = getAll($HISJKN, "SELECT * FROM regpas_antrian_sync_history ORDER BY id DESC LIMIT 1");
    if (count($lastSync)) {
        $lastSync = $lastSync[0];
        $newSyncStart = $lastSync['TanggalEnd'];

        if ($newSyncStart == NULL) {
            $newSyncStart = $lastSync['TanggalStart'];
        }
    } else {
        $newSyncStart = '2022-02-00'; // DEFAULT START DATE AGAR TIDAK AMBIL SEMUA DATA REGPAS
    }

    // TODO CHANGE THIS
    $limit = $limit ? "LIMIT $limit" : '';
    $regpases = getAll($HIS, "SELECT RegID, TanggalBuat FROM regpas WHERE NA = 'N' AND TanggalBuat > ? ORDER BY TanggalBuat ASC ".$limit.";", [$newSyncStart]);

    $syncStart = '';
    $syncEnd = '';
    if (count($regpases)) {
        $syncStart = $regpases[0]['TanggalBuat'];
        if (count($lastSync) && $lastSync['TanggalEnd'] == null) {
            $syncId = $lastSync['id'];
        } else {
            $q = $HISJKN->query("INSERT INTO regpas_antrian_sync_history (TanggalStart, TanggalBuat) VALUES ('$syncStart', now());"); // Save sync start
            $syncId = $HISJKN->lastInsertId();
        }
        
        $syncEnd = null;
        $lastProcessedRegpas = null;
        $totalProcessedNumber = 0;
        try {
            $x = 0;
            foreach ($regpases as $regpas) {
                processRegpas($regpas);
                echo 'REGPAS '.$regpas['RegID'].' PROCESSED</br>'. PHP_EOL;

                $lastProcessedRegpas = $regpas;
                $totalProcessedNumber++;
                $syncEnd = $regpas['TanggalBuat'];
            }
        } catch(\Exception $e) {
            echo $e->getMessage();
            if ($lastProcessedRegpas) {
                $syncEnd = $lastProcessedRegpas['TanggalBuat'];
            }
        }

        if ($syncEnd) {
            $q = $HISJKN->query("UPDATE regpas_antrian_sync_history SET TanggalEnd='$syncEnd', total='$totalProcessedNumber' WHERE id='$syncId';"); // Save sync end
        }
    }
}
//***********************END POPULATE********************* */


//***********************START UPDATE DATA LOG*********** */
function updateLogWaktuAntrian() {
    global $HISJKN;
    global $HIS;
    global $ANTRIAN;

    $logWaktus = getAll($HISJKN, "SELECT
        RegID,
        tunggu_admisi,
        layanan_admisi,
        tunggu_poli,
        layanan_poli,
        tunggu_farmasi,
        layanan_farmasi,
        selesai
        FROM log_waktu_antrian WHERE 
            (tunggu_admisi IS NULL OR 
            layanan_admisi IS NULL OR
            tunggu_poli IS NULL OR
            layanan_poli IS NULL OR
            tunggu_farmasi IS NULL OR
            layanan_farmasi IS NULL OR
            selesai IS NULL) AND TanggalBuat >= DATE_ADD(CURDATE(), INTERVAL -1 DAY);");

    // TODO CHANGE THIS
    
    if (count($logWaktus)) {
        foreach ($logWaktus as $logWaktu) {
            $data_log = [];

            if ($logWaktu['tunggu_admisi'] == null || $logWaktu['layanan_admisi'] == null || $logWaktu['tunggu_poli'] == null) {
                $regpas = getAll($HIS, "SELECT RegID, TanggalBuat FROM regpas WHERE RegID = ? LIMIT 1", [$logWaktu['RegID']]);

                if (count($regpas)) {
                    if ($logWaktu['tunggu_admisi'] == null ) {
                        $logWaktu['tunggu_admisi'] = $regpas[0]['TanggalBuat'];
                    }
                    if ($logWaktu['layanan_admisi'] == null ) {
                        $logWaktu['layanan_admisi'] = $regpas[0]['TanggalBuat'];
                    }
                    if ($logWaktu['tunggu_poli'] == null ) {
                        $logWaktu['tunggu_poli'] = $regpas[0]['TanggalBuat'];
                    }
                }
            }

            // CARI layan_poli
            if ($logWaktu['layanan_poli'] == NULL || $logWaktu['tunggu_farmasi'] == NULL) {
                $detailPemeriksaanRj = getAll($HIS, "SELECT TanggalBuat, TanggalBuatNs, TanggalBuatDR FROM detail_pemeriksaan_rj WHERE RegID = ? AND NA = 'N' ORDER BY ID DESC LIMIT 1;", [$logWaktu['RegID']]);
                if (count($detailPemeriksaanRj)) {

                    // Determine Waktu layanan_poli (4)
                    if (empty($logWaktu['layanan_poli'])) {
                        if ($detailPemeriksaanRj[0]['TanggalBuatNs'] == '0000-00-00 00:00:00' || $detailPemeriksaanRj[0]['TanggalBuatNs'] == NULL) {
                            if ($detailPemeriksaanRj[0]['TanggalBuatDR'] == '0000-00-00 00:00:00' || $detailPemeriksaanRj[0]['TanggalBuatDR'] == NULL) {
                                // TanggalBuatNs dan TanggalBuatDR kosong
                                $data_log['layanan_poli'] = $detailPemeriksaanRj[0]['TanggalBuat'];
                            } else {
                                // Dokter Isi TanggalBuatDR tanpa perawat
                                // Ambil TanggalBuatDR dikurang (14-16 menit)
                                $data_log['layanan_poli'] = date('Y-m-d H:i:s', rand(strtotime($detailPemeriksaanRj[0]['TanggalBuatDR'] . ' -14 minutes'), strtotime($detailPemeriksaanRj[0]['TanggalBuatDR'] . ' -16 minutes')));
                            }
                        } else {
                            $data_log['layanan_poli'] = $detailPemeriksaanRj[0]['TanggalBuatNs'];
                        }
                    }

                    // Determine Waktu tunggu_farmasi / selesai poli (5)
                    if (empty($logWaktu['tunggu_farmasi'])) {
                        $datetimeLayananPoli = !empty($logWaktu['layanan_poli']) ? $logWaktu['layanan_poli'] : (isset($data_log['layanan_poli']) ? $data_log['layanan_poli']: null);
                        // Skip if detail_pemeriksaan_rj TanggalBuat belum diatas batas waktu, maka jangan dianggap 14-16 menit dulu

                        if (strtotime($datetimeLayananPoli . '+16 minutes') < strtotime('now')) {

                            if ($detailPemeriksaanRj[0]['TanggalBuatDR'] == '0000-00-00 00:00:00' || $detailPemeriksaanRj[0]['TanggalBuatDR'] == NULL) {

                                if ($datetimeLayananPoli) {
                                    $data_log['tunggu_farmasi'] = date('Y-m-d H:i:s', rand(strtotime($datetimeLayananPoli . ' +14 minutes'), strtotime($datetimeLayananPoli . ' +16 minutes')));
                                }
    
                            } else {
                                $data_log['tunggu_farmasi'] = $detailPemeriksaanRj[0]['TanggalBuatDR'];
                            }
                        }
                    }
                }
            }

            // cari DATA FARMASI
            if (!empty($logWaktu['layanan_farmasi']) && !empty($logWaktu['selesai'])) {
                //LENGKAP
            } else {
                $nomor = getAll($ANTRIAN, "SELECT * FROM nomor WHERE RegID = ? ORDER BY ID DESC LIMIT 1;", [$logWaktu['RegID']]);
                if (count($nomor)) {
                    if (empty($logWaktu['layanan_farmasi']) && !empty($nomor[0]['TanggalPelayanan'])) {
                        $data_log['layanan_farmasi'] = $nomor[0]['TanggalPelayanan'];
                    };
                    if (empty($logWaktu['selesai']) && !empty($nomor[0]['TanggalCetak'])) {
                        $data_log['selesai'] = $nomor[0]['TanggalPanggil'];
                    };
                }
            }

            if (count($data_log)) {
                $query = "UPDATE log_waktu_antrian SET ";
                foreach($data_log as $field => $value) {
                    $query .= "$field = ".($value == '' ? 'NULL' : "'".$value."'").", ";
                }
                $query .= "TanggalUpdate = now() WHERE RegID = '".$logWaktu['RegID']."';";

                print_r('Updating Log : '.$logWaktu['RegID'].' '. PHP_EOL);
                $HISJKN->query($query);
            }
        }
    }   
}
//***********************END UPDATE DATA LOG*********** */


//***********************START SEND TO BPJS*********** */

$stamp = strtotime('now');
/***
 * Kirim Data Update Status Booking Ke BPJS
 */
// Copied From API, with some modification of return and Log Name
function update_status($request) {
	global $antreanBPJS;
	global $stamp;
	Logger::info('[CRON-Update Status]['.$stamp.'] Start');
	$mapping_status = [
		'tungguadmisi' => 1, // jkn CHECKIN / APEM
		'dilayaniadmisi' => 2, 
		'tunggupoli' => 3, // pendaftaran_rj SUBMIT
		'dilayanipoli' => 4, // leadtime ON NEXT_POLI
		'tunggufarmasi' => 5, // leadtime selesai_dokter
		'selesaifarmasi' => 6, // leadtime LAYANI
		'selesai' => 7, // leadtime SELESAIKAN
	];
	if (!array_key_exists($request['status'], $mapping_status)) {
		$available_status = array_keys($mapping_status);
		$available_status = implode(', ', $available_status);
		Logger::info('[CRON-Update Status]['.$stamp.'] Failed Wrong Status');
		return (makeResponse("Status tidak dikenali, silahkan gunakan : ".$available_status, 422));
	}
	
	try {
		// Pastikan ada di LogWaktuAntrian dan Antrian PRC
        global $logWaktuAntrian;
        $logWaktuAntrian = $logWaktuAntrian->create($request['KodeBooking'], $request['AntrianID'], $request['RegID']);
        
        global $antrianPrc;
        $prc_id = $antrianPrc->create($logWaktuAntrian['id']);

        // Mapping Status ke Data Log $mapping_status > $waktuataMapping
        $waktuDataMapping = [
            1 => 'tunggu_admisi',
            2 => 'layanan_admisi',
            3 => 'tunggu_poli',
            4 => 'layanan_poli',
            5 => 'tunggu_farmasi',
            6 => 'layanan_farmasi',
            7 => 'selesai',
        ];

		$status = [
			'kodebooking' => $request['KodeBooking'],
			'taskid' => $mapping_status[$request['status']],
			'waktu' => isset($request['waktu']) ? strtotime($request['waktu'])*1000 : round(microtime(true) * 1000)
		];

		Logger::info('[CRON-Update Status]['.$stamp.'] Request : '. json_encode($status));
    // Kirim
		$response = $antreanBPJS->updateWaktu($status);

    // Handle Response
		if ($response) {
			Logger::info('[CRON-Update Status]['.$stamp.'] Success : '. json_encode($response));
			if ($response['metadata']['code'] == 200) {
				$logWaktuData = [
					$waktuDataMapping[$mapping_status[$request['status']]] => date('Y-m-d H:i:s', $status['waktu']/1000),
					$waktuDataMapping[$mapping_status[$request['status']]].'_sent' => 1,
					$waktuDataMapping[$mapping_status[$request['status']]].'_response' => $response['metadata']['message']
				];
				$logWaktuAntrian->updateByKodeBooking($request['KodeBooking'], $logWaktuData);

				return (makeResponse("Ok", 200, $response));
			} else {
				$logWaktuData = [
					$waktuDataMapping[$mapping_status[$request['status']]] => date('Y-m-d H:i:s', $status['waktu']/1000),
					$waktuDataMapping[$mapping_status[$request['status']]].'_sent' => -1,
					$waktuDataMapping[$mapping_status[$request['status']]].'_response' => $response['metadata']['message']
				];
				$logWaktuAntrian->updateByKodeBooking($request['KodeBooking'], $logWaktuData);

                Logger::error('[CRON-Update Status]['.$stamp.'] Failed : '. json_encode($response));
				return (makeResponse($response['metadata']['message'], $response['metadata']['code']));
			}
		} else {
			Logger::error('[CRON-Update Status]['.$stamp.'] Error : '. json_encode($response));
			return (makeResponse("Gagal update status antrean ke BPJS", 500, $response));
		}
	} catch (\Exception $e) {
		Logger::error('[CRON-Update Status]['.$stamp.'] ERROR : '.$e->getMessage());
		return (makeResponse($e->getMessage(), 400));
	}
}

// TODO FILL THIS
function getDataAntrean($antrian_id)
{
    // TODO AMBIL DARI TABEL ANTRIAN 
    return [
        "kodebooking" => "16032021A001",
        "jenispasien" => "JKN",
        "nomorkartu" => "00012345678",
        "nik" => "3212345678987654",
        "nohp" => "085635228888",
        "kodepoli" => "ANA",
        "namapoli" => "Anak",
        "pasienbaru" => 0,
        "norm" => "123345",
        "tanggalperiksa" => "2021-01-28",
        "kodedokter" => 12345,
        "namadokter" => "Dr. Hendra",
        "jampraktek" => "08:00-16:00",
        "jeniskunjungan" => 1,
        "nomorreferensi" => "0001R0040116A000001",
        "nomorantrean" => "A-12",
        "angkaantrean" => 12,
        "estimasidilayani" => 1615869169000,
        "sisakuotajkn" => 5,
        "kuotajkn" => 30,
        "sisakuotanonjkn" => 5,
        "kuotanonjkn" => 30,
        "keterangan" => "Peserta harap 30 menit lebih awal guna pencatatan administrasi."
    ];
}

/***
 * Kirim Data Kode Booking Ke BPJS
 */
// Copied From API, with some modification of return and Log Name
function new_appointment($request) {
	global $antreanBPJS;
	global $stamp;
    global $skipDokterID;
    global $skipDepartemenID;

	Logger::info('[CRON-New Antrian]['.$stamp.'] Processed with data : '. json_encode($request));
	try {
    /***** AMBIL DETAIL DATA ANTRIAN/BOOKING UNTUK DIKIRIM KE BPJS */ 
		// if (empty($request['AntrianID']) && isset($request['RegPasID'])) {
		// 	$request['AntrianID'] = getAntrianIdByRegpas($request['RegPasID']);
		// }
		$antrian = getDataAntrean($request['AntrianID']);
    
    // LEWATI DOKTER ATAU POLI
        if (isset($skipDepartemenID) && in_array($antrian['DepartemenID'], $skipDepartemenID)) {
		    Logger::info('[CRON-New Antrian]['.$stamp.'] Skipped: by DepartemenID'. ($antrian['DepartemenID']));
            return ['status' => 201, 'message' => 'SKIPPED by DepartemenID'];
        }
        if (isset($skipDokterID) && in_array($antrian['DokterID'], $skipDokterID)) {
		    Logger::info('[CRON-New Antrian]['.$stamp.'] Skipped: by DokterID'. ($antrian['DepartemenID']));
            return ['status' => 201, 'message' => 'SKIPPED by DokterID'];
        }
    
		$antrian['jeniskunjungan'] = isset($request['JKNJenisKunjungan']) ? $request['JKNJenisKunjungan'] : (!empty($request['jeniskunjungan']) ? $request['jeniskunjungan'] : 1);
		if ($antrian['jenispasien'] == 'JKN') {
			$antrian['nomorreferensi'] = isset($request['JKNNoRujukan']) ? $request['JKNNoRujukan'] : $antrian['nomorreferensi'];
		} else {
			$antrian['nomorreferensi'] = '';
		}
		Logger::info('[CRON-New Antrian]['.$stamp.'] Request: '. json_encode($antrian));

		// Pastikan terCatat ke Log Waktu Antrian
    global $logWaktuAntrian;
		$logWaktuAntrian = $logWaktuAntrian->create($antrian['kodebooking'], $antrian['AntrianID'], $antrian['RegPasID']);
    
    global $antrianPrc;
		$prc_id = $antrianPrc->create($logWaktuAntrian['id']);
		
		$array_to_bpjs = array_intersect_key($antrian, array_flip([
			'kodebooking',
			'jenispasien',
			'nomorkartu',
			'nik',
			'nohp',
			'kodepoli',
			'namapoli',
			'pasienbaru',
			'norm',
			'tanggalperiksa',
			'kodedokter',
			'namadokter',
			'jampraktek',
			'jeniskunjungan',
			'nomorreferensi',
			'nomorantrean',
			'angkaantrean',
			'estimasidilayani',
			'sisakuotajkn',
			'kuotajkn',
			'sisakuotanonjkn',
			'kuotanonjkn',
			'keterangan',
		]));

        // KIRIM TO BPJS
		$response = $antreanBPJS->addAntrean($array_to_bpjs);
		// $response = ['metadata' => ['code' => 200, 'message' => 'OK'], 'response' => $array_to_bpjs];

    // Handle Response
		if ($response) {
			Logger::info('[CRON-New Antrian]['.$stamp.'] Response : '. json_encode($response));
			if ($response['metadata']['code'] == 200) {
				$logWaktuAntrian->updateByKodeBooking($antrian['kodebooking'], [
					'booking_sent' => 1,
					'booking_response' => date('Y-m-d H:i:s'),
				]);
				$prc_id = $antrianPrc->create($logWaktuAntrian['id'], 1);

				Logger::info('[CRON-New Antrian]['.$stamp.']  Success : '. json_encode($response));
				return ['status' => 200, 'message' => 'OK', 'response' => $response];
			} else if ($response['metadata']['code'] == 208 || $response['metadata']['message'] == 'Terdapat duplikasi Kode Booking') {
                // Kode Booking Sudah ada di BPJS
				Logger::info('[CRON-New Antrian]['.$stamp.']  Rejected : Sudah Terkirim');

				$logWaktuAntrian->updateByKodeBooking($antrian['kodebooking'], [
					'booking_sent' => 1
				]);
				return ['status' => 200, 'message' => 'OK - Already', 'response' => $response];
            } else {
				$logWaktuAntrian->updateByKodeBooking($antrian['kodebooking'], [
					'booking_sent' => '-1',
					'booking_response' => $response['metadata']['message'],
				]);
				// $prc_id = $antrianPrc->create_antrian_prc($logWaktuAntrian['id']);
				Logger::info('[CRON-New Antrian]['.$stamp.']  Error : '. json_encode($response));
				return ['status' => $response['metadata']['code'], 'message' => 'REJECTED', 'response' => $response];
			}
		} else {
			Logger::error('[CRON-New Antrian] Gagal Terkirim ke BPJS');
            return ['status' => 500, 'message' => 'NO RESPONSE'];
		}

	} catch (\Exception $e) {
		// $prc_id = $antrianPrc->create_antrian_prc($logWaktuAntrian['id']);
		Logger::error('[CRON-New Antrian] ERROR : '.$e->getMessage());
		return ['status' => 500, 'message' => $e->getMessage()];
	}
}

/**
 * Proses pengiriman data yang masih ada di antrian prc, berdasarkan waktu yang sudah tercatat di log_waktu_antrian
 */
function sentToBPJS($KodeBooking = null) {
    global $HISJKN;

    if ($KodeBooking) {
        $logWaktus = getAll($HISJKN, "SELECT log_waktu_antrian.*
        FROM log_waktu_antrian WHERE 
            KodeBooking = ?", [$KodeBooking]);
    } else {
        $logWaktus = getAll($HISJKN, "SELECT *
        FROM antrian_prc 
        LEFT JOIN log_waktu_antrian 
            ON antrian_prc.waktu_antrian_id = log_waktu_antrian.id
        WHERE 
            antrian_prc.TanggalBuat >= DATE_ADD(CURDATE(), INTERVAL -1 DAY)
            AND (
            log_waktu_antrian.tunggu_admisi_sent = 0 OR 
            log_waktu_antrian.layanan_admisi_sent = 0 OR
            log_waktu_antrian.tunggu_poli_sent = 0 OR
            log_waktu_antrian.layanan_poli_sent = 0 OR
            log_waktu_antrian.tunggu_farmasi_sent = 0 OR
            log_waktu_antrian.layanan_farmasi_sent = 0 OR
            log_waktu_antrian.selesai_sent = 0
            );");
    }

    foreach ($logWaktus as $logWaktu) {
        $kirim = [];

        // Kirim Antrian to BPJS
        if ($logWaktu['booking_sent'] != 1) {
            try {
                echo 'KIRIM ANTRIAN '.$logWaktu['AntrianID']. PHP_EOL;
                $kirim = new_appointment(['AntrianID' => $logWaktu['AntrianID']]);
            } catch (\Exception $e) {
                echo 'GAGAL KIRIM ANTRIAN: '.$logWaktu['AntrianID'].' - '.$e->getMessage().''. PHP_EOL;
                continue;
            }

            if ($kirim['status'] != 200) {
                echo 'GAGAL KIRIM ANTRIAN: '.$logWaktu['AntrianID'].' - '.$kirim['message'].' : '. @json_encode($kirim['response']).''. PHP_EOL;
                continue;
            }
        } else {
            $kirim['status'] = 200;
        }

        // 1
        if (($logWaktu['booking_sent'] == 1 || $kirim['status'] == 200) && !empty($logWaktu['tunggu_admisi']) && $logWaktu['tunggu_admisi_sent'] == '0') {
            try {
                echo 'TRY SENDING TUNGGU ADMISI '.$logWaktu['KodeBooking'].' '.$logWaktu['tunggu_admisi'].''. PHP_EOL;
                $data = [
                    'KodeBooking' => $logWaktu['KodeBooking'],
                    'AntrianID' => $logWaktu['KodeBooking'],
                    'RegID' => $logWaktu['RegID'],
                    'waktu' => $logWaktu['tunggu_admisi'],
                    'status' => 'tungguadmisi',
                ];
                $response = update_status($data);
                $response = json_decode($response, true);
                if (isset($response['metadata']) && $response['metadata']['code'] != 200) {
                    continue;            
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // 2
        if (($logWaktu['booking_sent'] == 1 || $kirim['status'] == 200) && !empty($logWaktu['layanan_admisi']) && $logWaktu['layanan_admisi_sent'] == '0') {
            try {
                echo 'TRY SENDING LAYANAN ADMISI '.$logWaktu['KodeBooking'].' '.$logWaktu['layanan_admisi'].''. PHP_EOL;
                $data = [
                    'KodeBooking' => $logWaktu['KodeBooking'],
                    'AntrianID' => $logWaktu['KodeBooking'],
                    'RegID' => $logWaktu['RegID'],
                    'waktu' => $logWaktu['layanan_admisi'],
                    'status' => 'dilayaniadmisi',
                ];
                $response = update_status($data);
                $response = json_decode($response, true);
                if (isset($response['metadata']) && $response['metadata']['code'] != 200) {
                    continue;            
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // 3
        if (($logWaktu['booking_sent'] == 1 || $kirim['status'] == 200) && !empty($logWaktu['tunggu_poli']) && $logWaktu['tunggu_poli_sent'] == '0') {
            try {
                echo 'TRY SENDING TUNGGU POLI '.$logWaktu['KodeBooking'].' '.$logWaktu['tunggu_poli'].''. PHP_EOL;
                $data = [
                    'KodeBooking' => $logWaktu['KodeBooking'],
                    'AntrianID' => $logWaktu['KodeBooking'],
                    'RegID' => $logWaktu['RegID'],
                    'waktu' => $logWaktu['tunggu_poli'],
                    'status' => 'tunggupoli',
                ];
                $response = update_status($data);
                $response = json_decode($response, true);
                if (isset($response['metadata']) && $response['metadata']['code'] != 200) {
                    continue;            
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // 4
        if (($logWaktu['booking_sent'] == 1 || $kirim['status'] == 200) && !empty($logWaktu['layanan_poli']) && $logWaktu['layanan_poli_sent'] == '0') {
            try {
                echo 'TRY SENDING LAYANAN_POLI '.$logWaktu['KodeBooking'].' '.$logWaktu['layanan_poli'].''. PHP_EOL;
                $data = [
                    'KodeBooking' => $logWaktu['KodeBooking'],
                    'AntrianID' => $logWaktu['KodeBooking'],
                    'RegID' => $logWaktu['RegID'],
                    'waktu' => $logWaktu['layanan_poli'],
                    'status' => 'dilayanipoli',
                ];
                $response = update_status($data);
                $response = json_decode($response, true);
                if (isset($response['metadata']) && $response['metadata']['code'] != 200) {
                    continue;            
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // 5
        if (($logWaktu['booking_sent'] == 1 || $kirim['status'] == 200) && !empty($logWaktu['tunggu_farmasi']) && $logWaktu['tunggu_farmasi_sent'] == '0') {
            try {
                echo 'TRY SENDING TUNGGU_FARMASI'.$logWaktu['KodeBooking'].' '.$logWaktu['tunggu_farmasi'].''. PHP_EOL;
                $data = [
                    'KodeBooking' => $logWaktu['KodeBooking'],
                    'AntrianID' => $logWaktu['KodeBooking'],
                    'RegID' => $logWaktu['RegID'],
                    'waktu' => $logWaktu['tunggu_farmasi'],
                    'status' => 'tunggufarmasi',
                ];
                $response = update_status($data);
                $response = json_decode($response, true);
                if (isset($response['metadata']) && $response['metadata']['code'] != 200) {
                    continue;            
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // 6
        if (($logWaktu['booking_sent'] == 1 || $kirim['status'] == 200) && !empty($logWaktu['layanan_farmasi']) && $logWaktu['layanan_farmasi_sent'] == '0') {
            try {
                echo 'TRY SENDING LAYANAN FARMASI'.$logWaktu['KodeBooking'].' '.$logWaktu['layanan_farmasi'].''. PHP_EOL;
                $data = [
                    'KodeBooking' => $logWaktu['KodeBooking'],
                    'AntrianID' => $logWaktu['KodeBooking'],
                    'RegID' => $logWaktu['RegID'],
                    'waktu' => $logWaktu['layanan_farmasi'],
                    'status' => 'selesaifarmasi',
                ];
                $response = update_status($data);
                $response = json_decode($response, true);
                if (isset($response['metadata']) && $response['metadata']['code'] != 200) {
                    continue;            
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // 7
        if (($logWaktu['booking_sent'] == 1 || $kirim['status'] == 200) && !empty($logWaktu['selesai']) && $logWaktu['selesai_sent'] == '0') {
            try {
                echo 'TRY SENDING SELESAI'.$logWaktu['KodeBooking'].' '.$logWaktu['selesai'].''. PHP_EOL;
                $data = [
                    'KodeBooking' => $logWaktu['KodeBooking'],
                    'AntrianID' => $logWaktu['KodeBooking'],
                    'RegID' => $logWaktu['RegID'],
                    'waktu' => $logWaktu['selesai'],
                    'status' => 'selesai',
                ];
                $response = update_status($data);
                $response = json_decode($response, true);
                if (isset($response['metadata']) && $response['metadata']['code'] != 200) {
                    continue;            
                } else {
                    global $antrianPrc;
                    $antrianPrc->deleteByLogWaktuAntrianID($logWaktu['id']);
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}

function clearUpAntrianPrc() {
    global $antrianPrc;
    $antrianPrc->get('DELETE FROM antrian_prc WHERE TanggalBuat <= DATE_ADD(CURDATE(), INTERVAL -1 DAY)');
}

if (isset($_REQUEST['KodeBooking'])) {
    echo 'ONLY SEND BOOKING : '.$_REQUEST['KodeBooking'].''. PHP_EOL;
    sentToBPJS($_REQUEST['KodeBooking']);
} else {
    // clearUpAntrianPrc();
    $percycle = defined('REGPAS_PER_CRON') ? REGPAS_PER_CRON : 20;
    getFromRegpas($percycle); // SET LIMIT PER PROCESS
    updateLogWaktuAntrian();
    sentToBPJS();
}
