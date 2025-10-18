# 🏦 NEO Bank - Sistema Bancário POO

> **Segunda Atividade Final de Projeto**

Sistema bancário completo desenvolvido em PHP com **Programação Orientada a Objetos**, **autenticação de usuários**, **caixinhas de economia** e **exportação de relatórios**.

---

## 🚀 Instalação

### **Requisitos:**
- PHP 7.4+
- Servidor web (Apache/Nginx/XAMPP)
- Navegador moderno

### **Como usar:**

1. **Clone ou baixe o projeto**
2. **Coloque na pasta do servidor:**
   - XAMPP: `C:\xampp\htdocs\neobank\`
   - Linux: `/var/www/html/neobank/`

3. **Acesse no navegador:**
   ```
   http://localhost/neobank/
   ```

---

## ✨ Funcionalidades

### 🔐 **Autenticação**
- Login com **número da conta + senha**
- Cadastro completo de usuário
- Dados: nome completo, telefone, email, CPF, data de nascimento
- Formatação automática de campos
- Modal de **Política de Privacidade** integrado
- Sistema de sessões PHP
- **UX Melhorada**: Erros de cadastro permanecem na aba de cadastro
- **Preservação de dados**: Formulário mantém dados preenchidos após erro
- **Feedback inteligente**: Mensagens específicas para login e cadastro

### 💳 **Conta Poupança**
- Conta poupança com **taxa de rendimento configurável**
- Depósitos e saques com validações
- Limite de saque configurável
- Saldo total exibido em tempo real
- Interface responsiva e moderna

### 🎯 **Caixinhas de Economia**
- Criar até **4 caixinhas** (uma de cada tipo):
  - 💰 Reserva de Emergência
  - ✈️ Fazer uma viagem
  - 🏠 Reformar a Casa
  - 🎓 Focar na carreira
- Depósitos diretos nas caixinhas
- Exclusão apenas com **saldo zerado**
- Ícones personalizados para cada tipo
- Saldo individual por caixinha
- **Isolamento de dados**: Cada usuário vê apenas suas próprias contas e caixinhas

### 📊 **Exportação de Relatórios**
- **Exportar CSV:**
  - Formato profissional com `fputcsv()`
  - Delimitador `;` (compatível com Excel BR)
  - UTF-8 com BOM para acentuação perfeita
  - Estrutura organizada por seções
- **Exportar PDF:**
  - HTML para impressão/PDF
  - Design compacto e profissional
  - Botão de impressão integrado
  - Compatível com "Salvar como PDF"

### 🎨 **Interface**
- **Tema azul moderno** (#1976d2)
- Layout **100% responsivo**
- Formulários com validação frontend e backend
- Cards organizados em grid
- Animações suaves

---

## 🎯 Conceitos POO Implementados

### ✅ **1. Classes e Objetos**
- `ContaBancaria` (classe abstrata base)
- `ContaPoupanca` (classe concreta)

### ✅ **2. Propriedades, Métodos e Construtor**
- Propriedades: `titular`, `saldo`, `numeroConta`, `taxaRendimento`
- Construtor com parâmetros obrigatórios
- Métodos: `depositar()`, `sacar()`, `verSaldo()`, `aplicarRendimento()`

### ✅ **3. Encapsulamento**
- Propriedades `protected` e `private`
- Getters e Setters para acesso controlado
- Validação de valores negativos
- Proteção de dados sensíveis

### ✅ **4. Herança**
- `ContaPoupanca extends ContaBancaria`
- Propriedade adicional: `taxaRendimento`
- Método específico: `aplicarRendimento()`
- Reutilização de código da classe pai

### ✅ **5. Classes e Métodos Abstratos**
- `ContaBancaria` é abstrata (não pode ser instanciada)
- Método abstrato: `calcularTarifa()`
- Implementação obrigatória nas classes filhas

### ✅ **6. Métodos Estáticos e Constantes**
- Constante: `LIMITE_SAQUE = 1000`
- Método estático: `mostrarLimite()`
- Acesso sem instanciar objeto

### ✅ **7. Interfaces**
- Interface `OperacoesBancarias`
- Métodos: `depositar()`, `sacar()`, `verSaldo()`
- Implementada por `ContaPoupanca`
- Contrato de implementação

### ✅ **8. Injeção e Inversão de Dependência**
- `GeradorDeRelatorio` recebe conta no construtor
- Métodos: `exportarCSV()`, `exportarPDF()`
- Desacoplamento de código
- Facilita testes e manutenção

### ✅ **9. Autoload**
- Classes organizadas em pastas
- `spl_autoload_register()` implementado
- Carregamento automático de classes
- Estrutura: `classes/`, `interfaces/`, `servicos/`

---

## 📁 Estrutura do Projeto

```
neobank/
├── classes/
│   ├── ContaBancaria.php      # Classe abstrata base
│   └── ContaPoupanca.php      # Herança + Interface
├── interfaces/
│   └── OperacoesBancarias.php # Interface de operações
├── servicos/
│   ├── Autenticacao.php       # Autenticação de usuários (renomeado)
│   ├── ContaManager.php       # Gerenciamento de contas e caixinhas
│   └── GeradorDeRelatorio.php # Exportação CSV/PDF (DI)
├── css/
│   ├── index.css              # Estilos da página de login/cadastro
│   └── dashboard.css          # Estilos do painel do usuário
├── data/
│   ├── usuarios.json          # Dados dos usuários (sistema limpo)
│   └── contas.json            # Dados das contas e caixinhas (sistema limpo)
├── autoload.php               # Autoload de classes
├── index.php                  # Login/Cadastro (página principal)
├── dashboard.php              # Painel do usuário (operações)
├── README.md                  # Documentação
└── LICENSE                    # Licença MIT
```

---

## 🗄️ Persistência de Dados

### **Armazenamento JSON:**
- **`data/usuarios.json`** - Dados de usuários (sistema limpo, pronto para primeiro cadastro)
- **`data/contas.json`** - Contas e caixinhas com saldos (sistema limpo)
- **Sessões PHP** - Autenticação e controle de acesso
- **Sem banco de dados** - Sistema simples e portátil

### **Segurança:**
- Validação de entrada (frontend + backend)
- Proteção contra valores negativos
- Try-catch em pontos críticos
- **Isolamento de usuários**: Cada usuário acessa apenas seus dados

---

## 📝 Como Usar

### 🚀 **Primeiro Acesso (Sistema Limpo):**
1. **Acesse:** `http://localhost/neobank/`
2. **Clique na aba "Cadastrar"**
3. **Preencha todos os campos obrigatórios:**
   - Nome completo
   - Telefone: formato `(81) 99999-9999`
   - Email
   - CPF: formato `123.456.789-00`
   - Data de nascimento
   - Número da conta: 6-8 dígitos (formato `12345-6`)
   - Senha: 6-10 caracteres
   - Aceitar política de privacidade
