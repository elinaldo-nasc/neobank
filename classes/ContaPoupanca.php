<?php

class ContaPoupanca extends ContaBancaria implements OperacoesBancarias
{
    private float $taxaRendimento;
    
    public function __construct(string $titular, string $numeroConta, float $taxaRendimento = 0.5)
    {
        parent::__construct($titular, $numeroConta);
        $this->taxaRendimento = $taxaRendimento;
    }
    
    public function aplicarRendimento(): void
    {
        $rendimento = $this->saldo * ($this->taxaRendimento / 100);
        $this->saldo += $rendimento;
    }
    
    public function calcularTarifa(): float
    {
        return 0.0;
    }
    
    public function getTaxaRendimento(): float
    {
        return $this->taxaRendimento;
    }
}