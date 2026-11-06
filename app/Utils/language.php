<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

if (! function_exists('translate')) {
    function translate($key = null, array $replace = []): ?string
    {
        static $resolved = [];

        $local = getDefaultLanguage();
        $appLocale = isApiLocaleRequest() ? $local : getLanguageCode(country_code: $local);
        $memoizable = $replace === [] && is_string($key);

        if (! $key) {
            useLocale($appLocale);

            return $key;
        }

        if ($memoizable && isset($resolved[$local][$key])) {
            useLocale($appLocale);

            return $resolved[$local][$key];
        }

        useLocale($local);
        $message = getOrPutTranslateMessageValueByKey(local: $local, key: $key, replace: $replace);
        useLocale($appLocale);

        if ($memoizable) {
            $resolved[$local][$key] = $message;
        }

        return $message;
    }

    function useLocale(string $locale): void
    {
        if (App::getLocale() !== $locale) {
            App::setLocale($locale);
        }
    }

    function applyTranslationReplacements(string $line, array $replace): string
    {
        if (empty($replace)) {
            return $line;
        }

        uksort($replace, fn ($a, $b) => mb_strlen((string) $b) <=> mb_strlen((string) $a));

        $substitutions = [];
        foreach ($replace as $token => $value) {
            if (! is_scalar($value) && ! is_null($value)) {
                continue;
            }

            $token = (string) $token;
            $value = (string) $value;

            $substitutions[':'.$token] = $value;
            $substitutions[':'.mb_convert_case($token, MB_CASE_TITLE, 'UTF-8')] = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
            $substitutions[':'.mb_strtoupper($token, 'UTF-8')] = mb_strtoupper($value, 'UTF-8');
        }

        return strtr($line, $substitutions);
    }

    function normalizeTranslationKey(string $key): string
    {
        static $normalized = [];

        if (isset($normalized[$key])) {
            return $normalized[$key];
        }

        $canonical = str_replace('"', '', $key);
        $canonical = preg_replace_callback(
            '/:[a-zA-Z][a-zA-Z0-9]*|_/',
            fn ($match) => $match[0] === '_' ? ' ' : $match[0],
            $canonical
        );

        $canonical = trim(preg_replace('/\s+/u', ' ', $canonical));

        if (! preg_match('/^\p{Ll}{1,2}\p{Lu}/u', $canonical)) {
            $canonical = mb_strtoupper(mb_substr($canonical, 0, 1)).mb_substr($canonical, 1);
        }

        return $normalized[$key] = $canonical;
    }

    function exportLanguageFileEntry(string $key, string $value): string
    {
        $escape = fn (string $text) => str_replace(['\\', "'"], ['\\\\', "\\'"], $text);

        return "'".$escape($key)."' => '".$escape($value)."',\n";
    }

    function getOrPutTranslateMessageValueByKey(string $local, string $key, array $replace = []): array|string|null
    {
        static $loadedMessages = [];

        try {
            if (! isset($loadedMessages[$local])) {
                $newMessagesPath = base_path('resources/lang/'.$local.'/new-messages.php');
                $loadedMessages[$local] = [
                    'messages' => include (base_path('resources/lang/'.$local.'/messages.php')),
                    'new' => file_exists($newMessagesPath) ? include ($newMessagesPath) : [],
                ];
            }
            $translatedMessagesArray = $loadedMessages[$local]['messages'];
            $newMessagesArray = $loadedMessages[$local]['new'];
            $sourceText = $key;
            $key = normalizeTranslationKey($key);

            $processedKey = preg_match('/^\p{Ll}{1,2}\p{Lu}/u', $key) ? $key : ucfirst($key);

            if (! array_key_exists($key, $translatedMessagesArray) && ! array_key_exists($key, $newMessagesArray)) {
                $message = $processedKey;

                if (isPersistableTranslationKey($sourceText)) {
                    $newMessagesArray[$key] = $processedKey;
                    $loadedMessages[$local]['new'] = $newMessagesArray;

                    $languageFileContents = "<?php\n\nreturn [\n";
                    foreach ($newMessagesArray as $languageKey => $value) {
                        $languageFileContents .= '    '.exportLanguageFileEntry((string) $languageKey, (string) $value);
                    }
                    $languageFileContents .= "];\n";

                    $targetPath = base_path('resources/lang/'.$local.'/new-messages.php');
                    file_put_contents($targetPath, $languageFileContents);
                }
            } elseif (array_key_exists($key, $translatedMessagesArray)) {
                $message = __('messages.'.$key, $replace);
            } elseif (array_key_exists($key, $newMessagesArray)) {
                $message = (string) $newMessagesArray[$key];
            } else {
                $message = __('messages.'.$key, $replace);
            }
        } catch (Exception $exception) {
            $message = ucfirst(normalizeTranslationKey(str_replace("\\'", "'", $key)));
        }
        $message = applyTranslationReplacements($message, $replace);

        if ($local === 'en' && ! preg_match('/^\p{Ll}{1,2}\p{Lu}/u', $message)) {
            $message = ucfirst($message);
        }

        return $message;
    }
}

