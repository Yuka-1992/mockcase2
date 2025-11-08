<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class AdminStaffsIndexController extends Controller
{
    public function index()
    {
        $users = DB::table('users')
            ->where('role', 'user')
            ->orderBy('id')
            ->get();

        return view('admin.staff.list', compact('users'));
    }
}
