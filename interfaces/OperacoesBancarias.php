<?php

interface OperacoesBancarias
{
    public function depositar(float $valor): void;
    public function sacar(float $valor): void;
    public function verSaldo(): float;
}