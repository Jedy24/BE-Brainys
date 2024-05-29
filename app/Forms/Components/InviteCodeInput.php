<?php

namespace App\Forms\Components;

use Filament\Forms\Components\TextInput;

class InviteCodeInput extends TextInput
{
    protected string $view = 'forms.components.invite-code-input';

    public function generateInviteCode()
    {
        $this->state($this->generateRandomCode(8));
    }

    protected function generateRandomCode($length)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
