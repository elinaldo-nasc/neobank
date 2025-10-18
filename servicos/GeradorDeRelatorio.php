<?php

class GeradorDeRelatorio
{
    private ContaBancaria $conta;

    public function __construct(ContaBancaria $conta)
    {
        $this->conta = $conta;
    }

    public function gerar(): string
    {
        try {
            // Validação
            if (!$this->conta) {
                throw new Exception('Conta inválida para gerar relatório!');
            }
            
            $tipoConta = get_class($this->conta);
            $html = "<div class='relatorio'>";
            $html .= "<h3>📊 Relatório da Conta</h3>";
            $html .= "<table>";
            $html .= "<tr><td><strong>Tipo:</strong></td><td>{$tipoConta}</td></tr>";
            $html .= "<tr><td><strong>Número:</strong></td><td>{$this->conta->getNumeroConta()}</td></tr>";
            $html .= "<tr><td><strong>Titular:</strong></td><td>{$this->conta->getTitular()}</td></tr>";
            $html .= "<tr><td><strong>Saldo:</strong></td><td>R$ " . number_format($this->conta->getSaldo(), 2, ',', '.') . "</td></tr>";
            $html .= "<tr><td><strong>Tarifa:</strong></td><td>R$ " . number_format($this->conta->calcularTarifa(), 2, ',', '.') . "</td></tr>";
            
            if ($this->conta instanceof ContaPoupanca) {
                $html .= "<tr><td><strong>Taxa Rendimento:</strong></td><td>{$this->conta->getTaxaRendimento()}%</td></tr>";
            }
            
            $html .= "</table>";
            $html .= "</div>";
            
            return $html;
            
        } catch (Exception $e) {
            return "<div class='relatorio error'><h3>❌ Erro ao gerar relatório</h3><p>" . htmlspecialchars($e->getMessage()) . "</p></div>";
        }
    }

