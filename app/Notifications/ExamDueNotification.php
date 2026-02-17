<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExamDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Colección de intentos de examen próximos a vencer.
     *
     * @var Collection
     */
    protected Collection $attempts;

    /**
     * Lista de correos en copia oculta (BCC).
     *
     * @var array<string>
     */
    protected array $bcc;

    /**
     * Nombre de la DNC.
     *
     * @var string
     */
    protected string $dncName;

    /**
     * Crear una nueva instancia de la notificación.
     *
     * @param  Collection     $attempts  Colección de ExamAttempt o simulados
     * @param  array<string>  $bcc       Lista de correos en BCC
     * @param  string         $dncName   Nombre de la DNC
     */
    public function __construct(Collection $attempts, array $bcc, string $dncName)
    {
        $this->attempts = $attempts;
        $this->bcc      = $bcc;
        $this->dncName  = $dncName;
    }

    /**
     * Canales de entrega.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Representación por correo usando tu plantilla x-mail.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Construir detalles: [ 'Examen A (DNC X)' => 'vence el dd/mm/YYYY', … ]
        $details = $this->attempts->mapWithKeys(function ($attempt) {
            $examName = $attempt->exam->name ?? 'Examen sin nombre';
            $endAt    = Carbon::parse($attempt->exam->end_at)->format('d/m/Y');
            return ["{$examName}" => "vence el {$endAt}"];
        })->toArray();

        $title   = "Notificación de exámenes — {$this->dncName}";
        $message = 'Tienes los siguientes exámenes próximos a vencer:';
        $footer  = 'Por favor, complétalos antes de la fecha de vencimiento.';

        $mail = (new MailMessage)
            ->subject($title)
            ->markdown('emails.info_notification', [
                'title'   => $title,
                'message' => $message,
                'details' => $details,
                'footer'  => $footer,
            ]);

        if (! empty($this->bcc)) {
            $mail->bcc($this->bcc);
        }

        return $mail;
    }

    /**
     * Representación en array (si usas storage notifications).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'dnc'      => $this->dncName,
            'attempts' => $this->attempts->map(fn($a) => [
                'exam_id'   => $a->exam_id,
                'exam_name' => $a->exam->name ?? null,
                'end_at'    => $a->exam->end_at,
            ])->toArray(),
        ];
    }
}