if (! function_exists('isPersistableTranslationKey')) {
    function isPersistableTranslationKey($key): bool
    {
        if (! is_string($key)) {
            return false;
        }

        $key = trim($key);

        if ($key === '' || mb_strlen($key) > 500) {
            return false;
        }

        if (! preg_match('/\p{L}/u', $key)) {
            return false;
        }

        if (! preg_match('/[A-Za-z]/', $key)) {
            return false;
        }

        if (isLeakedCodeOrMarkup($key) || isMachineDataShape($key) || isPlaceholderMaskOrKeyboardMash($key)) {
            return false;
        }

        $words = preg_split('/\s+/', $key, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) <= 3 && preg_match('/^[\w-]+(\.[\w-]+)*\.(p8|pem|key|crt|cer|json|xml|env|zip|csv|pdf|php|js|txt|log)$/i', end($words))) {
            return false;
        }

        if (isNavigationPathThroughAnotherProduct($key)) {
            return false;
        }

        $text = trim(preg_replace('/\s+/u', ' ', str_replace('_', ' ', $key)));

        if (isCalendarLabel($text) || isBareTechnicalToken($text) || isRenderedValidationMessage($text) || isCredentialFieldLabel($text)) {
            return false;
        }

        if (preg_match('/^\(.*\)[.,;:!?]?$/su', $text) || preg_match('/^[‘“].*[’”][.,;:!?]?$/su', $key)) {
            return false;
        }

        if (isMediaSizeConstraint($text)) {
            return false;
        }

        if (isFigureLedBadge($text)) {
            return false;
        }

        if (isPlaceholderLedStem($text)) {
            return false;
        }

        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($tokens) >= 4) {
            $utility = 0;
            foreach ($tokens as $token) {
                if (preg_match('/^[a-z]+(-[a-z0-9]+)+$/', $token)) {
                    $utility++;
                }
            }
            if ($utility >= 3 && $utility * 2 >= count($tokens)) {
                return false;
            }
        }

        if (isCountryName($text)) {
            return false;
        }

        if (isColourOrVariationCode($text)) {
            return false;
        }

        if (isExcludedFromTranslation($text)) {
            return false;
        }

        if (isThirdPartyIntegrationInstruction($text)) {
            return false;
        }

        if (isPlaceholderWord($text)) {
            return false;
        }

        if (mb_strlen($text) <= 1 || preg_match('/^\p{L}\s*[-–—]\s*\p{L}$/u', $text)) {
            return false;
        }

        if (preg_match('/^(400|401|403|404|405|408|409|410|419|422|429|500|502|503|504)\b/', $text)
            && str_word_count($text) <= 3) {
            return false;
        }

        if (preg_match('/^\s*(ex|e\.?g|example|for example)\s*[:.]/i', $text)) {
            return false;
        }

        if (preg_match('/\b\d{1,6}[A-Za-z]?\s+\p{L}+.*\b(ave|avenue|street|st|road|rd|blvd|boulevard|lane|ln|drive|dr|court|ct|way)\.?\b/iu', $text)) {
            return false;
        }

        if (preg_match('/^[\w-]{12,}$/', $text) && preg_match('/\d/', $text)) {
            return false;
        }

        if (str_contains($text, ' = ') || preg_match_all('/\s[+×÷]\s/u', $text) >= 2) {
            return false;
        }

        if (preg_match('/^[$€£¥₹৳]\s*[\d,]+(\.\d+)?/u', $text)) {
            return false;
        }

        if (preg_match('/,\s*etc\.?\s*$/i', $text)
            && substr_count($text, ',') >= 2
            && ! preg_match('/\b(you|your|is|are|can|will|please|must|of|for|with)\b/i', $text)) {
            return false;
        }

        return ! isFileFormatOrSizeHint($text) && ! isFigureWithOnlyFillerAround($text);
    }

    function isLeakedCodeOrMarkup(string $key): bool
    {
        $codeMarkers = [
            '{{', '}}', '<?', '?>', '${', '@@',
            "' +", "+ '", '" +', '+ "',
            'function(', 'function (', 'document.', 'window.',
            'querySelector', 'innerHTML',
        ];
        foreach ($codeMarkers as $marker) {
            if (str_contains($key, $marker)) {
                return true;
            }
        }

        return (bool) preg_match('~</?\s*[a-z][a-z0-9]*(\s[^<>]*)?/?>~i', $key)
            || (bool) preg_match('/\w+\s*\[\s*\w+\s*\]\s*\./', $key)
            || (bool) preg_match('/\\\\[a-zA-Z]/', $key);
    }

    function isMachineDataShape(string $key): bool
    {
        if (preg_match('/^[\w-]+(\.[\w-]+)*\.(blade\.php|php|js|jsx|ts|tsx|vue|css|scss|less|json|xml|ya?ml|env|lock|md|sql|sh|bat|ini|conf|htaccess|txt|log|zip|pdf|csv|xlsx?|docx?)$/i', $key)) {
            return true;
        }

        if (preg_match('~\b[a-z][a-z0-9+.-]*://\S~i', $key)
            || preg_match('~^\d{1,3}(\.\d{1,3}){3}(:\d+)?$~', $key)
            || preg_match('~^localhost(:\d+)?$~i', $key)
            || (preg_match('~^[\w-]+(\.[\w-]+)+$~', $key)
                && preg_match('~\.(com|net|org|io|co|dev|local|test|app|xyz|info|biz|cloud)$~i', $key))) {
            return true;
        }

        if (preg_match('~^[\w.+-]+@[\w-]+(\.[\w-]+)+$~', $key)) {
            return true;
        }

        return (bool) preg_match('/\((EN|BD|BN|AR|SA|ES|IN|FR|DE|HI|PT|RU|ZH)\)\s*$/i', $key)
            || (bool) preg_match('/[\s_]x[\s_]*\d+$/i', $key)
            || (bool) preg_match('/\b\d{1,2}\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/i', $key)
            || (bool) preg_match('/\b\d{1,2}:\d{2}\s*(am|pm)\b/i', $key);
    }

    function isNavigationPathThroughAnotherProduct(string $key): bool
    {
        if (! str_contains($key, '→') && ! preg_match('/\s-+>\s/', $key)) {
            return false;
        }

        $segments = preg_split('/\s*(?:→|-+>)\s*/u', rtrim($key, " .\u{00A0}"), -1, PREG_SPLIT_NO_EMPTY);
        if (count($segments) < 2) {
            return false;
        }

        foreach ($segments as $segment) {

            if (str_word_count($segment) > 4) {
                return false;
            }
        }

        return count($segments) >= 3
            || (preg_match('/^[A-Z]/', $segments[0])
                && ! preg_match('/^(Found|Go|Open|Copy|Created|Click|Visit|Navigate|See|Head|Choose|Select|Enter)\b/i', $segments[0]));
    }

    function isPlaceholderMaskOrKeyboardMash(string $key): bool
    {
        $letterRuns = preg_split('/[^A-Za-z]+/', $key, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($letterRuns)) {
            return false;
        }

        $allMask = true;
        foreach ($letterRuns as $run) {
            if (strlen($run) < 3 || count(array_unique(str_split(strtolower($run)))) !== 1) {
                $allMask = false;
                break;
            }
        }
        if ($allMask) {
            return true;
        }

        $technicalRuns = ['https', 'xhtml', 'shtml', 'smtps', 'crypt'];

        foreach ($letterRuns as $run) {
            if (strlen($run) >= 5
                && preg_match('/[a-z]/', $run)
                && ! preg_match('/[aeiouy]/i', $run)
                && ! in_array(strtolower($run), $technicalRuns, true)
                && count(array_unique(str_split(strtolower($run)))) > 1) {
                return true;
            }
        }

        return (bool) preg_match('/^[A-Z0-9]{6,}$/', $key)
            && ! preg_match('/[AEIOUY]/', $key)
            && preg_match('/\d/', $key);
    }

    function isCalendarLabel(string $text): bool
    {
        return (bool) preg_match('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|Sun|Mon|Tue|Wed|Thu|Fri|Sat)$/', $text)
            || (bool) preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)$/i', $text);
    }

    function isFigureLedBadge(string $text): bool
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) < 2 || count($words) > 4) {
            return false;
        }

        return (bool) preg_match('/^(\d+(\.\d+)?%|\d+\/\d+)$/', $words[0]);
    }

    function isPlaceholderLedStem(string $text): bool
    {
        return (bool) preg_match('/^:[a-zA-Z][a-zA-Z0-9]*(\s|$)/', $text);
    }

    function isPaymentOrSmsGateway(string $text): bool
    {
        $gateways = 'twilio|2 ?factor|msg91|nexmo|alphanet sms|sms to|akandit sms|global sms|releans|signal ?wire|viatech|019 sms'
            .'|mercadopago|liqpay|instamojo|paypal|hyper ?pay|foloosi|paytm|amazon ?pay|cashfree|paytabs|bkash|fatoorah'
            .'|ccavenue|thawani|sixcash|hubtel|payfast|esewa|viva ?wallet|stripe|iyzi ?pay|phonepe|moncash|razor ?pay'
            .'|senang ?pay|paymob ?accept|maxicash|pvit|flutterwave|paystack|xendit|worldpay|ssl ?commerz|nagad|rocket|upay';

        return (bool) preg_match('/^('.$gateways.')$/i', $text);
    }

    function isCountryName(string $text): bool
    {
        static $countries = null;

        if ($countries === null) {
            $countries = [];

            $path = dirname(__DIR__, 2).'/Modules/Courier/resources/data/countries.php';
            if (is_file($path)) {
                $loaded = include $path;
                if (is_array($loaded)) {
                    foreach ($loaded as $name) {
                        $countries[mb_strtolower((string) $name)] = true;
                    }
                }
            }
            foreach (['brasil', 'méxico', 'perú', 'uae', 'ksa', 'usa', 'uk', 'españa', 'deutschland'] as $variant) {
                $countries[$variant] = true;
            }
        }

        return isset($countries[mb_strtolower(trim($text))]);
    }

    function isThirdPartyIntegrationInstruction(string $text): bool
    {
        if (count(preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY)) < 4) {
            return false;
        }

        if (preg_match('/^(are you sure|turn\s+(on|off))\b/i', $text)) {
            return false;
        }

        $platform = 'google analytics|google tag manager|tag manager|events? manager|ads manager|campaign manager'
            .'|data sources|data stream|meta pixel|facebook pixel|tiktok|snapchat|snap pixel|pinterest|linkedin'
            .'|twitter pixel|x pixel|gtm-';
        $credential = 'pixel id|measurement id|container id|partner id|tag id|tracking ids?|insight tag';

        return (bool) preg_match('/('.$platform.'|'.$credential.')/i', $text);
    }

    function isExcludedFromTranslation(string $text): bool
    {
        static $excluded = null;

        if ($excluded === null) {
            $path = dirname(__DIR__, 2).'/resources/lang/excluded-keys.php';
            $loaded = is_file($path) ? include $path : [];
            $excluded = [];
            if (is_array($loaded)) {
                foreach (array_keys($loaded) as $key) {
                    $excluded[mb_strtolower((string) $key)] = true;
                }
            }
        }

        return isset($excluded[mb_strtolower(normalizeTranslationKey($text))]);
    }

    function isColourOrVariationCode(string $text): bool
    {
        if (isColourWord($text)) {
            return true;
        }

        if (! preg_match('/^[A-Za-z0-9]+(-[A-Za-z0-9]+)+$/', $text)) {
            return false;
        }

        $segments = explode('-', $text);
        $colours = 0;
        $numbers = 0;

        foreach ($segments as $segment) {
            if (isColourWord($segment)) {
                $colours++;
            }
            if (ctype_digit($segment)) {
                $numbers++;
            }
        }

        return $colours > 0 || ($numbers > 0 && count($segments) >= 3);
    }

    function isColourWord(string $text): bool
    {
        return (bool) preg_match('/^(light|dark|deep|pale|bright)?\s*(red|blue|green|yellow|black|white|orange|purple'
            .'|pink|brown|grey|gray|maroon|navy|beige|cyan|magenta|violet|indigo|turquoise|teal|khaki|crimson'
            .'|multicolor|multicolour)$/i', trim($text));
    }

    function isPlaceholderWord(string $text): bool
    {
        if (preg_match('/^(foo|bar|baz|lorem|ipsum|lorem ipsum|asdf|qwerty|dummy|blah)$/i', $text)) {
            return true;
        }

        if (preg_match('/^(john doe|jhon doe|jhone doe|jhone|jane doe|john smith|robert smith|anya sharma|alex)$/i', $text)) {
            return true;
        }

        if (! preg_match('/^[A-Za-z]{3,6}$/', $text)) {
            return false;
        }

        $letters = strtolower($text);
        for ($i = 1; $i < strlen($letters); $i++) {
            if (ord($letters[$i]) !== ord($letters[$i - 1]) + 1) {
                return false;
            }
        }

        return true;
    }

    function isMediaSizeConstraint(string $text): bool
    {
        if (! preg_match('/\d+\s*(mb|kb|gb|tb|px|pixels?)\b/i', $text)) {
            return false;
        }

        $rest = preg_replace('/\d+\s*[:x×]\s*\d+/iu', '', $text);
        $rest = preg_replace('/\d+(\.\d+)?\s*(mb|kb|gb|tb|px|pixels?)?/i', '', $rest);
        $rest = preg_replace('/\b(image|images|logo|logos|banner|banners|icon|icons|thumbnail|thumbnails'
            .'|photo|photos|picture|pictures|video|videos|audio|document|documents|attachment|attachments'
            .'|file|files|size|sizes|dimension|dimensions|resolution|ratio|aspect'
            .'|max|maximum|min|minimum|must|should|be|is|are|may|can|will|not|no|exceed|exceeds|exceeding'
            .'|greater|larger|bigger|smaller|less|lower|higher|than|or|and|the|a|an|of|in|to|up|under|over'
            .'|within|only|each|every|allowed|allow|upload|uploaded|uploading|total|recommended|ensure|keep)\b/i', '', $rest);
        $rest = preg_replace('/[\s,.:;+\-\/()\[\]!?&*]+/u', ' ', $rest);

        return count(preg_split('/\s+/', trim($rest), -1, PREG_SPLIT_NO_EMPTY)) <= 1;
    }

    function isBareTechnicalToken(string $text): bool
    {

        if (preg_match('/^\d+(st|nd|rd|th)$/i', $text) || preg_match('/\b\d+[A-Z]{2,}\b/', $text)) {
            return true;
        }

        if (isPaymentOrSmsGateway($text)) {
            return true;
        }

        return (bool) preg_match('/^(smtp|imap|pop3|tls|ssl|https?|ftps?|sftp|ssh|tcp|udp|dns|cdn|cors|jwt|oauth|sdk|cli|json|xml|ya?ml|sql|api|url|uri|uuid|ltr|rtl|vin|cng|nid|qr|gps|iso|ui|ux|kb|mb|gb|px|b2b|b2c|cod|csv|ios|android|en|ar|bn'
            .'|apple|google|firebase|facebook|twitter|instagram|youtube|tiktok|linkedin|github|whatsapp|telegram|viber|imo|wechat|amazon'
            .'|stripe|paypal|paystack|razorpay|paytm|bkash|nagad|rocket|upay|flutterwave|mercadopago|paytabs|paymob|sslcommerz|senangpay|liqpay|xendit|midtrans|iyzico|hyperpay|foloosi|payfast|payumoney|instamojo)$/i', $text);
    }

    function isCredentialFieldLabel(string $text): bool
    {
        $text = trim(preg_replace('/\s*\([^)]*\)\s*$/u', '', $text));

        if (preg_match('/^sid$/i', $text)) {
            return true;
        }

        if (preg_match('/[.!?,;:]/', $text)) {
            return false;
        }

        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) < 2 || count($words) > 5) {
            return false;
        }

        $noun = '(id|ids|key|keys|secret|secrets|token|tokens|password|passwords|passphrase|sid|username|credential|credentials)';
        if (! preg_match('/^'.$noun.'$/i', end($words))) {
            return false;
        }

        $qualifier = '(api|app|application|access|account|auth|oauth|bearer|callback|client|consumer|developer'
            .'|encryption|gateway|integration|live|master|merchant|partner|primary|private|production|project'
            .'|public|publishable|refresh|sandbox|secondary|secret|server|session|shared|signing|staging|store'
            .'|subscription|template|tenant|test|webhook|workspace)';

        foreach (array_slice($words, 0, -1) as $word) {
            if (! preg_match('/^'.$qualifier.'$/i', $word) && ! preg_match('/^'.$noun.'$/i', $word)) {
                return false;
            }
        }

        return true;
    }

    function isRenderedValidationMessage(string $text): bool
    {

        if (preg_match('/^The .+ must be /i', $text) && preg_match('/:[a-zA-Z][a-zA-Z0-9]*/', $text)) {
            return true;
        }

        return (bool) preg_match('/^The .+ (field is required|field must be|has already been taken|format is invalid)\b/i', $text)
            || (bool) preg_match('/^The selected .+ is invalid\.?$/i', $text)
            || (bool) preg_match('/^(\w+(\s+\w+){0,3}\s+)?field\s+(is\s+(required|invalid)|must\s+.{1,60}|has\s+already\s+been\s+taken)[.!]?$/i', $text)
            || $text === 'This action is unauthorized.';
    }

    function isFileFormatOrSizeHint(string $text): bool
    {
        $formats = 'jpe?g|png|gif|webp|svg|bmp|ico|tiff?|pdf|docx?|xlsx?|pptx?|csv|txt|zip|rar|7z'
            .'|mp4|mp3|mov|avi|3gp|webm|mkv|flv|wmv|ogg|ogv|wav|m4a|aac';
        $qualifier = 'max|min|size|file|files|format|formats|image|images|video|videos|audio|supported|allowed|accepted|less|than|only|up|to|and|or';

        $tokenCount = preg_match_all('/\b('.$formats.')\b/i', $text);
        if ($tokenCount < 2 && ! ($tokenCount === 1 && preg_match('/\b('.$qualifier.')\b/i', $text))) {
            return false;
        }

        $rest = preg_replace('/\b('.$formats.')\b/i', '', $text);
        $rest = preg_replace('/\d+\s*(mb|kb|gb|px)?/i', '', $rest);
        $rest = preg_replace('/\b('.$qualifier.'|and|the|a|an)\b/i', '', $rest);
        $rest = preg_replace('/[\s,.:;\-\/()\[\]!?&]+/u', '', $rest);

        return $rest === '';
    }

    function isFigureWithOnlyFillerAround(string $text): bool
    {
        if (! preg_match('/\d/', $text) || mb_strlen($text) > 60) {
            return false;
        }

        if (preg_match('/\d+\s*[:x×]\s*\d+/iu', $text)) {
            $rest = preg_replace('/\d+\s*[:x×]\s*\d+/iu', '', $text);
            $rest = preg_replace('/\b(ratio|size|image|images|icon|logo|banner|thumbnail|photo|min|max|minimum|maximum|recommended|px|pixels?|mb|kb|and|or|the|a|an|is|are|be|must|should|of|in|to)\b/i', '', $rest);
            $rest = preg_replace('/\d+/', '', $rest);
            $rest = preg_replace('/[\s,.:;+\-\/()\[\]!?&*]+/u', '', $rest);
            if ($rest === '') {
                return true;
            }
        }

        $text = preg_replace('/\d+\s*(mb|kb|gb|tb|px)\b/i', '', $text);

        $technical = 'TLS|SSL|SMTP|IMAP|POP3?|HTTPS?|FTP|SFTP|TCP|UDP|SSH|DNS|IP'
            .'|seconds?|mins?|minutes?|hrs?|hours?|days?|weeks?|months?|years?'
            .'|MB|KB|GB|TB|PX|MS|characters?|chars?|digits?|bits?|bytes?|ports?'
            .'|pcs?|pieces?|units?|items?|qty|kgs?|gms?|grams?|mg|lbs?|oz|ml|ltrs?|lit(?:re|er)s?'
            .'|tons?|boxe?s?|packs?|sets?|pairs?|dozen|cm|mm|inch(?:es)?|ft';
        $rest = preg_replace('/\b('.$technical.')\b/i', '', $text);
        $rest = preg_replace('/\d+(\.\d+)?\+?/', '', $rest);
        $rest = preg_replace('/\b(for|or|and|the|a|an|to|in|on|of|is|are|use|uses|used|default|recommended|only|per|up'
            .'|at|least|more|than|less|minimum|maximum|min|max|must|be|should|required|long|length|allowed|between'
            .'|total|file|files|size|sizes|no|not|upload|each)\b/i', '', $rest);
        $rest = preg_replace('/[\s,.:;+\-\/()\[\]!?&*x×]+/u', '', $rest);

        return $rest === '';
    }
}

