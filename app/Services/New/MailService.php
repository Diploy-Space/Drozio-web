<?php

namespace App\Services\New;

use App\Mail\TestEmailSender;
use Exception;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailService
{
    /** @return array{sent: bool, error: ?string} */
    public function sendTestMail(string $email): array
    {
        try {
            Mail::to($email)->send(new TestEmailSender);

            return ['sent' => true, 'error' => null];
        } catch (Exception $exception) {
            return ['sent' => false, 'error' => $exception->getMessage()];
        }
    }

    public function sendContactReply(string $email, string $companyName, string $subject, string $body): bool
    {
        try {
            Mail::send('email-templates.customer-message', ['body' => $body], function ($message) use ($email, $companyName, $subject): void {
                $message->to($email, $companyName)->subject($subject);
            });

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
