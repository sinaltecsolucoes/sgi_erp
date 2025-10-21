<?php
// Controller/RelatorioController.php

class RelatorioController extends AppController
{
    private $relatorioModel;

    public function __construct()
    {
        parent::__construct();
        $this->relatorioModel = new RelatorioModel();

        // Regra: A rota é protegida pelo ACL no index.php
        // A checagem para 'RelatorioController@pagamentos' deve estar no Acl.php
    }

    /**
     * Exibe o relatório de pagamento por produtividade.
     * Rota: /relatorios (ou /relatorios/pagamentos)
     * Permitido: Admin, Financeiro (checa no ACL)
     */
    public function pagamentos()
    {
        // 1. Define o período padrão (Mês atual)
        $hoje = new DateTime();
        $data_inicio_padrao = $hoje->format('Y-m-01');
        $data_fim_padrao = $hoje->format('Y-m-t'); // 't' para o último dia do mês

        // 2. Coleta o filtro do usuário (via GET)
        $data_inicio = filter_input(INPUT_GET, 'data_inicio', FILTER_SANITIZE_STRING) ?? $data_inicio_padrao;
        $data_fim = filter_input(INPUT_GET, 'data_fim', FILTER_SANITIZE_STRING) ?? $data_fim_padrao;

        $relatorio = [];
        $erro = '';

        // 3. Validação Básica
        if (strtotime($data_inicio) > strtotime($data_fim)) {
            $erro = 'A data inicial não pode ser maior que a data final.';
        } else {
            // 4. Executa o Cálculo no Model
            $relatorio = $this->relatorioModel->calcularPagamentoPorFuncionario($data_inicio, $data_fim);

            if (empty($relatorio) && empty($erro)) {
                $erro = 'Nenhum lançamento de produção encontrado para o período selecionado.';
            }
        }

        // 5. Preparar dados para a View
        $dados = [
            'relatorio' => $relatorio,
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'erro' => $erro
        ];

        $title = "Relatório de Pagamento";

        $content_view = ROOT_PATH . 'View' . DS . 'relatorio_pagamento.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    // Futuramente, outros métodos como 'producaoGeral' ou 'servicosExtras' viriam aqui.
}
