<?php

class PendonorModel
{
    private $pdo
    private $builder;

    public function __construct($database) {
        $this->pdo = $database;
        $this->builder = $this->db->table('pendonor');
    }

    public function getRiwayatDonasi($id_pendonor) {
        return $this->db->table('transaksi_donasi as td')
            ->select('
                td.id_transaksi,
                td.id_pendonor,
                kd.id_kegiatan,
                p.id_petugas,
                td.tanggal_donasi,
                td.jumlah_kantong
            ')
            ->join('kegiatan_donasi as kd', 'td.id_kegiatan = kd.id_kegiatan')
            ->join('petugas as p', 'td.id_petugas = p.id_petugas')
            ->where('td.id_pendonor', $id_pendonor)
            ->orderBy('td.tanggal_donasi', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getPend()
    }


}