if (! function_exists('getDirectoriesByGivenPath')) {
    function getDirectoriesByGivenPath(string $path): array
    {
        $directories = [];
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item == '..' || $item == '.') {
                continue;
            }
            if (is_dir($path.'/'.$item)) {
                $directories[] = $item;
            }
        }

        return $directories;
    }
}

if (! function_exists('removeSpecialCharacters')) {
    function removeSpecialCharacters(?string $text): ?string
    {
        return str_ireplace(['\'', '"', ';', '<', '>', '?', '“', '”'], ' ', preg_replace('/\s\s+/', ' ', $text));
    }
}

if (! function_exists('isApiLocaleRequest')) {
    function isApiLocaleRequest(): bool
    {
        return (bool) strpos(url()->current(), '/api');
    }
}

if (! function_exists('getDefaultLanguage')) {
    function getDefaultLanguage(): string
    {
        if (isApiLocaleRequest()) {
            $lang = App::getLocale();
        } elseif (($stored = session('local')) !== null) {
            $lang = $stored;
        } else {
            $data = getWebConfig('language');
            $code = 'en';
            $direction = 'ltr';
            foreach ($data ?? [] as $ln) {
                if (array_key_exists('default', $ln) && $ln['default']) {
                    $code = $ln['code'];
                    if (array_key_exists('direction', $ln)) {
                        $direction = $ln['direction'];
                    }
                }
            }
            session()->put('local', $code);
            Session::put('direction', $direction);
            $lang = $code;
        }

        return $lang;
    }
}

