<?php

namespace App\Models;

use CodeIgniter\Model;

class PengumpulandataIndikatorModel extends Model
{
    protected $table = 'pengumpulandata_indikator';
    protected $primaryKey = 'id_tetapan';
    protected $allowedFields = [];

    public function getDataByKodIndikator(string $kodIndikator): array
    {
        $builder = $this->builder();

        $builder->select('nilai');
        $builder->where('kod_indikator', $kodIndikator);

        $query = $builder->get();

        return $query->getResultArray();
    }

    public function getNilaiByKodIndikator(?string $kodIndikator): array
    {
        if (empty($kodIndikator)) {
            log_message('error', 'getNilaiByKodIndikator() called with empty kod_indikator');
            return [];
        }

        $builder = $this->db->table('pengumpulandata_indikator');
        $builder->select('nilai');
        $builder->where('kod_indikator', $kodIndikator);
        $query = $builder->get();

        if ($query === false) {
            $error = $this->db->error();
            log_message('error', 'Query builder failed in getNilaiByKodIndikator for kod_indikator: ' . $kodIndikator);
            log_message('error', print_r($error, true));
            return [];
        }

        return $query->getResultArray();
    }
}
