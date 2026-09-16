<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\PessoaJuridicaRepository;
use App\Services\CreaApiService;

// RF02 - consulta (somente leitura) dos dados oficiais da empresa (pessoa_juridica)
// no CREA-AM: cadastro por CNPJ, quadro tecnico e Acervo Operacional (CAO). Nao
// persiste nada no banco local, apenas repassa a resposta da API oficial.
class EmpresaController
{
    public function __construct(
        private readonly PessoaJuridicaRepository $pessoasJuridicas = new PessoaJuridicaRepository(),
        private readonly CreaApiService $creaApiService = new CreaApiService(),
    ) {
    }

    // Busca os dados oficiais da empresa (a autenticada, ou outra via ?id=) pelo CNPJ.
    public function show(Request $request): void
    {
        $pessoaJuridica = $this->buscarPessoaJuridica($request);

        if ($pessoaJuridica === null) {
            Response::json(['message' => 'Empresa não encontrada.'], 404);
            return;
        }

        try {
            $resposta = $this->creaApiService->buscarEmpresaPorCnpj($pessoaJuridica->cnpj);
        } catch (\RuntimeException $e) {
            Response::json(['message' => 'Não foi possível contatar a API do CREA-AM. Tente novamente.'], 502);
            return;
        }

        if ($resposta['status'] !== 200) {
            Response::json(['message' => 'Falha ao consultar a empresa no CREA-AM.'], 502);
            return;
        }

        Response::json(['crea' => $resposta['body']]);
    }

    // Consulta o quadro tecnico da empresa no CREA-AM.
    public function quadroTecnico(Request $request): void
    {
        $this->consultarPorRegistroCrea($request, fn (string $registro) => $this->creaApiService->quadroTecnicoDaEmpresa($registro));
    }

    // Simula o Acervo Operacional (CAO) da empresa no CREA-AM.
    public function cao(Request $request): void
    {
        $this->consultarPorRegistroCrea($request, fn (string $registro) => $this->creaApiService->acervoOperacionalDaEmpresa($registro));
    }

    // Resolve o emp_registro_crea a partir do CNPJ e executa a consulta pedida.
    private function consultarPorRegistroCrea(Request $request, callable $consulta): void
    {
        $pessoaJuridica = $this->buscarPessoaJuridica($request);

        if ($pessoaJuridica === null) {
            Response::json(['message' => 'Empresa não encontrada.'], 404);
            return;
        }

        try {
            $registroCrea = $this->creaApiService->obterRegistroCreaPorCnpj($pessoaJuridica->cnpj);
        } catch (\RuntimeException $e) {
            Response::json(['message' => 'Não foi possível contatar a API do CREA-AM. Tente novamente.'], 502);
            return;
        }

        if ($registroCrea === null) {
            Response::json(['message' => 'Empresa não confirmada pelo CREA-AM.'], 422);
            return;
        }

        $resposta = $consulta($registroCrea);

        if ($resposta['status'] !== 200) {
            Response::json(['message' => 'Falha ao consultar o CREA-AM.'], 502);
            return;
        }

        Response::json($resposta['body']);
    }

    private function buscarPessoaJuridica(Request $request): ?\App\Models\PessoaJuridica
    {
        $usuarioId = (int) ($request->input('id') ?? auth_id());

        return $this->pessoasJuridicas->findByUsuarioId($usuarioId);
    }
}
