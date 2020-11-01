<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Laravel\Facades\Telegram;

class NewsController extends Controller
{
    private $url = "https://www.kopertis7.go.id/pengumumanLengkap";
    private $data = [];

    public function __construct()
    {
        $response = Http::get($this->url);
        $body = $response->body();

        $crawler = new Crawler($body);
        $filter = 'div.col-md-8 > div.pengumuman-isi';
        $catsHTML = $crawler->filter($filter)->each(function (Crawler $node) {
                    $explode = explode("-",$node->filter('div.title-berita > a.link-pengumuman')->attr('href'));
                    $id = (int)$explode[1];
                    $title = $node->filter('div.title-berita')->text();
                    $link = "http://lldikti7.ristekdikti.go.id/".$node->filter('div.title-berita > a.link-pengumuman')->attr('href');
                    $tgl = trim($node->filter('p')->text());
                    $date = str_replace(",","",$tgl);
                    return array('id' => $id, 'title' => $title, 'link' => $link, 'tgl' => $date);
        });
        unset($crawler);

        $this->data = $catsHTML;
    }

    public function insert($data)
    {
        $berita = DB::table('news')->insert($data);

        if ($berita) {
            $message = "New data created.";
            foreach ($data as $dt) {
                $text = "<b>PENGUMUMAN</b> \n";
                $text .= $dt['title'];
                $text .= "\n";
                $text .= "<a href=\"".$dt['link']."\">".$dt['link']."</a>";
                Telegram::sendMessage([
                    'chat_id' => env('TELEGRAM_CHANNEL_ID', ''),
                    'parse_mode' => 'HTML',
                    'text' => $text
                ]);
            }
        } else {
            $message = "Failed insert data.";
        }

        $res = [
            "message" => $message,
            "new_rows" => count($data)
        ];

        return $res;
    }

    public function compare()
    {
        $data = $this->data;

        $latest = DB::table('news')->latest('id')->first();
        $id = $latest->id;

        // compare
        $new_data = array_filter($data, function($obj) use ($id){
            if ($obj['id'] > $id) {
                return true;
            }
        });

        // if latest data different then insert
        if (count($new_data) > 0) {
            $res = $this->insert($new_data);
        } else {
            $res = [
                "message" => "Data up to date.",
                "new_rows" => 0
            ];
        }

        return response()->json($res);
    }
}
