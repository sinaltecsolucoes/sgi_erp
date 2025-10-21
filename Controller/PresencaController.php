<?php
// Controller/PresencaController.php

class PresencaController extends AppController
{
    private $funcionarioModel;
    private $presencaModel;

    public function __construct()
    {
        // Chama o construtor do AppController para garantir a autenticação
        parent::__construct();

        $this->funcionarioModel = new FuncionarioModel();
        $this->presencaModel = new PresencaModel();

        // OPÇÃO: Rejeitar se não for 'apontador'
        if ($_SESSION['funcionario_tipo'] !== 'apontador') {
            $_SESSION['erro'] = 'Apenas Apontadores podem fazer a chamada.';
            header('Location: /sgi_erp/dashboard');
            exit();
        }
    }

    /**
     * Exibe a lista de funcionários para registro de presença.
     * Rota: /presenca
     */
    public function index()
    {
        $hoje = date('Y-m-d');
        $funcionarios = $this->funcionarioModel->buscarPresentesHoje($hoje);

        // As variáveis dos dados da View (Funcionários e Data)
        $dados = [
            'data' => $hoje,
            'funcionarios' => $funcionarios
        ];

        // Variáveis para o Template
        $title = "Registro de Presença";
        $content_view = ROOT_PATH . 'View' . DS . 'presenca.php';

        // Inclui o template. Nota: as variáveis $dados estão disponíveis na View/presenca.php
        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Processa o formulário de salvamento de presença.
     * Rota: /presenca (via POST)
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/presenca');
            exit();
        }

        $hoje = date('Y-m-d');

        // IDs dos funcionários que foram marcados como PRESENTES no formulário
        $presentes_ids = isset($_POST['presente']) ? $_POST['presente'] : [];

        // Busca TODOS os funcionários de produção para saber quem está na lista
        $todos_funcionarios = $this->funcionarioModel->buscarTodos();

        $sucessos = 0;
        $falhas = 0;

        foreach ($todos_funcionarios as $funcionario) {
            $id = $funcionario->id;

            // Verifica se o ID do funcionário está na lista de quem foi marcado como PRESENTE
            if (in_array($id, $presentes_ids)) {
                // Registrar Presença (TRUE)
                if ($this->presencaModel->registrarPresenca($id, $hoje)) {
                    $sucessos++;
                } else {
                    $falhas++;
                }
            } else {
                // Se o funcionário NÃO foi marcado, remove a presença (marca como FALSE/Ausente)
                // Isso é importante para desmarcar um erro ou registrar uma falta
                if ($this->presencaModel->removerPresenca($id, $hoje)) {
                    // Aqui não contamos como sucesso de registro, mas sim de operação.
                } else {
                    $falhas++;
                }
            }
        }

        // Redireciona com feedback
        if ($falhas === 0) {
            $_SESSION['sucesso'] = "Chamada registrada com sucesso! ($sucessos presentes)";
        } else {
            $_SESSION['erro'] = "Chamada registrada, mas houve $falhas erros no processo.";
        }

        header('Location: /sgi_erp/presenca');
        exit();
    }
}
