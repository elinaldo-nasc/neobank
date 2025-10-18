<?php

class Autenticacao
{
    private static $usuarios = [];
    private static $dataFile = __DIR__ . '/../data/usuarios.json';

    public function __construct()
    {
        // Inicializar sessão se não existir
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Carregar dados do JSON
        $this->carregarDados();
    }
    
    private function carregarDados(): void
    {
        if (file_exists(self::$dataFile)) {
            $json = file_get_contents(self::$dataFile);
            self::$usuarios = json_decode($json, true) ?: [];
        }
    }
    
    private function salvarDados(): bool
    {
        try {
            $json = json_encode(self::$usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return file_put_contents(self::$dataFile, $json) !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function login(string $numeroConta, string $senha): bool
    {
        try {
            // Validações
            if (empty($numeroConta) || empty($senha)) {
                throw new Exception('Número da conta e senha são obrigatórios!');
            }
            
            // Buscar usuário pela conta
            foreach (self::$usuarios as $username => $dados) {
                if (in_array($numeroConta, $dados['contas']) && $dados['senha'] === $senha) {
                    $_SESSION['user'] = [
                        'username' => $username,
                        'nome' => $dados['nome'],
                        'conta_numero' => $numeroConta
                    ];
                    
                    return true;
                }
            }
            
            throw new Exception('Credenciais inválidas!');
            
        } catch (Exception $e) {
            return false;
        }
    }

    public function register(string $senha, string $nome, string $telefone, string $email, string $cpf, string $dataNascimento, string $numeroConta): bool
    {
        try {
            // Validações
            if (empty($senha) || empty($nome) || empty($telefone) || empty($email) || empty($cpf) || empty($dataNascimento) || empty($numeroConta)) {
                throw new Exception('Todos os campos são obrigatórios!');
            }
            
            $username = strtolower(str_replace(' ', '_', $nome));
            
            // Verificar se já existe
            if (isset(self::$usuarios[$username])) {
                throw new Exception('Usuário já existe!');
            }

            // Criar novo usuário
            self::$usuarios[$username] = [
                'nome' => $nome,
                'senha' => $senha,
                'telefone' => $telefone,
                'email' => $email,
                'cpf' => $cpf,
                'data_nascimento' => $dataNascimento,
                'contas' => [$numeroConta]
            ];

            // Salvar no JSON
            if (!$this->salvarDados()) {
                throw new Exception('Erro ao salvar dados do usuário!');
            }

            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }

    public function getNome(): string
    {
        return $_SESSION['user']['nome'] ?? '';
    }

    public function getUsername(): string
    {
        return $_SESSION['user']['username'] ?? '';
    }

    public function getNumeroConta(): string
    {
        return $_SESSION['user']['conta_numero'] ?? '';
    }

    public function logout(): void
    {
        session_destroy();
    }

    public function getAllUsers(): array
    {
        return self::$usuarios;
    }
}