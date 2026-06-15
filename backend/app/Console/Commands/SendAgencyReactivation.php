<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SendAgencyReactivation extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mail:agency-reactivation
        {--recipients= : Ścieżka do pliku z adresami (jeden na linię, opcjonalnie "email,Nazwa Agencji"). Linie puste i zaczynające się od # są pomijane.}
        {--template= : Ścieżka do pliku HTML z treścią maila.}
        {--subject=Wasze nośniki na ReklaMap — wrzucamy za Was, wystarczy cokolwiek macie pod ręką : Temat wiadomości.}
        {--throttle=8 : Pauza w sekundach między kolejnymi mailami (ochrona przed limitem serwera).}
        {--limit=0 : Maksymalna liczba maili do wysłania (0 = bez limitu). Przydatne do testu na małej próbce.}
        {--dry-run : Nic nie wysyła — tylko pokazuje, do kogo poszłoby i z jakim tematem.}';

    /**
     * @var string
     */
    protected $description = 'Wysyła mail reaktywacyjny do agencji OOH z listy adresów (szablon HTML, pojedynczo, z throttlingiem).';

    public function handle(): int
    {
        $recipientsPath = $this->option('recipients')
            ?: base_path('../reklamap-os/status/agencje-maile.txt');
        $templatePath = $this->option('template')
            ?: base_path('../reklamap-os/templates/email-reaktywacja-agencje.html');
        $subject = (string) $this->option('subject');
        $throttle = max(0, (int) $this->option('throttle'));
        $limit = max(0, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($recipientsPath)) {
            $this->error("Nie znaleziono pliku z adresami: {$recipientsPath}");
            $this->line('Podaj go przez --recipients=/ścieżka/do/pliku.txt');

            return self::FAILURE;
        }

        if (! is_file($templatePath)) {
            $this->error("Nie znaleziono szablonu: {$templatePath}");

            return self::FAILURE;
        }

        $html = (string) file_get_contents($templatePath);
        $recipients = $this->parseRecipients($recipientsPath);

        if ($recipients === []) {
            $this->error('Plik z adresami nie zawiera żadnego poprawnego adresu e-mail.');

            return self::FAILURE;
        }

        if ($limit > 0) {
            $recipients = array_slice($recipients, 0, $limit);
        }

        $from = config('mail.from.address');
        $this->info(sprintf(
            '%s %d adres(y/ów) | temat: "%s" | from: %s | pauza: %ds',
            $dryRun ? '[PRÓBA] Wysłałbym do' : 'Wysyłam do',
            count($recipients),
            $subject,
            $from,
            $throttle
        ));
        $this->newLine();

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $i => $r) {
            $position = $i + 1;
            $body = $this->personalize($html, $r['name']);

            if ($dryRun) {
                $this->line(sprintf('  [%d] %s%s', $position, $r['email'], $r['name'] ? " ({$r['name']})" : ''));

                continue;
            }

            try {
                Mail::html($body, function ($message) use ($r, $subject): void {
                    $message->to($r['email'])->subject($subject);
                });
                $sent++;
                $this->line(sprintf('  <fg=green>✓</> [%d] %s', $position, $r['email']));
                Log::info('Mail reaktywacyjny wysłany', ['email' => $r['email']]);
            } catch (\Throwable $e) {
                $failed++;
                $this->line(sprintf('  <fg=red>✗</> [%d] %s — %s', $position, $r['email'], $e->getMessage()));
                Log::error('Błąd wysyłki maila reaktywacyjnego', ['email' => $r['email'], 'error' => $e->getMessage()]);
            }

            if ($throttle > 0 && $position < count($recipients)) {
                sleep($throttle);
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info('Próba zakończona — nic nie wysłano. Usuń --dry-run, żeby wysłać naprawdę.');
        } else {
            $this->info("Gotowe. Wysłano: {$sent} | Błędy: {$failed}");
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{email: string, name: string|null}>
     */
    private function parseRecipients(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $out = [];
        $seen = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$email, $name] = array_pad(array_map('trim', explode(',', $line, 2)), 2, null);
            $email = strtolower((string) $email);

            if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
                $this->warn("Pomijam niepoprawny adres: {$line}");

                continue;
            }

            if (isset($seen[$email])) {
                continue;
            }

            $seen[$email] = true;
            $out[] = ['email' => $email, 'name' => $name ?: null];
        }

        return $out;
    }

    private function personalize(string $html, ?string $name): string
    {
        return str_replace('{{nazwa_agencji}}', $name ?? 'Państwa', $html);
    }
}
