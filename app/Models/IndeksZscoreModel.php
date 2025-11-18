<?php

namespace App\Models;

use CodeIgniter\Model;

class IndeksZscoreModel extends Model
{
    protected $table = 'indeks_zscore';
    protected $primaryKey = 'id_indekszscore';
    protected $allowedFields = ['id_pengumpulandata', 'id_indikator', 'nilai_indekszscore', 'tarikh_cipta', 'tarikh_ubah', 'nilai_purata', 'nilai_sisihan_piawai'];

    public function getListMissingZscore(): array
    {
        $db = \Config\Database::connect();

        // SELECT 
        //     i.id_indikator,
        //     p.id_pengumpulandata
        // FROM 
        //     indikator i,
        //     pengumpulandata p
        // WHERE 
        //     NOT EXISTS (
        //         SELECT 1 
        //         FROM indeks_zscore z
        //         WHERE z.id_indikator = i.id_indikator
        //         AND z.id_pengumpulandata = p.id_pengumpulandata
        //     )
        // ORDER BY 
        //     i.id_indikator, p.id_pengumpulandata

        $sql = "
            SELECT i.id_indikator, p.id_pengumpulandata
            FROM indikator i
            CROSS JOIN pengumpulandata p
            LEFT JOIN indeks_zscore iz
                ON iz.id_indikator = i.id_indikator
                AND iz.id_pengumpulandata = p.id_pengumpulandata
            WHERE iz.id_indekszscore IS null
            ORDER BY 
                i.id_indikator, p.id_pengumpulandata
        ";

        return $db->query($sql)->getResultArray();
    }

    public function getZscorePopulate(int $idPengumpulandata, int $idIndikator): bool
    {
        $builder = $this->builder();

        $builder->select('1');
        $builder->where('id_pengumpulandata', $idPengumpulandata);
        $builder->where('id_indikator', $idIndikator);
        $builder->limit(1);

        $query = $builder->get();

        return $query->getNumRows() > 0;
    }

    public function getZscoreNull(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT iz.id_pengumpulandata, iz.id_indikator, i.kod_indikator, pi.nilai, i.nilai_purata, i.nilai_sisihanpiawai
            FROM indeks_zscore AS iz
            LEFT JOIN indikator AS i ON iz.id_indikator = i.id_indikator
            LEFT JOIN pengumpulandata_indikator AS pi ON i.kod_indikator = pi.kod_indikator AND iz.id_pengumpulandata = pi.id_pengumpulandata
            WHERE iz.nilai_indekszscore IS NULL
        ";

        $query = $db->query($sql);

        if ($query === false) {
            throw new \Exception('Query failed in getIndikatorPurataNull()');
        }

        return $query->getResultArray();
    }

}
