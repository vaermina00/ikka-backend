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

    public function downloadIndeksCsv()
    {
        // 1. Get Parameter data from service
        $parameter = [
            'base_tahun'   => $this->request->getGet('base_tahun'),
            'base_penggal' => $this->request->getGet('base_penggal')
        ];

        $indeks = $this->calculateIndexService->generateIndeksCsv($parameter);

        if ($indeks['status']) {
            $rawSample = json_decode($indeks['data'][0]['nilai_indeks'], true);

            if (!is_array($rawSample) || empty($rawSample)) {
                return $this->fail('Invalid nilai_indeks format.');
            }

            $sample = $rawSample[0];

            // 2. Detect dynamic columns
            $dateColumns = [];

            foreach ($sample as $key => $value) {
                if (
                    str_starts_with($key, 'nilaizscore_') ||
                    str_starts_with($key, 'nilaiindeksindikator_') ||
                    str_starts_with($key, 'nilaiindeksasas_') ||
                    str_starts_with($key, 'nilaiindekskomponen_') ||
                    str_starts_with($key, 'nilaiindeksteras_') ||
                    str_starts_with($key, 'nilaiindekstahun_')
                ) {
                    $dateColumns[] = $key;
                }
            }

            // Convert column names to friendly text
            $csvColumns = array_map(function ($col) {
                return strtoupper(str_replace(
                    [
                        'nilaizscore_', 'nilaiindeksindikator_', 'nilaiindeksasas_',
                        'nilaiindekskomponen_', 'nilaiindeksteras_', 'nilaiindekstahun_', '_'
                    ],
                    ['', '', '', '', '', '', ' '],
                    $col
                ));
            }, $dateColumns);

            // 3. Build CSV rows
            $rows = [
                ['Indeks' => 'Nilai Zscore'],
                ['Indeks' => 'Nilai Indeks Indikator'],
                ['Indeks' => 'Nilai Indeks Asas'],
                ['Indeks' => 'Nilai Indeks Komponen'],
                ['Indeks' => 'Nilai Indeks Teras'],
                ['Indeks' => 'Nilai Indeks Tahun'],
            ];

            foreach ($dateColumns as $col) {
                $suffix = substr($col, strpos($col, '_') + 1);

                $rows[0][$col] = $sample["nilaizscore_" . $suffix] ?? '';
                $rows[1][$col] = $sample["nilaiindeksindikator_" . $suffix] ?? '';
                $rows[2][$col] = $sample["nilaiindeksasas_" . $suffix] ?? '';
                $rows[3][$col] = $sample["nilaiindekskomponen_" . $suffix] ?? '';
                $rows[4][$col] = $sample["nilaiindeksteras_" . $suffix] ?? '';
                $rows[5][$col] = $sample["nilaiindekstahun_" . $suffix] ?? '';
            }

            // 4. Write CSV
            $filename = 'indeks.csv';

            // use memory stream (no file created on disk)
            $fp = fopen('php://temp', 'r+');

            // header row
            fputcsv($fp, array_merge(['Indeks'], $csvColumns));

            // data rows
            foreach ($rows as $r) {
                fputcsv($fp, $r);
            }

            // move pointer back to start
            rewind($fp);

            // get CSV contents as string
            $csv = stream_get_contents($fp);
            fclose($fp);

            // return as downloadable file
            return $this->response
                ->setHeader('Content-Type', 'text/csv')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($csv);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => $indeks['message'],
                'error' => $indeks['error']
            ]);
        }

    }

    public function downloadIndikatorCsv()
    {
        $data = $this->calculateIndexService->generateIndikatorCsv();

        // build CSV string
        $filename = 'indikator_' . date('Ymd_His') . '.csv';

        // CSV header
        $header = [
            'Teras',
            'Komponen',
            'Indikator',
            'Impak',
            'Pemberat',
            'Status',
            'Peratusan',
            'Purata',
            'Sisihan Piawai'
        ];

        // open memory stream
        $fp = fopen('php://temp', 'r+');

        // write header
        fputcsv($fp, $header);

        // write data rows
        foreach ($data as $r) {
            fputcsv($fp, [
                $r['nama_teras'],
                $r['nama_komponen'],
                $r['nama_indikator'],
                $r['impak_indikator'],
                $r['pemberat_indikator'],
                $r['status_indikator'],
                $r['peratusan_indikator'],
                $r['nilai_purata'],
                $r['nilai_sisihanpiawai'],
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        // return as downloadable file
        return $this->response
                    ->setHeader('Content-Type', 'text/csv')
                    ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->setBody($csv);
    }

    public function calculateIndikator()
    {
        $data = $this->calculateIndexService->calculateIndikator();

        if ($data['status']) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => $data['message']
            ]);
        } else {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => $data['message'],
                'error' => $data['error']
            ]);
        }
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

            if ($data['status']) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => $data['message']
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => $data['message'],
                    'error' => $data['error']
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'Error in calculatePengiraanIndeks: ' . $e->getMessage());

            return $this->respond([
                'status' => 'error',
                'message' => 'Failed to calculate pengiraan indeks: ' . $e->getMessage()
            ], 500);
        }
    }
}
