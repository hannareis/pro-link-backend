<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\PessoaJuridica;
use App\Repositories\PessoaJuridicaRepository;
use App\Services\CreaApiService;
use App\Services\FileUploadService;

// RF02 - consulta (somente leitura) dos dados oficiais da empresa (pessoa_juridica)
// no CREA-AM: cadastro por CNPJ, quadro tecnico e Acervo Operacional (CAO). Nao
// persiste nada no banco local, apenas repassa a resposta da API oficial. Tambem
// gerencia o selo de verificacao da empresa (documentos enviados pelo usuario).
class EmpresaController
{
    // MIME real (via fileinfo) -> extensao aceita para os documentos de verificacao.
    private const VERIFICACAO_MIMES_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private const VERIFICACAO_TAMANHO_MAXIMO_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private readonly PessoaJuridicaRepository $pessoasJuridicas = new PessoaJuridicaRepository(),
        private readonly CreaApiService $creaApiService = new CreaApiService(),
        private readonly FileUploadService $fileUploadService = new FileUploadService(
            self::VERIFICACAO_MIMES_PERMITIDOS,
            self::VERIFICACAO_TAMANHO_MAXIMO_BYTES
        ),
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

    // Consulta o status atual do selo de verificacao da empresa autenticada.
    public function statusVerificacao(Request $request): void
    {
        $pessoaJuridica = $this->pessoasJuridicas->findByUsuarioId(auth_id());

        if ($pessoaJuridica === null) {
            Response::json(['message' => 'Empresa não encontrada.'], 404);
            return;
        }

        Response::json([
            'status' => $pessoaJuridica->statusVerificacao,
            'data_solicitacao' => $pessoaJuridica->dataSolicitacaoVerificacao,
        ]);
    }

    // Recebe o contrato social e o comprovante cadastral da empresa autenticada e
    // abre uma solicitacao de verificacao (status PENDENTE), analisada manualmente
    // pelo ADMIN_CREA. Os documentos sao sempre vinculados ao proprio usuario logado.
    public function solicitarVerificacao(Request $request): void
    {
        $pessoaJuridica = $this->pessoasJuridicas->findByUsuarioId(auth_id());

        if ($pessoaJuridica === null) {
            Response::json(['message' => 'Empresa não encontrada.'], 404);
            return;
        }

        if (in_array($pessoaJuridica->statusVerificacao, [PessoaJuridica::STATUS_PENDENTE, PessoaJuridica::STATUS_APROVADA], true)) {
            Response::json(['message' => 'Já existe uma solicitação de verificação em andamento ou aprovada.'], 409);
            return;
        }

        try {
            $contrato = $this->fileUploadService->store($request->file('contrato_social'), 'verificacao_empresas');
            $comprovante = $this->fileUploadService->store($request->file('comprovante_cadastral'), 'verificacao_empresas');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage()], 400);
            return;
        } catch (\RuntimeException $e) {
            Response::json(['message' => $e->getMessage()], 500);
            return;
        }

        if ($contrato === null || $comprovante === null) {
            Response::json(['message' => 'Envie o contrato social e o comprovante cadastral.'], 400);
            return;
        }

        $this->pessoasJuridicas->salvarSolicitacaoVerificacao(
            auth_id(),
            $contrato['caminho_armazenamento'],
            $comprovante['caminho_armazenamento']
        );

        Response::json(['message' => 'Solicitação de verificação enviada.', 'status' => PessoaJuridica::STATUS_PENDENTE], 201);
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
