<?php
// Controller/RelatorioController.php

class RelatorioController extends AppController
{
    private $relatorioModel;

    public function __construct()
    {
        parent::__construct();
        $this->relatorioModel = new RelatorioModel();

        // A ACL no index.php protege todos os métodos deste Controller
        // para usuários 'admin' e 'financeiro'.
    }

    /**
     * Coleta as datas do filtro e chama o Model para processar todos os dados.
     * @return array [data_inicio, data_fim, relatorio_dados, erro]
     */
    private function coletarDadosRelatorio()
    {
        // Lógica de coleta de datas
        $hoje = new DateTime();
        $data_inicio_padrao = $hoje->format('Y-m-01');
        $data_fim_padrao = $hoje->format('Y-m-t');

        $data_inicio = filter_input(INPUT_GET, 'data_inicio', FILTER_SANITIZE_STRING) ?? $data_inicio_padrao;
        $data_fim = filter_input(INPUT_GET, 'data_fim', FILTER_SANITIZE_STRING) ?? $data_fim_padrao;

        $erro = '';
        $relatorio_dados = [];

        if (strtotime($data_inicio) > strtotime($data_fim)) {
            $erro = 'A data inicial não pode ser maior que a data final.';
        } else {
            // Chama o método unificado do Model para calcular TUDO
            $relatorio_dados = $this->relatorioModel->gerarRelatorioCompleto($data_inicio, $data_fim);

            if (empty($relatorio_dados['producao']) && empty($relatorio_dados['servicos_apoio']) && empty($erro)) {
                $erro = 'Nenhum lançamento encontrado para o período selecionado.';
            }
        }

        return [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'relatorio_dados' => $relatorio_dados,
            'erro' => $erro
        ];
    }

    /**
     * Combina os detalhes de dois relatórios (produção e serviços) somando os totais.
     * @param array $relatorio1 (Ex: Producao)
     * @param array $relatorio2 (Ex: Servicos)
     * @return array Relatório final combinado.
     */
    private function combinarRelatorios(array $relatorio1, array $relatorio2)
    {
        $final = $relatorio1;

        foreach ($relatorio2 as $func_id => $data) {
            if (isset($final[$func_id])) {
                // O funcionário já existe na primeira array, então somamos
                $final[$func_id]['total_a_pagar'] += $data['total_a_pagar'];
                // Fazemos o merge dos detalhes (registros)
                $final[$func_id]['detalhes'] = array_merge($final[$func_id]['detalhes'], $data['detalhes']);
            } else {
                // O funcionário só tem serviço, então adicionamos
                $final[$func_id] = $data;
            }
        }
        return $final;
    }

