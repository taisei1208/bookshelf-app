<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return '
        <h1>書籍一覧（準備中）</h1>

        <form method="POST" action="'.route('logout').'">
            '.csrf_field().'
            <button type="submit">ログアウト</button>
        </form>
    ';
});
