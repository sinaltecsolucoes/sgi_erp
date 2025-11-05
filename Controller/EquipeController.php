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
        /*   if ($_SESSION['funcionario_tipo'] !== 'apontador') {
            $_SESSION['erro'] = 'Acesso negado. Apenas Apontadores podem montar equipes.';
            header('Location: /sgi_erp/dashboard');
            exit();
        }*/
    }

    /**
     * Exibe a interface para montar a equipe.
     * Rota: /equipes
     */
    /* public function index()
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
    } */


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

        // IMPORTANTE: Mudar o nome da View!
        $content_view = ROOT_PATH . 'View' . DS . 'equipes_abas.php';

        require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'main.php';
    }


    /**
     * Processa a criação e/ou associação de funcionários à equipe.
     * Rota: /equipes/salvar
     */
    /* public function salvar()
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
    } */


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
     * Processa a EDIÇÃO/Atualização de uma equipe existente (nome e membros).
     * Rota: /equipes/salvar
     */
    /*  public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/equipes');
            exit();
        }

        $equipe_id = filter_input(INPUT_POST, 'equipe_id', FILTER_VALIDATE_INT);
        $nome_equipe = isset($_POST['nome_equipe']) ? trim($_POST['nome_equipe']) : 'Equipe Padrão';

        // Membros: A lista de membros que está CHECKED é enviada com o nome membros[equipe_id][].
        // O Flutter/Web ainda precisa enviar o array correto, mas a lógica de remoção/adição é a mesma.
        $membros_selecionados_ids = isset($_POST['membros'][$equipe_id]) ? $_POST['membros'][$equipe_id] : [];

        if (!$equipe_id) {
            $_SESSION['erro'] = 'ID da equipe não encontrado para salvar.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        // 1. Atualiza o nome da equipe
        $this->equipeModel->atualizarNome($equipe_id, $nome_equipe);

        // 2. Remove todos os membros antigos
        $this->equipeModel->removerTodosFuncionarios($equipe_id);

        // 3. Associa os novos membros
        $sucessos = 0;
        foreach ($membros_selecionados_ids as $funcionario_id) {
            $id = (int)$funcionario_id;
            if ($this->equipeModel->associarFuncionario($equipe_id, $id)) {
                $sucessos++;
            }
        }

        $_SESSION['sucesso'] = "Equipe **{$nome_equipe}** atualizada com $sucessos membros.";
        header('Location: /sgi_erp/equipes');
        exit();
    } */


    /**
     * Processa a EDIÇÃO/Atualização de uma equipe existente.
     * Rota: /equipes/salvar
     */
    /* public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/equipes');
            exit();
        }

        $equipe_id = filter_input(INPUT_POST, 'equipe_id', FILTER_VALIDATE_INT);
        if (!$equipe_id) {
            $_SESSION['erro'] = 'ID da equipe não encontrado para salvar.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        $sucessos = 0;
        $erros = 0;
        $feedback_message = "";

        // NOVO: Busca o objeto da equipe atual para obter o nome original.
        $equipe_atual_obj = $this->equipeModel->buscarEquipePorId($equipe_id);
        $nome_equipe_atual = $equipe_atual_obj->nome ?? 'Equipe Desconhecida'; // Usar o nome do banco como padrão

        // 1. Determina o nome a ser usado/salvo (se veio do Modal de Edição, senão usa o nome do banco)
        $novo_nome = isset($_POST['nome_equipe']) ? trim($_POST['nome_equipe']) : $nome_equipe_atual;

        // ===============================================
        // CENÁRIO 1: REMOÇÃO DE MEMBROS (Vindo da aba principal)
        // O formulário da aba só envia 'equipe_id' e a lista final de membros (membros[equipe_id][])
        // ===============================================
        if (isset($_POST['remover_membros']) && !isset($_POST['is_modal_edicao'])) {

            // 1a. Renomeação: Se o nome mudou, atualiza no banco
            if ($novo_nome !== $nome_equipe_atual) {
                if ($this->equipeModel->atualizarNome($equipe_id, $novo_nome)) {
                    $feedback_message .= "Nome atualizado para '{$novo_nome}'. ";
                } else {
                    $erros++;
                }
            }

            $membros_selecionados_ids = isset($_POST['membros'][$equipe_id]) ? $_POST['membros'][$equipe_id] : [];

            // 2. Busca todos os membros atuais no banco
            $membros_atuais_objetos = $this->equipeModel->buscarFuncionariosDaEquipe($equipe_id);
            $membros_atuais_ids = array_map(fn($m) => (int)$m->id, $membros_atuais_objetos);

            $removidos = 0;

            // 3. Itera e remove apenas quem foi desmarcado no switch
            foreach ($membros_atuais_ids as $membro_id) {
                // Se o ID ATUAL do banco NÃO está na lista submetida (desmarcou o switch)
                if (!in_array($membro_id, $membros_selecionados_ids)) {
                    if ($this->equipeModel->removerFuncionarioDeEquipe($equipe_id, $membro_id)) {
                        $removidos++;
                    } else {
                        $erros++;
                    }
                }
            }

            if ($removidos > 0) {
                $feedback_message .= "Membros removidos da equipe com sucesso! ($removidos removidos).";
            } else {
                $feedback_message .= "Nenhuma alteração de membros detectada. ";
            }


            // ===============================================
            // CENÁRIO 2: ADIÇÃO/EDIÇÃO (Vindo do Modal de Edição)
            // ===============================================
        } elseif (isset($_POST['is_modal_edicao'])) {

            // A lógica de Renomear está correta aqui:
            if ($novo_nome !== $nome_equipe_atual) {
                if ($this->equipeModel->atualizarNome($equipe_id, $novo_nome)) {
                    $feedback_message .= "Nome atualizado para '{$novo_nome}'. ";
                } else {
                    $erros++;
                }
            }

            // Lógica de Adição: Adiciona novos membros (apenas os marcados na lista de disponíveis do modal)
            $membros_adicionar_ids = isset($_POST['membros_adicionar']) ? $_POST['membros_adicionar'] : [];

            $adicionados = 0;
            foreach ($membros_adicionar_ids as $funcionario_id) {
                $id = (int)$funcionario_id;
                if ($this->equipeModel->associarFuncionario($equipe_id, $id)) {
                    $adicionados++;
                } else {
                    $erros++;
                }
            }

            if ($adicionados > 0) {
                $feedback_message .= "{$adicionados} novos membros adicionados.";
            }
        } else {
            // Se cair aqui, é um formulário sem contexto, ou um erro de UX
            $_SESSION['erro'] = 'Erro: Formulário de edição submetido de forma inesperada. Tente novamente.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        // Feedback final
        if ($erros === 0) {
            $_SESSION['sucesso'] = trim($feedback_message) ? trim($feedback_message) : "Equipe salva com sucesso!";
        } else {
            $_SESSION['erro'] = "Erro(s) ao salvar alterações na equipe.";
        }

        header('Location: /sgi_erp/equipes');
        exit();
    } */

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
    /* public function mover()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sgi_erp/equipes');
            exit();
        }

        $funcionario_id = filter_input(INPUT_POST, 'funcionario_id', FILTER_VALIDATE_INT);
        $equipe_origem_id = filter_input(INPUT_POST, 'equipe_origem_id', FILTER_VALIDATE_INT);
        $equipe_destino_id = filter_input(INPUT_POST, 'equipe_destino_id', FILTER_VALIDATE_INT);

        // Simples validação de ID
        if (!$funcionario_id || !$equipe_origem_id || !$equipe_destino_id) {
            $_SESSION['erro'] = 'Erro: Dados de movimentação incompletos.';
            header('Location: /sgi_erp/equipes');
            exit();
        }

        // 1. Remove o funcionário da equipe de origem (EquipeModel::removerFuncionarioDeEquipe deve existir)
        $removido = $this->equipeModel->removerFuncionarioDeEquipe($equipe_origem_id, $funcionario_id);

        // 2. Adiciona o funcionário à equipe de destino
        $adicionado = $this->equipeModel->associarFuncionario($equipe_destino_id, $funcionario_id);

        if ($removido && $adicionado) {
            $_SESSION['sucesso'] = 'Funcionário movido com sucesso!';
        } else {
            $_SESSION['erro'] = 'Erro ao mover funcionário. Verifique as associações.';
        }

        header('Location: /sgi_erp/equipes');
        exit();
    } */

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
