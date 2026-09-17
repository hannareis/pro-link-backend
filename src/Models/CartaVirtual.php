<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `cartas_virtuais`: carta/certificado enviado pelo usuario autor (id_usuario)
// a um destinatario externo por e-mail, opcionalmente vinculada a uma demanda e com
// um arquivo anexo (documento/imagem) opcional.
class CartaVirtual
{
    public function __construct(
        public ?int $id = null,
        public int $idUsuario = 0,
        public ?int $idDemanda = null,
        public string $titulo = '',
        public ?string $legenda = null,
        public string $remetenteEmail = '',
        public string $destinatarioEmail = '',
        public ?string $nomeArquivo = null,
        public ?string $nomeArmazenado = null,
        public ?string $tipoMime = null,
        public ?int $tamanhoArquivo = null,
        public ?string $caminhoArmazenamento = null,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
