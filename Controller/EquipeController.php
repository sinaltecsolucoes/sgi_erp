<?php
// Controller/EquipeController.php

class EquipeController extends AppController
{
    private $equipeModel;
    private $funcionarioModel;
    // Precisaremos de PresencaModel para saber quem está presente
    private $presencaModel;

    public function __construct()
    {
        parent::__construct();

        $this->equipeModel = new EquipeModel();
        $this->funcionarioModel = new FuncionarioModel();
        $this->presencaModel = new PresencaModel(); // Necessário para checar a presença

        // Regra de Negócio: Apenas apontadores podem acessar esta função
        if ($_SESSION['funcionario_tipo'] !== 'apontador') {
            $_SESSION['erro'] = 'Acesso negado. Apenas Apontadores podem montar equipes.';
            header('Location: /sgi_erp/dashboard');
            exit();
        }
    }

    /**
     * Exibe a interface para montar a equipe.
     * Rota: /equipes
     */
    public function index()
    {
        $apontador_id = $_SESSION['funcionario_id'];
        $equipe = $this->equipeModel->buscarEquipeDoApontador($apontador_id);

        // 1. Encontra todos os funcionários de produção presentes hoje
        $hoje = date('Y-m-d');
        // Buscar todos os funcionários de produção
        $funcionarios_producao = $this->funcionarioModel->buscarPresentesHoje($hoje);

        $membros_equipe_ids = [];
        $funcionarios_disponiveis = [];

        if ($equipe) {
            // 2. Se a equipe já existe, busca os membros atuais
            $membros_equipe = $this->equipeModel->buscarFuncionariosDaEquipe($equipe->id);
            $membros_equipe_ids = array_map(fn($m) => $m->id, $membros_equipe);

            // 3. Filtra a lista: Funcionário está presente E não está em outra equipe (Simplificado: para o nosso MVP, assumimos que se está na lista, está disponível)
            foreach ($funcionarios_producao as $f) {
                // Apenas incluir na lista se estiver PRESENTE
                if ($f->esta_presente) {
                    $funcionarios_disponiveis[] = $f;
                }
            }
        } else {
            // Se não tem equipe, todos os presentes estão disponíveis
            foreach ($funcionarios_producao as $f) {
                if ($f->esta_presente) {
                    $funcionarios_disponiveis[] = $f;
                }
            }
        }

        // Variáveis para o Template
        $title = "Montagem de Equipe";
        $content_view = ROOT_PATH . 'View' . DS . 'equipes.php';

        $dados = [
            'equipe' => $equipe,
            'funcionarios_disponiveis' => $funcionarios_disponiveis,
            'membros_equipe_ids' => $membros_equipe_ids // IDs dos que já estão na equipe
        ];

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Processa a criação e/ou associação de funcionários à equipe.
     * Rota: /equipes/salvar
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/equipes');
            exit();
        }

        $apontador_id = $_SESSION['funcionario_id'];
        $nome_equipe = isset($_POST['nome_equipe']) ? trim($_POST['nome_equipe']) : 'Equipe Padrão';
        $membros_selecionados_ids = isset($_POST['membros']) ? $_POST['membros'] : [];

        // 1. Tenta encontrar a equipe existente
        $equipe = $this->equipeModel->buscarEquipeDoApontador($apontador_id);
        $equipe_id = null;

        if (!$equipe) {
            // 2. Se não existe, cria
            $equipe_id = $this->equipeModel->criarEquipe($apontador_id, $nome_equipe);
            if (!$equipe_id) {
                $_SESSION['erro'] = 'Erro ao criar a equipe.';
                header('Location: /sgi_erp/equipes');
                exit();
            }
        } else {
            // 2b. Se existe, usa o ID existente
            $equipe_id = $equipe->id;

            // Atualiza o nome da equipe no banco de dados
            $this->equipeModel->atualizarNome($equipe_id, $nome_equipe);
        }

        // 3. Limpa a equipe para reconstruir com os novos membros
        $this->equipeModel->removerTodosFuncionarios($equipe_id);

        $sucessos = 0;
        foreach ($membros_selecionados_ids as $funcionario_id) {
            // Garante que é um número inteiro válido
            $id = (int)$funcionario_id;

            // 4. Associa cada funcionário selecionado
            if ($this->equipeModel->associarFuncionario($equipe_id, $id)) {
                $sucessos++;
            }
        }

        $_SESSION['sucesso'] = "Equipe **{$nome_equipe}** atualizada com $sucessos membros.";
        header('Location: /sgi_erp/equipes');
        exit();
    }
}
