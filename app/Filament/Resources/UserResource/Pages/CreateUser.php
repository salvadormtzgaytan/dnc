<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\UserCreatedMail;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected string $plainPassword = '';

    protected function beforeCreate(): void
    {
        // Guardamos la contraseña sin hashear para el correo
        $this->plainPassword = $this->data['password'] ?? '';
        // Hash de la contraseña para almacenar
        $this->data['password'] = Hash::make($this->plainPassword);
    }

    protected function afterCreate(): void
    {
        // Enviar correo si corresponde
        if (!empty($this->data['send_email']) && filled($this->plainPassword)) {
            $mail = Mail::to($this->record->email);
            $mail->bcc('master@e-360.com.mx');
            $mail->send(new UserCreatedMail($this->record, $this->plainPassword));
        }
    }
}