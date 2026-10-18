<?php

namespace App\Http\Requests;

use App\Models\Atividade;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login pelo nome de usuário. A mensagem de erro é sempre a mesma — não
 * diz se o usuário existe, se a senha está errada ou se a conta foi
 * desativada — e depois de 5 tentativas erradas o mesmo usuário (no mesmo
 * IP) fica bloqueado por um minuto.
 */
class LoginRequest extends FormRequest
{
    public const MAX_TENTATIVAS = 5;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['usuario' => Str::lower(trim((string) $this->input('usuario')))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'usuario' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'usuario.required' => 'Informe o seu usuário.',
            'password.required' => 'Informe a sua senha.',
        ];
    }

    public function autenticar(): void
    {
        $this->garantirQueNaoEstaBloqueado();

        $credenciais = [
            'usuario' => $this->input('usuario'),
            'password' => $this->input('password'),
            'ativo' => true,
        ];

        if (! Auth::attempt($credenciais, $this->boolean('lembrar'))) {
            RateLimiter::hit($this->chaveDoLimite());
            Atividade::registrar('acesso', "Tentativa de entrar com o usuário \"{$this->input('usuario')}\" falhou");

            throw ValidationException::withMessages([
                'usuario' => 'Usuário ou senha incorretos.',
            ]);
        }

        RateLimiter::clear($this->chaveDoLimite());
    }

    private function garantirQueNaoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->chaveDoLimite(), self::MAX_TENTATIVAS)) {
            return;
        }

        event(new Lockout($this));

        $segundos = RateLimiter::availableIn($this->chaveDoLimite());

        throw ValidationException::withMessages([
            'usuario' => "Muitas tentativas. Tente de novo em {$segundos} segundos.",
        ]);
    }

    private function chaveDoLimite(): string
    {
        return 'login|'.$this->input('usuario').'|'.$this->ip();
    }
}
