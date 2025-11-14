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
    /* public function equipeDados()
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
    }*/

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
    /*public function equipeSalvar()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $apontador_id = $data['apontador_id'] ?? 0;
        $nome_equipe = $data['nome_equipe'] ?? 'Equipe Padrão';
        $membros_ids = $data['membros_ids'] ?? [];
        $equipe_id = $data['equipe_id'] ?? null;

        if (!$apontador_id || empty($membros_ids)) {
            echo json_encode(['success' => false, 'message' => 'Apontador ID e membros são obrigatórios.']);
            return;
        }

        if ($equipe_id) {
            // EDITAR
            $this->equipeModel->atualizarNome($equipe_id, $nome_equipe);
            $this->equipeModel->removerTodosFuncionarios($equipe_id);
            foreach ($membros_ids as $func_id) {
                $this->equipeModel->associarFuncionario($equipe_id, $func_id);
            }
            echo json_encode(['success' => true, 'message' => 'Equipe atualizada com sucesso!']);
        } else {
            // CRIAR
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
    }*/

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
    /*  public function buscarEquipesOutros()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $apontadorId = (int)($data['funcionario_id'] ?? 0);

        $todas = $this->equipeModel->buscarTodasEquipesAtivasHoje();
        $outras = array_filter($todas, fn($e) => (int)$e->apontador_id !== $apontadorId);


        $resultado = array_map(fn($e) => [
            'id' => (int)$e->id,
            'nome' => $e->nome,
            'apontador_nome' => $this->funcionarioModel->buscarNomePorId((int)$e->apontador_id)
        ], $outras);

        echo json_encode([
            'success' => true,
            'equipes' => array_values($resultado)
        ]);
    }*/

    /* public function buscarEquipesOutros()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $apontadorId = (int)($data['funcionario_id'] ?? 0);
        if (!$apontadorId) {
            echo json_encode(['success' => false, 'message' => 'ID do apontador inválido.']);
            return;
        }

        $todas = $this->equipeModel->buscarTodasEquipesAtivasHoje();
        $outras = array_filter($todas, fn($e) => (int)$e->apontador_id !== $apontadorId);

        $resultado = array_map(fn($e) => [
            'id' => (int)$e->id,
            'nome' => $e->nome,
            'apontador_nome' => $e->apontador_nome
        ], $outras);

        echo json_encode([
            'success' => true,
            'equipes' => array_values($resultado)
        ]);
    }*/

    /*  public function buscarEquipesOutros()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $apontadorId = (int)($data['funcionario_id'] ?? 0);

        $todas = $this->equipeModel->buscarTodasEquipesAtivasHoje();
        $outras = array_filter($todas, fn($e) => (int)$e->apontador_id !== $apontadorId);

        $resultado = array_map(fn($e) => [
            'id' => (int)$e->id,
            'nome' => $e->nome,
            'apontador_nome' => $e->apontador_nome
        ], $outras);

        echo json_encode(['success' => true, 'equipes' => array_values($resultado)]);
    }*/

    public function buscarEquipesOutros()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $apontadorId = (int)($data['funcionario_id'] ?? 0);

        $todas = $this->equipeModel->buscarTodasEquipesAtivasHoje();

        // CORREÇÃO AQUI (Linha 259 original): Verifica se a propriedade existe antes de acessá-la
        $outras = array_filter($todas, fn($e) => property_exists($e, 'apontador_id') && (int)$e->apontador_id !== $apontadorId);

        // A linha 262 também pode falhar se $e->apontador_nome não existir
        $resultado = array_map(fn($e) => [
            'id' => (int)$e->id,
            'nome' => $e->nome,
            // CORREÇÃO: Usa um valor padrão se a propriedade não existir
            'apontador_nome' => property_exists($e, 'apontador_nome') ? $e->apontador_nome : 'Desconhecido'
        ], $outras);

        echo json_encode(['success' => true, 'equipes' => array_values($resultado)]);
    }

    // ==========================================
    // 5. MOVER MEMBRO
    // ==========================================
    /* public function moverMembro()
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

        if (!$this->equipeModel->estaFuncionarioNaEquipe($origemId, $membroId)) {
            echo json_encode(['success' => false, 'message' => 'Funcionário não está na origem.']);
            return;
        }

        $sucesso = $this->equipeModel->removerFuncionarioDeEquipe($origemId, $membroId) &&
            $this->equipeModel->associarFuncionario($destinoId, $membroId);

        echo json_encode([
            'success' => $sucesso,
            'message' => $sucesso ? 'Movido!' : 'Erro ao mover.'
        ]);
    }*/

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
    /*public function retirarMembro()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $equipeId = (int)($data['equipe_id'] ?? 0);
        $membroId = (int)($data['membro_id'] ?? 0);

        if (!$equipeId || !$membroId) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $sucesso = $this->equipeModel->removerFuncionarioDeEquipe($equipeId, $membroId);
        echo json_encode([
            'success' => $sucesso,
            'message' => $sucesso ? 'Membro retirado.' : 'Membro não encontrado.'
        ]);
    }*/

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
    /* public function editarEquipe()
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
                if (!$this->equipeModel->estaFuncionarioNaEquipe($equipeId, $mid)) {
                    $this->equipeModel->associarFuncionario($equipeId, $mid);
                }
            }
        }

        echo json_encode([
            'success' => $sucesso,
            'message' => $sucesso ? 'Equipe atualizada.' : 'Erro ao salvar.'
        ]);
    }*/

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
    /* public function buscarFuncionariosDisponiveis()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $hoje = date('Y-m-d');
        $alocados = $this->equipeModel->buscarFuncionariosAlocadosHoje();
        $presentes = $this->funcionarioModel->buscarPresentesHoje($hoje);

        $disponiveis = array_filter($presentes, fn($f) => !in_array($f['id'], $alocados));

        // Converte para array associativo
        $disponiveis = array_map(fn($f) => [
            'id' => $f->id,
            'nome' => $f->nome,
            // outros campos se necessário
        ], $disponiveis);

        echo json_encode(['success' => true, 'funcionarios' => array_values($disponiveis)]);
    }*/

    public function buscarFuncionariosDisponiveis()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? 0);

        $hoje = date('Y-m-d');
        $alocados = $this->equipeModel->buscarFuncionariosAlocadosHoje();
        $presentes = $this->funcionarioModel->buscarPresentesHoje($hoje);

        $disponiveis = array_filter($presentes, fn($f) => !in_array($f->id, $alocados));

        $disponiveis = array_map(fn($f) => [
            'id' => $f->id,
            'nome' => $f->nome,
        ], $disponiveis);

        echo json_encode(['success' => true, 'funcionarios' => array_values($disponiveis)]);
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
}
