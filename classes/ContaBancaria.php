<?php

abstract class ContaBancaria
{
    public const LIMITE_SAQUE = 1000;
    
    protected string $titular;
    protected float $saldo;
    protected string $numeroConta;
    
    public function __construct(string $titular, string $numeroConta)
    {
        $this->titular = $titular;
        $this->numeroConta = $numeroConta;
        $this->saldo = 0;
    }
    
    public function depositar(float $valor): void
    {
        if ($valor <= 0) {
            throw new Exception("Valor deve ser positivo");
        }
        $this->saldo += $valor;
    }
    
    public function sacar(float $valor): void
    {
        if ($valor <= 0) {
            throw new Exception("Valor deve ser positivo");
        }
        if ($valor > self::LIMITE_SAQUE) {
            throw new Exception("Limite de saque excedido");
        }
        if ($valor > $this->saldo) {
            throw new Exception("Saldo insuficiente");
        }
        $this->saldo -= $valor;
    }
    
    public function verSaldo(): float
    {
        return $this->saldo;
    }
    
    abstract public function calcularTarifa(): float;
    
    public static function mostrarLimite(): float
    {
        return self::LIMITE_SAQUE;
    }
    
    public function getTitular(): string { return $this->titular; }
    public function getSaldo(): float { return $this->saldo; }
    public function getNumeroConta(): string { return $this->numeroConta; }
    public function setSaldo(float $saldo): void { $this->saldo = $saldo; }
}