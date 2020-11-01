<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Keyboard\Keyboard;
use App\Scrap;

class News extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scraping news from main website.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('Scraping news...');
        $response = Http::get("https://www.kopertis7.go.id/pengumumanLengkap");
        if ($response->successful()) {
            $body = $response->body();
        } else {
            // insert DB {status: error, desc: Main website status response 404}
            Scrap::create([
                'status' => 'error',
                'description' => 'Main website status response 404'
            ]);
            die();
        }

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

        $data = $catsHTML;
        $latest = DB::table('news')->latest('id')->first();

        if ($latest) {
            $id = $latest->id;
        } else {
            $id = 0;
        }

        // compare
        $new_data = array_filter($data, function($obj) use ($id){
            if ($obj['id'] > $id) {
                return true;
            }
        });

        // if latest data different then insert
        if (count($new_data) > 0) {
            // insert DB {status: success, desc: Scraping success. Got [n] news!}
            Scrap::create([
                'status' => 'success',
                'description' => 'Got ['.count($new_data).'] news!',
            ]);

            $this->line("Got [". count($new_data)."] news! Inserting to Database now...");
            $berita = DB::table('news')->insert($new_data);
            if ($berita) {
                $this->info('Success. Sending notification...');
                foreach ($new_data as $dt) {
                    $text = "<b>PENGUMUMAN</b> \n";
                    $text .= $dt['title'];
                    $text .= "\n";
                    $text .= "<a href=\"".$dt['link']."\">".$dt['link']."</a>";

                    $reply_markup = Keyboard::make()->inline()->row(
                                        Keyboard::inlineButton(['text' => 'Kunjungi Web', 'url' => $dt['link']]),
                                    );
                    Telegram::sendMessage([
                        'chat_id' => env('TELEGRAM_CHANNEL_ID', ''),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $reply_markup,
                        'text' => $text
                    ]);
                    sleep(2);
                }
                $this->info('Done.');
            } else {
                $this->error('Opss, failed inserting data. Quit.');
            }
        } else {
            // insert DB {status: success, desc: Scraping success. Database up to date}
            Scrap::create([
                'status' => 'success',
                'description' => 'Database up to date.',
            ]);
            $this->info('No latest news. Database up to date.');
        }
    }
}
