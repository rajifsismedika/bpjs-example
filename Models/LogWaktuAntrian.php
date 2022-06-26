<?php
require_once 'BaseModel.php';

class LogWaktuAntrian extends BaseModel{

    private $table_name = 'log_waktu_antrian';

    public function __construct($host, $username, $password, $db_name, $charset = 'utf8')
    {
        parent::__construct($host, $username, $password, $db_name, $charset);
    }
    
    public function migrate() {
        
        $check = $this->get("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1;", [$this->db_name, $this->table_name]);
    
        if (!count($check)) {
            $this->connection->query("
            CREATE TABLE `log_waktu_antrian` ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `RegID` VARCHAR(50) NULL , 
                `AntrianID` BIGINT(20) NOT NULL , 
                `KodeBooking` VARCHAR(13) NOT NULL , 
    
                `tunggu_admisi` DATETIME NULL , 
                `tunggu_admisi_sent` TINYINT(1) NULL DEFAULT 0 , 
                `tunggu_admisi_response` TEXT NULL , 
    
                `layanan_admisi` DATETIME NULL , 
                `layanan_admisi_sent` TINYINT(1) NULL DEFAULT 0 , 
                `layanan_admisi_response` TEXT NULL , 
    
                `tunggu_poli` DATETIME NULL , 
                `tunggu_poli_sent` TINYINT(1) NULL DEFAULT 0 , 
                `tunggu_poli_response` TEXT NULL , 
    
                `layanan_poli` DATETIME NULL , 
                `layanan_poli_sent` TINYINT(1) NULL DEFAULT 0 , 
                `layanan_poli_response` TEXT NULL , 
    
                `tunggu_farmasi` DATETIME NULL , 
                `tunggu_farmasi_sent` TINYINT(1) NULL DEFAULT 0 , 
                `tunggu_farmasi_response` TEXT NULL , 
    
                `layanan_farmasi` DATETIME NULL , 
                `layanan_farmasi_sent` TINYINT(1) NULL DEFAULT 0 , 
                `layanan_farmasi_response` TEXT NULL , 
    
                `selesai` DATETIME NULL , 
                `selesai_sent` TINYINT(1) NULL DEFAULT 0 , 
                `selesai_response` TEXT NULL , 
                `TanggalBuat` TIMESTAMP NULL DEFAULT NOW(),
                `TanggalUpdate` DATETIME NULL ,
                
                PRIMARY KEY (`id`), 
                INDEX (`RegID`, `KodeBooking`, `AntrianID`)
            ) ENGINE = InnoDB;
            ");
        }
    
    
        $check = $this->get("SHOW COLUMNS FROM `log_waktu_antrian` LIKE 'booking_sent';");
    
        if (!count($check)) {
            $this->connection->query("ALTER TABLE `log_waktu_antrian` 
                ADD `booking_sent` TINYINT(1) NULL DEFAULT 0 AFTER `KodeBooking`, 
                ADD `booking_response` TEXT NULL AFTER `booking_sent`;");
        }
    
        $check = $this->find("SHOW COLUMNS FROM `log_waktu_antrian` LIKE 'KodeBooking';");
    
        // $check = getAll($HISJKN, "SHOW COLUMNS FROM `log_waktu_antrian` LIKE 'KodeBooking';");
        if (count($check) && $check['Key'] != 'UNI') {
            $this->connection->query("ALTER TABLE `log_waktu_antrian` ADD UNIQUE(`KodeBooking`);");
        }
    }

    public function get_all()
    {
        $query = "SELECT * FROM $this->table_name";
        return $this->get($query);
    }

    public function create($KodeBooking, $AntrianID, $RegID = null, $isSent = null)
    {
        // $log_waktu_antrian = $this->find('SELECT * FROM log_waktu_antrian WHERE KodeBooking = :KodeBooking', ['KodeBooking' => $KodeBooking]);
        $stmt = $this->connection->prepare("INSERT INTO $this->table_name (KodeBooking, AntrianID, RegID) VALUES(
            :KodeBooking,
            :AntrianID,
            :RegID
        ) ON DUPLICATE KEY UPDATE RegID = :RegID ");
            $result = $stmt->execute([
                'KodeBooking' => $KodeBooking,
                'AntrianID' => $AntrianID,
                'RegID' => $RegID,
            ]);
        return $this->find('SELECT * FROM log_waktu_antrian WHERE KodeBooking = :KodeBooking', ['KodeBooking' => $KodeBooking]);
    }

    public function updateByKodeBooking($KodeBooking, $data) {
        $currentLog = $this->find('SELECT * FROM log_waktu_antrian WHERE KodeBooking = :KodeBooking', ['KodeBooking' => $KodeBooking]);

        // Abaikan jika status sent sudah 1
        foreach ($data as $field => $value) {
            if (in_array($field, [
                'booking_sent',
                'tunggu_admisi_sent',
                'layanan_admisi_sent',
                'tunggu_poli_sent',
                'layanan_poli_sent',
                'tunggu_farmasi_sent',
                'layanan_farmasi_sent',
                'selesai_sent',
                ])
            ) {
                if ($currentLog[$field] == 1) {
                    unset($data[$field]);
                    $fieldName = str_replace('_sent', '', $field);
                    unset($data[$fieldName]);
                    unset($data[$fieldName.'_response']);
                }
            }
        }

        if (count($data)) {
            $query = 'UPDATE log_waktu_antrian SET ';
            foreach ($data as $key => $value) {
                $query .= $key . ' = :' . $key . ', ';
            }

            $query = rtrim($query, ', ');
            $query .= " WHERE KodeBooking = '". $KodeBooking ."'";

            $stmt = $this->connection->prepare($query);
            $result = $stmt->execute($data);
            
            return $result;
        }
    }
    
}