4. **Faça login** com número da conta + senha
5. **No Dashboard:**
   - Deposite e saque da conta poupança
   - Crie caixinhas de economia
   - Deposite nas caixinhas
   - Exclua caixinhas (apenas com saldo zero)
   - Exporte relatórios em CSV ou PDF

### 💡 **Dados de Exemplo para Teste:**
- **Nome**: Elinaldo Oliveira
- **Telefone**: (81) 99999-9999
- **Email**: elinaldo@email.com
- **CPF**: 123.456.789-00
- **Data**: 1990-01-15
- **Conta**: 123456-7
- **Senha**: 12345678

---

## 🔧 Tecnologias

- **PHP 7.4+** (100% POO)
- **JSON** (persistência de dados)
- **HTML5 Semântico**
- **CSS3** (Grid, Flexbox, Dark Mode)
- **JavaScript** (validações, formatação, interações)
- **Sessões PHP** (autenticação)

---

## 📌 Destaques

### **Funcionalidades:**
- ✅ Sistema completo e funcional
- ✅ Caixinhas de economia (inspirado no Nubank)
- ✅ Exportação CSV profissional (`fputcsv()`)
- ✅ Exportação PDF para impressão
- ✅ Interface moderna e responsiva
- ✅ Validações robustas (frontend + backend)
- ✅ Modal de privacidade
- ✅ **UX aprimorada**: Erros de cadastro mantêm dados preenchidos
- ✅ **Isolamento de dados**: Usuários veem apenas suas próprias contas

