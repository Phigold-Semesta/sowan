<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublikController extends Controller
{
    // Halaman Beranda Utama
    public function beranda()
    {
        return view('welcome');
    }

    // Halaman Profil Instansi
    public function profil()
    {
        return view('profil_publik.profile');
    }

    // Halaman Layanan
    public function layanan()
    {
        return view('profil_publik.layanan');
    }

    // Halaman Buku Panduan
    public function panduan()
    {
        return view('profil_publik.panduan');
    }
}