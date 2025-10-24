<?php
// Controller/ApiController.php

class ApiController
{
    private $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        // Garante que APENAS JSON seja retornado por esta classe
        header('Content-Type: application/json');
    }

    /**
     * Endpoint de Login para o App Flutter.
     * Rota: /api/login
     * Método: POST
     */
    public function login()
    {
        // A API espera um JSON no corpo da requisição
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        // Coleta de dados (Assumindo que o Flutter envia 'login' e 'senha')
        $login = $data['login'] ?? '';
        $senha = $data['senha'] ?? '';

        // 1. Validação
        if (empty($login) || empty($senha)) {
            echo json_encode([
                'success' => false,
                'message' => 'Login e senha são obrigatórios.'
            ]);
            return;
        }

        // 2. Autenticação via Model
        $usuario = $this->usuarioModel->logar($login, $senha);

        if ($usuario) {
            // 3. Login bem-sucedido: Retorna dados essenciais
            echo json_encode([
                'success' => true,
                'message' => 'Login efetuado com sucesso!',
                'funcionario_id' => $usuario->funcionario_id,
                'funcionario_nome' => $usuario->funcionario_nome,
                'funcionario_tipo' => $usuario->funcionario_tipo, // Essencial para regras do app
            ]);
        } else {
            // 4. Falha na autenticação
            echo json_encode([
                'success' => false,
                'message' => 'Credenciais inválidas.'
            ]);
        }
    }

    /**
     * Endpoint de Informação/Teste de Conexão.
     * Rota: /api
     * Método: GET (acesso via navegador)
     */
    public function info()
    {
        // Garante que o retorno seja JSON
        header('Content-Type: application/json');

        echo json_encode([
            'status' => 'online',
            'service' => 'SGI ERP API',
            'message' => 'API está funcional. Use /api/login ou /api/presenca via POST.',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Endpoint de Registro de Presença para o App Flutter.
     * Rota: /api/presenca
     * Método: POST
     */
    public function presenca()
    {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        $funcionario_id = $data['funcionario_id'] ?? null;
        $data_hoje = date('Y-m-d');

        // 1. Validação (Apenas o ID é necessário)
        if (empty($funcionario_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'ID do funcionário é obrigatório.'
            ]);
            return;
        }

        // 2. Processamento via Model (Reutilizamos o PresencaModel)
        $presencaModel = new PresencaModel();

        // Assumimos que o App SEMPRE envia apenas o ID para MARCAR PRESENTE
        if ($presencaModel->registrarPresenca($funcionario_id, $data_hoje)) {
            echo json_encode([
                'success' => true,
                'message' => 'Presença registrada com sucesso!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao salvar presença. Tente novamente.'
            ]);
        }
    }

    /**
     * Endpoint para buscar dados de Montagem de Equipe: 
     * Lista todos os funcionários de produção e o status de presença (do Model).
     * Rota: /api/equipe/dados
     * Método: POST (Recebe o funcionario_id do apontador logado)
     */
    public function equipeDados()
    {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        $apontador_id = $data['apontador_id'] ?? null;
        $data_hoje = date('Y-m-d');

        if (empty($apontador_id)) {
            echo json_encode(['success' => false, 'message' => 'ID do Apontador é obrigatório.']);
            return;
        }

        $funcionarioModel = new FuncionarioModel();
        $equipeModel = new EquipeModel();

        // 1. Buscar todos os funcionários de produção com status de presença
        $membros_com_presenca = $funcionarioModel->buscarPresentesHoje($data_hoje);

        // 2. Buscar a equipe do apontador
        $equipe_dados = $equipeModel->buscarEquipeDoApontador($apontador_id);
        $equipe_membros = [];

        if ($equipe_dados) {
            $equipe_membros = $equipeModel->buscarFuncionariosDaEquipe($equipe_dados->id);
        }

        // Formatar a resposta JSON
        echo json_encode([
            'success' => true,
            'equipe_atual' => $equipe_dados ? (array)$equipe_dados : null,
            'membros_equipe_ids' => array_map(fn($m) => $m->id, $equipe_membros), // Apenas IDs para facilitar o Flutter
            'funcionarios_producao' => array_map(fn($m) => [
                'id' => (int)$m->id,
                'nome' => $m->nome,
                'presente' => (int)$m->esta_presente
            ], $membros_com_presenca),
        ]);
    }
}
