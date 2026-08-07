<?php

use App\Providers\AIServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\SeoServiceProvider;
use Mailtrap\Bridge\Laravel\MailtrapApiProvider;

return [
    AppServiceProvider::class,
    AIServiceProvider::class,
    SeoServiceProvider::class,
    MailtrapApiProvider::class,
];