    public function exportarCSV(array $contas, array $caixinhas, string $nomeTitular): void
    {
        try {
            $data = date('d/m/Y H:i:s');
            $saldoTotal = 0;
            
            // Calcular saldo total
            foreach ($contas as $conta) {
                $saldoTotal += $conta['saldo'];
            }
            foreach ($caixinhas as $caixinha) {
                $saldoTotal += $caixinha['saldo'];
            }
            
            // Abrir output direto
            $output = fopen('php://output', 'w');
            
            // BOM UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // ===== CABEÇALHO DO RELATÓRIO =====
            fputcsv($output, ['RELATÓRIO FINANCEIRO - NEO BANK'], ';');
            fputcsv($output, [''], ';');
            fputcsv($output, ['Titular:', $nomeTitular], ';');
            fputcsv($output, ['Data:', $data], ';');
            fputcsv($output, ['Saldo Total:', 'R$ ' . number_format($saldoTotal, 2, ',', '.')], ';');
            fputcsv($output, ['Limite de Saque:', 'R$ ' . number_format(ContaBancaria::mostrarLimite(), 2, ',', '.')], ';');
            fputcsv($output, [''], ';');
            
            // ===== CONTA POUPANÇA PRINCIPAL =====
            fputcsv($output, ['CONTA POUPANÇA'], ';');
            fputcsv($output, ['Número da Conta', 'Saldo', 'Taxa de Rendimento'], ';');
            
            foreach ($contas as $conta) {
                fputcsv($output, [
                    $conta['numero'],
                    'R$ ' . number_format($conta['saldo'], 2, ',', '.'),
                    $conta['taxa_rendimento'] . '%'
                ], ';');
            }
            
            fputcsv($output, [''], ';');
            
            // ===== CAIXINHAS =====
            if (!empty($caixinhas)) {
                fputcsv($output, ['CAIXINHAS'], ';');
                fputcsv($output, ['Tipo', 'Número', 'Saldo'], ';');
                
                foreach ($caixinhas as $caixinha) {
                    fputcsv($output, [
                        $caixinha['tipo_caixinha'],
                        $caixinha['numero'],
                        'R$ ' . number_format($caixinha['saldo'], 2, ',', '.')
                    ], ';');
                }
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            throw new Exception("Erro ao gerar CSV: " . $e->getMessage());
        }
    }
    
    public function exportarPDF(array $contas, array $caixinhas, string $nomeTitular): void
    {
        try {
            $data = date('d/m/Y H:i:s');
            $saldoTotal = 0;
            
            // Calcular saldo total
            foreach ($contas as $conta) {
                $saldoTotal += $conta['saldo'];
            }
            foreach ($caixinhas as $caixinha) {
                $saldoTotal += $caixinha['saldo'];
            }
            
            // Gerar HTML para impressão
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório Financeiro - NEO Bank</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body { 
            font-family: "Roboto", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
            font-size: 13px;
        }
        .container {
            background: white;
            padding: 25px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 0 auto;
        }
        h1 { 
            color: #1976d2; 
            text-align: center; 
            margin-bottom: 5px;
            font-size: 22px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 15px;
            font-size: 12px;
        }
        .info-box { 
            background: #f8f9fa;
            padding: 12px 15px; 
            border-radius: 4px;
            margin: 15px 0;
            border-left: 3px solid #1976d2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .info-box p {
            margin: 0;
            color: #333;
            font-size: 12px;
        }
        .info-box strong {
            color: #1976d2;
            font-weight: 600;
        }
        h2 {
            color: #1976d2;
            border-bottom: 2px solid #1976d2;
            padding-bottom: 6px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 16px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0 15px 0;
            background: white;
            font-size: 12px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px 10px; 
            text-align: left; 
        }
        th { 
            background-color: #1976d2; 
            color: white;
            font-weight: 600;
            font-size: 12px;
        }
        tr:nth-child(even) { 
            background-color: #f9f9f9; 
        }
        tr:hover {
            background-color: #f0f0f0;
        }
        .section {
            margin: 15px 0;
        }
        .print-button {
            background: #1976d2;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin: 15px auto;
            display: block;
            transition: background 0.3s;
        }
        .print-button:hover {
            background: #1565c0;
        }
        @media print {
            body { 
                margin: 0; 
                background: white;
                font-size: 11px;
            }
            .container { 
                box-shadow: none;
                padding: 15px;
            }
            .no-print { display: none; }
            h1 { font-size: 18px; }
            h2 { font-size: 14px; margin-top: 15px; }
            table { font-size: 10px; }
            th, td { padding: 6px 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Relatório Financeiro</h1>
        <p class="subtitle">NEO Bank - Sistema de Gestão Financeira</p>
        
        <button onclick="window.print()" class="print-button no-print">🖨️ Imprimir / Salvar PDF</button>
        
        <div class="info-box">
            <p><strong>👤 Titular:</strong> ' . htmlspecialchars($nomeTitular) . '</p>
            <p><strong>📅 Data de Geração:</strong> ' . $data . '</p>
            <p><strong>💰 Saldo Total:</strong> R$ ' . number_format($saldoTotal, 2, ',', '.') . '</p>
            <p><strong>🏦 Limite de Saque:</strong> R$ ' . number_format(ContaBancaria::mostrarLimite(), 2, ',', '.') . '</p>
        </div>
        
        <div class="section">
            <h2>💳 Conta Poupança</h2>
            <table>
                <thead>
                    <tr>
                        <th>Número da Conta</th>
                        <th>Saldo</th>
                        <th>Taxa de Rendimento</th>
                    </tr>
                </thead>
                <tbody>';
                
            foreach ($contas as $conta) {
                echo '<tr>
                    <td>' . htmlspecialchars($conta['numero']) . '</td>
                    <td>R$ ' . number_format($conta['saldo'], 2, ',', '.') . '</td>
                    <td>' . htmlspecialchars($conta['taxa_rendimento']) . '%</td>
                </tr>';
            }
            
            echo '</tbody>
            </table>
        </div>';
        
        // Adicionar caixinhas se existirem
        if (!empty($caixinhas)) {
            echo '<div class="section">
                <h2>🎯 Caixinhas</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Tipo de Caixinha</th>
                            <th>Número</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>';
                    
            foreach ($caixinhas as $caixinha) {
                echo '<tr>
                    <td>' . htmlspecialchars($caixinha['tipo_caixinha']) . '</td>
                    <td>' . htmlspecialchars($caixinha['numero']) . '</td>
                    <td>R$ ' . number_format($caixinha['saldo'], 2, ',', '.') . '</td>
                </tr>';
            }
            
            echo '</tbody>
                </table>
            </div>';
        }
        
        echo '
    </div>
</body>
</html>';
            
        } catch (Exception $e) {
            throw new Exception("Erro ao gerar PDF: " . $e->getMessage());
        }
    }
}                                                                