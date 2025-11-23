<?php 

namespace App\Services;

use App\Models\PengumpulandataIndikatorModel;

class DataEntryService
{
    protected $model;

    public function __construct()
    {
        $this->model = new PengumpulandataIndikatorModel();
    }

    public function updateNilai(array $items)
    {
        $updatedCount = 0;

        foreach ($items as $item) {
            $id_tetapan = $item['id_tetapan'];
            $nilai      = $item['nilai'];

            $this->model
                ->where('id_tetapan', $id_tetapan)
                ->set(['nilai' => $nilai])
                ->update();

            if ($this->model->db->affectedRows() > 0) {
                $updatedCount++;
            }
        }

        if ($updatedCount === 0) {
            return [
                'status'  => false,
                'message' => 'No records updated. IDs may not exist.'
            ];
        }

        return [
            'status'  => true,
            'message' => "$updatedCount record(s) updated successfully."
        ];
    }

}