    /**
     * R02: Exibe o relatório de pagamento total (Produtividade + Serviços).
     * Rota: /relatorios (Rota principal de pagamentos)
     */
    public function pagamentos()
    {
        $dados_filtro = $this->coletarDadosRelatorio();
        $relatorio_dados = $dados_filtro['relatorio_dados'];
        $visualizacao = filter_input(INPUT_GET, 'visualizacao', FILTER_SANITIZE_STRING) ?? 'sintetico';

        $producao = $relatorio_dados['producao'] ?? [];
        $servicos = $relatorio_dados['servicos_apoio'] ?? [];

        $relatorio_final = $this->combinarRelatorios($producao, $servicos);

        // IDs de funcionário mantidos
        $relatorio_final_indexado = array_values($relatorio_final);

        $dados = [
            'relatorio' => $relatorio_final_indexado,
            'visualizacao' => $visualizacao,
            'tipo_relatorio' => 'Pagamento por Produtividade',
            'titulo_relatorio' => 'Relatório de Pagamento por Produtividade',
            'coluna_principal' => 'Valores (R$)',
            'coluna_detalhe' => 'Valor',
            'incluir_horas' => true,
            'erro' => $dados_filtro['erro'],
            'data_inicio' => $dados_filtro['data_inicio'],
            'data_fim' => $dados_filtro['data_fim']
        ];

        $title = "Pagamento Total";
        $content_view = ROOT_PATH . 'View' . DS . 'relatorio_geral.php'; // View unificada
        

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * R01: Exibe o relatório de quantidades produzidas (Apenas Produção Rastreável).
     * Rota: /relatorios/quantidades
     */
    public function quantidades()
    {
        $dados_filtro = $this->coletarDadosRelatorio();
        $relatorio_dados = $dados_filtro['relatorio_dados'];

        $dados = [
            'relatorio' => $relatorio_dados['producao'] ?? [],
            'tipo_relatorio' => 'Quantidades de Produção',
            'titulo_relatorio' => 'Relatório de Quantidades Produtividade',
            'coluna_principal' => 'Quant. (Kg)',
            'coluna_detalhe' => 'Quant. (kg)',
            'incluir_horas' => true,
            'erro' => $dados_filtro['erro'],
            'data_inicio' => $dados_filtro['data_inicio'],
            'data_fim' => $dados_filtro['data_fim']
        ];

        $title = "Relatório de Quantidades";
        $content_view = ROOT_PATH . 'View' . DS . 'relatorio_quantidades.php'; // View específica

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * R03: Exibe o relatório de serviços/diárias (Apenas Serviços de Apoio).
     * Rota: /relatorios/servicos
     */
    public function servicos()
    {
        $dados_filtro = $this->coletarDadosRelatorio();
        $relatorio_dados = $dados_filtro['relatorio_dados'];

        $dados = [
            'relatorio' => $relatorio_dados['servicos_apoio'] ?? [],
            'tipo_relatorio' => 'Serviços e Diárias (Apoio)',
            'titulo_relatorio' => 'Relatório de Serviços - Diárias',
            'coluna_principal' => 'Valores (R$)',
            'coluna_detalhe' => 'Valor',
            'incluir_horas' => true,
            'erro' => $dados_filtro['erro'],
            'data_inicio' => $dados_filtro['data_inicio'],
            'data_fim' => $dados_filtro['data_fim']
        ];

        $title = "Relatório de Serviços";
        $content_view = ROOT_PATH . 'View' . DS . 'relatorio_servicos.php'; // View específica

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * R04: Exibe o relatório de Produtividade (Kg/Hora).
     * Rota: /relatorios/produtividade
     */
    public function produtividade()
    {
        $dados_filtro = $this->coletarDadosRelatorio();
        $relatorio_dados = $dados_filtro['relatorio_dados'];

        $dados = [
            'relatorio' => $relatorio_dados['analise_produtividade'] ?? [],
            'tipo_relatorio' => 'Produtividade (Kg/Hora)',
            'titulo_relatorio' => 'Análise de Produtividade por Hora',
            // ... (Restante dos dados de filtro e erro)
        ];

        $title = "Produtividade/Hora";
        $content_view = ROOT_PATH . 'View' . DS . 'relatorio_produtividade.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Exibe o relatório de pagamento com um layout otimizado para impressão/PDF.
     * Rota: /relatorios/imprimir
     */
    public function imprimir()
    {
        // Coleta os mesmos dados do filtro
        $dados_filtro = $this->coletarDadosRelatorio();
        $relatorio_dados = $dados_filtro['relatorio_dados'];
        
        // Coleta o modo de visualização da URL de impressão
        $visualizacao = filter_input(INPUT_GET, 'visualizacao', FILTER_SANITIZE_STRING) ?? 'sintetico';

        // Combina Produção e Serviços
        $producao = $relatorio_dados['producao'] ?? [];
        $servicos = $relatorio_dados['servicos_apoio'] ?? [];
        $relatorio_final = $this->combinarRelatorios($producao, $servicos);

        $dados = [
            'relatorio' => array_values($relatorio_final),
            'visualizacao' => $visualizacao, // Passa o modo de visualização
            
        ];

        // **IMPORTANTE:** Incluir uma view customizada SEM o main.php
        require_once ROOT_PATH . 'View' . DS . 'relatorio_imprimir.php';
    }
}
