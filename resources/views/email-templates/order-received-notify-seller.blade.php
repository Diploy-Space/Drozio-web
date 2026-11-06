<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ translate('New order received') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/email-basic.css') }}">
</head>
<body>

<?php

$companyPhone = getWebConfig(name: 'company_phone');
$companyEmail = getWebConfig(name: 'company_email');
$companyName = getWebConfig(name: 'company_name');
$companyLogo = getWebConfig(name: 'company_web_logo');
?>

<div class="d-flex justify-content-center align-items-center m-auto vh-100">
    <div class="card">
        <div class="m-auto bg-white pt-40px pb-40px text-center">
            <div class="d-block">
                <div class="d-flex justify-content-center align-items-center gap-1">
                    <img src="{{ getStorageImages(path: $companyLogo, type: 'backend-logo') }}" alt="{{ $companyName }}"
                         class="width-auto h-50px">
                    {{ $companyName }}
                </div>
            </div>
        </div>
        <div class="card-header mb-3 text-center">
            <h3 class="pb-20px">{{ translate('Notification mail for new order received') }}</h3>
            {{ translate('You have a new order. Log in to your panel to see.') }}.
            <br/>
            <h3 class="pt-20px">{{ translate('New order ID for you') }} :</h3>
        </div>
        <div class="card-body">
            <h1 class="text-info text-center pb-20px">{{ $id }}</h1>

            <p class="text-center">
                {{ translate('Need help or have questions? Email us.') }}.
                {{ translate('From') }} {{ getWebConfig(name: 'company_name') }}
            </p>

        </div>
    </div>
</div>
</body>
</html>


