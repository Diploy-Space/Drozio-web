<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\BuilderBindingServiceProvider;
use App\Providers\ConfigServiceProvider;
use App\Providers\FirebaseServiceProvider;
use App\Providers\InterfaceServiceProvider;
use App\Providers\MailConfigServiceProvider;
use App\Providers\ObserverServiceProvider;
use App\Providers\PaymentConfigProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\SocialLoginServiceProvider;
use App\Providers\ThemeServiceProvider;
use Intervention\Image\ImageServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;
use Madnest\Madzipper\MadzipperServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    FirebaseServiceProvider::class,
    RouteServiceProvider::class,
    MailConfigServiceProvider::class,
    PaymentConfigProvider::class,
    ConfigServiceProvider::class,
    ThemeServiceProvider::class,
    SocialLoginServiceProvider::class,
    InterfaceServiceProvider::class,
    BuilderBindingServiceProvider::class,
    ObserverServiceProvider::class,

    ImageServiceProvider::class,
    ExcelServiceProvider::class,
    MadzipperServiceProvider::class,
];
