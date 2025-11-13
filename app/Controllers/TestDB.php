<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\Exceptions\DatabaseException;

class TestDB extends BaseController
{
    public function index()
    {
        //
    }

    public function testDB()
    {
        try {
            $db = \Config\Database::connect();
            $db->initialize();

            if ($db->connID) {
                return $this->response->setJSON(['status' => 'success', 'message' => 'Database connected successfully.']);
            }
        } catch (DatabaseException $e) {
            return $this->response->setJSON(['status' => 'fail', 'message' => $e->getMessage()]);
        }
    }

    public function listDB()
    {
        $db = \Config\Database::connect();
        $tables = $db->listTables();
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $tables,
        ])->setStatusCode(200);
    }
}
