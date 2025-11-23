<?php

namespace App\Controllers\Api\Protected;

use CodeIgniter\RESTful\ResourceController;
use App\Services\DataEntryService;

class DataEntryController extends ResourceController
{
    protected $dataEntryService;

    public function __construct() 
    {
        $this->dataEntryService = new DataEntryService();
    }

    public function updateDataEntryNilai()
    {
        $requestBody = $this->request->getJSON(true); // PUT JSON body

        foreach ($requestBody as $item) {
            if (!isset($item['id_tetapan']) || !isset($item['nilai'])) {
                return $this->failValidationErrors('Each item must have id_tetapan and nilai');
            }
        }

        // Call service for bulk update
        $result = $this->dataEntryService->updateNilai($requestBody);

        if ($result['status'] === false) {
            return $this->respond([
                'status'  => 'error',
                'message' => $result['message'],
                'data'    => null
            ], 400);
        }

        return $this->respond([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => null
        ], 200);
    }

}
