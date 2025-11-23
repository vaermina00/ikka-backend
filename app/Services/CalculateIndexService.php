<?php

namespace App\Services;

use App\Models\IndikatorModel;
use App\Models\IndeksZscoreModel;
use App\Models\PengumpulandataIndikatorModel;
use App\Models\IndeksIndikatorModel;
use App\Models\IndeksAsasModel;
use App\Models\IndeksKomponenModel;
use App\Models\IndeksTerasModel;
use App\Models\IndeksTahunModel;
use App\Models\IndeksBankModel;
use Config\Database;
use Exception;

class CalculateIndexService
{
    protected $indikatorModel;
    protected $zscoreModel;
    protected $pengumpulandataIndikatorModel;
    protected $indeksIndikatorModel;
    protected $indeksAsasModel;
    protected $indeksKomponenModel;
    protected $indeksTerasModel;
    protected $indeksTahunModel;
    protected $indeksBankModel;
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->indikatorModel = new IndikatorModel();
        $this->zscoreModel = new IndeksZscoreModel();
        $this->pengumpulandataIndikatorModel = new PengumpulandataIndikatorModel();
        $this->indeksIndikatorModel = new IndeksIndikatorModel();
        $this->indeksAsasModel = new IndeksAsasModel();
        $this->indeksKomponenModel = new IndeksKomponenModel();
        $this->indeksTerasModel = new IndeksTerasModel();
        $this->indeksTahunModel = new IndeksTahunModel();
        $this->indeksBankModel = new IndeksBankModel();
    }

    public function generateIndikatorCsv(): array
    {
        $data = $this->indikatorModel->getIndikatorCsv();

        return $data;
    }

    public function calculateIndikator()
    {
        try {
            // $this->db->transBegin();

            // calculate indexes
            $this->calculatePeratusIndikator();
            $this->calculatePurataSisihanPiawaiIndikator();
            
            // $this->db->transCommit();
            return [
                'status' => true,
                'message' => 'Pengiraan indeks berjaya dijana.'
            ];
        } catch (Exception $e) {
            // $this->db->transRollback();
            log_message('error', 'Error in generatePengiraanIndeksIndikator: ' . $e->getMessage());
            throw $e;
        }
    }

    public function generatePengiraanIndeks($requestBody): void
    {
        try {
            $db = \Config\Database::connect();
            // $this->db->transBegin();
            
            $indeksBank = $this->indeksBankModel->getIndeksBank($requestBody['base_tahun'], $requestBody['base_penggal']);

            if (empty($indeksBank)) {
                // calculate indexes
                $this->calculateZscore();
                $this->calculateIndeksIndikator();
                $this->calculateIndeksAsas($requestBody['base_tahun'], $requestBody['base_penggal']);
                $this->calculateIndeksKomponen();
                $this->calculateIndeksTeras();
                $this->calculateIndeksTahun();

                // Compile final JSONB into indeks_bank table
                $generateIndeksBank = $this->indeksBankModel->generateIndeksBank($requestBody['base_tahun'], $requestBody['base_penggal']);

                if ($generateIndeksBank) {
                    $db->table('indeks_asas')->truncate();
                    $db->table('indeks_indikator')->truncate();
                    $db->table('indeks_komponen')->truncate();
                    $db->table('indeks_tahun')->truncate();
                    $db->table('indeks_teras')->truncate();
                    $db->table('indeks_zscore')->truncate();
                }
            }

            // $this->db->transCommit();
        } catch (Exception $e) {
            // $this->db->transRollback();
            log_message('error', 'Error in generatePengiraanIndeks: ' . $e->getMessage());
            throw $e;
        }
    }


    public function calculatePeratusIndikator(): void
    {
        try {
            $sumPemberat = $this->calculateSumPemberat();

            if ($sumPemberat == 0) {
                throw new Exception('Sum of pemberat cannot be zero.');
            }

            $pemberatList = $this->indikatorModel
                                ->select('id_indikator, pemberat_indikator')
                                ->findAll();

            if (empty($pemberatList)) {
                foreach ($pemberatList as $item) {
                    $peratus = sprintf('%.17f', $item['pemberat_indikator'] / $sumPemberat);
                    $this->indikatorModel->update($item['id_indikator'], ['peratusan_indikator' => $peratus]);
                }
            }
            
        } catch (Exception $e) {
            log_message('error', 'Error in calculatePeratusIndikator: ' . $e->getMessage());
            throw $e;
        }
    }

    public function calculateSumPemberat(): int
    {
        $sum = (int) $this->indikatorModel->getSumPemberat()['sum_pemberat'] ?? 0;

        if ($sum <= 0) {
            throw new Exception('Invalid sum of pemberat.');
        }

        return $sum;
    }

    public function calculatePurataSisihanPiawaiIndikator(): void
    {
        try {
            $missingPurataSisihanPiawai = $this->indikatorModel->getMissingPurataSisihanPiawai();

            if (!empty($missingPurataSisihanPiawai)) { // if purata sisihan piawai missing
                foreach ($missingPurataSisihanPiawai as $row) {
                    $nilaiArray = $this->pengumpulandataIndikatorModel->getNilaiByKodIndikator($row['kod_indikator']);
                    $nilai = array_column($nilaiArray, 'nilai');

                    $purata = sprintf('%.14f', $this->calculatePurata($nilai));
                    $sisihanPiawai = sprintf('%.14f', $this->calculateSisihanPiawai($nilai));

                    $this->indikatorModel->where('kod_indikator', $row['kod_indikator'])
                                        ->set('nilai_purata', $purata)
                                        ->set('nilai_sisihanpiawai', $sisihanPiawai)
                                        ->update();
                }
            }
        } catch (Exception $e) {
            log_message('error', 'Error in calculatePurataSisihanPiawaiIndikator: ' . $e->getMessage());
            throw $e;
        }
    }

    public function calculatePurata($nilai): float
    {
        if (empty($nilai)) {
            throw new Exception('Nilai array is empty.');
        }

        $purata = round(array_sum($nilai) / count($nilai), 14);
        return $purata;
    }

    public function calculateSisihanPiawai($nilai): float
    {
        if (empty($nilai)) {
            throw new Exception('Nilai array is empty.');
        }

        $purata = $this->calculatePurata($nilai);
        $sumSquares = 0;

        foreach ($nilai as $x) {
            $sumSquares += pow($x - $purata, 2);
        }

        $sisihanPiawai = round(sqrt($sumSquares / count($nilai)), 14);

        if ($sisihanPiawai == 0) {
            $sisihanPiawai = '0.0000001'; // set default minimal value
        }
        return $sisihanPiawai;
    }

    public function calculateZscore(): void
    {
        try {
            $missingZscore = $this->zscoreModel->getListMissingZscore();

            if (!empty($missingZscore)) { // to populate missing record in indeks_zscore
                foreach ($missingZscore as $row) {
                    $this->zscoreModel->insert([
                        'id_pengumpulandata'  => $row['id_pengumpulandata'],
                        'id_indikator'        => $row['id_indikator'],
                        'tarikh_cipta'        => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $zscoreNull = $this->zscoreModel->getZscoreNull();
            if (!empty($zscoreNull)) {
                foreach ($zscoreNull as $row) {
                    $nilai = (float) $row['nilai'];                // observed value
                    $mean = (float) $row['nilai_purata'];          // mean
                    $stdDev = (float) $row['nilai_sisihanpiawai']; // standard deviation

                    $zscore = sprintf('%.15f', ($nilai - $mean) / $stdDev);

                    $this->zscoreModel->where('id_pengumpulandata', $row['id_pengumpulandata'])
                          ->where('id_indikator', $row['id_indikator'])
                          ->set('nilai_indekszscore', $zscore)
                          ->update();
                }
            }
        } catch (Exception $e) {
            log_message('error', 'Error in calculateZscore: ' . $e->getMessage());
            throw $e;
        }
    }

    public function calculateIndeksIndikator(): void
    {
        $missingIndikator = $this->indeksIndikatorModel->getListMissingIndikator();
        if (!empty($missingIndikator)) { // to populate missing record in indeks_indikator
            foreach ($missingIndikator as $row) {
                $this->indeksIndikatorModel->insert([
                    'id_pengumpulandata'  => $row['id_pengumpulandata'],
                    'id_indikator'        => $row['id_indikator'],
                    'tarikh_cipta'        => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $indeksIndikator = 0;
        $indikatorNull = $this->indeksIndikatorModel->getIndeksIndikatorNull();
        if (!empty($indikatorNull)) {
            foreach ($indikatorNull as $row) {
                if ($row['impak_indikator'] === '+') {
                    $indeksIndikator = sprintf('%.15f', 100 + $row['nilai_indekszscore'] * 10);
                } else {
                    $indeksIndikator = sprintf('%.15f', 100 - $row['nilai_indekszscore'] * 10);
                }

                $this->indeksIndikatorModel->where('id_pengumpulandata', $row['id_pengumpulandata'])
                          ->where('id_indikator', $row['id_indikator'])
                          ->set('nilai_indeksindikator', $indeksIndikator)
                          ->update();
            }
        }
    }

    public function calculateIndeksAsas($baseTahun, $basePenggal): void
    {
        $missingAsas = $this->indeksAsasModel->getListMissingAsas();
        if (!empty($missingAsas)) { // to populate missing record in indeks_indikator
            foreach ($missingAsas as $row) {
                $this->indeksAsasModel->insert([
                    'id_pengumpulandata'  => $row['id_pengumpulandata'],
                    'id_indikator'        => $row['id_indikator'],
                    'tarikh_cipta'        => date('Y-m-d H:i:s'),
                ]);
            }
        }

        switch ($basePenggal) {
            case "Jun":
                $penggal = 1;
                break;
            case "Dec":
                $penggal = 2;
                break;
            default:
                break;
        }
        
        $indeksAsas = 0;
        $asasNull = $this->indeksAsasModel->getIndeksAsasNull();
        if (!empty($asasNull)) {
            foreach ($asasNull as $row) {
                $baseIndeksIndikator = $this->indeksAsasModel->getBaseIndeksIndikator($row['id_indikator'], $baseTahun, $penggal);
                $indeksAsas = sprintf('%.13f', $row["nilai_indeksindikator"]/$baseIndeksIndikator*100);
                
                $this->indeksAsasModel->where('id_pengumpulandata', $row['id_pengumpulandata'])
                          ->where('id_indikator', $row['id_indikator'])
                          ->set('nilai_indeksasas', $indeksAsas)
                          ->update();
            }
        }

        
    } 

    public function calculateIndeksKomponen(): void
    {
        $missingKomponen = $this->indeksKomponenModel->getListMissingKomponen();
        if (!empty($missingKomponen)) {
            foreach ($missingKomponen as $row) {
                $this->indeksKomponenModel->insert([
                    'id_pengumpulandata'  => $row['id_pengumpulandata'],
                    'id_komponen'         => $row['id_komponen'],
                    'tarikh_cipta'        => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Step 1: Get rows where nilai_indekskomponen is NULL
        $komponenNull = $this->indeksKomponenModel->getIndeksKomponenNull();

        // Skip if there’s nothing to calculate
        if (empty($komponenNull)) {
            log_message('info', 'No null Indeks Komponen records found. Skipping calculation.');
            return;
        }

        $indeksKomponen = [];
        $grouped = [];

        // Step 2: Group by id_pengumpulandata + id_komponen
        foreach ($komponenNull as $row) {
            $pid = $row['id_pengumpulandata'];
            $kid = $row['id_komponen'];

            $grouped[$pid][$kid][] = [
                'peratusan' => (float)$row['peratusan_indikator'],
                'indeksasas' => (float)$row['nilai_indeksasas'],
            ];
        }

        // Step 3: Calculate weighted average (SUMPRODUCT / SUM)
        foreach ($grouped as $id_pengumpulandata => $komponenGroup) {
            foreach ($komponenGroup as $id_komponen => $rows) {
                $sumProduct = 0;
                $sumPeratusan = 0;

                foreach ($rows as $r) {
                    $sumProduct += $r['peratusan'] * $r['indeksasas'];
                    $sumPeratusan += $r['peratusan'];
                }

                $weightedAverage = $sumPeratusan != 0 ? $sumProduct / $sumPeratusan : 0;

                $indeksKomponen[] = [
                    'id_pengumpulandata'   => $id_pengumpulandata,
                    'id_komponen'          => $id_komponen,
                    'nilai_indekskomponen' => sprintf('%.13f', $weightedAverage),
                    'tarikh_ubah'          => date('Y-m-d H:i:s'),
                ];
            }
        }

        // Step 4: Update table nilai_indekskomponen for each pair
        foreach ($indeksKomponen as $item) {
            $this->indeksKomponenModel
                ->where('id_pengumpulandata', $item['id_pengumpulandata'])
                ->where('id_komponen', $item['id_komponen'])
                ->set(['nilai_indekskomponen' => $item['nilai_indekskomponen']])
                ->update();
        }
    }

    public function calculateIndeksTeras(): void
    {
        $missingTeras = $this->indeksTerasModel->getListMissingTeras();
        if (!empty($missingTeras)) {
            foreach ($missingTeras as $row) {
                $this->indeksTerasModel->insert([
                    'id_pengumpulandata'  => $row['id_pengumpulandata'],
                    'id_teras'         => $row['id_teras'],
                    'tarikh_cipta'        => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Step 1: Get rows where nilai_indeksteras is NULL
        $terasNull = $this->indeksTerasModel->getIndeksTerasNull();

        // Skip if there’s nothing to calculate
        if (empty($terasNull)) {
            log_message('info', 'No null Indeks Komponen records found. Skipping calculation.');
            return;
        }

        $indeksTeras = [];
        $grouped = [];

        // Step 2: Group by id_pengumpulandata + id_komponen
        foreach ($terasNull as $row) {
            $pid = $row['id_pengumpulandata'];
            $tid = $row['id_teras'];

            $grouped[$pid][$tid][] = [
                'peratusan'  => (float)$row['peratusan_indikator'],
                'indeksasas' => (float)$row['nilai_indeksasas'],
            ];
        }

        // Step 3: Calculate weighted average (SUMPRODUCT / SUM)
        foreach ($grouped as $id_pengumpulandata => $terasGroup) {
            foreach ($terasGroup as $id_teras => $rows) {
                $sumProduct = 0;
                $sumPeratusan = 0;

                foreach ($rows as $r) {
                    $sumProduct += $r['peratusan'] * $r['indeksasas'];
                    $sumPeratusan += $r['peratusan'];
                }

                $weightedAverage = $sumPeratusan != 0 ? $sumProduct / $sumPeratusan : 0;

                $indeksTeras[] = [
                    'id_pengumpulandata' => $id_pengumpulandata,
                    'id_teras'            => $id_teras,
                    'nilai_indeksteras'   => sprintf('%.13f', $weightedAverage),
                    'tarikh_ubah'         => date('Y-m-d H:i:s'),
                ];
            }
        }

        // Step 4: Update nilai_indeksteras for each pair
        foreach ($indeksTeras as $item) {
            $this->indeksTerasModel
                ->where('id_pengumpulandata', $item['id_pengumpulandata'])
                ->where('id_teras', $item['id_teras'])
                ->set(['nilai_indeksteras' => $item['nilai_indeksteras']])
                ->update();
        }
    }

    public function calculateIndeksTahun(): void
    {
        $missingTahun = $this->indeksTahunModel->getListMissingTahun();
        if (!empty($missingTahun)) {
            foreach ($missingTahun as $row) {
                $this->indeksTahunModel->insert([
                    'id_pengumpulandata'  => $row['id_pengumpulandata'],
                    'tarikh_cipta'        => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Step 1: Get rows where nilai_indeksteras is NULL
        $tahunNull = $this->indeksTahunModel->getIndeksTahunNull();

        // Skip if there’s nothing to calculate
        if (empty($tahunNull)) {
            log_message('info', 'No null Indeks Tahun records found. Skipping calculation.');
            return;
        }

        $indeksTahun = [];
        $grouped = [];

        // Step 2: Group data by id_pengumpulandata
        foreach ($tahunNull as $row) {
            $pid = $row['id_pengumpulandata'];
            $grouped[$pid][] = [
                'peratusan'  => (float)$row['peratusan_indikator'],
                'indeksasas' => (float)$row['nilai_indeksasas'],
            ];
        }

        // Step 3: Calculate weighted average (SUMPRODUCT / SUM)
        foreach ($grouped as $id_pengumpulandata => $rows) {
            $sumProduct = 0;
            $sumPeratusan = 0;

            foreach ($rows as $r) {
                $sumProduct += $r['peratusan'] * $r['indeksasas'];
                $sumPeratusan += $r['peratusan'];
            }

            $weightedAverage = $sumPeratusan != 0 ? $sumProduct / $sumPeratusan : 0;

            $indeksTahun[] = [
                'id_pengumpulandata' => $id_pengumpulandata,
                'nilai_indekstahun'  => sprintf('%.13f', $weightedAverage),
                'tarikh_ubah'        => date('Y-m-d H:i:s'),
            ];
        }

        // Step 4: Update nilai_indekstahun for each id_pengumpulandata
        foreach ($indeksTahun as $item) {
            $this->indeksTahunModel
                ->where('id_pengumpulandata', $item['id_pengumpulandata'])
                ->set([
                    'nilai_indekstahun' => $item['nilai_indekstahun']
                ])
                ->update();
        }
    }
}
