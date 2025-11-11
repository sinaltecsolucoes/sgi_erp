<?php
// Controller/ApiController.php

class ApiController
{
    private $usuarioModel;
    private $funcionarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        $this->funcionarioModel = new FuncionarioModel();
        header('Content-Type: application/json');
    }

    // ==========================================
    // CHECAGEM DE PERMISSÃO (ADMIN + TIPO ESPECÍFICO)
    // ==========================================
    private function checkPermission($requiredType, $providedType, $funcionarioId)
    {
        if (empty($funcionarioId) || empty($providedType)) {
            echo json_encode(['success' => false, 'message' => 'Dados do usuário ausentes.']);
            exit;
        }

        $funcionario = $this->funcionarioModel->buscarPorId($funcionarioId);
        if (!$funcionario || $funcionario->tipo !== $providedType) {
            echo json_encode(['success' => false, 'message' => 'Usuário inválido.']);
            exit;
        }

        if ($providedType !== $requiredType && $providedType !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Acesso negado: perfil insuficiente.']);
            exit;
        }
    }

    // ==========================================
    // LOGIN
    // ==========================================
    public function login()
    {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        $login = $data['login'] ?? '';
        $senha = $data['senha'] ?? '';

        if (empty($login) || empty($senha)) {
            echo json_encode(['success' => false, 'message' => 'Login e senha obrigatórios.']);
            return;
        }

        $usuario = $this->usuarioModel->logar($login, $senha);

        if ($usuario) {
            echo json_encode([
                'success' => true,
                'message' => 'Login efetuado!',
                'funcionario_id' => $usuario->funcionario_id,
                'funcionario_nome' => $usuario->funcionario_nome,
                'funcionario_tipo' => $usuario->funcionario_tipo,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
        }
    }

    // ==========================================
    // LISTA PARA CHAMADA (só porteiro ou admin)
    // ==========================================

    public function presencaFuncionarios()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Permite porteiro OU admin
        $tipoRequerido = 'porteiro';
        $tipoUsuario = $data['funcionario_tipo'] ?? '';
        $funcionarioId = $data['funcionario_id'] ?? null;

        if ($tipoUsuario !== 'admin' && $tipoUsuario !== $tipoRequerido) {
            echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
            return;
        }

        if (empty($funcionarioId)) {
            echo json_encode(['success' => false, 'message' => 'Usuário não identificado.']);
            return;
        }

        $funcionarioModel = new FuncionarioModel();

        // BUSCA APENAS FUNCIONÁRIOS DO TIPO 'producao'
        $funcionarios = $funcionarioModel->buscarTodosComPresencaHoje();

        echo json_encode([
            'success' => true,
            'funcionarios' => $funcionarios ?? []
        ]);
    }

    // ==========================================
    // SALVAR CHAMADA
    // ==========================================
    public function presencaSalvar()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // === VALIDAÇÃO DE PERMISSÃO ===
        $this->checkPermission('porteiro', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? null);

        $presentesIds = $data['presentes_ids'] ?? [];

        // Validação básica
        if (!is_array($presentesIds)) {
            echo json_encode(['success' => false, 'message' => 'Formato inválido: presentes_ids deve ser um array.']);
            return;
        }

        $hoje = date('Y-m-d');
        $funcionarioModel = new FuncionarioModel();
        $presencaModel = new PresencaModel(); // ← Certifique-se que existe

        // === BUSCAR TODOS OS FUNCIONÁRIOS DE PRODUÇÃO (mesmo filtro da lista) ===
        $todosProducao = $funcionarioModel->buscarTodosComPresencaHoje(); // Reutiliza o método!
        $idsValidos = array_column($todosProducao, 'id'); // [10, 11, 12, ...]

        $sucessos = 0;
        $falhas = 0;
        $erros = [];

        foreach ($idsValidos as $funcionarioId) {
            $estaPresente = in_array($funcionarioId, $presentesIds);

            try {
                if ($estaPresente) {
                    // Registrar presença
                    if ($presencaModel->registrarPresenca($funcionarioId, $hoje)) {
                        $sucessos++;
                    } else {
                        $falhas++;
                        $erros[] = "Falha ao registrar presença para ID $funcionarioId";
                    }
                } else {
                    // Remover presença (se existir)
                    if ($presencaModel->removerPresenca($funcionarioId, $hoje)) {
                        // Não conta como sucesso, mas operação OK
                    } else {
                        $falhas++;
                        $erros[] = "Falha ao remover presença para ID $funcionarioId";
                    }
                }
            } catch (Exception $e) {
                $falhas++;
                $erros[] = "Erro inesperado para ID $funcionarioId: " . $e->getMessage();
            }
        }

        // === RESPOSTA FINAL ===
        if ($falhas === 0) {
            echo json_encode([
                'success' => true,
                'message' => "Chamada registrada com sucesso! ($sucessos presentes)"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Chamada registrada com $falhas erros.",
                'erros' => $erros
            ]);
        }
    }

    // ==========================================
    // DADOS DA EQUIPE (apontador ou admin)
    // ==========================================
    public function equipeDados()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? null);

        // Bloco try-catch para capturar qualquer falha no banco
        try {
            $apontador_id = $data['apontador_id'] ?? null;
            $hoje = date('Y-m-d');

            if (empty($apontador_id)) {
                echo json_encode(['success' => false, 'message' => 'ID do Apontador é obrigatório.']);
                return;
            }

            $funcionarioModel = new FuncionarioModel();
            $equipeModel = new EquipeModel();

            // === 1. BUSCAR EQUIPES DO APONTADOR ===
            $equipes = $equipeModel->buscarEquipesDoApontador($apontador_id);
            if (!$equipes) {
                $equipes = [];
            }

            // Adicionar membros a cada equipe
            foreach ($equipes as &$eq) {
                $eq['membros'] = $equipeModel->buscarFuncionariosDaEquipe($eq->id);
            }

            // === 2. BUSCAR FUNCIONÁRIOS PRESENTES HOJE ===
            $presentes = $funcionarioModel->buscarPresentesHoje($hoje);

            // === 3. BUSCAR FUNCIONÁRIOS ALOCADOS HOJE (TODAS AS EQUIPES) ===
            $alocadosIds = $equipeModel->buscarFuncionariosAlocadosHoje();

            // === 4. MONTAR LISTA DE DISPONÍVEIS ===
            $meusMembrosIds = [];
            foreach ($equipes as $eq) {
                foreach ($eq['membros'] as $m) {
                    $meusMembrosIds[] = $m['id'];
                }
            }

            $disponiveis = [];
            foreach ($presentes as $p) {
                if ($p->esta_presente == 1) {
                    $id = (int)$p->id;
                    if (!in_array($id, $alocadosIds) || in_array($id, $meusMembrosIds)) {
                        $disponiveis[] = [
                            'id' => $id,
                            'nome' => $p->nome,
                            'presente' => 1,
                            'na_minha_equipe' => in_array($id, $meusMembrosIds),
                        ];
                    }
                }
            }

            // === 5. FORMATO PARA O APP (adaptado para uma equipe só, se necessário) ===
            // Como o app espera 'equipe_atual', pegamos a primeira equipe ou null
            $equipe_atual = !empty($equipes) ? $equipes[0] : null;
            $membros_equipe_ids = $equipe_atual ? array_column($equipe_atual['membros'], 'id') : [];

            echo json_encode([
                'success' => true,
                'data' => [
                    'funcionarios_producao' => $disponiveis, // Apenas disponíveis + os da própria equipe
                    'membros_equipe_ids' => $membros_equipe_ids,
                    'equipe_atual' => $equipe_atual ? [
                        'id' => $equipe_atual['id'],
                        'nome' => $equipe_atual['nome']
                    ] : null
                ]
            ]);
        } catch (\Throwable $th) {
            error_log("Erro em equipeDados: " . $th->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno.']);
        }
    }

    // ==========================================
    // SALVAR EQUIPE
    // ==========================================
    public function equipeSalvar()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? null);

        $apontador_id = $data['apontador_id'] ?? null;
        $nome_equipe = $data['nome_equipe'] ?? 'Equipe Padrão';
        $membros_ids = $data['membros_ids'] ?? [];

        // 1. Validação
        if (!$apontador_id || empty($membros_ids)) {
            echo json_encode(['success' => false, 'message' => 'Apontador ID e membros são obrigatórios.']);
            return;
        }

        // 2. Salvar usando o EquipeModel
        $equipeModel = new EquipeModel();
        if ($equipeModel->salvarEquipe($apontador_id, $nome_equipe, $membros_ids)) {
            echo json_encode([
                'success' => true,
                'message' => 'Equipe salva com sucesso!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar a equipe.']);
        }
    }

    // ==========================================
    // OPÇÕES DE LANÇAMENTO
    // ==========================================
    public function lancamentoOpcoes()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? null);

        $apontador_id = $data['apontador_id'] ?? null;

        if (empty($apontador_id)) {
            echo json_encode(['success' => false, 'message' => 'ID do Apontador é obrigatório.']);
            return;
        }

        $equipeModel = new EquipeModel();
        $acaoModel = new AcaoModel();
        $tipoProdutoModel = new TipoProdutoModel();

        // 1. Buscar membros da equipe
        $equipe = $equipeModel->buscarEquipeDoApontador($apontador_id);
        if (!$equipe) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma equipe encontrada. Monte sua equipe primeiro.']);
            return;
        }
        $membros = $equipeModel->buscarFuncionariosDaEquipe($equipe->id);

        // 2. Buscar opções de Ação e Produto (incluindo usa_lote)
        $acoes = $acaoModel->buscarTodas();
        $tipos_produto = $tipoProdutoModel->buscarTodos();

        echo json_encode([
            'success' => true,
            'equipe_id' => $equipe->id,
            'membros' => array_map(fn($m) => ['id' => $m->id, 'nome' => $m->nome], $membros),
            'acoes' => array_map(fn($a) => ['id' => $a->id, 'nome' => $a->nome], $acoes),
            // Passa o flag usa_lote para o JS
            'produtos' => array_map(fn($p) => ['id' => $p->id, 'nome' => $p->nome, 'usa_lote' => (int)$p->usa_lote], $tipos_produto),
        ]);
    }

    // ==========================================
    // SALVAR LANÇAMENTO EM MASSA (apontador/admin)
    // ==========================================
    public function lancamentoSalvarMassa()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? null);

        $lancamentos = $data['lancamentos'] ?? [];  // Array de lançamentos [{funcionario_id, acao_id, produto_id, lote, quantidade_kg, hora_inicio, hora_fim}]

        if (empty($lancamentos)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum lançamento fornecido.']);
            return;
        }

        $producaoModel = new ProducaoModel();
        $errors = [];
        $successCount = 0;

        foreach ($lancamentos as $lanc) {
            $funcionario_id = $lanc['funcionario_id'];
            $acao_id = $lanc['acao_id'];
            $produto_id = $lanc['produto_id'];
            $lote = $lanc['lote'] ?? '';
            $quantidade_kg = $lanc['quantidade_kg'];
            $hora_inicio = $lanc['hora_inicio'];
            $hora_fim = $lanc['hora_fim'];

            // Validação básica
            if (empty($funcionario_id) || empty($acao_id) || empty($produto_id) || $quantidade_kg <= 0) {
                $errors[] = "Dados inválidos para funcionário $funcionario_id";
                continue;
            }

            // Salva no banco (use seu Model)
            if ($producaoModel->registrarLancamento(
                $funcionario_id,
                $acao_id,
                $produto_id,
                $lote,
                $quantidade_kg,
                $hora_inicio,
                $hora_fim
            )) {
                $successCount++;
            } else {
                $errors[] = "Erro ao salvar para funcionário $funcionario_id";
            }
        }

        if (empty($errors)) {
            echo json_encode(['success' => true, 'message' => 'Todos os lançamentos salvos com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Alguns erros ocorreram', 'errors' => $errors]);
        }
    }

    // ==========================================
    // INFO / TESTE
    // ==========================================
    public function info()
    {
        echo json_encode([
            'status' => 'online',
            'service' => 'SGI ERP API',
            'message' => 'API rodando perfeitamente!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
