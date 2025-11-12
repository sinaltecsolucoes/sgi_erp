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

        try {
            $apontador_id = $data['apontador_id'] ?? null;
            $hoje = date('Y-m-d');

            if (empty($apontador_id)) {
                echo json_encode(['success' => false, 'message' => 'ID do Apontador é obrigatório.']);
                return;
            }

            $funcionarioModel = new FuncionarioModel();
            $equipeModel = new EquipeModel();

            // 1) Buscar equipes (stdClass) e converter para arrays associativos com membros
            $equipes_raw = $equipeModel->buscarEquipesDoApontador($apontador_id) ?? [];

            $equipes = [];
            foreach ($equipes_raw as $eq) {
                // membros_raw pode vir em modo default (BOTH); vamos mapear para assoc
                $membros_raw = $equipeModel->buscarFuncionariosDaEquipe($eq->id) ?? [];
                $membros = array_map(function ($m) {
                    // garantir acesso por índice associativo
                    // se vier como objeto, convertemos; se vier como array, usamos direto
                    if (is_object($m)) {
                        return ['id' => (int) $m->id, 'nome' => $m->nome];
                    } else {
                        return ['id' => (int) $m['id'], 'nome' => $m['nome']];
                    }
                }, $membros_raw);

                $equipes[] = [
                    'id' => (int) $eq->id,
                    'nome' => $eq->nome,
                    'membros' => $membros,
                ];
            }

            // 2) Presentes e alocados
            $presentes = $funcionarioModel->buscarPresentesHoje($hoje); // stdClass[]
            $alocadosIds = $equipeModel->buscarFuncionariosAlocadosHoje(); // int[]

            // 3) IDs dos membros das minhas equipes
            $meusMembrosIds = [];
            foreach ($equipes as $eq) {
                foreach ($eq['membros'] as $m) {
                    $meusMembrosIds[] = (int) $m['id'];
                }
            }

            // 4) Disponíveis: presente hoje e não alocado em outra equipe,
            // ou já na minha equipe (liberado para edição)
            $disponiveis = [];
            foreach ($presentes as $p) {
                if ((int) $p->esta_presente === 1) {
                    $id = (int) $p->id;
                    if (!in_array($id, $alocadosIds, true) || in_array($id, $meusMembrosIds, true)) {
                        $disponiveis[] = [
                            'id' => $id,
                            'nome' => $p->nome,
                            'presente' => 1,
                            'na_minha_equipe' => in_array($id, $meusMembrosIds, true),
                        ];
                    }
                }
            }

            // 5) Equipe atual = primeira, e lista de IDs de seus membros
            $primeira_equipe = !empty($equipes) ? $equipes[0] : null;
            $membros_equipe_ids = $primeira_equipe ? array_column($primeira_equipe['membros'], 'id') : [];

            // Debug opcional
            error_log("DEBUG equipeDados → apontador_id=$apontador_id");
            error_log("Equipes encontradas: " . json_encode($equipes));
            error_log("Disponíveis: " . json_encode($disponiveis));

            // 6) Resposta final já em arrays associativos (compatível com Flutter)
            echo json_encode([
                'success' => true,
                'data' => [
                    'funcionarios_producao' => $disponiveis,
                    'membros_equipe_ids' => $membros_equipe_ids,
                    'equipes_do_apontador' => $equipes,
                    'equipe_atual' => $primeira_equipe,
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
        $equipe_id = $data['equipe_id'] ?? null; 

        if (!$apontador_id || empty($membros_ids)) {
            echo json_encode(['success' => false, 'message' => 'Apontador ID e membros são obrigatórios.']);
            return;
        }

        $equipeModel = new EquipeModel();

        if ($equipe_id) {
            // EDITAR equipe existente
            $equipeModel->atualizarNome($equipe_id, $nome_equipe);
            $equipeModel->removerTodosFuncionarios($equipe_id);
            foreach ($membros_ids as $func_id) {
                $equipeModel->associarFuncionario($equipe_id, $func_id);
            }
            echo json_encode(['success' => true, 'message' => 'Equipe atualizada com sucesso!']);
        } else {
            // CRIAR nova equipe
            $nova_id = $equipeModel->criarEquipe($apontador_id, $nome_equipe);
            if ($nova_id) {
                foreach ($membros_ids as $func_id) {
                    $equipeModel->associarFuncionario($nova_id, $func_id);
                }
                echo json_encode(['success' => true, 'message' => 'Equipe criada com sucesso!', 'equipe_id' => $nova_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar equipe.']);
            }
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
            'produtos' => array_map(fn($p) => ['id' => $p->id, 'nome' => $p->nome, 'usa_lote' => (int) $p->usa_lote], $tipos_produto),
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
            if (
                $producaoModel->registrarLancamento(
                    $funcionario_id,
                    $acao_id,
                    $produto_id,
                    $lote,
                    $quantidade_kg,
                    $hora_inicio,
                    $hora_fim
                )
            ) {
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
