<?php

use App\Providers\AppServiceProvider;
use Mailtrap\Bridge\Laravel\MailtrapApiProvider;

return [
    AppServiceProvider::class,
    MailtrapApiProvider::class,
];
