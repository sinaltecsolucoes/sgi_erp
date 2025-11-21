<?php
// Controller/ServicosExtrasController.php

class ServicosExtrasController extends AppController
{
    private $servicoModel;
    private $funcionarioModel;
    private $acaoModel;
    private $tipoProduto;

    public function __construct()
    {
        parent::__construct();
        $this->servicoModel = new ServicosExtrasModel();
        $this->funcionarioModel = new FuncionarioModel();
        $this->acaoModel = new AcaoModel();
        $this->tipoProduto = new TipoProdutoModel();
    }

    public function index()
    {
        //$funcionarios = $this->funcionarioModel->buscarTodosComPresencaHoje(); //Apenas funcionarios do tipo Produção
        $funcionarios = $this->funcionarioModel->buscarApenasPresentesHoje();
        // $funcionarios = $this->funcionarioModel->buscarTodos(); // Todos os funcionarios ativos inclusive ADM/FINANCEIRO
        $acoes = $this->acaoModel->buscarTodas();
        $servicos     = $this->tipoProduto->buscarSemLote();

        $title = "Lançamento de Diárias e Serviços Extras";
        $content_view = ROOT_PATH . 'View/servicos_extras_lancamento.php';

        require_once ROOT_PATH . 'View/template/main.php';
    }

    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/servicos-extras');
            exit;
        }

        $acao_id   = filter_input(INPUT_POST, 'acao_id', FILTER_VALIDATE_INT);
        $descricao = trim($_POST['descricao'] ?? '');

        if (!$acao_id || empty($descricao)) {
            $_SESSION['erro'] = 'Ação e tipo de serviço são obrigatórios.';
            header('Location: /sgi_erp/servicos-extras');
            exit;
        }

        $valores = $_POST['valores'] ?? [];
        $salvos = 0;
        $com_valor = 0;

        foreach ($valores as $funcionario_id => $valor_str) {
            // Limpa formatação brasileira
            $valor_str = trim($valor_str);
            if ($valor_str === '' || $valor_str === '0,00' || $valor_str === '0.00') {
                continue; // pula zeros e vazios
            }

            $valor_limpo = str_replace(['.', ' '], '', $valor_str);
            $valor_limpo = str_replace(',', '.', $valor_limpo);
            $valor = round(floatval($valor_limpo), 2);

            if ($valor <= 0) {
                continue;
            }

            $com_valor++;

            if ($this->servicoModel->registrar((int)$funcionario_id, $descricao, $valor, $acao_id)) {
                $salvos++;
            }
        }

        // Mensagens de aviso
        if ($salvos > 0) {
            $_SESSION['sucesso'] = "$salvos valor(es) lançado(s) com sucesso!";
        } elseif ($com_valor > 0) {
            $_SESSION['erro'] = "Erro ao salvar no banco. Verifique os logs.";
        } else {
            $_SESSION['info'] = "Nenhum valor maior que zero foi informado. Nada foi salvo.";
        }

        header('Location: /sgi_erp/servicos-extras');
        exit;
    }
}
