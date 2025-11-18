<?php 

namespace App\Controllers\Api\Protected;

use CodeIgniter\RESTful\ResourceController;
use App\Services\CalculateIndexService;
use Exception;

class CalculateIndexController extends ResourceController
{
    private $calculateIndexService;

    public function __construct() 
    {
        $this->calculateIndexService = new CalculateIndexService();
    }

    public function peratusKomponen()
    {
        try {
            $data = $this->calculateIndexService->checkPeratusanKomponen();

            return $this->respond([
                'status' => 'success',
                'komponen' => $data
            ]);
        } catch (Exception $e) {
            log_message('error', 'Error in peratusKomponen: ' . $e->getMessage());

            return $this->respond([
                'status' => 'error',
                'message' => 'Failed to calculate peratus komponen: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sumPemberat()
    {
        try {
            $data = $this->calculateIndexService->calculateSumPemberat();

            return $this->respond([
                'status' => 'success',
                'sum_pemberat' => $data
            ]);
        } catch (Exception $e) {
            log_message('error', 'Error in sumPemberat: ' . $e->getMessage());

            return $this->respond([
                'status' => 'error',
                'message' => 'Failed to calculate sum pemberat: ' . $e->getMessage()
            ], 500);
        }
    }

    public function calculatePengiraanIndeks()
    {
        try {
            $requestBody = $this->request->getJSON(true);

            $data = $this->calculateIndexService->generatePengiraanIndeks($requestBody);

            return $this->respond([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (Exception $e) {
            log_message('error', 'Error in calculatePengiraanIndeks: ' . $e->getMessage());

            return $this->respond([
                'status' => 'error',
                'message' => 'Failed to calculate pengiraan indeks: ' . $e->getMessage()
            ], 500);
        }
    }
}
