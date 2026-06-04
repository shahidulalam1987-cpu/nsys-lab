<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        $totalClients = 150;
        $totalOrders = 325;
        $totalRevenue = 5200;
        $pendingPayments = 800;

        $clients = [
            [
                'name' => 'Rahim',
                'phone' => '017xxxxxxxx',
                'status' => 'Active'
            ],
            [
                'name' => 'Karim',
                'phone' => '018xxxxxxxx',
                'status' => 'Pending'
            ]
        ];

        return view('welcome', compact(
            'totalClients',
            'totalOrders',
            'totalRevenue',
            'pendingPayments',
            'clients'
        ));
    }
}