<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpClient;

// RF02 - integracao com a API REST oficial do Desafio CREA Pro-Link, o "motor de
// confiabilidade" do sistema. A API roteia tudo por um unico endpoint (index.php),
// selecionando o recurso pelo parametro de query `p` (ex: ?p=profissionais&cpf=...
// ou ?p=profissionais/{pro_rnp}/arts&page=1&limit=20), autenticado via Bearer Token.
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

    // ----- Fluxo de Profissionais -------------------------------------------

    // 1. Pesquisa um profissional pelo CPF; a resposta traz o pro_rnp e as
    // modalidades, usados para consultar ARTs/CATs abaixo.
    public function buscarProfissionalPorCpf(string $cpf): array
    {
        return $this->buscar(['p' => 'profissionais', 'cpf' => $cpf]);
    }

    // Busca o profissional pelo CPF e confirma se ele existe no cadastro oficial
    // do CREA-AM (sem depender do formato exato do registro retornado).
    public function profissionalExisteNoCrea(string $cpf): bool
    {
        $resposta = $this->buscarProfissionalPorCpf($cpf);

        return $resposta['status'] === 200 && !empty($resposta['body']);
    }

    // Extrai o pro_rnp da resposta de busca por CPF, usado para consultar
    // ARTs/CATs. Tenta os formatos mais prováveis (objeto direto, primeiro item
    // de uma lista, ou primeiro item de uma lista paginada em `data`) - ainda NAO
    // confirmado com um caso real (nenhum CPF de teste disponivel retornou match
    // ate a criacao deste metodo). Valide o formato assim que houver um hit real
    // e ajuste aqui se necessario.
    public function obterProRnpPorCpf(string $cpf): ?string
    {
        $resposta = $this->buscarProfissionalPorCpf($cpf);

        if ($resposta['status'] !== 200) {
            return null;
        }

        $body = $resposta['body'];
        $registro = $body[0] ?? ($body['data'][0] ?? $body);

        return isset($registro['pro_rnp']) ? (string) $registro['pro_rnp'] : null;
    }

    // 2. Lista as ARTs (Anotacoes de Responsabilidade Tecnica) do profissional.
    public function listarArtsDoProfissional(string $proRnp, int $page = 1, int $limit = 20): array
    {
        return $this->buscar([
            'p' => "profissionais/{$proRnp}/arts",
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    // 3. Lista as CATs (Certidoes de Acervo Tecnico) emitidas para o profissional.
    public function listarCatsDoProfissional(string $proRnp, int $page = 1, int $limit = 20): array
    {
        return $this->buscar([
            'p' => "profissionais/{$proRnp}/cats",
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    // ----- Fluxo de Empresas & CAO ------------------------------------------

    // 1. Pesquisa uma empresa pelo CNPJ; a resposta traz o emp_registro_crea.
    public function buscarEmpresaPorCnpj(string $cnpj): array
    {
        return $this->buscar(['p' => 'empresas', 'cnpj' => $cnpj]);
    }

    // Busca a empresa pelo CNPJ e confirma se ela existe no cadastro oficial do CREA-AM.
    public function empresaExisteNoCrea(string $cnpj): bool
    {
        $resposta = $this->buscarEmpresaPorCnpj($cnpj);

        return $resposta['status'] === 200 && !empty($resposta['body']);
    }

    // Extrai o emp_registro_crea da resposta de busca por CNPJ, usado para
    // consultar quadro tecnico/CAO. Mesma ressalva de `obterProRnpPorCpf`: ainda
    // NAO confirmado com um caso real (nenhum CNPJ de teste disponivel retornou
    // match ate a criacao deste metodo).
    public function obterRegistroCreaPorCnpj(string $cnpj): ?string
    {
        $resposta = $this->buscarEmpresaPorCnpj($cnpj);

        if ($resposta['status'] !== 200) {
            return null;
        }

        $body = $resposta['body'];
        $registro = $body[0] ?? ($body['data'][0] ?? $body);

        return isset($registro['emp_registro_crea']) ? (string) $registro['emp_registro_crea'] : null;
    }

    // 2. Consulta o quadro tecnico (profissionais vinculados) da empresa.
    public function quadroTecnicoDaEmpresa(string $empRegistroCrea): array
    {
        return $this->buscar(['p' => "empresas/{$empRegistroCrea}/quadro-tecnico"]);
    }

    // 3. Simula o Acervo Operacional (CAO) da empresa.
    public function acervoOperacionalDaEmpresa(string $empRegistroCrea): array
    {
        return $this->buscar(['p' => "empresas/{$empRegistroCrea}/cao"]);
    }

    // ----- Tabela de Obras e Servicos (TOS) ---------------------------------

    // Pesquisa termos na Tabela TOS - liga uma necessidade tecnica a atividades
    // padronizadas, que por sua vez se relacionam a ARTs/CATs/acervo de
    // profissionais e empresas (Necessidade -> Atividade TOS -> Capacidade tecnica).
    public function pesquisarTos(string $search, int $page = 1, int $limit = 50): array
    {
        return $this->buscar([
            'p' => 'tos',
            'search' => $search,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    // Todos os recursos vivem no mesmo endpoint, diferenciados pela query `p`.
    private function buscar(array $query): array
    {
        return $this->http->get('', $query);
    }
}
