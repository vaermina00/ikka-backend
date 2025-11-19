<?php

namespace App\Models;

use CodeIgniter\Model;

class PenggunaModel extends Model
{
    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';

    protected $allowedFields = [
        'katanama_pengguna',
        'katalaluan_pengguna',
        'nama_pengguna',
        'emel_pengguna',
        'telefon_pengguna',
        'jawatan_pengguna',
        'status_pengguna',
        'kod_jenispengguna',
        'tarikh_padam',
        'token_awam',
        'token_rahsia'
    ];

    public function getUserIndikator($username, $jenis, $tahun, $penggal): array
    {
        $db = \Config\Database::connect();

        $sql = "
            select t.nama_teras, k.nama_komponen, i.kod_indikator, i.nama_indikator, i.impak_indikator, i.pemberat_indikator, i.status_indikator, pi2.nilai
            from pengguna p
            left join komponen k on k.id_agensi = p.id_agensi and k.id_jabatan = p.id_jabatan
            inner join indikator i on i.id_komponen = k.id_komponen
            left join teras t on t.id_teras = k.id_teras
            left join pengumpulandata_indikator pi2 on pi2.id_teras = t.id_teras and pi2.id_komponen = k.id_komponen and pi2.id_indikator = i.id_indikator
            left join pengumpulandata p2 on p2.id_pengumpulandata = pi2.id_pengumpulandata 
            where p.katanama_pengguna = ? and p.kod_jenispengguna = ? and p.status_pengguna = true and p2.tahun_pengumpulandata = ? and p2.penggal_pengumpulandata = ?
            order by i.id_indikator
        ";

        return $db->query($sql, [$username, $jenis, $tahun, $penggal])->getResultArray();
    }
}
