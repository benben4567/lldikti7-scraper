## <p align="center">NEWS SCRAPER</p>
<p align="center">
<img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

## About this project

Just small project that solve my problem for stay up to date with latest news in [LLDIKTI 7 Website](http://lldikti7.ristekdikti.go.id). How it's works:

- Scraping web page
- Post in telegram channel
- Just that!

This app isn't real-time, but almost real-time. I use longpolling method. 

## How to use
1. git clone https://github.com/benben4567/lldikti7-scraper.git
2. composer install
3. php artisan key:generate
4. Update your .env (Database and Telegram api-key)
5. php artisan migrate
6. php artisan news:fetch

If you want automate scraping, you can use cron job in your server.

## Requirement

- PHP 7.2

## Dependencies

- [Laravel 7.0](https://laravel.com)
- [Telegram Bot SDK](https://telegram-bot-sdk.readme.io/docs)
- [Symfony Domcrawler](https://packagist.org/packages/symfony/dom-crawler)


## Contributing

Anyone is welcome to contribute in this repository.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
