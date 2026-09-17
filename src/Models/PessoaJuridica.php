<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `pessoa_juridica`: dados exclusivos de um usuario tipo_pessoa = JURIDICA
// (empresas). Relacao 1:1 com `usuarios`.
class PessoaJuridica
{
    // Valores possiveis para $statusVerificacao (selo de verificacao da empresa).
    public const STATUS_NAO_SOLICITADA = 'NAO_SOLICITADA';
    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_APROVADA = 'APROVADA';
    public const STATUS_REJEITADA = 'REJEITADA';

    public function __construct(
        public int $idUsuario = 0,
        public string $cnpj = '',
        public string $razaoSocial = '',
        public ?string $nomeFantasia = null,
        public string $statusVerificacao = self::STATUS_NAO_SOLICITADA,
        public ?string $dataSolicitacaoVerificacao = null,
        public ?string $docContratoSocial = null,
        public ?string $docComprovanteCadastral = null,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
