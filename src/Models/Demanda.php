<?php

declare(strict_types=1);

namespace App\Models;

// RF04 - projeto/oportunidade cadastrado por uma empresa ou terceiro.
class Demanda
{
    public const TIPO_ESTAGIO = 'ESTAGIO';
    public const TIPO_PROJETO = 'PROJETO';
    public const TIPO_CONSULTORIA = 'CONSULTORIA';
    public const TIPO_ART = 'ART';
    public const TIPO_PERICIA = 'PERICIA';
    public const TIPO_MENTORIA = 'MENTORIA';
    public const TIPO_PESQUISA = 'PESQUISA';
    public const TIPO_VOLUNTARIADO = 'VOLUNTARIADO';

    public const MODALIDADE_HIBRIDO = 'HIBRIDO';
    public const MODALIDADE_PRESENCIAL = 'PRESENCIAL';
    public const MODALIDADE_REMOTO = 'REMOTO';

    public const STATUS_ABERTA = 'ABERTA';
    public const STATUS_FECHADA = 'FECHADA';
    public const STATUS_CANCELADA = 'CANCELADA';
    public const STATUS_SUSPENSA = 'SUSPENSA_PELO_CREA';

    public function __construct(
        public ?int $id = null,
        public ?int $empresaId = null,
        public string $titulo = '',
        public string $descricao = '',
        public string $area = '',
        public string $tipo = self::TIPO_PROJETO,
        public string $cidade = '',
        public string $uf = '',
        public string $modalidade = self::MODALIDADE_PRESENCIAL,
        public string $status = self::STATUS_ABERTA,
        public ?string $dataPublicacao = null,
        public ?string $dataFechamento = null,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
        // Areas de atuacao sugeridas pelo Agente de Recomendacao (NLP), sujeitas a aprovacao humana.
        public array $areasSugeridas = [],
        // Campos calculados por DemandaRepository::buscarComFiltros() para exibicao no
        // card/detalhe da busca (nao existem como coluna propria).
        public ?string $company = null,
        public int $interessados = 0,
    ) {
    }
}
