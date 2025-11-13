<?php

namespace App\Models;

use CodeIgniter\Model;

class IndeksTahunModel extends Model
{
    protected $table = 'indeks_tahun';
    protected $primaryKey = 'id_indekstahun';
    protected $allowedFields = ['tarikh_cipta','tarikh_ubah','nilai_indekstahun','id_pengumpulandata'];

    public function getListMissingTahun(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT p.id_pengumpulandata
            FROM pengumpulandata p
            LEFT JOIN indeks_tahun i
                ON i.id_pengumpulandata = p.id_pengumpulandata
            WHERE i.id_pengumpulandata IS null
            ORDER BY p.id_pengumpulandata
        ";

        return $db->query($sql)->getResultArray();
    }

    public function getIndeksTahunNull(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT a.id_pengumpulandata, i.id_indikator, i.peratusan_indikator, a.nilai_indeksasas
            FROM indeks_asas a
            JOIN indikator i ON i.id_indikator = a.id_indikator
            LEFT JOIN indeks_tahun t ON t.id_pengumpulandata = a.id_pengumpulandata
            WHERE t.nilai_indekstahun IS null
            ORDER BY a.id_pengumpulandata, i.id_indikator
        ";

        $query = $db->query($sql);

        if ($query === false) {
            throw new \Exception('Query failed in getIndeksTahunNull()');
        }

        return $query->getResultArray();
    }
}
