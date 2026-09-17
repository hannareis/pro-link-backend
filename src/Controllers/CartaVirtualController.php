<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\Validator;
use App\Models\CartaVirtual;
use App\Repositories\CartaVirtualRepository;
use App\Repositories\DemandaRepository;
use App\Repositories\UserRepository;
use App\Services\FileUploadService;
use App\Services\NotificacaoService;

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
        // Mesmo servico/SMTP usado por AuthController::recoverPassword.
        private readonly NotificacaoService $notificacaoService = new NotificacaoService(),
        private readonly UserRepository $users = new UserRepository(),
        private readonly DemandaRepository $demandas = new DemandaRepository(),
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
        // O remetente e sempre o proprio usuario autenticado - a SPA nao pede esse campo,
        // evitando depender de um valor que o cliente teria que preencher manualmente.
        $remetenteEmail = trim((string) $request->input('remetente_email', auth_user()['email'] ?? ''));
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
            // Arquivo em si invalido (tipo/tamanho/erro de upload) - erro do cliente.
            Response::json(['message' => $e->getMessage()], 400);
            return;
        } catch (\RuntimeException $e) {
            // Falha de I/O ao salvar (ex: permissao do diretorio) - erro do servidor,
            // mas o usuario precisa de feedback em vez de um erro fatal sem resposta JSON.
            Response::json(['message' => $e->getMessage()], 500);
            return;
        }

        $carta = $this->fromRequest($request, $titulo, $remetenteEmail, $destinatarioEmail, $arquivo);
        $id = $this->cartasVirtuais->save($carta);
        $carta->id = $id;

        $emailEnviado = $this->enviarCartaPorEmail($carta);
        $mensagem = $emailEnviado
            ? 'Carta virtual criada e enviada por e-mail.'
            : 'Carta virtual criada, mas não foi possível enviar o e-mail ao destinatário.';

        Response::json(['message' => $mensagem, 'id' => $id, 'email_enviado' => $emailEnviado], 201);
    }

    // Envia a carta virtual por e-mail ao destinatario (com o anexo, se houver), usando o
    // mesmo NotificacaoService/SMTP da recuperacao de senha (AuthController::recoverPassword).
    // Falha de envio nao desfaz a criacao da carta - ela ja foi persistida e continua
    // visivel para o autor mesmo que o SMTP esteja fora do ar.
    private function enviarCartaPorEmail(CartaVirtual $carta): bool
    {
        $remetente = $this->users->findById($carta->idUsuario);
        $nomeRemetente = $remetente?->nome ?? $carta->remetenteEmail;

        $corpo = sprintf(
            '<p>Você recebeu uma carta virtual de <strong>%s</strong> (%s) através do Pro-Link.</p><hr>'
                . '<h3>%s</h3><div>%s</div>',
            htmlspecialchars($nomeRemetente),
            htmlspecialchars($carta->remetenteEmail),
            htmlspecialchars($carta->titulo),
            $carta->legenda ?? ''
        );

        if ($carta->idDemanda !== null) {
            $corpo .= $this->blocoDemandaParaEmail($carta->idDemanda);
        }

        $anexoCaminho = $carta->caminhoArmazenamento !== null
            ? PATH_PUBLIC . '/' . $carta->caminhoArmazenamento
            : null;

        return $this->notificacaoService->enviarEmail(
            $carta->destinatarioEmail,
            $carta->titulo,
            $corpo,
            $anexoCaminho,
            $carta->nomeArquivo,
            $carta->remetenteEmail,
            $nomeRemetente
        );
    }

    // Monta o bloco HTML com os dados da demanda vinculada (titulo, descricao, area,
    // tipo, modalidade e localizacao), inserido no corpo do e-mail da carta virtual.
    // Demanda inexistente/removida nao quebra o envio - a carta segue sem esse bloco.
    private function blocoDemandaParaEmail(int $idDemanda): string
    {
        $demanda = $this->demandas->findById($idDemanda);

        if ($demanda === null) {
            return '';
        }

        $humanizar = fn (string $valor): string => ucwords(strtolower(str_replace('_', ' ', $valor)));
        $localizacao = trim($demanda->cidade . ($demanda->uf !== '' ? '/' . $demanda->uf : ''));

        return sprintf(
            '<hr><h4>Demanda vinculada: %s</h4>'
                . '<p>%s</p>'
                . '<ul>'
                . '<li><strong>Área:</strong> %s</li>'
                . '<li><strong>Tipo:</strong> %s</li>'
                . '<li><strong>Modalidade:</strong> %s</li>'
                . '%s'
                . '</ul>',
            htmlspecialchars($demanda->titulo),
            nl2br(htmlspecialchars($demanda->descricao)),
            htmlspecialchars($demanda->area),
            htmlspecialchars($humanizar($demanda->tipo)),
            htmlspecialchars($humanizar($demanda->modalidade)),
            $localizacao !== '' ? '<li><strong>Localização:</strong> ' . htmlspecialchars($localizacao) . '</li>' : ''
        );
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
        } catch (\RuntimeException $e) {
            Response::json(['message' => $e->getMessage()], 500);
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
