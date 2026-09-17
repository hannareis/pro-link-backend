<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\Validator;
use App\Models\CartaVirtual;
use App\Repositories\CartaVirtualRepository;
use App\Services\FileUploadService;

// Carta virtual: certificado/carta enviado pelo usuario autenticado a um
// destinatario externo por e-mail, opcionalmente vinculada a uma demanda e com
// um arquivo anexo (documento/imagem) opcional. Contem e-mails pessoais do
// remetente/destinatario, por isso as acoes sao restritas ao autor da carta.
class CartaVirtualController
{
    // MIME real (via fileinfo) -> extensao aceita para o arquivo anexo.
    private const ARQUIVO_MIMES_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private const ARQUIVO_TAMANHO_MAXIMO_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private readonly CartaVirtualRepository $cartasVirtuais = new CartaVirtualRepository(),
        private readonly FileUploadService $fileUploadService = new FileUploadService(
            self::ARQUIVO_MIMES_PERMITIDOS,
            self::ARQUIVO_TAMANHO_MAXIMO_BYTES
        ),
    ) {
    }

    // Lista as cartas virtuais criadas pelo usuario autenticado.
    public function index(Request $request): void
    {
        Response::json(['data' => $this->cartasVirtuais->listByUsuario(auth_id())]);
    }

    // Exibe uma carta virtual do usuario autenticado.
    public function show(Request $request): void
    {
        $carta = $this->cartasVirtuais->findById((int) $request->input('id'));

        if ($carta === null || $carta->idUsuario !== auth_id()) {
            Response::json(['message' => 'Carta virtual não encontrada.'], 404);
            return;
        }

        Response::json(['data' => $carta]);
    }

    // Cria uma nova carta virtual, com upload opcional de arquivo anexo (campo "arquivo").
    public function store(Request $request): void
    {
        $titulo = trim((string) $request->input('titulo', ''));
        $remetenteEmail = trim((string) $request->input('remetente_email', ''));
        $destinatarioEmail = trim((string) $request->input('destinatario_email', ''));

        if ($titulo === '' || $remetenteEmail === '' || $destinatarioEmail === '') {
            Response::json(['message' => 'Título, e-mail do remetente e e-mail do destinatário são obrigatórios.'], 400);
            return;
        }

        if (!Validator::isValidEmail($remetenteEmail) || !Validator::isValidEmail($destinatarioEmail)) {
            Response::json(['message' => 'E-mail do remetente ou do destinatário inválido.'], 422);
            return;
        }

        try {
            $arquivo = $this->fileUploadService->store($request->file('arquivo'), 'cartas_virtuais');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage()], 400);
            return;
        }

        $carta = $this->fromRequest($request, $titulo, $remetenteEmail, $destinatarioEmail, $arquivo);
        $id = $this->cartasVirtuais->save($carta);

        Response::json(['message' => 'Carta virtual criada.', 'id' => $id], 201);
    }

    // Atualiza uma carta virtual do usuario autenticado. Um novo arquivo (campo
    // "arquivo") substitui o anterior; sem novo arquivo, o anexo atual e mantido.
    public function update(Request $request): void
    {
        $carta = $this->cartasVirtuais->findById((int) $request->input('id'));

        if ($carta === null || $carta->idUsuario !== auth_id()) {
            Response::json(['message' => 'Carta virtual não encontrada.'], 404);
            return;
        }

        $titulo = trim((string) $request->input('titulo', $carta->titulo));
        $remetenteEmail = trim((string) $request->input('remetente_email', $carta->remetenteEmail));
        $destinatarioEmail = trim((string) $request->input('destinatario_email', $carta->destinatarioEmail));

        if ($titulo === '' || $remetenteEmail === '' || $destinatarioEmail === '') {
            Response::json(['message' => 'Título, e-mail do remetente e e-mail do destinatário são obrigatórios.'], 400);
            return;
        }

        if (!Validator::isValidEmail($remetenteEmail) || !Validator::isValidEmail($destinatarioEmail)) {
            Response::json(['message' => 'E-mail do remetente ou do destinatário inválido.'], 422);
            return;
        }

        try {
            $arquivo = $this->fileUploadService->store($request->file('arquivo'), 'cartas_virtuais');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage()], 400);
            return;
        }

        $carta->idDemanda = $request->input('id_demanda') !== null ? (int) $request->input('id_demanda') : $carta->idDemanda;
        $carta->titulo = $titulo;
        $carta->legenda = (string) $request->input('legenda', $carta->legenda ?? '') ?: null;
        $carta->remetenteEmail = $remetenteEmail;
        $carta->destinatarioEmail = $destinatarioEmail;

        if ($arquivo !== null) {
            $carta->nomeArquivo = $arquivo['nome_arquivo'];
            $carta->nomeArmazenado = $arquivo['nome_armazenado'];
            $carta->tipoMime = $arquivo['tipo_mime'];
            $carta->tamanhoArquivo = $arquivo['tamanho'];
            $carta->caminhoArmazenamento = $arquivo['caminho_armazenamento'];
        }

        $this->cartasVirtuais->save($carta);

        Response::json(['message' => 'Carta virtual atualizada.']);
    }

    // Remove uma carta virtual do usuario autenticado.
    public function destroy(Request $request): void
    {
        $carta = $this->cartasVirtuais->findById((int) $request->input('id'));

        if ($carta === null || $carta->idUsuario !== auth_id()) {
            Response::json(['message' => 'Carta virtual não encontrada.'], 404);
            return;
        }

        $this->cartasVirtuais->delete((int) $carta->id);

        Response::json(['message' => 'Carta virtual removida.']);
    }

    private function fromRequest(
        Request $request,
        string $titulo,
        string $remetenteEmail,
        string $destinatarioEmail,
        ?array $arquivo
    ): CartaVirtual {
        $idDemanda = $request->input('id_demanda');

        return new CartaVirtual(
            idUsuario: auth_id(),
            idDemanda: $idDemanda !== null ? (int) $idDemanda : null,
            titulo: $titulo,
            legenda: (string) $request->input('legenda', '') ?: null,
            remetenteEmail: $remetenteEmail,
            destinatarioEmail: $destinatarioEmail,
            nomeArquivo: $arquivo['nome_arquivo'] ?? null,
            nomeArmazenado: $arquivo['nome_armazenado'] ?? null,
            tipoMime: $arquivo['tipo_mime'] ?? null,
            tamanhoArquivo: $arquivo['tamanho'] ?? null,
            caminhoArmazenamento: $arquivo['caminho_armazenamento'] ?? null,
        );
    }
}
