<?php

namespace App\Models;

use CodeIgniter\Model;

class IndeksTerasModel extends Model
{
    protected $table = 'indeks_teras';
    protected $primaryKey = 'id_indeksteras';
    protected $allowedFields = ['id_pengumpulandata', 'id_teras', 'tarikh_cipta', 'nilai_indeksteras'];

    public function getListMissingTeras(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT t.id_teras, p.id_pengumpulandata
            FROM teras t 
            CROSS JOIN pengumpulandata p
            LEFT JOIN indeks_teras it
                ON it.id_teras = t.id_teras
                AND it.id_pengumpulandata = p.id_pengumpulandata
            WHERE it.id_indeksteras IS null
            ORDER BY 
                t.id_teras, p.id_pengumpulandata
        ";

        return $db->query($sql)->getResultArray();
    }

    public function getIndeksTerasNull(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT it.id_pengumpulandata, t.id_teras, i.id_indikator, i.peratusan_indikator, ia.nilai_indeksasas
            FROM indeks_teras AS it
            LEFT JOIN teras as t ON it.id_teras = t.id_teras
            LEFT JOIN komponen AS k on t.id_teras = k.id_teras
            LEFT JOIN indikator AS i ON k.id_komponen = i.id_komponen 
            LEFT JOIN indeks_asas AS ia ON i.id_indikator = ia.id_indikator AND it.id_pengumpulandata = ia.id_pengumpulandata 
            WHERE it.nilai_indeksteras IS null
            ORDER BY it.id_pengumpulandata, i.id_indikator
        ";

        $query = $db->query($sql);

        if ($query === false) {
            throw new \Exception('Query failed in getIndeksTerasNull()');
        }

        return $query->getResultArray();
    }
}
