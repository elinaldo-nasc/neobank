<?php
session_start();
require_once 'autoload.php';

// Funções de formatação e limpeza
function limparNumeros($valor) {
    return preg_replace('/\D/', '', $valor); // Remove tudo que não é dígito
}

function formatarCPF($cpf) {
    $cpf = limparNumeros($cpf);
    if (strlen($cpf) == 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
    return $cpf;
}

function formatarTelefone($telefone) {
    $telefone = limparNumeros($telefone);
    if (strlen($telefone) == 11) {
        // Celular: (81) 98479-2068
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
    } elseif (strlen($telefone) == 10) {
        // Fixo: (81) 3479-2068
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
    }
    return $telefone;
}

function validarCPF($cpf) {
    $cpf = limparNumeros($cpf);
    return strlen($cpf) == 11;
}

function validarTelefone($telefone) {
    $telefone = limparNumeros($telefone);
    return strlen($telefone) == 10 || strlen($telefone) == 11;
}

function formatarNumeroConta($numero) {
    $numero = limparNumeros($numero);
    if (strlen($numero) >= 2) {
        // Formato: 12345-6 (últimos dígito é o verificador)
        $base = substr($numero, 0, -1);
        $verificador = substr($numero, -1);
        return $base . '-' . $verificador;
    }
    return $numero;
}

function validarNumeroConta($numero) {
    $numero = limparNumeros($numero);
    return strlen($numero) >= 6 && strlen($numero) <= 8;
}

$auth = new Autenticacao();

// Se já estiver logado, redireciona para o dashboard
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        try {
            $numeroContaRaw = trim($_POST['numero_conta'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validação básica
            if (empty($numeroContaRaw) || empty($password)) {
                throw new Exception('Por favor, preencha todos os campos!');
            }
            
            // Limpar e formatar número da conta para comparação
            $numeroContaLimpo = limparNumeros($numeroContaRaw);
            $numeroContaFormatado = formatarNumeroConta($numeroContaLimpo);
            
            // Login com número de conta formatado
            if ($auth->login($numeroContaFormatado, $password)) {
                header('Location: ./dashboard.php');
                exit;
            }
            
            throw new Exception('Número da conta ou senha inválidos!');
            
        } catch (Exception $e) {
            $_SESSION['login_error'] = $e->getMessage();
            header('Location: ./index.php');
            exit;
        }
    } elseif (isset($_POST['register'])) {
        try {
            // Receber e limpar dados
            $nome = trim($_POST['nome'] ?? '');
            $telefoneRaw = trim($_POST['telefone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $cpfRaw = trim($_POST['cpf'] ?? '');
            $data_nascimento = $_POST['data_nascimento'] ?? '';
            $password = $_POST['password'] ?? '';
            $numeroConta = trim($_POST['numero_conta'] ?? '');
            $aceitar = isset($_POST['aceitar']);
            
            // Validações básicas
            if (empty($nome) || empty($telefoneRaw) || empty($email) || empty($cpfRaw) || empty($data_nascimento) || empty($password) || empty($numeroConta)) {
                throw new Exception('Todos os campos são obrigatórios!');
            }
            
            if (!$aceitar) {
                throw new Exception('Você deve aceitar a política de privacidade!');
            }
            
            // Limpar CPF, Telefone e Número da Conta (remover formatação)
            $cpfLimpo = limparNumeros($cpfRaw);
            $telefoneLimpo = limparNumeros($telefoneRaw);
            $numeroContaLimpo = limparNumeros($numeroConta);
            
            // Validações adicionais
            if (!validarCPF($cpfLimpo)) {
                throw new Exception('CPF inválido! Deve conter 11 dígitos.');
            }
            
            if (!validarTelefone($telefoneLimpo)) {
                throw new Exception('Telefone inválido! Deve conter 10 ou 11 dígitos.');
            }
            
            if (strlen($password) < 6 || strlen($password) > 10) {
                throw new Exception('Senha inválida! Deve ter entre 6 e 10 caracteres.');
            }
            
            if (!validarNumeroConta($numeroContaLimpo)) {
                throw new Exception('Número da conta inválido! Deve ter entre 6 e 8 dígitos.');
            }
            
            // Formatar para armazenamento
            $cpfFormatado = formatarCPF($cpfLimpo);
            $telefoneFormatado = formatarTelefone($telefoneLimpo);
            $numeroContaFormatado = formatarNumeroConta($numeroContaLimpo);
            
            // Verificar se número da conta já existe
            $contaManager = new ContaManager();
            if ($contaManager->buscar($numeroContaFormatado)) {
                throw new Exception('Número da conta já existe! Escolha outro número.');
            }
            
            // Criar usuário com dados formatados
            $sucesso = $auth->register($password, $nome, $telefoneFormatado, $email, $cpfFormatado, $data_nascimento, $numeroContaFormatado);
            if (!$sucesso) {
                throw new Exception('Erro ao criar usuário! Nome de usuário já existe.');
            }
            
            // Criar conta automaticamente
            $manager = new ContaManager();
            $conta = new ContaPoupanca($nome, $numeroContaFormatado);
            if (!$manager->criar($conta, $password)) {
                throw new Exception('Erro ao criar conta bancária! Tente novamente.');
            }
            
            // Sucesso!
            $_SESSION['success_message'] = 'Cadastro realizado com sucesso! Faça login com seu número de conta.';
            header('Location: ./index.php');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['register_error'] = $e->getMessage();
            // Preservar dados do formulário
            $_SESSION['register_data'] = [
                'nome' => $nome,
                'telefone' => $telefoneRaw,
                'email' => $email,
                'cpf' => $cpfRaw,
                'data_nascimento' => $data_nascimento,
                'numero_conta' => $numeroConta,
                'aceitar' => $aceitar
            ];
            header('Location: ./index.php');
            exit;
        }
    }
}
// Mensagens via sessão
$error = '';
$registerError = '';
$success = '';

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (isset($_SESSION['register_error'])) {
    $registerError = $_SESSION['register_error'];
    unset($_SESSION['register_error']);
}

// Dados preservados do formulário de cadastro
$registerData = [];
if (isset($_SESSION['register_data'])) {
    $registerData = $_SESSION['register_data'];
    unset($_SESSION['register_data']);
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEO Bank - Login</title>
    <meta name="description" content="Sistema bancário desenvolvido em PHP com POO">
    <link rel="stylesheet" href="css/index.css">
</head>
<body class="login-container">
    <div class="login-wrapper">
        <header class="login-header">
            <h1 class="logo" aria-label="Logo do banco">🏦</h1>
            <h2 class="title">NEO Bank</h2>
            <p class="subtitle">Sistema Bancário POO</p>
        </header>
        <main class="login-main" role="main">
            <?php if ($error): ?>
            <div id="error-message" class="message error" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div id="success-message" class="message success" role="alert">
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <nav class="tab-nav" role="tablist" aria-label="Navegação entre login e cadastro">
                <button onclick="showTab('login')" id="tab-login" class="tab-button active" role="tab" aria-selected="true" aria-controls="form-login">Login</button>
                <button onclick="showTab('register')" id="tab-register" class="tab-button inactive" role="tab" aria-selected="false" aria-controls="form-register">Cadastrar</button>
            </nav>
            
            <!-- Login Section -->
            <section class="login-section" aria-labelledby="login-heading">
                <h2 id="login-heading" class="sr-only">Formulário de Login</h2>
                <form method="POST" id="form-login" class="form-section" role="tabpanel" aria-labelledby="tab-login">
                    <fieldset class="form-fieldset">
                        <legend class="sr-only">Dados de acesso</legend>
                        <input type="text" name="numero_conta" required placeholder="Número da Conta" class="form-input" aria-label="Número da conta">
                        <input type="password" name="password" required placeholder="Senha" class="form-input" aria-label="Senha">
                        <button type="submit" name="login" class="submit-button">Entrar</button>
                    </fieldset>
                </form>
            </section>
            
            <!-- Register Section -->
            <section class="register-section" aria-labelledby="register-heading">
                <h2 id="register-heading" class="sr-only">Formulário de Cadastro</h2>
                <?php if ($registerError): ?>
                <div id="register-error-message" class="message error" role="alert">
                    <?= htmlspecialchars($registerError) ?>
                </div>
                <?php endif; ?>
                <form method="POST" id="form-register" class="form-section hidden" role="tabpanel" aria-labelledby="tab-register">
                    <fieldset class="form-fieldset">
                        <legend class="sr-only">Dados pessoais</legend>
                        <input type="text" name="nome" required placeholder="Digite seu nome completo" class="form-input" aria-label="Nome completo" value="<?= htmlspecialchars($registerData['nome'] ?? '') ?>">
                        <input type="tel" name="telefone" required placeholder="Digite seu telefone" class="form-input" aria-label="Telefone" value="<?= htmlspecialchars($registerData['telefone'] ?? '') ?>">
                        <input type="email" name="email" required placeholder="Digite seu e-mail" class="form-input" aria-label="E-mail" value="<?= htmlspecialchars($registerData['email'] ?? '') ?>">
                        <input type="text" name="cpf" required placeholder="Digite seu CPF" class="form-input" aria-label="CPF" value="<?= htmlspecialchars($registerData['cpf'] ?? '') ?>">
                        <input type="date" name="data_nascimento" required class="form-input" min="1900-01-01" max="<?= date('Y-m-d', strtotime('-13 years')) ?>" aria-label="Data de nascimento" value="<?= htmlspecialchars($registerData['data_nascimento'] ?? '') ?>">
                    </fieldset>
                    
                    <fieldset class="form-fieldset">
                        <legend class="sr-only">Dados da conta</legend>
                        <input type="text" name="numero_conta" required placeholder="Número da conta (6 a 8 dígitos)" class="form-input" aria-label="Número da conta" value="<?= htmlspecialchars($registerData['numero_conta'] ?? '') ?>">
                        <input type="password" name="password" required minlength="6" maxlength="10" placeholder="Senha (6 a 10 caracteres)" class="form-input" aria-label="Senha">
                    </fieldset>
                    
                    <fieldset class="form-fieldset">
                        <legend class="sr-only">Termos e condições</legend>
                        <div class="checkbox-container">
                            <input type="checkbox" id="aceitar" name="aceitar" required class="checkbox" <?= isset($registerData['aceitar']) && $registerData['aceitar'] ? 'checked' : '' ?>>
                            <label for="aceitar" class="checkbox-label">
                                Autorizo o NEO Bank a tratar meus dados pessoais para envio de comunicações sobre seus produtos e serviços e também estou de acordo com a <a href="#" onclick="openPrivacyModal(); return false;" class="link">Política de Privacidade</a>.
                            </label>
                        </div>
                    </fieldset>
                    
                    <button type="submit" name="register" class="submit-button">Criar Conta</button>
                </form>
            </section>
        </main>
        
        <footer class="login-footer" role="contentinfo">
            <p>&copy; <?= date('Y') ?> NEO Bank - Sistema Bancário POO</p>
        </footer>
    </div>
    
    <!-- Modal de Política de Privacidade -->
    <aside id="privacyModal" class="privacy-modal" role="dialog" aria-labelledby="privacy-title" aria-modal="true">
        <div class="privacy-modal-content">
            <header class="privacy-modal-header">
                <h2 id="privacy-title" class="privacy-modal-title">📋 Política de Privacidade</h2>
                <button class="privacy-modal-close" onclick="closePrivacyModal()" aria-label="Fechar modal">&times;</button>
            </header>
            <div class="privacy-modal-body">
                <h3>1. Informações Gerais</h3>
                <p>Esta Política de Privacidade descreve como o NEO Bank ("nós", "nosso" ou "empresa") coleta, usa, armazena e protege suas informações pessoais quando você utiliza nossos serviços bancários.</p>
                
                <h3>2. Informações que Coletamos</h3>
                <p>Coletamos as seguintes informações pessoais quando você se cadastra em nosso sistema:</p>
                <ul>
                    <li><strong>Dados Pessoais:</strong> Nome completo, CPF e data de nascimento</li>
                    <li><strong>Dados de Contato:</strong> Telefone e e-mail</li>
                    <li><strong>Dados de Acesso:</strong> Número da conta e senha</li>
                    <li><strong>Dados Bancários:</strong> Informações sobre suas contas e transações</li>
                </ul>
                
                <h3>3. Como Utilizamos suas Informações</h3>
                <p>Utilizamos suas informações pessoais para:</p>
                <ul>
                    <li>Fornecer serviços bancários e financeiros</li>
                    <li>Processar transações e operações bancárias</li>
                    <li>Verificar sua identidade e prevenir fraudes</li>
                    <li>Enviar comunicações sobre produtos e serviços</li>
                    <li>Cumprir obrigações legais e regulamentares</li>
                    <li>Melhorar nossos serviços e experiência do usuário</li>
                </ul>
                
                <h3>4. Compartilhamento de Informações</h3>
                <p>Não vendemos, alugamos ou compartilhamos suas informações pessoais com terceiros, exceto quando:</p>
                <ul>
                    <li>Necessário para fornecer nossos serviços</li>
                    <li>Exigido por lei ou autoridades competentes</li>
                    <li>Você autorizar expressamente o compartilhamento</li>
                    <li>Para proteger nossos direitos legais ou segurança</li>
                </ul>
                
                <h3>5. Segurança dos Dados</h3>
                <p>Implementamos medidas de segurança técnicas e organizacionais para proteger suas informações pessoais contra acesso não autorizado, alteração, divulgação ou destruição.</p>
                
                <h3>6. Seus Direitos</h3>
                <p>Você tem o direito de acessar, corrigir, solicitar a exclusão de seus dados, restringir o processamento e portabilidade de dados.</p>
                
                <h3>7. Contato</h3>
                <p>Para questões sobre esta Política de Privacidade, entre em contato: <strong>privacidade@neobank.com</strong></p>
                
                <p class="privacy-date"><strong>Última atualização:</strong> <?= date('d/m/Y') ?></p>
            </div>
            <div class="privacy-modal-footer">
                <button class="privacy-modal-btn" onclick="closePrivacyModal()">Fechar</button>
            </div>
        </div>
    </div>
    
    <script>
        // Ocultar mensagem de sucesso automaticamente
        setTimeout(function() {
            const successMsg = document.getElementById('success-message');
            if (successMsg) {
                successMsg.style.transition = 'opacity 0.5s';
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 3000);
        // Ocultar mensagem de erro automaticamente
        setTimeout(function() {
            const errorMsg = document.getElementById('error-message');
            if (errorMsg) {
                errorMsg.style.transition = 'opacity 0.5s';
                errorMsg.style.opacity = '0';
                setTimeout(() => errorMsg.remove(), 500);
            }
        }, 3000);
        
        // Ocultar mensagem de erro de cadastro automaticamente
        setTimeout(function() {
            const registerErrorMsg = document.getElementById('register-error-message');
            if (registerErrorMsg) {
                registerErrorMsg.style.transition = 'opacity 0.5s';
                registerErrorMsg.style.opacity = '0';
                setTimeout(() => registerErrorMsg.remove(), 500);
            }
        }, 3000);
        
        // Mostrar aba de cadastro se houver erro de cadastro
        <?php if ($registerError): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showTab('register');
        });
        <?php endif; ?>
        
        function showTab(tab) {
            if (tab === 'login') {
                document.getElementById('form-login').classList.remove('hidden');
                document.getElementById('form-register').classList.add('hidden');
                document.getElementById('tab-login').classList.add('active');
                document.getElementById('tab-login').classList.remove('inactive');
                document.getElementById('tab-register').classList.remove('active');
                document.getElementById('tab-register').classList.add('inactive');
            } else {
                document.getElementById('form-login').classList.add('hidden');
                document.getElementById('form-register').classList.remove('hidden');
                document.getElementById('tab-register').classList.add('active');
                document.getElementById('tab-register').classList.remove('inactive');
                document.getElementById('tab-login').classList.remove('active');
                document.getElementById('tab-login').classList.add('inactive');
            }
        }
        
        // Modal de Privacidade
        function openPrivacyModal() {
            const modal = document.getElementById('privacyModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closePrivacyModal() {
            const modal = document.getElementById('privacyModal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
        
        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('privacyModal');
            if (event.target === modal) {
                closePrivacyModal();
            }
        }
        
        // Formatação automática de CPF
        document.querySelector('input[name="cpf"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
            
            // Formatar: 123.456.789-00 (sem limitar)
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.replace(/(\d{3})(\d+)/, '$1.$2');
                } else if (value.length <= 9) {
                    value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1.$2.$3');
                } else if (value.length <= 11) {
                    value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
                } else {
                    // Se passar de 11, pega só os 11 primeiros e formata
                    value = value.substring(0, 11);
                    value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                }
            }
            
            e.target.value = value;
        });
        
        // Formatação automática de Telefone
        document.querySelector('input[name="telefone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
            
            // Formatar conforme digita
            if (value.length > 0) {
                if (value.length <= 2) {
                    value = value.replace(/(\d{1,2})/, '($1');
                } else if (value.length <= 6) {
                    value = value.replace(/(\d{2})(\d+)/, '($1) $2');
                } else if (value.length <= 10) {
                    // Fixo: (81) 3479-2068
                    value = value.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
                } else if (value.length === 11) {
                    // Celular: (81) 98479-2068
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                } else {
                    // Se passar de 11, pega só os 11 primeiros
                    value = value.substring(0, 11);
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                }
            }
            
            e.target.value = value;
        });
        
        // Formatação automática de Número da Conta (ambos os formulários)
        document.querySelectorAll('input[name="numero_conta"]').forEach(function(input) {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
                
                // Formatar conforme digita
                if (value.length > 0) {
                    if (value.length === 1) {
                        value = value;
                    } else if (value.length <= 8) {
                        // Formatar: 12345-6 (último dígito é verificador)
                        let base = value.substring(0, value.length - 1);
                        let verificador = value.substring(value.length - 1);
                        value = base + '-' + verificador;
                    } else {
                        // Se passar de 8, pega só os 8 primeiros e formata
                        value = value.substring(0, 8);
                        let base = value.substring(0, 7);
                        let verificador = value.substring(7, 8);
                        value = base + '-' + verificador;
                    }
                }
                
                e.target.value = value;
            });
        });
        
        // Validação do campo de data
        document.querySelector('input[name="data_nascimento"]').addEventListener('input', function(e) {
            const value = e.target.value;
            
            // Verificar se o ano tem mais de 4 dígitos
            if (value && value.length > 10) {
                // Forçar formato correto YYYY-MM-DD
                const match = value.match(/(\d{4})-(\d{1,2})-(\d{1,2})/);
                if (match) {
                    e.target.value = match[0];
                }
            }
            
            // Verificar se é uma data válida
            if (value) {
                const date = new Date(value);
                const today = new Date();
                const minAge = new Date(today.getFullYear() - 100, today.getMonth(), today.getDate());
                const maxAge = new Date(today.getFullYear() - 13, today.getMonth(), today.getDate());
                
                if (date < minAge || date > maxAge) {
                    e.target.setCustomValidity('Data deve ser entre 13 e 100 anos atrás');
                } else {
                    e.target.setCustomValidity('');
                }
            }
        });
        
        // Validação do formulário de cadastro
        document.getElementById('form-register').addEventListener('submit', function(e) {
            const numeroConta = document.querySelector('#form-register input[name="numero_conta"]').value.replace(/\D/g, '');
            const password = document.querySelector('#form-register input[name="password"]').value;
            const dataNascimento = document.querySelector('#form-register input[name="data_nascimento"]').value;
            
            // Validar número da conta
            if (numeroConta.length < 6 || numeroConta.length > 8) {
                e.preventDefault();
                alert('❌ Número da conta inválido!\n\nDeve ter entre 6 e 8 dígitos.\n\nExemplo: 123456-7 ou 1234567-8');
                return false;
            }
            
            // Validar senha
            if (password.length < 6 || password.length > 10) {
                e.preventDefault();
                alert('❌ Senha inválida!\n\nDeve ter entre 6 e 10 caracteres.');
                return false;
            }
            
            // Validar data de nascimento
            if (dataNascimento) {
                const date = new Date(dataNascimento);
                const today = new Date();
                const minAge = new Date(today.getFullYear() - 100, today.getMonth(), today.getDate());
                const maxAge = new Date(today.getFullYear() - 13, today.getMonth(), today.getDate());
                
                if (date < minAge || date > maxAge) {
                    e.preventDefault();
                    alert('❌ Data de nascimento inválida!\n\nDeve ser entre 13 e 100 anos atrás.');
                    return false;
                }
            }
            
            return true;
        });
    </script>
</body>
</html>