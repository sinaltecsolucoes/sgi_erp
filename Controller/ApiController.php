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

        // Coleta de dados 
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
        // Bloco try-catch para capturar qualquer falha no banco
        try {
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

            $equipe_dados = null;
            $equipe_membros = [];

            // 1. Buscar quem está alocado hoje
            $funcionarios_alocados_ids = $equipeModel->buscarFuncionariosAlocadosHoje();

            // 2. Buscar todos os funcionários de produção com status de presença
            $membros_com_presenca = $funcionarioModel->buscarPresentesHoje($data_hoje);

            $equipe_temp = $equipeModel->buscarEquipeDoApontador($apontador_id);

            if ($equipe_temp) {
                // É crucial que $equipe_dados seja um array ou null
                $equipe_dados = (array)$equipe_temp;
                $equipe_membros = $equipeModel->buscarFuncionariosDaEquipe($equipe_temp->id);
            }

            // 3. Aplicar o FILTRO: Presente E NÃO alocado (exceto os da equipe do próprio apontador, que serão adicionados pelo Flutter)
            $funcionarios_filtrados = [];
            foreach ($membros_com_presenca as $membro) {
                // Se o funcionário estiver presente E o ID dele não estiver na lista de alocados
                $id_membro = (int)$membro->id;

                // CRUCIAL: Se o ID NÃO estiver alocado OU se o ID já for um membro DA MINHA equipe (para edição)
                $is_already_my_member = in_array($id_membro, array_map(fn($m) => $m->id, $equipe_membros));

                if ((int)$membro->esta_presente === 1 && (!in_array($id_membro, $funcionarios_alocados_ids) || $is_already_my_member)) {
                    $funcionarios_filtrados[] = $membro;
                }
            }


            // Formatar a resposta JSON
            echo json_encode([
                'success' => true,
                'equipe_atual' => $equipe_dados, // Será array ou null
                'membros_equipe_ids' => array_map(fn($m) => $m->id, $equipe_membros),
                'funcionarios_producao' => array_map(fn($m) => [
                    'id' => (int)$m->id,
                    'nome' => $m->nome,
                    'presente' => (int)$m->esta_presente
                ], $funcionarios_filtrados),
            ]);
        } catch (\Throwable $th) {
            // Se qualquer Model lançar uma exceção, ela será capturada aqui
            error_log("Erro Fatal em equipeDados: " . $th->getMessage());

            // Retorna um JSON de erro para que o Flutter possa tratar
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno ao carregar dados da equipe. Verifique os logs do servidor.'
            ]);
        }
    }

    /**
     * Endpoint para salvar a montagem da equipe (EquipeModel).
     * Rota: /api/equipe/salvar
     * Método: POST
     */

    public function equipeSalvar()
    {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

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

    /**
     * Endpoint para buscar opções de lançamento: Membros, Ações, Produtos (com usa_lote).
     * Rota: /api/lancamento/opcoes
     * Método: POST (Recebe o funcionario_id do apontador logado)
     */
    public function lancamentoOpcoes()
    {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);
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

    /**
     * Endpoint para salvar um lançamento de produção individual.
     * Rota: /api/lancamento/salvar
     * Método: POST
     */
    public function lancamentoSalvar()
    {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        // Coleta todos os dados necessários
        $apontador_id = $data['apontador_id'] ?? null;
        $funcionario_id = $data['funcionario_id'] ?? null;
        $acao_id = $data['acao_id'] ?? null;
        $tipo_produto_id = $data['tipo_produto_id'] ?? null;
        $lote_produto = $data['lote_produto'] ?? '';
        $hora_inicio = $data['hora_inicio'] ?? null;
        $hora_fim = $data['hora_fim'] ?? null;
        $quantidade_kg = (float)($data['quantidade_kg'] ?? 0.0);

        if (!$apontador_id || !$funcionario_id || !$acao_id || !$tipo_produto_id || $quantidade_kg <= 0 || !$hora_inicio || !$hora_fim) {
            echo json_encode(['success' => false, 'message' => 'Todos os campos de produção e tempo são obrigatórios.']);
            return;
        }

        // 1. Obter equipe_id (necessário para a tabela produção)
        $equipeModel = new EquipeModel();
        $equipe = $equipeModel->buscarEquipeDoApontador($apontador_id);
        $equipe_id = $equipe->id ?? null;

        if (!$equipe_id) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma equipe associada ao apontador.']);
            return;
        }

        // 2. Validação do Lote (reutilizando a lógica do sistema web)
        $tipoProdutoModel = new TipoProdutoModel();
        $tipoProduto = $tipoProdutoModel->buscarPorId($tipo_produto_id);

        if (($tipoProduto->usa_lote ?? 1) && empty($lote_produto)) {
            echo json_encode(['success' => false, 'message' => 'O Lote do Produto é obrigatório para este tipo de produto/serviço.']);
            return;
        }

        // 3. Salvar o lançamento (Model já suporta Lote e Tempo)
        $producaoModel = new ProducaoModel();

        if ($producaoModel->registrarLancamento(
            $funcionario_id,
            $acao_id,
            $tipo_produto_id,
            $lote_produto,
            $quantidade_kg,
            $equipe_id,
            $hora_inicio,
            $hora_fim
        )) {
            echo json_encode([
                'success' => true,
                'message' => 'Lançamento individual de ' . $quantidade_kg . ' kg salvo com sucesso!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar o registro de produção.']);
        }
    }
}
