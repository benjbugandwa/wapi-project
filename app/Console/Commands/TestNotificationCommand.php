<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NotificationChannelEnum;
use App\Mail\TestEmailMailable;
use App\Services\TwilioNotificationGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wapi:test-notification
                            {channel : Le canal à tester (email, sms, ou whatsapp)}
                            {recipient : L\'adresse email ou le numéro de téléphone (+243XXXXXXXXX)}
                            {--message= : Message texte personnalisé optionnel}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste l\'envoi de notifications via Resend (Email) ou Twilio (SMS / WhatsApp)';

    /**
     * Execute the console command.
     */
    public function handle(TwilioNotificationGateway $twilioGateway): int
    {
        $channel = strtolower((string) $this->argument('channel'));
        $recipient = (string) $this->argument('recipient');
        $customMessage = (string) ($this->option('message') ?: 'Ceci est un message de test envoyé depuis Wapi.');

        return match ($channel) {
            'email' => $this->testEmail($recipient, $customMessage),
            'sms' => $this->testTwilio($twilioGateway, NotificationChannelEnum::Sms, $recipient, $customMessage),
            'whatsapp' => $this->testTwilio($twilioGateway, NotificationChannelEnum::WhatsApp, $recipient, $customMessage),
            default => $this->invalidChannel($channel),
        };
    }

    private function testEmail(string $recipient, string $message): int
    {
        $resendKey = config('services.resend.key');

        $this->info("--> Test d'envoi d'un email via Resend à : {$recipient}");

        if (empty($resendKey)) {
            $this->warn('[ATTENTION] La clé API Resend (RESEND_KEY ou RESEND_API_KEY) n\'est pas encore configurée dans le fichier .env.');
        }

        try {
            Mail::to($recipient)->send(new TestEmailMailable($message));

            $this->info('✓ Email envoyé avec succès vers '.$recipient);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ Erreur lors de l\'envoi de l\'email : '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function testTwilio(TwilioNotificationGateway $twilioGateway, NotificationChannelEnum $channel, string $recipient, string $message): int
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $from = $channel === NotificationChannelEnum::WhatsApp
            ? config('services.twilio.whatsapp_from')
            : config('services.twilio.sms_from');

        $channelName = $channel === NotificationChannelEnum::WhatsApp ? 'WhatsApp' : 'SMS';

        $this->info("--> Test d'envoi d'une notification {$channelName} via Twilio à : {$recipient}");

        $missing = [];
        if (empty($accountSid)) {
            $missing[] = 'TWILIO_ACCOUNT_SID';
        }
        if (empty($authToken)) {
            $missing[] = 'TWILIO_AUTH_TOKEN';
        }
        if (empty($from)) {
            $missing[] = $channel === NotificationChannelEnum::WhatsApp ? 'TWILIO_WHATSAPP_FROM' : 'TWILIO_SMS_FROM';
        }

        if (! empty($missing)) {
            $this->error('[ERREUR] Configurations Twilio manquantes dans .env : '.implode(', ', $missing));

            return Command::FAILURE;
        }

        $success = $twilioGateway->send($channel, $recipient, $message);

        if ($success) {
            $this->info("✓ Notification {$channelName} transmise à Twilio avec succès pour : {$recipient}");

            return Command::SUCCESS;
        }

        $this->error("✗ Échec de l'envoi {$channelName} via Twilio. Consultez storage/logs/laravel.log pour plus de détails.");

        return Command::FAILURE;
    }

    private function invalidChannel(string $channel): int
    {
        $this->error("Canal invalide '{$channel}'. Les canaux autorisés sont : email, sms, whatsapp.");

        return Command::INVALID;
    }
}
