<?php

class ContaManager
{
    private static $contas = [];
    private static $dataFile = __DIR__ . '/../data/contas.json';

    public function __construct()
    {
        // Carregar dados do JSON
        $this->carregarDados();
    }
    
    private function carregarDados(): void
    {
        if (file_exists(self::$dataFile)) {
            $json = file_get_contents(self::$dataFile);
            $contasData = json_decode($json, true) ?: [];
            
            // Converter dados JSON de volta para objetos ContaBancaria
            foreach ($contasData as $numero => $data) {
                if ($data['tipo'] === 'ContaPoupanca') {
                    $conta = new ContaPoupanca($data['titular'], $numero);
                    $conta->setSaldo($data['saldo']);
                    
                    // Restaurar propriedades de caixinha se existir
                    if (isset($data['e_caixinha']) && $data['e_caixinha']) {
                        $conta->eCaixinha = true;
                        $conta->tipoCaixinha = $data['tipo_caixinha'] ?? null;
                    }
                    
                    self::$contas[$numero] = $conta;
                }
            }
        }
    }
    
    private function salvarDados(): bool
    {
        try {
            $contasData = [];
            foreach (self::$contas as $numero => $conta) {
                $contasData[$numero] = [
                    'titular' => $conta->getTitular(),
                    'saldo' => $conta->getSaldo(),
                    'tipo' => get_class($conta),
                    'tipo_caixinha' => isset($conta->tipoCaixinha) ? $conta->tipoCaixinha : null,
                    'e_caixinha' => isset($conta->eCaixinha) ? $conta->eCaixinha : false
                ];
            }
            
            $json = json_encode($contasData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return file_put_contents(self::$dataFile, $json) !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function criar(ContaBancaria $conta, string $password = ''): bool
    {
        try {
            $numero = $conta->getNumeroConta();
            
            // Validações
            if (empty($numero)) {
                throw new Exception('Número da conta não pode ser vazio!');
            }
            
            // Verificar se conta já existe
            if ($this->buscar($numero)) {
                throw new Exception('Conta já existe!');
            }

            self::$contas[$numero] = $conta;
            
            // Salvar no JSON
            if (!$this->salvarDados()) {
                throw new Exception('Erro ao salvar dados da conta!');
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }

    public function buscar(string $numero): ?ContaBancaria
    {
        return self::$contas[$numero] ?? null;
    }

    public function listar(): array
    {
        return self::$contas;
    }

    public function listarPorUsuario(string $username): array
    {
        $auth = new Autenticacao();
        $user = $auth->getAllUsers()[$username] ?? null;
        
        if (!$user) {
            return [];
        }

        $contasUsuario = [];
        foreach ($user['contas'] as $numeroConta) {
            if (isset(self::$contas[$numeroConta])) {
                $contasUsuario[] = [
                    'numero' => $numeroConta,
                    'titular' => self::$contas[$numeroConta]->getTitular(),
                    'saldo' => self::$contas[$numeroConta]->getSaldo(),
                    'tipo' => get_class(self::$contas[$numeroConta]),
                    'taxa_rendimento' => self::$contas[$numeroConta] instanceof ContaPoupanca ? 
                        self::$contas[$numeroConta]->getTaxaRendimento() : 0
                ];
            }
        }

        return $contasUsuario;
    }

    public function depositar(string $numero, float $valor): bool
    {
        $conta = $this->buscar($numero);
        if (!$conta) {
            return false;
        }

        try {
            $conta->depositar($valor);
            
            // Salvar no JSON após operação
            if (!$this->salvarDados()) {
                throw new Exception('Erro ao salvar dados da conta!');
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function sacar(string $numero, float $valor): bool
    {
        $conta = $this->buscar($numero);
        if (!$conta) {
            return false;
        }

        try {
            $conta->sacar($valor);
            
            // Salvar no JSON após operação
            if (!$this->salvarDados()) {
                throw new Exception('Erro ao salvar dados da conta!');
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function atualizar(string $numero, float $saldo): bool
    {
        $conta = $this->buscar($numero);
        if (!$conta) {
            return false;
        }

        try {
            $conta->setSaldo($saldo);
            
            // Salvar no JSON após atualização
            if (!$this->salvarDados()) {
                throw new Exception('Erro ao salvar dados da conta!');
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function aplicarRendimento(string $numero): bool
    {
        $conta = $this->buscar($numero);
        if (!$conta || !($conta instanceof ContaPoupanca)) {
            return false;
        }

        try {
            $conta->aplicarRendimento();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function criarCaixinha(string $nomeTitular, string $tipoCaixinha): bool
    {
        try {
            // Validar tipo de caixinha
            $tiposValidos = ['Reserva de Emergência', 'Fazer uma viagem', 'Reformar a Casa', 'Focar na carreira'];
            if (!in_array($tipoCaixinha, $tiposValidos)) {
                throw new Exception('Tipo de caixinha inválido!');
            }
            
            // Verificar se já existe uma caixinha deste tipo para este usuário
            foreach (self::$contas as $conta) {
                if (isset($conta->tipoCaixinha) && $conta->tipoCaixinha === $tipoCaixinha && $conta->getTitular() === $nomeTitular) {
                    throw new Exception('Você já possui uma caixinha deste tipo!');
                }
            }
            
            // Gerar número único para a caixinha
            $prefixos = [
                'Reserva de Emergência' => 'EMERG',
                'Fazer uma viagem' => 'VIAGEM',
                'Reformar a Casa' => 'CASA',
                'Focar na carreira' => 'CARREIRA'
            ];
            
            $prefixo = $prefixos[$tipoCaixinha];
            $contador = 1;
            do {
                $numero = $prefixo . '-' . str_pad($contador, 3, '0', STR_PAD_LEFT);
                $contador++;
            } while ($this->buscar($numero));
            
            // Criar a caixinha como uma ContaPoupanca
            $caixinha = new ContaPoupanca($nomeTitular, $numero);
            $caixinha->tipoCaixinha = $tipoCaixinha;
            $caixinha->eCaixinha = true;
            
            self::$contas[$numero] = $caixinha;
            
            // Salvar no JSON
            if (!$this->salvarDados()) {
                throw new Exception('Erro ao salvar caixinha!');
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function listarCaixinhasPorUsuario(string $username): array
    {
        $caixinhas = [];
        
        // Buscar o nome do usuário para filtrar as caixinhas
        $auth = new Autenticacao();
        $user = $auth->getAllUsers()[$username] ?? null;
        
        if (!$user) {
            return [];
        }
        
        $nomeTitular = $user['nome'];
        
        foreach (self::$contas as $numero => $conta) {
            if (isset($conta->eCaixinha) && $conta->eCaixinha === true && $conta->getTitular() === $nomeTitular) {
                $caixinhas[] = [
                    'numero' => $numero,
                    'titular' => $conta->getTitular(),
                    'saldo' => $conta->getSaldo(),
                    'tipo_caixinha' => $conta->tipoCaixinha ?? 'Desconhecido'
                ];
            }
        }
        
        return $caixinhas;
    }

    public function deletar(string $numero): bool
    {
        try {
            if (empty($numero)) {
                throw new Exception('Número da conta não pode ser vazio!');
            }
            
            if (isset(self::$contas[$numero])) {
                unset(self::$contas[$numero]);
                
                // Salvar no JSON após deletar
                if (!$this->salvarDados()) {
                    throw new Exception('Erro ao salvar após exclusão!');
                }
                
                return true;
            }
            
            throw new Exception('Conta não encontrada!');
            
        } catch (Exception $e) {
            return false;
        }
    }

    public function contar(): int
    {
        return count(self::$contas);
    }

    public function salvar(): bool
    {
        // Em um sistema simples, as contas ficam em memória
        // Em uma versão mais avançada, poderia salvar em arquivo JSON
        return true;
    }

    public function carregar(): bool
    {
        // Em um sistema simples, as contas são carregadas no construtor
        return true;
    }
}