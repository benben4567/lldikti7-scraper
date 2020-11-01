<?php

namespace App\Http\Controllers;

use App\Scrap;
use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class ScrapController extends Controller
{


    public function send()
    {
        Telegram::sendMessage([
            'chat_id' => env('TELEGRAM_CHANNEL_ID', ''),
            'parse_mode' => 'HTML',
            'text' => "Uji Coba",
        ]);
    }
}
