<?php

namespace App\Models;

use CodeIgniter\Model;

class IndeksAsasModel extends Model
{
    protected $table = 'indeks_asas';
    protected $primaryKey = 'id_indeksasas';
    protected $allowedFields = ['id_pengumpulandata', 'id_indikator', 'tarikh_cipta', 'nilai_indeksasas'];

    public function getListMissingAsas(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT i.id_indikator, p.id_pengumpulandata
            FROM indikator i
            CROSS JOIN pengumpulandata p
            LEFT JOIN indeks_asas ia
                ON ia.id_indikator = i.id_indikator
                AND ia.id_pengumpulandata = p.id_pengumpulandata
            WHERE ia.id_indeksasas IS null
            ORDER BY 
                i.id_indikator, p.id_pengumpulandata
        ";

        return $db->query($sql)->getResultArray();
    }

    public function getIndeksAsasNull(): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT ia.id_pengumpulandata, ia.id_indikator, ii.nilai_indeksindikator 
            FROM indeks_asas AS ia
            LEFT JOIN indikator AS i ON ia.id_indikator = i.id_indikator
            LEFT JOIN indeks_indikator ii on ia.id_indikator = ii.id_indikator AND ia.id_pengumpulandata = ii.id_pengumpulandata
            WHERE ia.nilai_indeksasas IS null
            ORDER BY ia.id_pengumpulandata, ia.id_indikator
        ";

        $query = $db->query($sql);

        if ($query === false) {
            throw new \Exception('Query failed in getIndikatorPurataNull()');
        }

        return $query->getResultArray();
    }

    public function getBaseIndeksIndikator($id_indikator, $tahun, $penggal): float
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT ii.nilai_indeksindikator
            FROM indeks_indikator ii
            JOIN pengumpulandata p 
            ON ii.id_pengumpulandata = p.id_pengumpulandata
            WHERE p.tahun_pengumpulandata = ?
            AND p.penggal_pengumpulandata = ?
            AND ii.id_indikator = ?;
        ";

        $query = $db->query($sql, [$tahun, $penggal, $id_indikator]);
        $row = $query->getRow();

        return $row ? (float) $row->nilai_indeksindikator : 0.0;
    } 
}
