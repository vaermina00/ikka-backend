<?php

namespace App\Models;

use CodeIgniter\Model;

class IndeksBankModel extends Model
{
    protected $table = 'indeks_bank';
    protected $primaryKey = 'id_indeksbank';
    protected $allowedFields = [
        'id_pengumpulandata',
        'nilai_indeks',
        'tarikh_cipta',
        'tahun_pengumpulandata',
        'penggal_pengumpulandata',
        'tarikh_validasi'
    ];

    public function getIndeksBank($base_tahun, $base_penggal): array
    {
        switch ($base_penggal) {
            case "Jun":
                $penggal = 1;
                break;
            case "Dec":
                $penggal = 2;
                break;
            default:
                break;
        }

        $builder = $this->db->table('indeks_bank');

        $builder->select('id_indeksbank');
        $builder->where('base_tahun', $base_tahun);
        $builder->where('base_penggal', $penggal);

        $query = $builder->get();

        if ($query === false) {
            throw new \Exception('Query failed in getIndeksBank()');
        }

        return $query->getResultArray();
    }

    public function generateIndeksBank($base_tahun, $base_penggal): bool
    {
        $db = \Config\Database::connect();

        // 1. Get indikator + nilai rows
        $indikatorRows = $db->query("
            SELECT i.id_indikator, k.id_komponen, t.id_teras, i.kod_indikator, i.nama_indikator, i.impak_indikator, i.pemberat_indikator, i.peratusan_indikator, i.pemberat_indikator , i.nilai_purata, i.nilai_sisihanpiawai, 
                p.id_pengumpulandata, CASE WHEN p.penggal_pengumpulandata = 1 THEN 'JUN' WHEN p.penggal_pengumpulandata = 2 THEN 'DEC' END AS penggal_pengumpulandata, p.tahun_pengumpulandata, pi2.nilai, 
                ii.nilai_indeksindikator, iz.nilai_indekszscore, ia.nilai_indeksasas
            FROM indikator i
            LEFT JOIN komponen k ON k.id_komponen = i.id_komponen 
            LEFT JOIN teras t ON t.id_teras = k.id_teras  
            LEFT JOIN indeks_indikator ii ON ii.id_indikator = i.id_indikator
            LEFT JOIN indeks_zscore iz ON iz.id_indikator = i.id_indikator AND ii.id_pengumpulandata = iz.id_pengumpulandata
            LEFT JOIN indeks_asas ia ON ia.id_indikator = i.id_indikator AND ia.id_pengumpulandata = iz.id_pengumpulandata AND ia.id_pengumpulandata = ii.id_pengumpulandata
            LEFT JOIN pengumpulandata p ON p.id_pengumpulandata = ii.id_pengumpulandata AND p.id_pengumpulandata = iz.id_pengumpulandata AND p.id_pengumpulandata = ia.id_pengumpulandata
            LEFT JOIN pengumpulandata_indikator pi2 ON pi2.kod_indikator = i.kod_indikator AND pi2.id_pengumpulandata = p.id_pengumpulandata
            ORDER BY i.id_indikator, p.tahun_pengumpulandata, p.penggal_pengumpulandata
        ")->getResultArray();

        // 2. Extra tables
        $komponenRows  = $db->query("SELECT * FROM indeks_komponen ORDER BY id_pengumpulandata")->getResultArray();
        $terasRows     = $db->query("SELECT * FROM indeks_teras ORDER BY id_pengumpulandata")->getResultArray();
        $tahunRows     = $db->query("SELECT * FROM indeks_tahun ORDER BY id_pengumpulandata")->getResultArray();

        // 3. Re-index by pengumpulandata
        $komponenMap = [];
        foreach ($komponenRows as $r) {
            $komponenMap[$r['id_pengumpulandata']] = $r['nilai_indekskomponen'];
        }

        $terasMap = [];
        foreach ($terasRows as $r) {
            $terasMap[$r['id_pengumpulandata']] = $r['nilai_indeksteras'];
        }

        $tahunMap = [];
        foreach ($tahunRows as $r) {
            $tahunMap[$r['id_pengumpulandata']] = $r['nilai_indekstahun'];
        }

        // 4. Build final array grouped by indikator
        $final = [];
        foreach ($indikatorRows as $row) {

            $id = $row['id_indikator'];
            $penggal = strtolower($row['penggal_pengumpulandata']);     // jun/dec
            $tahun = $row['tahun_pengumpulandata'];

            if (!isset($final[$id])) {
                $final[$id] = [
                    "id_indikator" => $row["id_indikator"],
                    "kod_indikator" => $row["kod_indikator"],
                    "nama_indikator" => $row["nama_indikator"],
                    "impak_indikator" => $row["impak_indikator"],
                    "pemberat_indikator" => $row["pemberat_indikator"],
                    "nilai_indeksindikator" => $row["nilai_indeksindikator"],
                    "nilai_purata" => $row["nilai_purata"],
                    "nilai_sisihanpiawai" => $row["nilai_sisihanpiawai"],
                    "nilai_pengumpulandata" => $row["nilai"],
                ];
            }

            $suffix = "{$penggal}_{$tahun}";

            // Main pengumpulandata numeric input
            $final[$id]["pengumpulandata_{$suffix}"] = $row["id_pengumpulandata"];

            // Three indikator-level indeks
            $final[$id]["nilaizscore_{$suffix}"]           = $row["nilai_indekszscore"];
            $final[$id]["nilaiindeksindikator_{$suffix}"]  = $row["nilai_indeksindikator"];
            $final[$id]["nilaiindeksasas_{$suffix}"]       = $row["nilai_indeksasas"];

            // Add komponent/teras/tahun if available
            $pid = $row['id_pengumpulandata'];

            if (isset($komponenMap[$pid])) {
                $final[$id]["nilaiindekskomponen_{$suffix}"] = $komponenMap[$pid];
            }

            if (isset($terasMap[$pid])) {
                $final[$id]["nilaiindeksteras_{$suffix}"] = $terasMap[$pid];
            }

            if (isset($tahunMap[$pid])) {
                $final[$id]["nilaiindekstahun_{$suffix}"] = $tahunMap[$pid];
            }
        }

        // 5. Convert to final array (json list)
        $json = array_values($final);

        // 6. Insert into indeks_bank table
        switch ($base_penggal) {
            case "Jun":
                $penggal = 1;
                break;
            case "Dec":
                $penggal = 2;
                break;
            default:
                break;
        }

        $insert = $db->table('indeks_bank')->insert([
            'tarikh_cipta'                  => date('Y-m-d H:i:s'),
            'nilai_indeks'                  => json_encode($json),
            'base_tahun'                    => $base_tahun,
            'base_penggal'                  => $penggal
        ]);

        if ($insert) {
            return true;
        }
        else {
            return false;
        }
        
    }

}
