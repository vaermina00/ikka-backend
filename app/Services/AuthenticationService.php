<?php 

namespace App\Services;

use App\Models\PenggunaModel;

class AuthenticationService
{
    protected $penggunaModel;

    public function __construct()
    {
        $this->penggunaModel = new PenggunaModel();
    }

    private function findActiveUser(string $username, string $jenis)
    {
        // Fetch user
        $user = $this->penggunaModel
            ->where('katanama_pengguna', $username)
            ->where('kod_jenispengguna', $jenis)
            ->where('tarikh_padam', null)
            ->first();

        if (!$user) {
            return [
                'status'  => false,
                'message' => 'User not found.',
                'data'    => null
            ];
        }

        // Check active/activated
        if ($user['status_pengguna'] === false) {
            return [
                'status'  => false,
                'message' => 'User is inactive.',
                'data'    => null
            ];
        }

        // Success → return user object
        return [
            'status'  => true,
            'data'    => $user
        ];
    }

    public function login(string $username, string $password, string $jenis): array
    {
        // find active user
        $activeUser = $this->findActiveUser($username, $jenis);

        if (!$activeUser['status']) {
            return $activeUser; // return the error
        }

        $user = $activeUser['data'];

        // Verify password
        if (!password_verify($password, $user['katalaluan_pengguna'])) {
            return [
                'status'  => false,
                'message' => 'Invalid password.',
                'data'    => null
            ];
        }

        // $userIndikator = $this->retrieveUserIndikator($username, $jenis);

        // if (!$userIndikator['status']) {
        //     return $userIndikator; // return the error
        // }

        // $user = $userIndikator['data'];

        return [
            'status'  => true,
            'message' => 'Login successful.',
            'data'    => [
                            'id_pengguna'       => $user['id_pengguna'],
                            'nama'              => $user['nama_pengguna'],
                            'email'             => $user['emel_pengguna'],
                            'telefon'           => $user['telefon_pengguna'],
                            'jawatan'           => $user['jawatan_pengguna'],
                            'no_surat'          => $user['no_surat_pengguna'],
                            'tarikh_surat'      => $user['tarikh_surat_pengguna'],
                            'tarikh_aktif'      => $user['tarikh_aktif_pengguna'],
                            'status'            => $user['status_pengguna'],
                            'agensi'            => $user['id_agensi'],
                            'jabatan'           => $user['id_jabatan'],
                            'id_jenispengguna'  => $user['id_jenispengguna'],
                            'pengguna_pencipta' => $user['id_pengguna_pencipta'],
                            'kod_jenispengguna' => $user['kod_jenispengguna']
                        ]
        ];
    }

    public function retrieveUserIndikator(string $username, string $jenis): array
    {
        // retrieve user data
        $userIndikator = $this->penggunaModel->getUserIndikator($username, $jenis);

        // indikator assigned
        if (empty($userIndikator)) {
            return [
                'status'  => false,
                'message' => 'No indikator assigned.',
                'data'    => null
            ];
        }

        // Initialize the grouped data structure
        $groupedIndikator = [];

        foreach ($userIndikator as $row) {
            $teras = $row['nama_teras'];
            $komponen = $row['nama_komponen'];

            // 1. Create the Teras entry if it doesn't exist
            if (!isset($groupedIndikator[$teras])) {
                $groupedIndikator[$teras] = [
                    'nama_teras' => $teras,
                    'komponen' => [] // Initialize array for Komponen under this Teras
                ];
            }

            // 2. Create the Komponen entry under the Teras if it doesn't exist
            if (!isset($groupedIndikator[$teras]['komponen'][$komponen])) {
                $groupedIndikator[$teras]['komponen'][$komponen] = [
                    'nama_komponen' => $komponen,
                    'indikator' => [] // Initialize array for Indicators under this Komponen
                ];
            }

            // 3. Add the indicator data to the current Komponen's indicator list
            $groupedIndikator[$teras]['komponen'][$komponen]['indikator'][] = [
                'kod_indikator'      => $row['kod_indikator'],
                'nama_indikator'     => $row['nama_indikator'],
                'impak_indikator'    => $row['impak_indikator'],
                'pemberat_indikator' => $row['pemberat_indikator'],
                'status_indikator'   => $row['status_indikator']
            ];
        }

        // Convert the associative array (with Teras names as keys) back to a sequential array 
        // for a cleaner JSON output, and convert Komponen sub-arrays as well.
        $finalData = [];
        foreach ($groupedIndikator as $terasData) {
            // Convert the 'komponen' associative array back to a sequential array
            $terasData['komponen'] = array_values($terasData['komponen']);
            $finalData[] = $terasData;
        }


        return [
            'status'  => true,
            'message' => 'Indikator retrieve success.',
            'data'    => $finalData // Use the new grouped and cleaned array
        ];
    }
}
