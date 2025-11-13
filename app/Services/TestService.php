<?php

namespace App\Services;

use App\Repositories\TestRepository;

class TestService
{
    protected $testRepository;

    public function __construct(TestRepository $testRepository)
    {
        $this->testRepository = $testRepository;
    }

    public function fetchAll()
    {
        return $this->testRepository->getAll();
    }
}
