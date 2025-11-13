<?php

namespace App\Models;

use CodeIgniter\Model;

class IndeksBankModel extends Model
{
    protected $table = 'indeks_bank';
    protected $primaryKey = 'id_indeksbank';
    protected $allowedFields = [
        'id_pengumpulandata',
        'tarikh_cipta',
        'nilai_indeks',
        'tahun_pengumpulandata',
        'penggal_pengumpulandata',
        'tarikh_validasi'
    ];

    /**
     * Compile final indeks data from related tables and insert into indeks_bank
     */
    public function compileAndInsertIndeksBank(): void
    {
        $db = \Config\Database::connect();

        // Build JSONB query that aggregates all indeks values
        $sql = "
            SELECT 
                pd.id_pengumpulandata, pd.tahun_pengumpulandata, pd.penggal_pengumpulandata,
                jsonb_build_object(
                    'indikator', jsonb_agg(DISTINCT jsonb_build_object(
                        'id_indikator', ii.id_indikator,
                        'nilai_indeksindikator', ii.nilai_indeksindikator
                    )),
                    'asas', jsonb_agg(DISTINCT jsonb_build_object(
                        'id_indikator', ia.id_indikator,
                        'nilai_indeksasas', ia.nilai_indeksasas
                    )),
                    'komponen', jsonb_agg(DISTINCT jsonb_build_object(
                        'id_komponen', ik.id_komponen,
                        'nilai_indekskomponen', ik.nilai_indekskomponen
                    )),
                    'teras', jsonb_agg(DISTINCT jsonb_build_object(
                        'id_teras', it.id_teras,
                        'nilai_indeksteras', it.nilai_indeksteras
                    )),
                    'tahun', jsonb_agg(DISTINCT jsonb_build_object(
                        'nilai_indekstahun', ith.nilai_indekstahun
                    ))
                ) AS nilai_indeks
            FROM pengumpulandata pd
            LEFT JOIN indeks_indikator ii ON ii.id_pengumpulandata = pd.id_pengumpulandata
            LEFT JOIN indeks_asas ia ON ia.id_pengumpulandata = pd.id_pengumpulandata
            LEFT JOIN indeks_komponen ik ON ik.id_pengumpulandata = pd.id_pengumpulandata
            LEFT JOIN indeks_teras it ON it.id_pengumpulandata = pd.id_pengumpulandata
            LEFT JOIN indeks_tahun ith ON ith.id_pengumpulandata = pd.id_pengumpulandata
            GROUP BY pd.id_pengumpulandata
        ";

        $results = $db->query($sql)->getResultArray();

        foreach ($results as $row) {
            // Insert compiled JSON into indeks_bank
            $this->insert([
                'id_pengumpulandata'      => $row['id_pengumpulandata'],
                'tarikh_cipta'            => date('Y-m-d H:i:s'),
                'nilai_indeks'            => $row['nilai_indeks'],
                'tahun_pengumpulandata'   => $row['tahun_pengumpulandata'],
                'penggal_pengumpulandata' => $row['penggal_pengumpulandata'],
                'tarikh_validasi'         => null
            ]);
        }
    }
}