if (! function_exists('getLanguageName')) {
    function getLanguageName(string $key): string
    {
        $values = getWebConfig('language');
        foreach ($values as $value) {
            if ($value['code'] == $key) {
                $key = $value['name'];
            }
        }

        return ucfirst($key);
    }
}

if (! function_exists('getLanguageDirection')) {
    function getLanguageDirection(string $code): string
    {
        foreach ((array) getWebConfig('language') as $language) {
            if (($language['code'] ?? null) === $code) {
                return $language['direction'] ?? 'ltr';
            }
        }

        return 'ltr';
    }
}

if (! function_exists('getLanguageCode')) {
    function getLanguageCodeList(): array
    {
        return [
            'af-ZA',
            'am-ET',
            'ar-AE',
            'ar-BH',
            'ar-DZ',
            'ar-EG',
            'ar-IQ',
            'ar-JO',
            'ar-KW',
            'ar-LB',
            'ar-LY',
            'ar-MA',
            'ar-OM',
            'ar-QA',
            'ar-SA',
            'ar-SY',
            'ar-TN',
            'ar-YE',
            'az-Cyrl-AZ',
            'az-Latn-AZ',
            'be-BY',
            'bg-BG',
            'bn-BD',
            'bs-Cyrl-BA',
            'bs-Latn-BA',
            'ca-AD',
            'cs-CZ',
            'da-DK',
            'de-AT',
            'de-CH',
            'de-DE',
            'de-LI',
            'de-LU',
            'dv-MV',
            'el-GR',
            'en-AU',
            'en-BZ',
            'en-CA',
            'en-GB',
            'en-IE',
            'en-JM',
            'en-MY',
            'en-NZ',
            'en-SG',
            'en-TT',
            'en-US',
            'en-ZA',
            'en-ZW',
            'es-AR',
            'es-BO',
            'es-CL',
            'es-CO',
            'es-CR',
            'es-DO',
            'es-EC',
            'es-ES',
            'es-GT',
            'es-HN',
            'es-MX',
            'es-NI',
            'es-PA',
            'es-PE',
            'es-PR',
            'es-PY',
            'es-SV',
            'es-US',
            'es-UY',
            'es-VE',
            'et-EE',
            'fa-IR',
            'fi-FI',
            'fil-PH',
            'fo-FO',
            'fr-BE',
            'fr-CA',
            'fr-CH',
            'fr-FR',
            'fr-LU',
            'fr-MC',
            'he-IL',
            'hi-IN',
            'hr-BA',
            'hr-HR',
            'hu-HU',
            'hy-AM',
            'id-ID',
            'ig-NG',
            'is-IS',
            'it-CH',
            'it-IT',
            'ja-JP',
            'ka-GE',
            'kk-KZ',
            'kl-GL',
            'km-KH',
            'ko-KR',
            'ky-KG',
            'lb-LU',
            'lo-LA',
            'lt-LT',
            'lv-LV',
            'mi-NZ',
            'mk-MK',
            'mn-MN',
            'ms-BN',
            'ms-MY',
            'mt-MT',
            'nb-NO',
            'ne-NP',
            'nl-BE',
            'nl-NL',
            'pl-PL',
            'prs-AF',
            'ps-AF',
            'pt-BR',
            'pt-PT',
            'ro-RO',
            'ru-RU',
            'rw-RW',
            'sv-SE',
            'si-LK',
            'sk-SK',
            'sl-SI',
            'sq-AL',
            'sr-Cyrl-BA',
            'sr-Cyrl-CS',
            'sr-Cyrl-ME',
            'sr-Cyrl-RS',
            'sr-Latn-BA',
            'sr-Latn-CS',
            'sr-Latn-ME',
            'sr-Latn-RS',
            'sw-KE',
            'tg-Cyrl-TJ',
            'th-TH',
            'tk-TM',
            'tr-TR',
            'uk-UA',
            'ur-PK',
            'uz-Cyrl-UZ',
            'uz-Latn-UZ',
            'vi-VN',
            'wo-SN',
            'yo-NG',
            'zh-CN',
            'zh-HK',
            'zh-MO',
            'zh-SG',
            'zh-TW',

            'en-AG',
            'en-AI',
            'pt-AO',
            'en-AS',
            'nl-AW',
            'sv-AX',
            'en-BB',
            'fr-BF',
            'rn-BI',
            'fr-BJ',
            'en-BM',
            'dz-BT',
            'en-BW',
            'en-CC',
            'fr-CD',
            'fr-CF',
            'fr-CG',
            'fr-CI',
            'en-CK',
            'fr-CM',
            'es-CU',
            'pt-CV',
            'en-CX',
            'el-CY',
            'fr-DJ',
            'en-DM',
            'ar-EH',
            'ti-ER',
            'en-FJ',
            'en-FK',
            'en-FM',
            'fr-GA',
            'en-GD',
            'fr-GF',
            'en-GH',
            'en-GI',
            'en-GM',
            'fr-GN',
            'fr-GP',
            'es-GQ',
            'en-GU',
            'pt-GW',
            'en-GY',
            'en-HM',
            'ht-HT',
            'en-IO',
            'en-KI',
            'ar-KM',
            'en-KN',
            'ko-KP',
            'en-KY',
            'en-LC',
            'en-LR',
            'en-LS',
            'ro-MD',
            'fr-MG',
            'en-MH',
            'bm-ML',
            'my-MM',
            'en-MP',
            'fr-MQ',
            'ar-MR',
            'en-MS',
            'en-MU',
            'en-MW',
            'pt-MZ',
            'en-NA',
            'fr-NC',
            'fr-NE',
            'en-NF',
            'en-NR',
            'en-NU',
            'fr-PF',
            'en-PG',
            'fr-PM',
            'en-PN',
            'ar-PS',
            'en-PW',
            'en-SB',
            'fr-SC',
            'ar-SD',
            'en-SH',
            'no-SJ',
            'it-SM',
            'fr-SN',
            'so-SO',
            'nl-SR',
            'pt-ST',
            'en-SZ',
            'en-TC',
            'fr-TD',
            'fr-TF',
            'fr-TG',
            'en-TK',
            'pt-TL',
            'en-TO',
            'en-TV',
            'sw-TZ',
            'en-UG',
            'en-UM',
            'la-VA',
            'en-VC',
            'en-VG',
            'en-VI',
            'bi-VU',
            'fr-WF',
            'sm-WS',
            'fr-YT',
            'en-ZM',
            'en-ZW',
        ];
    }
}

