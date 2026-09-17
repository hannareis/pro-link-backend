<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PortfolioRepository;
use App\Repositories\UserRepository;
use App\Repositories\UniversitarioRepository;
use App\Repositories\UniversidadeRepository;
use App\Repositories\PessoaJuridicaRepository;
use App\Repositories\PessoaFisicaRepository;
use App\Repositories\ProfissionalRepository;

class PortfolioService
{
    public function __construct(
        private readonly UserRepository $userRepository = new UserRepository(),
        private readonly UniversitarioRepository $universitarioRepository = new UniversitarioRepository(),
        private readonly UniversidadeRepository $universidadeRepository = new UniversidadeRepository(),
        private readonly PessoaJuridicaRepository $pessoaJuridicaRepository = new PessoaJuridicaRepository(),
        private readonly PessoaFisicaRepository $pessoaFisicaRepository = new PessoaFisicaRepository(),
        private readonly PortfolioRepository $portfolioRepository = new PortfolioRepository(),
        private readonly ProfissionalRepository $profissionalRepository = new ProfissionalRepository()
    ){

    }

    public function obterPortfolioCompleto(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        $universitario = $this->universitarioRepository->findByUsuarioId($userId);
        $universidade = $universitario ? $this->universidadeRepository->findById($universitario->universidadeId): null;
        $pj = $this->pessoaJuridicaRepository->findByUsuarioId($userId);
        $pf = $this->pessoaFisicaRepository->findByUsuarioId($userId);
        $prof = $this->profissionalRepository->findByUsuarioId($userId);
        $port = $this->portfolioRepository->findByUsuarioId($userId);
        // TODO vincular profissional à empresa
        // $empresa = $this->pessoaJuridicaRepository->fin

        return [
            'usuario' => [
                'nome' => $user->nome,
                'email' => $user->email,
                'telefone' => $user->telefone,
                'cidade' => $user->cidade,
                'estado' => $user->estado,
            ],
            'universitario' => [
                'universidade' => $universidade ?: '',
                'curso' => $universitario?->curso,
                'grau_academico' => $universitario?->grau_academico ?? '',
                'matricula' => $universitario?->matricula ?? '',
                'semestre_atual' => $universitario?->semestreAtual ?? '',
                'previsao_formatura' => $universitario?->previsaoFormatura ?? '',
                'comprovante_matricula' => $universitario?->comprovanteMatricula ?? '',
            ],
            'empresa' => [
                'cnpj' => $pj?->cnpj,
                'razao_social' => $pj?->razaoSocial,
                'nome_fantasia' => $pj?->nomeFantasia ?? '',
            ],
            'pessoa_fisica' => [
                'cpf' => $pf?->cpf,
            ],
            'profissional' => [
                'registro' => $prof?->numeroRegistroConfeaCrea,
                'categoria' => $prof?->categoriaProfissional,
                'anos_experiencia' => $prof?->anosExperiencia ?? '',
                'empresa_atual' => $prof?->empresaAtualId ?? '',
                'grau_academico' => $prof?->grauAcademico,
                'registro_valido' => $prof?->registroValidado,
                'registro_validado_em' => $prof?->registroValidadoEm ?? '',
                'registro_ativo' => $prof?->registroAtivo
            ],
            'portfolio' => [
                'resumo' => $port?->resumoProfissional ?? '',
                'documento' => $port?->documentoIdentificacao ?? '',
                'links' => $port?->linksContato ?? '',
            ]
        ];
    }
}