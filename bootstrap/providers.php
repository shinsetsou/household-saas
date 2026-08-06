<?php

use App\Providers\AppServiceProvider;
use App\Providers\SeoServiceProvider;
use Mailtrap\Bridge\Laravel\MailtrapApiProvider;

return [
    AppServiceProvider::class,
    SeoServiceProvider::class,
    MailtrapApiProvider::class,
];