if (! function_exists('getLanguageCode')) {
    function getLanguageCode(string $country_code): string
    {
        $languageCodeOverrides = [
            'my' => 'ms',
        ];
        if (array_key_exists($country_code, $languageCodeOverrides)) {
            return $languageCodeOverrides[$country_code];
        }

        static $resolved = [];
        if (isset($resolved[$country_code])) {
            return $resolved[$country_code];
        }
        $originalCountryCode = $country_code;
        $scripts = [];
        if (Str::contains($country_code, '-')) {
            $countryCodeArr = explode('-', $country_code);
            $country_code = $countryCodeArr[0];
        }
        $locales = getLanguageCodeList();
        foreach ($locales as $locale) {
            $localeRegion = explode('-', $locale);
            if (strtoupper($country_code) === strtoupper(end($localeRegion))) {
                if (Str::contains($country_code, '-')) {
                    return $resolved[$originalCountryCode] = $localeRegion[0].'-'.end($scripts);
                }

                return $resolved[$originalCountryCode] = $localeRegion[0];
            }
        }

        return $resolved[$originalCountryCode] = 'en';
    }
}

if (! function_exists('translationProviderResponseBody')) {
    function translationProviderResponseBody(string $url): ?string
    {
        $timeoutSeconds = 20;

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => '',
                CURLOPT_USERAGENT => 'Mozilla/5.0',
            ]);

            $body = curl_exec($handle);
            $statusCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);

            return is_string($body) && $statusCode === 200 ? $body : null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => $timeoutSeconds,
                'header' => "User-Agent: Mozilla/5.0\r\nAccept: */*",
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return null;
        }

        $statusLine = $http_response_header[0] ?? '';

        return str_contains($statusLine, ' 200') ? $body : null;
    }
}

