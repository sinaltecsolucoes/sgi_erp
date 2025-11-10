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
    // DADOS DA EQUIPE (apontador ou admin)
    // ==========================================
    public function equipeDados()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? null);

        // ... seu código original aqui (busca equipe, membros, etc.)
        // Exemplo rápido:
        $equipeModel = new EquipeModel();
        $dados = $equipeModel->getDadosParaApp();
        echo json_encode($dados);
    }

    // ==========================================
    // SALVAR EQUIPE
    // ==========================================
    public function equipeSalvar()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? null);

        // ... seu código de salvar equipe
    }

    // ==========================================
    // LISTA PARA CHAMADA (só porteiro ou admin)
    // ==========================================
    public function presencaFuncionarios()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('porteiro', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? null);

        $funcionarioModel = new FuncionarioModel();
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
        $this->checkPermission('porteiro', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? null);

        $presentesIds = $data['presentes_ids'] ?? [];
        // ... seu código de salvar presença em massa
        echo json_encode(['success' => true, 'message' => 'Chamada salva!']);
    }

    // ==========================================
    // OPÇÕES DE LANÇAMENTO
    // ==========================================
    public function lancamentoOpcoes()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->checkPermission('apontador', $data['funcionario_tipo'] ?? '', $data['funcionario_id'] ?? null);

        // ... retorna ações, produtos, membros da equipe atual
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
