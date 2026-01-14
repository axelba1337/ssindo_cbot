<?php

use Illuminate\Support\Facades\Artisan;

// Contoh closure command (opsional):
Artisan::command('ping', function () {
    $this->info('pong');
})->describe('Simple health check');