if (! function_exists('requestTranslationProvider')) {
    /** @return array<int, mixed>|null */
    function requestTranslationProvider(string $text, string $sourceLang, string $targetLang): ?array
    {
        $apiTargetLang = $targetLang === 'sr-Latn' ? 'sr' : $targetLang;

        $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&ie=UTF-8&oe=UTF-8&dt=t'
            ."&sl={$sourceLang}&tl={$apiTargetLang}&q=".urlencode($text);

        $response = translationProviderResponseBody($url);
        if ($response === null) {
            return null;
        }

        $data = json_decode($response, true);

        return isset($data[0]) && is_array($data[0]) ? $data : null;
    }
}

if (! function_exists('autoTranslatorBatchedMode')) {
    function autoTranslatorBatchedMode(string $text, string $sourceLang, string $targetLang): ?string
    {
        $data = requestTranslationProvider($text, $sourceLang, $targetLang);
        if ($data === null) {
            return null;
        }

        $translated = '';
        foreach ($data[0] as $item) {
            $translated .= $item[0];
        }

        $translated = str_replace('\n', "\n", $translated);
        if (needsTransliteration($targetLang)) {
            $translated = transliterateCyrillicToLatin($translated, $targetLang);
        }

        return trim($translated);
    }
}

if (! function_exists('autoTranslator')) {
    function autoTranslator(string $text, string $sourceLang, string $targetLang): ?string
    {
        $data = requestTranslationProvider(str_replace('_', ' ', $text), $sourceLang, $targetLang);
        if ($data === null) {
            return null;
        }

        $translated = $data[0][0][0] ?? '';

        if (needsTransliteration($targetLang)) {
            $translated = transliterateCyrillicToLatin($translated, $targetLang);
        }

        return str_replace('_', ' ', $translated);
    }

    function needsTransliteration(string $targetLang): bool
    {
        $languagesRequiringTransliteration = [
            'rs-Latn',
            'ba-Latn',
            'az-Latn',
            'uz-Latn',
        ];

        return in_array($targetLang, $languagesRequiringTransliteration);
    }

    function transliterateCyrillicToLatin(string $text, string $langCode): string
    {
        $map = [];

        if (in_array($langCode, ['sr-Latn', 'ba-Latn'])) {

            $map = [
                'Љ' => 'Lj', 'Њ' => 'Nj', 'Џ' => 'Dž', 'љ' => 'lj', 'њ' => 'nj', 'џ' => 'dž',
                'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Ђ' => 'Đ', 'Е' => 'E', 'Ж' => 'Ž', 'З' => 'Z',
                'И' => 'I', 'Ј' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P',
                'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'Ћ' => 'Ć', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Č', 'Ш' => 'Š',
                'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'ђ' => 'đ', 'е' => 'e', 'ж' => 'ž', 'з' => 'z',
                'и' => 'i', 'ј' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p',
                'р' => 'r', 'с' => 's', 'т' => 't', 'ћ' => 'ć', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'č', 'ш' => 'š',
            ];
        } elseif ($langCode === 'uz-Latn') {

            $map = [
                'А' => 'A', 'Б' => 'B', 'Д' => 'D', 'Э' => 'E', 'Ф' => 'F', 'Г' => 'G', 'Ҳ' => 'H', 'И' => 'I', 'Ж' => 'J',
                'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Қ' => 'Q', 'Р' => 'R', 'С' => 'S',
                'Ш' => 'Sh', 'Т' => 'T', 'У' => 'U', 'В' => 'V', 'Х' => 'X', 'Й' => 'Y', 'З' => 'Z', 'Ч' => 'Ch', 'Ў' => 'O‘',
                'Ю' => 'Yu', 'Я' => 'Ya', 'Ё' => 'Yo', 'Ц' => 'Ts', 'Ъ' => '', 'Ь' => '', 'Е' => 'Ye', 'НГ' => 'Ng',
                'а' => 'a', 'б' => 'b', 'д' => 'd', 'э' => 'e', 'ф' => 'f', 'г' => 'g', 'ҳ' => 'h', 'и' => 'i', 'ж' => 'j',
                'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'қ' => 'q', 'р' => 'r', 'с' => 's',
                'ш' => 'sh', 'т' => 't', 'у' => 'u', 'в' => 'v', 'х' => 'x', 'й' => 'y', 'з' => 'z', 'ч' => 'ch', 'ў' => 'o‘',
                'ю' => 'yu', 'я' => 'ya', 'ё' => 'yo', 'ц' => 'ts', 'ъ' => '', 'ь' => '', 'е' => 'ye', 'нг' => 'ng',
            ];
        } elseif ($langCode === 'az-Latn') {

            $map = [
                'А' => 'A', 'Б' => 'B', 'С' => 'S', 'Ч' => 'Ç', 'Д' => 'D', 'Ә' => 'Ə', 'Е' => 'E', 'Ф' => 'F',
                'Г' => 'Q', 'Һ' => 'H', 'Ы' => 'I', 'Ж' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
                'О' => 'O', 'Ө' => 'Ö', 'П' => 'P', 'Р' => 'R', 'Ш' => 'Ş', 'Т' => 'T', 'У' => 'U', 'Ү' => 'Ü',
                'В' => 'V', 'Й' => 'Y', 'З' => 'Z',
                'а' => 'a', 'б' => 'b', 'с' => 's', 'ч' => 'ç', 'д' => 'd', 'ә' => 'ə', 'е' => 'e', 'ф' => 'f',
                'г' => 'q', 'һ' => 'h', 'ы' => 'ı', 'ж' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
                'о' => 'o', 'ө' => 'ö', 'п' => 'p', 'р' => 'r', 'ш' => 'ş', 'т' => 't', 'у' => 'u', 'ү' => 'ü',
                'в' => 'v', 'й' => 'y', 'з' => 'z',
            ];
        }

        return strtr($text, $map);
    }
}
