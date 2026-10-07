<?php

namespace App\Models;

use App\Services\Configuracoes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Cliente extends Model
{
    use HasFactory;

    /**
     * Paleta usada para gerar a cor de fundo do avatar padrão (iniciais).
     * Mesmos valores dos tokens --blue-400/--pink-400/--purple-500/
     * --purple-light-800/--navy-900 em resources/css/app.css (portados de
     * mobilenav_atualizado) — se a paleta mudar lá, atualizar aqui e em
     * resources/js/clientes.js (AVATAR_CORES) também, pois CSS não é
     * acessível a partir do PHP/JS.
     */
    private const CORES_AVATAR = [
        '#8bbaed', // --blue-400
        '#b47194', // --pink-400
        '#53577d', // --purple-500
        '#6c6588', // --purple-light-800
        '#2e3045', // --navy-900
    ];

    protected $fillable = [
        'nome',
        'data_nascimento',
        'whatsapp',
        'status',
        'foto',
        'observacoes',
        'creditos',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'creditos' => 'decimal:2',
    ];

    /**
     * Expõe a paleta de avatares para quem precisa gerar a mesma cor fora do
     * PHP (ver resources/views/layouts/app.blade.php, que injeta este valor
     * num <script type="application/json"> lido por resources/js/clientes.js)
     * — evita manter a lista duplicada "de cabeça" em dois arquivos.
     *
     * @return array<int, string>
     */
    public static function coresAvatar(): array
    {
        return self::CORES_AVATAR;
    }

    public function historicoCreditos()
    {
        return $this->hasMany(ClienteCreditoHistorico::class)->latest('id');
    }

    public function vendas()
    {
        return $this->hasMany(Venda::class);
    }

    /**
     * Dados curtos do cliente (avatar, nome e saldo) usados pela tela de
     * Vendas — busca de cliente, detalhes da venda e do aluguel.
     *
     * @return array<string, mixed>
     */
    public function dadosResumidos(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'iniciais' => $this->iniciais,
            'cor_avatar' => $this->cor_avatar,
            'foto_url' => $this->foto_url,
            'creditos' => (float) $this->creditos,
            'inativo' => $this->status === 'inativo',
        ];
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', 'ativo');
    }

    public function scopeInativos(Builder $query): Builder
    {
        return $query->where('status', 'inativo');
    }

    public function scopeComSaldo(Builder $query): Builder
    {
        return $query->where('creditos', '>', 0);
    }

    public function scopeSemSaldo(Builder $query): Builder
    {
        return $query->where('creditos', '<=', 0);
    }

    /**
     * Clientes cujo dia de nascimento cai no mês informado (mês corrente por
     * padrão) — usado no card de resumo "Aniversariantes do mês" e no filtro
     * de mesmo nome na listagem. Compara só o mês (whereMonth), não o dia,
     * pois o objetivo é planejar contato/campanha ao longo do mês inteiro.
     */
    public function scopeAniversariantesDoMes(Builder $query, ?int $mes = null): Builder
    {
        return $query->whereMonth('data_nascimento', $mes ?? now()->month);
    }

    /**
     * Idade atual do cliente, em anos completos.
     */
    public function getIdadeAttribute(): int
    {
        return $this->data_nascimento->age;
    }

    /**
     * URL pública da foto do cliente, ou null quando ele ainda não tem
     * foto cadastrada (nesse caso a tela usa o avatar de iniciais).
     *
     * Usa asset() em vez de Storage::disk('public')->url() (que só
     * concatena env('APP_URL'), sem saber da subpasta real da requisição —
     * ver config/filesystems.php) porque este atributo só é acessado durante
     * uma requisição HTTP de verdade (views, respostas JSON do
     * ClienteController), então asset() sempre tem uma requisição para
     * calcular a URL certa, mesmo servindo o app numa subpasta.
     */
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? asset('storage/'.$this->foto) : null;
    }

    /**
     * Duas primeiras letras do nome, usadas no avatar padrão quando o
     * cliente não possui foto cadastrada.
     */
    public function getIniciaisAttribute(): string
    {
        return mb_strtoupper(mb_substr(trim($this->nome), 0, 2));
    }

    /**
     * Cor de fundo determinística do avatar de iniciais, com base no id
     * do cliente, para que cada cliente sempre tenha a mesma cor.
     */
    public function getCorAvatarAttribute(): string
    {
        return self::CORES_AVATAR[$this->id % count(self::CORES_AVATAR)];
    }

    /**
     * Link "wa.me" pronto para abrir uma conversa com o cliente, ou null
     * quando ele não tem WhatsApp cadastrado. `whatsapp` é armazenado só com
     * DDD + número (ver migration); o DDI vem de Configurações > Clientes.
     */
    public function getWhatsappUrlAttribute(): ?string
    {
        if (! $this->whatsapp) {
            return null;
        }

        $ddi = preg_replace('/\D/', '', (string) Configuracoes::valor('clientes.whatsapp_ddi'));

        return 'https://wa.me/'.$ddi.preg_replace('/\D/', '', $this->whatsapp);
    }
}
