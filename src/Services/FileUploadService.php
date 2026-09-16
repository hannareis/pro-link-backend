<?php

declare(strict_types=1);

namespace App\Services;

// Upload seguro de arquivos enviados via multipart/form-data ($_FILES), reutilizado
// por qualquer entidade que precise anexar um arquivo externo (comprovantes, cartas
// virtuais, etc). Valida tamanho e o MIME real do conteudo (via fileinfo, nao a
// extensao/Content-Type enviados pelo cliente) e gera um nome de arquivo aleatorio
// ao mover para PATH_UPLOADS, evitando path traversal e upload de executaveis.
class FileUploadService
{
    public function __construct(
        // MIME real -> extensao aceita, ex: ['application/pdf' => 'pdf'].
        private readonly array $mimesPermitidos,
        private readonly int $tamanhoMaximoBytes,
    ) {
    }

    // Retorna null se nenhum arquivo foi enviado (campo opcional). Lanca
    // InvalidArgumentException se o arquivo enviado for invalido (tipo/tamanho/erro
    // de upload) e RuntimeException em falha de I/O ao salvar.
    public function store(?array $file, string $subdiretorio): ?array
    {
        if ($file === null) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Falha no envio do arquivo.');
        }

        if ($file['size'] > $this->tamanhoMaximoBytes) {
            throw new \InvalidArgumentException('Arquivo excede o tamanho máximo permitido.');
        }

        $mime = mime_content_type($file['tmp_name']);
        $extensao = $this->mimesPermitidos[$mime] ?? null;

        if ($extensao === null) {
            throw new \InvalidArgumentException('Tipo de arquivo não permitido.');
        }

        $diretorio = PATH_UPLOADS . '/' . $subdiretorio;
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
            throw new \RuntimeException('Não foi possível salvar o arquivo.');
        }

        $nomeArmazenado = bin2hex(random_bytes(16)) . '.' . $extensao;

        if (!move_uploaded_file($file['tmp_name'], $diretorio . '/' . $nomeArmazenado)) {
            throw new \RuntimeException('Não foi possível salvar o arquivo.');
        }

        return [
            'nome_arquivo' => (string) $file['name'],
            'nome_armazenado' => $nomeArmazenado,
            'tipo_mime' => $mime,
            'tamanho' => (int) $file['size'],
            'caminho_armazenamento' => 'uploads/' . $subdiretorio . '/' . $nomeArmazenado,
        ];
    }
}
