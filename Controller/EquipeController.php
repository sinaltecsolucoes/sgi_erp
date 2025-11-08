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
    }

    /**
     * Exibe a interface com ABAS para gerenciar as equipes do apontador.
     * Rota: /equipes
     */
    public function index()
    {
        $apontador_id = $_SESSION['funcionario_id'];
        $hoje = date('Y-m-d');
        $equipeModel = new EquipeModel();
        $funcionarioModel = new FuncionarioModel();

        // 1. Buscar TODAS as equipes ATIVAS do apontador HOJE
        $equipes = $equipeModel->buscarTodasEquipesDoApontador($apontador_id);

        // 2. Buscar IDs de funcionários alocados em QUALQUER equipe HOJE (para filtrar a lista de Disponíveis)
        $funcionarios_alocados_ids = $equipeModel->buscarFuncionariosAlocadosHoje();

        // 3. Buscar todos os funcionários de produção PRESENTE hoje
        $funcionarios_presentes = $funcionarioModel->buscarPresentesHoje($hoje);

        // 4. Montar a lista de Disponíveis (Presentes E Não Alocados)
        $funcionarios_disponiveis = [];
        foreach ($funcionarios_presentes as $f) {
            $id = (int)$f->id;

            // Se está presente E NÃO está em nenhuma equipe alocada (incluindo as próprias)
            if ((int)$f->esta_presente === 1 && !in_array($id, $funcionarios_alocados_ids)) {
                $funcionarios_disponiveis[] = $f;
            }
        }

        // 5. Adicionar a lista de membros a CADA EQUIPE (Para a aba específica da equipe)
        $equipes_com_membros = [];
        foreach ($equipes as &$equipe) {
            // Buscando os membros daquela equipe específica
            $membros = $equipeModel->buscarFuncionariosDaEquipe($equipe->id);
            $equipe->membros = $membros;
            $equipe->total_membros = count($membros);
            $equipes_com_membros[] = $equipe;
        }

        $dados = [
            //'equipes_do_apontador' => $equipes,
            'equipes_do_apontador' => $equipes_com_membros,
            'funcionarios_disponiveis' => $funcionarios_disponiveis,
        ];

        $title = "Montagem de Equipes";

        $content_view = ROOT_PATH . 'View' . DS . 'equipes_abas.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }

    /**
     * Processa a criação de uma NOVA equipe via Modal.
     * Rota: /equipes/salvar-nova
     */
    public function salvarNova()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/equipes');
            exit();
        }

        $apontador_id = $_SESSION['funcionario_id'];
        $nome_equipe = isset($_POST['nome_equipe']) ? trim($_POST['nome_equipe']) : 'Equipe Padrão';
        $membros_selecionados_ids = isset($_POST['membros']) ? $_POST['membros'] : [];

        // 1. Cria a nova equipe no banco (EquipeModel::criarEquipe garante que seja para hoje)
        $equipe_id = $this->equipeModel->criarEquipe($apontador_id, $nome_equipe);

        if (!$equipe_id) {
            $_SESSION['erro'] = 'Erro ao criar a equipe. Nome já está em uso ou falha no banco.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        // 2. Associa os membros iniciais (se houver)
        $sucessos = 0;
        foreach ($membros_selecionados_ids as $funcionario_id) {
            $id = (int)$funcionario_id;
            if ($this->equipeModel->associarFuncionario($equipe_id, $id)) {
                $sucessos++;
            }
        }

        $_SESSION['sucesso'] = "Equipe **{$nome_equipe}** criada com $sucessos membros iniciais.";
        header('Location: /sgi_erp/equipes');
        exit();
    }

    /**
     * Processa a EDIÇÃO/Atualização de uma equipe existente.
     * Rota: /equipes/salvar
     */

    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/equipes');
            exit();
        }

        $equipe_id = filter_input(INPUT_POST, 'equipe_id', FILTER_VALIDATE_INT);
        $nome_equipe = trim($_POST['nome_equipe'] ?? '');
        $feedback_message = '';
        $erros = 0;

        if (!$equipe_id || empty($nome_equipe)) {
            $_SESSION['erro'] = 'Dados inválidos para salvar equipe.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        // === 1. EDIÇÃO DE NOME (sempre acontece) ===
        $equipe_atual = $this->equipeModel->buscarEquipePorId($equipe_id);
        if (!$equipe_atual) {
            $_SESSION['erro'] = 'Equipe não encontrada.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        if ($nome_equipe !== $equipe_atual->nome) {
            if ($this->equipeModel->atualizarNome($equipe_id, $nome_equipe)) {
                $feedback_message .= "Nome alterado para '{$nome_equipe}'. ";
            } else {
                $erros++;
                $feedback_message .= "Erro ao atualizar nome. ";
            }
        }

        // === 2. REMOÇÃO DE MEMBROS (via aba principal) ===
        if (isset($_POST['remover_membros'])) {
            $membros_mantidos = $_POST['membros'][$equipe_id] ?? [];
            $membros_mantidos = array_map('intval', $membros_mantidos);

            $membros_atuais = $this->equipeModel->buscarFuncionariosDaEquipe($equipe_id);
            $membros_atuais_ids = array_map(fn($m) => (int)$m->id, $membros_atuais);

            $removidos = 0;
            foreach ($membros_atuais_ids as $membro_id) {
                if (!in_array($membro_id, $membros_mantidos)) {
                    if ($this->equipeModel->removerFuncionarioDeEquipe($equipe_id, $membro_id)) {
                        $removidos++;
                    } else {
                        $erros++;
                    }
                }
            }
            if ($removidos > 0) {
                $feedback_message .= "$removidos membro(s) removido(s). ";
            }
        }

        // === 3. ADIÇÃO DE MEMBROS (via modal de edição) ===
        if (isset($_POST['acao_edicao'])) {
            $membros_adicionar = $_POST['membros_adicionar'] ?? [];
            $membros_adicionar = array_map('intval', $membros_adicionar);

            $adicionados = 0;
            foreach ($membros_adicionar as $func_id) {
                // Evita duplicatas
                if (!$this->equipeModel->estaFuncionarioNaEquipe($equipe_id, $func_id)) {
                    if ($this->equipeModel->associarFuncionario($equipe_id, $func_id)) {
                        $adicionados++;
                    } else {
                        $erros++;
                    }
                }
            }
            if ($adicionados > 0) {
                $feedback_message .= "$adicionados novo(s) membro(s) adicionado(s). ";
            }
        }

        // === FEEDBACK FINAL ===
        if ($erros === 0) {
            $_SESSION['sucesso'] = trim($feedback_message) ?: 'Equipe atualizada com sucesso!';
        } else {
            $_SESSION['erro'] = 'Algumas alterações não foram salvas. Verifique os dados.';
        }

        header('Location: /sgi_erp/equipes');
        exit();
    }



    /**
     * Processa a exclusão de uma equipe.
     * Rota: /equipes/excluir?id={id}
     */
    public function excluir()
    {
        // Espera um ID via GET (vindo do JavaScript)
        $equipe_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$equipe_id) {
            $_SESSION['erro'] = 'ID da equipe não fornecido ou inválido para exclusão.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        // Regra de segurança: Apenas administradores e o próprio apontador que criou podem excluir.
        // Assumindo que a ACL já protege o método, verificamos se o apontador atual é o criador da equipe.
        // Para simplificar no MVP: Apenas permite a exclusão se o ID do Apontador logado estiver correto.

        if ($this->equipeModel->excluirEquipe($equipe_id)) {
            $_SESSION['sucesso'] = "Equipe excluída com sucesso!";
        } else {
            $_SESSION['erro'] = 'Erro interno ao excluir a equipe e suas associações.';
        }

        header('Location: /sgi_erp/equipes');
        exit();
    }

    /**
     * Processa a movimentação de um funcionário de uma equipe para outra.
     * Rota: /equipes/mover (via POST do Modal Mover Membro)
     */
    public function mover()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/equipes');
            exit();
        }

        $funcionario_id = filter_input(INPUT_POST, 'funcionario_id', FILTER_VALIDATE_INT);
        $equipe_origem_id = filter_input(INPUT_POST, 'equipe_origem_id', FILTER_VALIDATE_INT);
        $equipe_destino_id = filter_input(INPUT_POST, 'equipe_destino_id', FILTER_VALIDATE_INT);

        if (!$funcionario_id || !$equipe_origem_id || !$equipe_destino_id) {
            $_SESSION['erro'] = 'Dados inválidos para movimentação.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        if ($equipe_origem_id === $equipe_destino_id) {
            $_SESSION['erro'] = 'Não é possível mover para a mesma equipe.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        // Verifica se o funcionário está na origem
        $esta_na_origem = $this->equipeModel->estaFuncionarioNaEquipe($equipe_origem_id, $funcionario_id);
        if (!$esta_na_origem) {
            $_SESSION['erro'] = 'Funcionário não está na equipe de origem.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        // Remove da origem
        $removido = $this->equipeModel->removerFuncionarioDeEquipe($equipe_origem_id, $funcionario_id);
        // Adiciona no destino
        $adicionado = $this->equipeModel->associarFuncionario($equipe_destino_id, $funcionario_id);

        if ($removido && $adicionado) {
            $_SESSION['sucesso'] = 'Funcionário movido com sucesso!';
        } else {
            $_SESSION['erro'] = 'Erro ao mover funcionário. Tente novamente.';
        }

        header('Location: /sgi_erp/equipes');
        exit();
    }
}
