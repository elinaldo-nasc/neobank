<?php
require_once 'autoload.php';
date_default_timezone_set('America/Sao_Paulo');

$auth = new Autenticacao();

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ./index.php');
    exit;
}

if (!$auth->isLoggedIn()) {
    header('Location: ./index.php');
    exit;
}
$manager = new ContaManager();
$nome = $auth->getNome();
$username = $auth->getUsername();

// Exportar CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    try {
        $contas = $manager->listarPorUsuario($username);
        $caixinhas = $manager->listarCaixinhasPorUsuario($username);
        
        // Headers para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_financeiro_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Gerar e enviar CSV direto
        $relatorio = new GeradorDeRelatorio(new ContaPoupanca($nome, '000000'));
        $relatorio->exportarCSV($contas, $caixinhas, $nome);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao gerar relatório: ' . $e->getMessage();
        header('Location: dashboard.php');
        exit;
    }
}

// Exportar PDF
if (isset($_GET['action']) && $_GET['action'] === 'export_pdf') {
    try {
        $contas = $manager->listarPorUsuario($username);
        $caixinhas = $manager->listarCaixinhasPorUsuario($username);
        
        // Header para HTML
        header('Content-Type: text/html; charset=utf-8');
        
        // Gerar HTML para impressão
        $relatorio = new GeradorDeRelatorio(new ContaPoupanca($nome, '000000'));
        $relatorio->exportarPDF($contas, $caixinhas, $nome);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao gerar relatório: ' . $e->getMessage();
        header('Location: dashboard.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'depositar') {
            $numero = $_POST['numero'] ?? '';
            $valor = floatval($_POST['valor'] ?? 0);
            
            // Validações
            if (empty($numero)) {
                throw new Exception('Número da conta não informado!');
            }
            if ($valor <= 0) {
                throw new Exception('Valor do depósito deve ser maior que zero!');
            }
            
            $conta = $manager->buscar($numero);
            if (!$conta) {
                throw new Exception('Conta não encontrada!');
            }
            if ($conta->getTitular() !== $nome) {
                throw new Exception('Você não tem permissão para acessar esta conta!');
            }
            
            $conta->depositar($valor);
            $manager->atualizar($numero, $conta->getSaldo());
            $_SESSION['success'] = 'Depósito realizado!';
            header('Location: dashboard.php');
            exit;
        }
        elseif ($action === 'sacar') {
            $numero = $_POST['numero'] ?? '';
            $valor = floatval($_POST['valor'] ?? 0);
            
            // Validações
            if (empty($numero)) {
                throw new Exception('Número da conta não informado!');
            }
            if ($valor <= 0) {
                throw new Exception('Valor do saque deve ser maior que zero!');
            }
            
            $conta = $manager->buscar($numero);
            if (!$conta) {
                throw new Exception('Conta não encontrada!');
            }
            if ($conta->getTitular() !== $nome) {
                throw new Exception('Você não tem permissão para acessar esta conta!');
            }
            
            // Verificar se tem saldo suficiente
            if ($valor > $conta->getSaldo()) {
                throw new Exception('Saldo insuficiente! Saldo atual: R$ ' . number_format($conta->getSaldo(), 2, ',', '.'));
            }
            
            $conta->sacar($valor);
            $manager->atualizar($numero, $conta->getSaldo());
            $_SESSION['success'] = 'Saque realizado!';
            header('Location: dashboard.php');
            exit;
        }
        elseif ($action === 'criar_caixinha') {
            $tipoCaixinha = $_POST['tipo_caixinha'] ?? '';
            
            // Validações
            if (empty($tipoCaixinha)) {
                throw new Exception('Selecione um tipo de caixinha!');
            }
            
            if ($manager->criarCaixinha($nome, $tipoCaixinha)) {
                $_SESSION['success'] = 'Caixinha criada com sucesso!';
                header('Location: dashboard.php');
                exit;
            } else {
                throw new Exception('Não foi possível criar a caixinha. Você já pode ter uma deste tipo!');
            }
        }
        elseif ($action === 'excluir_caixinha') {
            $numero = $_POST['numero'] ?? '';
            
            // Validações
            if (empty($numero)) {
                throw new Exception('Número da caixinha não informado!');
            }
            
            $conta = $manager->buscar($numero);
            if (!$conta) {
                throw new Exception('Caixinha não encontrada!');
            }
            if ($conta->getTitular() !== $nome) {
                throw new Exception('Você não tem permissão para excluir esta caixinha!');
            }
            if ($conta->getSaldo() > 0) {
                throw new Exception('Não é possível excluir uma caixinha com saldo! Resgate todo o dinheiro primeiro.');
            }
            
            if ($manager->deletar($numero)) {
                $_SESSION['success'] = 'Caixinha excluída com sucesso!';
                header('Location: dashboard.php');
                exit;
            } else {
                throw new Exception('Erro ao excluir caixinha!');
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: dashboard.php');
        exit;
    }
}
$contas = $manager->listarPorUsuario($username);
$caixinhas = $manager->listarCaixinhasPorUsuario($username);

// Mensagens via sessão
$message = '';
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    unset($_SESSION['success']); // Remove após ler
}

// Erro via sessão
$error = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']); // Remove após ler
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NEO Bank</title>
    <meta name="description" content="Painel de controle do sistema bancário">
    <link rel="stylesheet" href="css/dashboard.css?v=2">
</head>
<body class="dashboard-container">
    <header class="dashboard-nav" role="banner">
        <div class="nav-container">
            <div class="nav-brand">
                <span class="nav-logo" aria-label="Logo do banco">🏦</span>
                <span class="nav-title">NEO Bank</span>
            </div>
            <nav class="nav-user" role="navigation" aria-label="Menu do usuário">
                <span class="nav-username" aria-label="Usuário logado">👨‍💼 <?= htmlspecialchars($nome) ?></span>
                <a href="?logout=1" class="logout-button" aria-label="Fazer logout">Sair</a>
            </nav>
        </div>
    </header>
    <main class="dashboard-main" role="main">
        <?php if ($message): ?>
        <div id="success-toast" class="toast success" style="display: block;">
            ✅ <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div id="error-toast" class="toast error" style="display: block;">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <section class="dashboard-top-layout">
            <div class="stats-column">
                <article class="stat-card green">
                    <h3 class="stat-title">Saldo Total</h3>
                    <p class="stat-value">R$ <?= number_format(array_sum(array_column($contas, 'saldo')) + array_sum(array_column($caixinhas, 'saldo')), 2, ',', '.') ?></p>
                </article>
                <article class="stat-card orange">
                    <h3 class="stat-title">Limite de Saque</h3>
                    <p class="stat-value">R$ <?= number_format(ContaBancaria::mostrarLimite(), 2, ',', '.') ?></p>
                </article>
            </div>
            
            <!-- Seção Criar Caixinhas -->
            <section class="create-box-section">
                <h3 class="create-box-title">📦 Criar Caixinhas</h3>
                <form method="POST" class="create-box-form">
                    <input type="hidden" name="action" value="criar_caixinha">
                    <select name="tipo_caixinha" required class="form-input">
                        <option value="">Selecione um objetivo...</option>
                        <option value="Reserva de Emergência">💰 Reserva de Emergência</option>
                        <option value="Fazer uma viagem">✈️ Fazer uma viagem</option>
                        <option value="Reformar a Casa">🏠 Reformar a Casa</option>
                        <option value="Focar na carreira">📚 Focar na carreira</option>
                    </select>
                    <button type="submit" class="submit-button">➕ Criar Caixinha</button>
                </form>
            </section>
            
            <!-- Seção de Exportação -->
            <div class="export-column">
                <a href="?action=export_csv" class="stat-card export-card csv">
                    <h3 class="stat-title">📄 Exportar CSV</h3>
                </a>
                <a href="?action=export_pdf" target="_blank" class="stat-card export-card pdf">
                    <h3 class="stat-title">📊 Exportar PDF</h3>
                </a>
            </div>
        </section>
        
        <div class="section-header">
            <h2 class="section-title">Minhas Contas</h2>
        </div>
        <?php if (empty($contas)): ?>
        <p class="empty-state">Nenhuma conta cadastrada.</p>
        <?php else: ?>
        <section class="accounts-section">
                <?php foreach ($contas as $conta): ?>
                <article class="account-card">
                    <div class="account-header">
                        <span class="account-type"><?= $conta['tipo'] === 'ContaPoupanca' ? '🏦 Poupança' : '💳 Corrente' ?></span>
                        <p class="account-number"><?= htmlspecialchars($conta['numero']) ?></p>
                    </div>
                    <p class="account-balance">R$ <?= number_format($conta['saldo'], 2, ',', '.') ?></p>
                    <div class="account-actions">
                        <form method="POST" class="action-form">
                            <input type="hidden" name="action" value="depositar">
                            <input type="hidden" name="numero" value="<?= $conta['numero'] ?>">
                            <input type="number" step="0.05" name="valor" placeholder="0,00" required class="action-input">
                            <button class="action-button deposit">💰 Depositar</button>
                            <?php if ($conta['tipo'] === 'ContaPoupanca'): ?>
                            <div class="taxa-badge">📈 Taxa: <?= $conta['taxa_rendimento'] ?>%</div>
                            <?php endif; ?>
                        </form>
                        <form method="POST" class="action-form">
                            <input type="hidden" name="action" value="sacar">
                            <input type="hidden" name="numero" value="<?= $conta['numero'] ?>">
                            <input type="number" step="0.05" name="valor" placeholder="0,00" required class="action-input">
                            <button class="action-button withdraw">💸 Sacar</button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
                
                <!-- Exibir Caixinhas -->
                <?php foreach ($caixinhas as $caixinha): 
                    $icones = [
                        'Reserva de Emergência' => '💰',
                        'Fazer uma viagem' => '✈️',
                        'Reformar a Casa' => '🏠',
                        'Focar na carreira' => '📚'
                    ];
                    $icone = $icones[$caixinha['tipo_caixinha']] ?? '📦';
                ?>
                <article class="account-card caixinha-card">
                    <div class="account-header">
                        <span class="account-type"><?= $icone ?> <?= htmlspecialchars($caixinha['tipo_caixinha']) ?></span>
                        <p class="account-number"><?= htmlspecialchars($caixinha['numero']) ?></p>
                    </div>
                    <p class="account-balance">R$ <?= number_format($caixinha['saldo'], 2, ',', '.') ?></p>
                    <div class="account-actions">
                        <form method="POST" class="action-form">
                            <input type="hidden" name="action" value="depositar">
                            <input type="hidden" name="numero" value="<?= $caixinha['numero'] ?>">
                            <input type="number" step="0.05" name="valor" placeholder="0,00" required class="action-input">
                            <button class="action-button deposit">💰 Depositar</button>
                        </form>
                        <form method="POST" class="action-form">
                            <input type="hidden" name="action" value="sacar">
                            <input type="hidden" name="numero" value="<?= $caixinha['numero'] ?>">
                            <input type="number" step="0.05" name="valor" placeholder="0,00" required class="action-input">
                            <button class="action-button withdraw">💸 Sacar</button>
                        </form>
                        <form method="POST" class="action-form" onsubmit="return confirm('Tem certeza que deseja excluir esta caixinha?');">
                            <input type="hidden" name="action" value="excluir_caixinha">
                            <input type="hidden" name="numero" value="<?= $caixinha['numero'] ?>">
                            <button class="action-button delete">🗑️ Excluir</button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
        </section>
        <?php endif; ?>
    </main>
    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>
    <script>
        // Sistema de Toast
        const toastContainer = document.getElementById('toastContainer');
        
        function showToast(type, title, message) {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icon = type === 'success' ? '✅' : '❌';
            
            toast.innerHTML = `
                <div class="toast-icon">${icon}</div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="removeToast(this)">&times;</button>
                <div class="toast-progress">
                    <div class="toast-progress-bar"></div>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Animação de entrada
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            // Auto-remove após 2 segundos
            setTimeout(() => {
                removeToast(toast.querySelector('.toast-close'));
            }, 2000);
        }
        
        function removeToast(button) {
            const toast = button.closest('.toast');
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }

        // Auto-ocultar toasts após 4 segundos
        setTimeout(() => {
            const successToast = document.getElementById('success-toast');
            const errorToast = document.getElementById('error-toast');
            
            if (successToast) {
                successToast.style.transition = 'opacity 0.5s';
                successToast.style.opacity = '0';
                setTimeout(() => successToast.remove(), 500);
            }
            
            if (errorToast) {
                errorToast.style.transition = 'opacity 0.5s';
                errorToast.style.opacity = '0';
                setTimeout(() => errorToast.remove(), 500);
            }
        }, 4000);
    </script>
    
    <footer class="dashboard-footer" role="contentinfo">
        <p>&copy; <?= date('Y') ?> NEO Bank - Sistema Bancário POO | Desenvolvido com PHP</p>
    </footer>
</body>
</html>