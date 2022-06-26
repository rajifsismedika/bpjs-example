<?php

require_once 'BpjsService.php';

class BpjsVclaimService extends BpjsService
{
    /********************* REFERENSI ******************** */

    // $param = Kode atau Nama Poli
    public function referensiPoli($param) {
        $path = 'referensi/poli/'.$param;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    // $param = Kode atau Nama Diagnosa
    public function referensiDiagnosa($param) {
        $path = 'referensi/diagnosa/'.$param;
    
        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function referensiPropinsi() {
        $path = 'referensi/propinsi';

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function referensiKabupaten($kodePropinsi) {
        $path = 'referensi/kabupaten/propinsi/'.$kodePropinsi;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function referensiKecamatan($kodeKabupaten) {
        $path = 'referensi/kecamatan/kabupaten/'.$kodeKabupaten;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function referensiDokterDPJP($jenisPelayanan, $tglPelayanan, $kodeSpesialis) {
        $path = 'referensi/dokter/pelayanan/'.$jenisPelayanan.'/tglPelayanan/'.$tglPelayanan.'/Spesialis/'.$kodeSpesialis;

        $response = $this->get($path);
        return json_decode($response, true);
    }
    
    public function referensiCaraKeluar() {
        $path = 'referensi/carakeluar';

        $response = $this->get($path);
        return json_decode($response, true);
    }
    
    public function referensiKelasRawat() {
        $path = 'referensi/kelasrawat';

        $response = $this->get($path);
        return json_decode($response, true);
    }
    
    public function referensiPascaPulang() {
        $path = 'referensi/pascapulang';

        $response = $this->get($path);
        return json_decode($response, true);
    }
    
    public function referensiProcedure($param) {
        $path = 'referensi/procedure/'.$param;

        $response = $this->get($path);
        return json_decode($response, true);
    }
    
    public function referensiDokter($param) {
        $path = 'referensi/dokter/'.$param;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function referensiFaskes($jenisFaskes, $param) {
        $path = 'referensi/faskes/'.$jenisFaskes.'/'.$param;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function referensiRuangRawat() {
        $path = 'referensi/ruangrawat';

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function referensiSpesialistik() {
        $path = 'referensi/spesialistik';

        $response = $this->get($path);
        return json_decode($response, true);
    }

    /********************* PESERTA ******************** */

    public function pesertaByNoKartu($noKartu, $tanggal) {
        $path = 'Peserta/nokartu/'.$noKartu.'/tglSEP/'.$tanggal;

        return $this->get($path);
    }

    public function pesertaByNoKartuArr($noKartu, $tanggal) {
        $response = $this->pesertaByNoKartu($noKartu, $tanggal);
        return json_decode($response, true);
    }

    public function pesertaByNIK($noKartu, $tanggal) {
        $path = 'Peserta/nik/'.$noKartu.'/tglSEP/'.$tanggal;

        return $this->get($path);
    }

    /********************* RUJUKAN ******************** */

    public function rujukanInsert($data = [])
    {
        $response = $this->post('Rujukan/2.0/insert', $data);
        return json_decode($response, true);
    }
    public function rujukanUpdate($data = [])
    {
        $response = $this->put('Rujukan/2.0/Update', $data);
        return json_decode($response, true);
    }
    public function rujukanDelete($data = [])
    {
        $response = $this->delete('Rujukan/delete', $data);
        return json_decode($response, true);
    }

    public function rujukanByNoRujukan($searchBy, $keyword)
    {
        if ($searchBy == 'RS') {
            $path = 'Rujukan/RS/'.$keyword;
        } else {
            $path = 'Rujukan/'.$keyword;
        }
        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function rujukanByNoKartu($searchBy, $keyword, $multi = false)
    {
        $record = $multi ? 'List/' : '';
        if ($searchBy == 'RS') {
            $path = 'Rujukan/RS/'.$record.'Peserta/'.$keyword;
        } else {
            $path = 'Rujukan/'.$record.'Peserta/'.$keyword;
        }
        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function rujukanByTanggal($searchBy, $keyword)
    {
        if ($searchBy == 'RS') {
            $path = 'Rujukan/RS/TglRujukan/'.$keyword;
        } else {
            $path = 'Rujukan/List/Peserta/'.$keyword;
        }
        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function rujukanListSarana($ppkRujukan)
    {
        $path = 'Rujukan/ListSarana/PPKRujukan/'.$ppkRujukan;
        
        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function rujukanListSpesialistik($ppkRujukan, $tglRujukan)
    {
        $path = 'Rujukan/ListSpesialistik/PPKRujukan/'.$ppkRujukan.'/TglRujukan/'.$tglRujukan;
        
        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function rujukanKhususInsert($data)
    {
        $path = 'Rujukan/Khusus/insert';

        $response = $this->post($path, $data);
        return json_decode($response, true);
    }

    public function rujukanKhususList($bln, $thn)
    {
        $path = 'Rujukan/Khusus/List/Bulan/'.$bln.'/Tahun/'.$thn;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function rujukanKhususDelete($data)
    {
        $path = 'Rujukan/Khusus/delete';

        $response = $this->post($path, $data);
        return json_decode($response, true);
    }

    /********************* SEP ******************** */

    public function sepInsert($data) {
        $path = 'SEP/2.0/insert';

        $response = $this->post($path, $data);
        return json_decode($response, true);
    }

    public function sepCari($noSEP) {
        $path = 'SEP/'.$noSEP;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function sepUpdate($data) {
        $path = 'SEP/2.0/update';

        $response = $this->put($path, $data);
        return json_decode($response, true);
    }

    public function sepDelete($data) {
        $path = 'SEP/2.0/delete';

        $response = $this->delete($path, $data);
        return json_decode($response, true);
    }

    public function sepSuplesi($noka, $tanggal) {
        $path = 'sep/JasaRaharja/Suplesi/'.$noka.'/tglPelayanan/'.$tanggal;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function sepPengajuan($data) {
        $path = 'Sep/pengajuanSEP';
        $arr = json_decode($data);
        $data = json_encode($arr);

        $response = $this->post($path, $data);
        return json_decode($response, true);
    }

    public function sepApproval($data) {
        $path = 'Sep/aprovalSEP';

        $response = $this->post($path, $data);
        return json_decode($response, true);
    }

    public function sepInacbg($param) {
        $path = 'sep/cbg/'.$param;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function sepUpdateTanggalPulang($data) {
        $path = 'SEP/2.0/updtglplg';

        $response = $this->put($path, $data);
        return json_decode($response, true);
    }

    public function sepInternalCari($noSEP) {
        $path = 'SEP/Internal/'.$noSEP;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function sepFingerprint($noKartu, $tanggal) {
        $path = 'SEP/FingerPrint/Peserta/'.$noKartu.'/TglPelayanan/'.$tanggal;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function sepListFingerprint($tanggal) {
        $path = 'SEP/FingerPrint/List/Peserta/TglPelayanan/'.$tanggal;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    /********************* LPK ******************** */

    public function lpkGet($tglMasuk, $jenisPelayanan) {
        $path = 'LPK/TglMasuk/'.$tglMasuk.'/JnsPelayanan/'.$jenisPelayanan;

        $response = $this->get($path);
        return json_decode($response, true);
    }
    
    public function lpkDelete($data) {
        $path = 'LPK/delete';

        $response = $this->delete($path, $data);
        return json_decode($response, true);
    }
    
    public function lpkInsert($data) {
        $path = 'LPK/insert';

        $response = $this->post($path, $data);
        return json_decode($response, true);
    }
    
    public function lpkUpdate($data) {
        $path = 'LPK/update';

        $response = $this->put($path, $data);
        return json_decode($response, true);
    }

    /********************* MONITORING ******************** */

    public function monitoringKunjunganByTanggal($tanggalSEP, $jenisPelayanan) {
        $path = 'Monitoring/Kunjungan/Tanggal/'.$tanggalSEP.'/JnsPelayanan/'.$jenisPelayanan;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function monitoringKlaim($tanggalPulang, $jenisPelayanan, $statusKlaim) {
        $path = 'Monitoring/Klaim/Tanggal/'.$tanggalPulang.'/JnsPelayanan/'.$jenisPelayanan.'/Status/'.$statusKlaim;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function monitoringHistoryPelayanan($noKartu, $tglStart, $tglEnd) {
        $path = 'monitoring/HistoriPelayanan/NoKartu/'.$noKartu.'/tglMulai/'.$tglStart.'/tglAkhir/'.$tglEnd;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function monitoringJasaRaharja($jenisPelayanan, $tglStart, $tglEnd) {
        $path = 'monitoring/JasaRaharja/JnsPelayanan/'.$jenisPelayanan.'/tglMulai/'.$tglStart.'/tglAkhir/'.$tglEnd;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    /********************* RENCANA KONTROL ******************** */

    public function rencanaKontrolInsert($data) {
        $path = 'RencanaKontrol/insert';

        $response = $this->post($path, $data);
        return json_decode($response, true);
    }

    public function rencanaKontrolUpdate($data) {
        $path = 'RencanaKontrol/Update';

        $response = $this->put($path, $data);
        return json_decode($response, true);
    }

    public function rencanaKontrolDelete($data) {
        $path = 'RencanaKontrol/Delete';

        $response = $this->delete($path, $data);
        return json_decode($response, true);
    }

    public function rencanaKontrolSPRIInsert($data) {
        $path = 'RencanaKontrol/InsertSPRI';

        $response = $this->post($path, $data);
        return json_decode($response, true);
    }

    public function rencanaKontrolSPRIUpdate($data) {
        $path = 'RencanaKontrol/UpdateSPRI';

        $response = $this->put($path, $data);
        return json_decode($response, true);
    }

    public function rencanaKontrolCariSEP($noSEP) {
        $path = 'RencanaKontrol/nosep/'.$noSEP;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    // $format {1 => Tanggal Entri, 2 => Tanggal Kontrol}
    public function rencanaKontrolList($tglAwal, $tglAkhir, $format = 1) {
        $path = 'RencanaKontrol/ListRencanaKontrol/tglAwal/'.$tglAwal.'/tglAkhir/'.$tglAkhir.'/filter/'.$format;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function rencanaKontrolListKartu($bulan, $tahun, $NoKartu, $format = 1) {
        $path = 'RencanaKontrol/ListRencanaKontrol/Bulan/'.$bulan.'/Tahun/'.$tahun.'/Nokartu/'.$NoKartu.'/filter/'.$format;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function rencanaKontrolByNoSuratKontrol($noSurat) {
        $path = 'RencanaKontrol/noSuratKontrol/'.$noSurat;

        $response = $this->get($path);
        return json_decode($response, true);
    }

    public function rencanaKontrolJadwalPraktekDokter($jenis, $kodePoli, $tglRencanaKontrol) {
        $path = 'RencanaKontrol/JadwalPraktekDokter/JnsKontrol/'.$jenis.'/KdPoli/'.$kodePoli.'/TglRencanaKontrol/'.$tglRencanaKontrol;

        $response = $this->get($path);
        return json_decode($response, true);
    }
}
