<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpClient;

// RF02 - integracao com a API REST oficial do CREA-AM, o "motor de confiabilidade" do sistema.
class CreaApiService
{
    private readonly HttpClient $http;

    // Monta o cliente HTTP com a URL base e o Token de Acesso Individual (Anexo I, item 8.4).
    public function __construct()
    {
        $this->http = new HttpClient(
            baseUrl: (string) config('crea_api.base_url'),
            defaultHeaders: ['Authorization: Bearer ' . config('crea_api.token')],
        );
    }

    // Consulta os dados cadastrais do profissional pelo CPF.
    public function validarProfissional(string $cpf): array
    {
        // RF02 - consulta dados cadastrais do profissional no CREA-AM
        return $this->http->get("/profissionais/{$cpf}");
    }

    // Consulta os dados cadastrais da empresa pelo CNPJ.
    public function validarEmpresa(string $cnpj): array
    {
        // RF02 - consulta dados cadastrais da empresa (CNPJ)
        return $this->http->get("/empresas/{$cnpj}");
    }

    // Consulta as Anotacoes de Responsabilidade Tecnica vinculadas ao RNP do profissional.
    public function consultarArts(string $rnp): array
    {
        // RF02/RF03 - Anotacoes de Responsabilidade Tecnica vinculadas ao RNP
        return $this->http->get("/rnp/{$rnp}/arts");
    }

    // Consulta as Certidoes de Acervo Tecnico vinculadas ao RNP do profissional.
    public function consultarCats(string $rnp): array
    {
        // RF02/RF03 - Certidoes de Acervo Tecnico vinculadas ao RNP
        return $this->http->get("/rnp/{$rnp}/cats");
    }
}
