<?php
require_once 'BaseModel.php';

// Antrian PRC is used to see what data that still need to sent to bpjs
class AntrianPrc extends BaseModel{

    private $table_name = 'antrian_prc';

    public function __construct($host, $username, $password, $db_name, $charset = 'utf8')
    {
        parent::__construct($host, $username, $password, $db_name, $charset);
    }

    public function migrate()
    {
        $check = $this->get("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1;", [$this->db_name, $this->table_name]);

        if (!count($check)) {
            $this->connection->query("
            CREATE TABLE `antrian_prc` ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `waktu_antrian_id` INT NOT NULL, 
                
                `booking_sent` TINYINT(1) NULL,
                
                `tunggu_admisi_sent` TINYINT(1) NULL, 

                `layanan_admisi_sent` TINYINT(1) NULL, 

                `tunggu_poli_sent` TINYINT(1) NULL, 

                `layanan_poli_sent` TINYINT(1) NULL, 

                `tunggu_farmasi_sent` TINYINT(1) NULL, 
    
                `layanan_farmasi_sent` TINYINT(1) NULL, 

                `selesai_sent` TINYINT(1) NULL, 

                `TanggalBuat` TIMESTAMP NULL DEFAULT NOW(), 
                `TanggalEdit` DATETIME NULL, 
                
                PRIMARY KEY (`id`),
                UNIQUE (`waktu_antrian_id`)
            ) ENGINE = InnoDB;
            ");
        }
    }
    
    public function get_all()
    {
        $query = "SELECT * FROM $this->table_name";
        return $this->get($query);
    }

    public function create_antrian_prc($waktu_antrian_id)
    {
        $antrian_prc = $this->find('SELECT * FROM antrian_prc WHERE waktu_antrian_id = :waktu_antrian_id', ['waktu_antrian_id' => $waktu_antrian_id]);

        if ($antrian_prc) {
            return $antrian_prc;
        } else {
            $stmt = $this->connection->prepare("INSERT INTO $this->table_name (waktu_antrian_id) VALUES(?) ON DUPLICATE KEY UPDATE    
            waktu_antrian_id=?");
            $result = $stmt->execute([
                $waktu_antrian_id,
                $waktu_antrian_id
            ]);
    
            return $this->find('SELECT * FROM antrian_prc WHERE waktu_antrian_id = :waktu_antrian_id', ['waktu_antrian_id' => $waktu_antrian_id]);
        }
    }

    public function create($waktu_antrian_id, $booking_sent = NULL, $waktu_sents = [])
    {
        $stmt = $this->connection->prepare("INSERT INTO $this->table_name (waktu_antrian_id, booking_sent) VALUES(
            :waktu_antrian_id,
            :booking_sent
        ) ON DUPLICATE KEY UPDATE    
            waktu_antrian_id = :waktu_antrian_id, booking_sent = :booking_sent");
            $result = $stmt->execute([
                'waktu_antrian_id' => $waktu_antrian_id,
                'booking_sent' => isset($booking_sent) ? ($booking_sent ? 1 : -1) : NULL,
            ]);
        return $this->find("SELECT * FROM $this->table_name WHERE waktu_antrian_id = :waktu_antrian_id", ['waktu_antrian_id' => $waktu_antrian_id]);
    }
   
    public function deleteByLogWaktuAntrianID($waktu_antrian_id)
    {
        $stmt = $this->connection->prepare("DELETE FROM `antrian_prc` WHERE `waktu_antrian_id` = ?");
        return $stmt->execute([ $waktu_antrian_id]);
    }
}
