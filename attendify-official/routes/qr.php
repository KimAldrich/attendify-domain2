<?php
// routes/qr.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

Route::get('/registrations/{eventCode}/{uid}/qr.png', function (string $eventCode, string $uid) {

    $payload = json_encode([
        'event' => $eventCode,
        'uid'   => $uid,
        'ver'   => 1,
    ], JSON_UNESCAPED_SLASHES);

    $qr = QrCode::create($payload)
        ->setEncoding(new Encoding('UTF-8'))
        ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
        ->setSize(1024)
        ->setMargin(16);

    $writer = new PngWriter();
    $result = $writer->write($qr);

    $eventTitle = $eventCode;
    $shortUid   = $uid !== 'guest' ? substr($uid, 0, 8) : 'guest';
    $filename   = 'attendify-qr-'.Str::slug($eventTitle).'-'.$shortUid.'.png';

    return response($result->getString(), 200, [
        'Content-Type'        => 'image/png',
        'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma'              => 'no-cache',
    ]);
})->middleware('auth')->name('registration.qr.download.png');
