<?php
// Registra a função de autoload
spl_autoload_register(function ($class) {
    // Define os diretórios onde as classes podem estar localizadas
    $paths = [
        __DIR__ . '/classes/',      // Classes principais (ContaBancaria, ContaPoupanca)
        __DIR__ . '/interfaces/',   // Interfaces (OperacoesBancarias)
        __DIR__ . '/servicos/'      // Serviços (Auth, ContaManager, GeradorDeRelatorio)
    ];
    
    // Percorre cada diretório procurando pela classe
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        
        // Se o arquivo existe, carrega e para a busca
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});