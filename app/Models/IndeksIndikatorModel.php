<?php

namespace App\Models;

use CodeIgniter\Model;

class IndeksIndikatorModel extends Model
{
    protected $table = 'indeks_indikator';
    protected $primaryKey = 'id_indeksindikator';
    protected $allowedFields = ['id_pengumpulandata', 'id_indikator', 'tarikh_cipta', 'nilai_indeksindikator'];

    public function getListMissingIndikator(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT i.id_indikator, p.id_pengumpulandata
            FROM indikator i
            CROSS JOIN pengumpulandata p
            LEFT JOIN indeks_indikator ii
                ON ii.id_indikator = i.id_indikator
                AND ii.id_pengumpulandata = p.id_pengumpulandata
            WHERE ii.id_indeksindikator IS null
            ORDER BY 
                i.id_indikator, p.id_pengumpulandata
        ";

        return $db->query($sql)->getResultArray();
    }

    public function getIndeksIndikatorNull(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT ii.id_pengumpulandata, ii.id_indikator, i.impak_indikator, i.kod_indikator, iz.nilai_indekszscore
            FROM indeks_indikator AS ii
            LEFT JOIN indikator AS i ON ii.id_indikator = i.id_indikator
            LEFT JOIN indeks_zscore iz on ii.id_indikator = iz.id_indikator AND ii.id_pengumpulandata = iz.id_pengumpulandata
            WHERE ii.nilai_indeksindikator IS NULL
        ";

        $query = $db->query($sql);

        if ($query === false) {
            throw new \Exception('Query failed in getIndikatorPurataNull()');
        }

        return $query->getResultArray();
    }
}
