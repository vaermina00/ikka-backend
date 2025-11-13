<?php

namespace App\Models;

use CodeIgniter\Model;

class IndeksKomponenModel extends Model
{
    protected $table = 'indeks_komponen';
    protected $primaryKey = 'id_indekskomponen';
    protected $allowedFields = ['id_pengumpulandata', 'id_komponen', 'tarikh_cipta', 'nilai_indekskomponen'];

    public function getListMissingKomponen(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT k.id_komponen, p.id_pengumpulandata
            FROM komponen k 
            CROSS JOIN pengumpulandata p
            LEFT JOIN indeks_komponen ik
                ON ik.id_komponen = k.id_komponen
                AND ik.id_pengumpulandata = p.id_pengumpulandata
            WHERE ik.id_indekskomponen IS null
            ORDER BY 
                k.id_komponen, p.id_pengumpulandata
        ";

        return $db->query($sql)->getResultArray();
    }

    public function getIndeksKomponenNull(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT ik.id_pengumpulandata, ik.id_komponen, i.peratusan_indikator, ia.nilai_indeksasas
            FROM indeks_komponen AS ik
            LEFT JOIN komponen AS k ON ik.id_komponen = k.id_komponen
            LEFT JOIN indikator AS i ON k.id_komponen = i.id_komponen 
            LEFT JOIN indeks_asas AS ia ON i.id_indikator = ia.id_indikator AND ik.id_pengumpulandata = ia.id_pengumpulandata 
            WHERE ik.nilai_indekskomponen IS null
            ORDER BY ik.id_pengumpulandata, ik.id_komponen
        ";

        $query = $db->query($sql);

        if ($query === false) {
            throw new \Exception('Query failed in getIndeksKomponenNull()');
        }

        return $query->getResultArray();
    }
}
