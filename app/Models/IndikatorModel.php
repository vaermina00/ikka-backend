<?php

namespace App\Models;

use CodeIgniter\Model;

class IndikatorModel extends Model
{
    // Adjust table name as needed
    protected $table = 'indikator';
    protected $primaryKey = 'id_indikator';

    // Return type and allowed fields
    protected $returnType = 'array';
    protected $allowedFields = ['peratusan_indikator', 'pemberat_indikator', 'nilai_purata', 'nilai_sisihanpiawai'];

    // // Timestamps / soft deletes
    // protected $useTimestamps = false;
    // protected $createdField  = 'created_at';
    // protected $updatedField  = 'updated_at';
    // protected $useSoftDeletes = false;
    // protected $deletedField  = 'deleted_at';

    // Add custom query methods below

    public function getPeratusanIndikator(): float
    {
        $result = $this->db->table($this->table)
                        ->selectSum('peratusan_indikator', 'sum_peratusan')
                        ->get()
                        ->getRowArray();

        return isset($result['sum_peratusan']) ? (float) $result['sum_peratusan'] : 0.0;
    }

    public function getSumPemberat(): array
    {
        return $this->db->table($this->table)
                        ->selectSum('pemberat_indikator', 'sum_pemberat')
                        ->get()
                        ->getRowArray();
    }

    public function getMissingPurataSisihanPiawai(): array
    {
        return $this->db->table($this->table)
                        ->select('kod_indikator')
                        ->where('nilai_purata', null)
                        ->where('nilai_sisihanpiawai', null)
                        ->get()
                        ->getResultArray();
    }
}