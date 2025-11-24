<?php
// Controller/ApiController.php

class ApiController
{
    private $usuarioModel;
    private $funcionarioModel;
    private $equipeModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        $this->funcionarioModel = new FuncionarioModel();
        $this->equipeModel = new EquipeModel();
        header('Content-Type: application/json');
    }

    // ==========================================
    // CHECAGEM DE PERMISSÃO
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
    // 1. LOGIN
    // ==========================================
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);
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
                'funcionario_id' => $usuario->funcionario_id,
                'funcionario_nome' => $usuario->funcionario_nome,
                'funcionario_tipo' => $usuario->funcionario_tipo,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
        }
    }

    // ==========================================
    // 2. DADOS DA EQUIPE
    // ==========================================
    public function equipeDados()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $apontadorId = (int)($data['apontador_id'] ?? 0);
        if (!$apontadorId) {
            echo json_encode(['success' => false, 'message' => 'ID do apontador inválido.']);
            return;
        }

        $equipesRaw = $this->equipeModel->buscarEquipesDoApontador($apontadorId);
        $equipes = [];

        foreach ($equipesRaw as $e) {
            $membros = $this->equipeModel->buscarFuncionariosDaEquipe($e->id);
            $equipes[] = [
                'id' => (int)$e->id,
                'nome' => $e->nome,
                'membros' => $membros
            ];
        }

        echo json_encode(['success' => true, 'data' => $equipes]);
    }

    // ==========================================
    // 3. SALVAR EQUIPE (CRIAR/EDITAR)
    // ==========================================
    public function equipeSalvar()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $apontador_id = $data['apontador_id'] ?? 0;
        $nome_equipe = $data['nome_equipe'] ?? 'Equipe Padrão';
        $membros_ids = $data['membros_ids'] ?? [];
        $equipe_id = $data['equipe_id'] ?? null;

        if (!$apontador_id || empty($membros_ids)) {
            echo json_encode(['success' => false, 'message' => 'Dados obrigatórios ausentes.']);
            return;
        }

        if ($equipe_id) {
            // Editar
            $this->equipeModel->atualizarNome($equipe_id, $nome_equipe);
            $this->equipeModel->removerTodosFuncionarios($equipe_id); // Remove todos atuais
            foreach ($membros_ids as $func_id) {
                $this->equipeModel->associarFuncionario($equipe_id, $func_id);
            }
            echo json_encode(['success' => true, 'message' => 'Equipe atualizada!']);
        } else {
            // Criar nova
            $nova_id = $this->equipeModel->criarEquipe($apontador_id, $nome_equipe);
            if ($nova_id) {
                foreach ($membros_ids as $func_id) {
                    $this->equipeModel->associarFuncionario($nova_id, $func_id);
                }
                echo json_encode(['success' => true, 'message' => 'Equipe criada!', 'equipe_id' => $nova_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar equipe.']);
            }
        }
    }

    // ==========================================
    // 4. EQUIPES DE OUTROS APONTADORES
    // ==========================================
    public function buscarEquipesOutros()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $apontadorId = (int)($data['funcionario_id'] ?? 0);

        $todas = $this->equipeModel->buscarTodasEquipesAtivasHoje();

        // Verifica se a propriedade existe antes de acessá-la
        $outras = array_filter($todas, fn($e) => property_exists($e, 'apontador_id') && (int)$e->apontador_id !== $apontadorId);

        // A linha 262 também pode falhar se $e->apontador_nome não existir
        $resultado = array_map(fn($e) => [
            'id' => (int)$e->id,
            'nome' => $e->nome,
            //  Usa um valor padrão se a propriedade não existir
            'apontador_nome' => property_exists($e, 'apontador_nome') ? $e->apontador_nome : 'Desconhecido'
        ], $outras);

        echo json_encode(['success' => true, 'equipes' => array_values($resultado)]);
    }

    // ==========================================
    // 5. MOVER MEMBRO
    // ==========================================
    public function moverMembro()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $membroId = (int)($data['membro_id'] ?? 0);
        $origemId = (int)($data['equipe_origem_id'] ?? 0);
        $destinoId = (int)($data['equipe_destino_id'] ?? 0);

        if (!$membroId || !$origemId || !$destinoId) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        if (
            $this->equipeModel->removerFuncionarioDeEquipe($origemId, $membroId) &&
            $this->equipeModel->associarFuncionario($destinoId, $membroId)
        ) {
            echo json_encode(['success' => true, 'message' => 'Movido!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao mover.']);
        }
    }

    // ==========================================
    // 6. RETIRAR MEMBRO
    // ==========================================
    public function retirarMembro()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $equipeId = (int)($data['equipe_id'] ?? 0);
        $membroId = (int)($data['membro_id'] ?? 0);

        if (!$equipeId || !$membroId) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        if ($this->equipeModel->removerFuncionarioDeEquipe($equipeId, $membroId)) {
            echo json_encode(['success' => true, 'message' => 'Membro retirado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao retirar.']);
        }
    }

    // ==========================================
    // 7. EDITAR EQUIPE
    // ==========================================
    public function editarEquipe()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $equipeId = (int)($data['equipe_id'] ?? 0);
        $novoNome = trim($data['novo_nome'] ?? '');
        $novosMembros = $data['novos_membros_ids'] ?? [];

        if (!$equipeId || empty($novoNome)) {
            echo json_encode(['success' => false, 'message' => 'Nome obrigatório.']);
            return;
        }

        $sucesso = $this->equipeModel->atualizarNome($equipeId, $novoNome);
        if ($sucesso) {
            foreach ($novosMembros as $mid) {
                $mid = (int)$mid;
                $this->equipeModel->associarFuncionario($equipeId, $mid);
            }
        }

        echo json_encode([
            'success' => $sucesso,
            'message' => $sucesso ? 'Equipe atualizada.' : 'Erro ao salvar.'
        ]);
    }

    // ==========================================
    // 8. FUNCIONÁRIOS DISPONÍVEIS
    // ==========================================
    public function buscarFuncionariosDisponiveis()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $hoje = date('Y-m-d');

        // 1. Funcionários PRESENTES hoje
        $presentes = $this->funcionarioModel->buscarPresentesHoje($hoje);

        // 2. IDs alocados hoje
        $alocados = $this->equipeModel->buscarFuncionariosAlocadosHoje();
        $alocadosIds = array_map('intval', $alocados);

        // 3. Filtra: só quem tem esta_presente == 1 E NÃO está alocado
        $disponiveis = [];
        foreach ($presentes as $f) {
            $id = (int)$f->id;
            if ($f->esta_presente == 1 && !in_array($id, $alocadosIds)) {
                $disponiveis[] = [
                    'id'   => $id,
                    'nome' => $f->nome
                ];
            }
        }

        echo json_encode([
            'success'      => true,
            'funcionarios' => $disponiveis,
            'total_presentes' => count($presentes),
            'total_alocados'  => count($alocadosIds),
            'disponiveis'     => count($disponiveis)
        ]);
    }

    // ==========================================
    // 9. LISTA PARA CHAMADA
    // ==========================================
    public function presencaFuncionarios()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('porteiro', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $funcionarios = $this->funcionarioModel->buscarTodosComPresencaHoje();
        echo json_encode(['success' => true, 'funcionarios' => $funcionarios ?? []]);
    }

    // ==========================================
    // 10. SALVAR CHAMADA
    // ==========================================
    public function presencaSalvar()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('porteiro', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $presentesIds = $data['presentes_ids'] ?? [];
        if (!is_array($presentesIds)) {
            echo json_encode(['success' => false, 'message' => 'Formato inválido.']);
            return;
        }

        $hoje = date('Y-m-d');
        $presencaModel = new PresencaModel();
        $todosProducao = $this->funcionarioModel->buscarTodosComPresencaHoje();
        $idsValidos = array_column($todosProducao, 'id');

        $sucessos = 0;
        foreach ($idsValidos as $id) {
            $estaPresente = in_array($id, $presentesIds);
            if ($estaPresente) {
                $presencaModel->registrarPresenca($id, $hoje) ? $sucessos++ : null;
            } else {
                $presencaModel->removerPresenca($id, $hoje);
            }
        }

        echo json_encode(['success' => true, 'message' => "Chamada registrada ($sucessos presentes)"]);
    }

    // ==========================================
    // 10. SALVAR CHAMADA
    // ==========================================
    public function equipesTodasAtivas()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $equipes = $this->equipeModel->buscarTodasEquipesAtivasHoje();

        echo json_encode([
            'success' => true,
            'equipes' => $equipes
        ]);
    }

    // ==========================================
    // 11. EXCLUIR EQUIPE 
    // ==========================================
    public function excluirEquipe()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $equipeId = (int)($data['equipe_id'] ?? 0);
        if ($equipeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID da equipe inválido.']);
            return;
        }

        // Verifica se a equipe pertence mesmo ao apontador logado
        $equipe = $this->equipeModel->buscarEquipePorId($equipeId);
        if (!$equipe || $equipe->apontador_id != $data['funcionario_id']) {
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para excluir esta equipe.']);
            return;
        }

        $sucesso = $this->equipeModel->excluirEquipe($equipeId);

        echo json_encode([
            'success' => $sucesso,
            'message' => $sucesso ? 'Equipe excluída com sucesso!' : 'Erro ao excluir a equipe.'
        ]);
    }

    // ========================================================
    // 12. OPÇÕES COMPLETAS PARA LANÇAMENTO EM MASSA
    //    (retorna todas as equipes ativas + ações + produtos)
    // ========================================================
    public function lancamentoOpcoesCompleto()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $apontador_id = (int)($data['funcionario_id'] ?? 0);
        if ($apontador_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Usuário não identificado.']);
            return;
        }

        $equipeModel      = new EquipeModel();
        $acaoModel        = new AcaoModel();
        $tipoProdutoModel = new TipoProdutoModel();

        // 1. Todas as equipes ATIVAS do apontador hoje
        //$equipesRaw = $equipeModel->buscarEquipesAtivasDoApontador($apontador_id);
        $equipesRaw = $equipeModel->buscarTodasEquipesDoApontador($apontador_id);

        $equipes = array_map(fn($e) => [
            'id'   => (int)$e->id,
            'nome' => $e->nome . ' (' . count($equipeModel->buscarFuncionariosDaEquipe($e->id)) . ' membros)'
        ], $equipesRaw);

        if (empty($equipes)) {
            echo json_encode(['success' => false, 'message' => 'Você não tem equipes ativas hoje.']);
            return;
        }

        // 2. Ações e Produtos
        $acoesRaw    = $acaoModel->buscarTodas();
        $produtosRaw = $tipoProdutoModel->buscarTodos();

        echo json_encode([
            'success'  => true,
            'equipes'  => $equipes,
            'acoes'    => array_map(fn($a) => ['id' => (int)$a->id, 'nome' => $a->nome], $acoesRaw),
            'produtos' => array_map(fn($p) => [
                'id'       => (int)$p->id,
                'nome'     => $p->nome,
                'usa_lote' => (int)$p->usa_lote
            ], $produtosRaw),
        ]);
    }

    // ========================================================
    // 13. BUSCAR MEMBROS DE UMA EQUIPE ESPECÍFICA
    // ========================================================
    public function getMembrosEquipe()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $equipe_id = (int)($data['equipe_id'] ?? 0);
        if ($equipe_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID da equipe inválido.']);
            return;
        }

        $equipeModel = new EquipeModel();

        // Verifica se a equipe pertence ao apontador
        $equipe = $equipeModel->buscarEquipePorId($equipe_id);
        if (!$equipe || (int)$equipe->apontador_id !== (int)$data['funcionario_id']) {
            echo json_encode(['success' => false, 'message' => 'Equipe não encontrada ou sem permissão.']);
            return;
        }

        $membrosRaw = $equipeModel->buscarFuncionariosDaEquipe($equipe_id);
        $membros = array_map(fn($m) => [
            'id'   => (int)$m->id,
            'nome' => $m->nome
        ], $membrosRaw);

        echo json_encode([
            'success' => true,
            'membros' => $membros
        ]);
    }

    // ==========================================
    // SALVAR LANÇAMENTO DE PRODUÇÃO
    // ==========================================
    public function salvarLancamentoMassa()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Permissão
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $lancamentos = $data['lancamentos'] ?? [];

        if (empty($lancamentos)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum lançamento enviado.']);
            return;
        }

        $producaoModel = new ProducaoModel();
        $sucessos = 0;
        $erros = [];

        foreach ($lancamentos as $l) {
            $funcionario_id = (int)($l['funcionario_id'] ?? 0);
            $acao_id        = (int)($l['acao_id'] ?? 0);
            $produto_id     = (int)($l['produto_id'] ?? 0);
            $quantidade     = (float)($l['quantidade'] ?? 0);
            $lote           = $l['lote'] ?? null;
            $hora_inicio    = $l['hora_inicio'] ?? null;
            $hora_fim       = $l['hora_fim'] ?? null;

            if ($funcionario_id > 0 && $acao_id > 0 && $produto_id > 0 && $quantidade > 0) {
                $ok = $producaoModel->registrarLancamento(
                    $funcionario_id,
                    $acao_id,
                    $produto_id,
                    $lote,
                    $quantidade,
                    null, // equipe_id (não precisamos aqui)
                    $hora_inicio,
                    $hora_fim
                );
                if ($ok) {
                    $sucessos++;
                } else {
                    $erros[] = "Funcionário ID $funcionario_id";
                }
            }
        }

        $total = count($lancamentos);
        $falhas = $total - $sucessos;

        echo json_encode([
            'success' => $falhas === 0,
            'message' => $falhas === 0
                ? "Todos os $sucessos lançamentos salvos com sucesso!"
                : "Sucesso: $sucessos | Falhas: $falhas (verifique os dados)",
            'salvos' => $sucessos,
            'falhas' => $falhas
        ]);
    }

    // ==========================================
    // BUSCAR LANÇAMENTOS DO DIA DO APONTADOR (PARA O APP)
    // ==========================================
    public function getLancamentosDoDiaApontador()
    {
        $dataInput = json_decode(file_get_contents('php://input'), true);

        // Verifica permissão (apontador ou admin)
        $this->checkPermission('apontador', $dataInput['funcionario_tipo'] ?? '', $dataInput['funcionario_id'] ?? 0);

        $data = $dataInput['data'] ?? date('Y-m-d');

        // Valida formato da data
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $data = date('Y-m-d');
        }

        $apontador_id = $dataInput['funcionario_id'];

        $producaoModel = new ProducaoModel();
        $lancamentos = $producaoModel->buscarLancamentosDoDiaDoApontador($data, $apontador_id);

        echo json_encode([
            'success' => true,
            'lancamentos' => $lancamentos
        ]);
    }

    // ==========================================
    // ATUALIZAR LANÇAMENTO (APP)
    // ==========================================
    public function atualizarLancamento()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        // Permissão
        $this->checkPermission('apontador', $input['funcionario_tipo'] ?? '', $input['funcionario_id'] ?? 0);

        $id             = (int)($input['id'] ?? 0);
        $acao_id        = (int)($input['acao_id'] ?? 0);
        $produto_id     = (int)($input['produto_id'] ?? 0);
        $quantidade     = (float)($input['quantidade_kg'] ?? 0);
        $lote           = $input['lote_produto'] ?? null;
        $hora_inicio    = $input['hora_inicio'] ?? null;
        $hora_fim       = $input['hora_fim'] ?? null;

        if ($id <= 0 || $acao_id <= 0 || $produto_id <= 0 || $quantidade <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $producaoModel = new ProducaoModel();
        $ok = $producaoModel->atualizarLancamentoApp($id, $acao_id, $produto_id, $quantidade, $lote, $hora_inicio, $hora_fim);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Lançamento atualizado com sucesso!' : 'Erro ao salvar'
        ]);
    }

    // ==========================================
    // EXCLUIR LANÇAMENTO (APP)
    // ==========================================
    public function excluirLancamento()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        // Permissão (igual nas outras funções)
        $this->checkPermission('apontador', $input['funcionario_tipo'] ?? '', $input['funcionario_id'] ?? 0);

        $id = (int)($input['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        $producaoModel = new ProducaoModel();
        $ok = $producaoModel->excluirLancamentoApp($id);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Lançamento excluído com sucesso!' : 'Erro ao excluir'
        ]);
    }
}