### **Código:**
- ✅ 100% Orientado a Objetos
- ✅ Código limpo e organizado
- ✅ Try-catch em pontos críticos
- ✅ Comentários explicativos
- ✅ Estrutura modular
- ✅ Fácil manutenção
- ✅ **Nomenclatura em português**: Classe `Autenticacao` para melhor legibilidade

### **Segurança:**
- ✅ Proteção contra valores negativos
- ✅ Sessões seguras
- ✅ Escape de HTML (`htmlspecialchars()`)
- ✅ **Isolamento de usuários**: Cada usuário acessa apenas seus dados

---

## 🎓 Atendimento aos Requisitos

### **9 Conceitos POO:**
✅ 1. Classes e Objetos  
✅ 2. Propriedades, Métodos e Construtor  
✅ 3. Encapsulamento  
✅ 4. Herança  
✅ 5. Classes e Métodos Abstratos  
✅ 6. Métodos Estáticos e Constantes  
✅ 7. Interfaces  
✅ 8. Injeção e Inversão de Dependência  
✅ 9. Autoload  

### **Desafio Final:**
✅ Sistema bancário completo  
✅ CRUD de contas e caixinhas  
✅ Interface web moderna  
✅ Relatórios exportáveis  

---

## 🔧 Melhorias Implementadas

### **Versão Atual (v2.0):**

#### **🔐 Sistema de Autenticação Aprimorado:**
- ✅ **Classe renomeada**: `Auth` → `Autenticacao` (nomenclatura em português)
- ✅ **UX melhorada**: Erros de cadastro permanecem na aba de cadastro
- ✅ **Preservação de dados**: Formulário mantém dados preenchidos após erro
- ✅ **Auto-ocultação**: Mensagens de erro desaparecem automaticamente

#### **🎯 Isolamento de Dados Corrigido:**
- ✅ **Bug corrigido**: Caixinhas agora são filtradas por usuário
- ✅ **Segurança aprimorada**: Cada usuário vê apenas suas próprias contas
- ✅ **Filtro por titular**: Sistema não mostra dados de outros usuários
- ✅ **Contas novas**: Aparecem com saldo zerado corretamente

#### **💻 Melhorias de Código:**
- ✅ **Separação de responsabilidades**: Erros de login e cadastro tratados separadamente
- ✅ **Variáveis de sessão específicas**: `login_error` e `register_error`
- ✅ **Preservação de dados**: `register_data` mantém dados do formulário
- ✅ **JavaScript inteligente**: Mostra aba correta baseada no tipo de erro

#### **🎨 Experiência do Usuário:**
- ✅ **Fluxo intuitivo**: Usuário não perde dados preenchidos
- ✅ **Feedback claro**: Mensagens aparecem no local correto
- ✅ **Eficiência**: Corrige apenas o que precisa ser alterado
- ✅ **Profissionalismo**: Sistema funciona como aplicações comerciais
- ✅ **Sistema limpo**: Pronto para primeira instalação sem dados pré-existentes

### **Problemas Resolvidos:**
1. ❌ **Problema**: Usuários viam caixinhas de outros usuários
   ✅ **Solução**: Filtro por titular implementado

2. ❌ **Problema**: Erros de cadastro voltavam para aba de login
   ✅ **Solução**: Sistema permanece na aba de cadastro

3. ❌ **Problema**: Dados do formulário eram perdidos após erro
   ✅ **Solução**: Preservação automática de dados na sessão

4. ❌ **Problema**: Nomenclatura em inglês inconsistente
   ✅ **Solução**: Classe `Autenticacao` em português

5. ❌ **Problema**: Dados reais expostos no projeto de demonstração
   ✅ **Solução**: Sistema limpo para primeira instalação

---

## 📄 Licença

Este projeto está licenciado sob a [MIT License](LICENSE) - veja o arquivo LICENSE para mais detalhes.

### Resumo da Licença MIT:
- ✅ Uso comercial permitido
- ✅ Modificação permitida
- ✅ Distribuição permitida
- ✅ Uso privado permitido
- ⚠️ Sem garantia
- ⚠️ Sem responsabilidade do autor