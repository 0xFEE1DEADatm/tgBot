<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
<<<<<<< HEAD
=======

// use Illuminate\Support\Facades\DB;

// Route::get('/db-test', function () {
//     try {
//         DB::connection()->getPdo();
//         return 'Connection to database successful!';
//     } catch (\Exception $e) {
//         return 'Connection failed: ' . $e->getMessage();
//     }
// });
>>>>>>> 21403cc (Initial commit)
