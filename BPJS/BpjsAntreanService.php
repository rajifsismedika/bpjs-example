<?php

require_once 'BpjsService.php';

class BpjsAntreanService extends BpjsService{

    public function getListTask($data = [])
    {
        $response = $this->post('antrean/getlisttask',$data);
        return json_decode($response, true);
    }
    public function addAntrean($data = [])
    {
        $response = $this->post('antrean/add', $data);
        return json_decode($response, true);
    }
    public function cancelAntrean($data = [])
    {
        $response = $this->post('antrean/batal', $data);
        return json_decode($response, true);
    }
    public function updateWaktu($data = [])
    {
        $response = $this->post('antrean/updatewaktu', $data);
        return json_decode($response, true);
    }
    public function getListPoli()
    {
        $response = $this->get('ref/poli');
        return json_decode($response, true);
    }
    public function getListDokter()
    {
        $response = $this->get('ref/dokter');
        return json_decode($response, true);
    }
    
    public function getJadwalDokter($kodepoli, $tanggal)
    {
        $response = $this->get('jadwaldokter/kodepoli/'.$kodepoli.'/tanggal/'.$tanggal);
        return json_decode($response, true);
    }

    public function updateJadwalDokter($data = [])
    {
        $response = $this->post('jadwaldokter/updatejadwaldokter', $data);
        return json_decode($response, true);
    }

    public function getDashboardPerTanggal($tanggal, $waktu)
    {
        $response = $this->get('/dashboard/waktutunggu/tanggal/'.$tanggal.'/waktu/'.$waktu);
        return json_decode($response, true);
    }

    public function getDashboardPerBulan($tahun, $bulan, $waktu)
    {
        $response = $this->get('/dashboard/waktutunggu/bulan/'.$bulan.'/tahun/'.$tahun.'/waktu/'.$waktu);
        return json_decode($response, true);
    }
}
