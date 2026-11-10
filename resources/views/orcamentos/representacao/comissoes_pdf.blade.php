<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de comissões</title>
    <style>
        @page { margin: 26px 30px 36px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 10px; line-height: 1.35; }
        h1 { margin: 0; color: #064e3b; font-size: 22px; }
        h2 { margin: 18px 0 8px; color: #065f46; font-size: 13px; }
        .muted { color: #6b7280; }
        .header { width: 100%; border-bottom: 2px solid #059669; padding-bottom: 12px; margin-bottom: 14px; }
        .header td { vertical-align: top; }
        .right { text-align: right; }
        .box { border: 1px solid #d1d5db; background: #f9fafb; padding: 10px; margin-bottom: 12px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary th { background: #ecfdf5; color: #065f46; text-align: left; border: 1px solid #a7f3d0; padding: 7px; }
        .summary td { border: 1px solid #d1fae5; padding: 7px; }
        .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items th { background: #065f46; color: white; padding: 7px 5px; text-align: left; font-size: 9px; }
        .items td { border-bottom: 1px solid #e5e7eb; padding: 7px 5px; vertical-align: top; }
        .items th:nth-child(1) { width: 9%; }
        .items th:nth-child(2) { width: 12%; }
        .items th:nth-child(3) { width: 14%; }
        .items th:nth-child(4) { width: 19%; }
        .items th:nth-child(5) { width: 16%; }
        .items th:nth-child(6) { width: 11%; }
        .items th:nth-child(7) { width: 11%; }
        .items th:nth-child(8) { width: 8%; }
        .money { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .total-row td { border-top: 2px solid #059669; font-weight: bold; color: #064e3b; }
        .footer { position: fixed; bottom: -22px; left: 0; right: 0; text-align: center; color: #9ca3af; font-size: 8px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>Relatório mensal de comissões</h1>
                <div class="muted">
                    {{ $empresa->razao_social }}
                    @if($empresa->cnpj) · CNPJ {{ $empresa->cnpj }} @endif
                </div>
            </td>
            <td class="right">
                <strong>Período:</strong> {{ $periodo['inicio']->format('d/m/Y') }} até {{ $periodo['fim']->format('d/m/Y') }}<br>
                <strong>Vendedor/responsável:</strong> {{ $vendedorSelecionado?->name ?? 'Todos' }}<br>
                <span class="muted">Gerado em {{ now()->format('d/m/Y H:i') }}</span>
            </td>
        </tr>
    </table>

    <div class="box">
        Este relatório considera os pedidos gerados a partir de orçamentos finalizados no mês selecionado.
        A base de comissão é o valor vendido sem impostos. O sistema calcula o desconto médio ponderado do período
        e aplica a faixa de comissão cadastrada que contém esse desconto.
    </div>

    <h2>Resumo por vendedor/responsável</h2>
    <table class="summary">
        <thead>
            <tr>
                <th>Vendedor/responsável</th>
                <th class="center">Qtd. pedidos</th>
                <th class="money">Valor em pedidos</th>
                <th class="money">Base sem impostos</th>
                <th class="center">Desconto médio</th>
                <th class="center">Faixa aplicada</th>
                <th class="center">Comissão %</th>
                <th class="money">Comissão a pagar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resumoPorVendedor as $linha)
                <tr>
                    <td>{{ $linha['vendedor']?->name ?? 'Usuário removido' }}</td>
                    <td class="center">{{ $linha['quantidade'] }}</td>
                    <td class="money">R$ {{ number_format((float) $linha['valor_pedidos'], 2, ',', '.') }}</td>
                    <td class="money">R$ {{ number_format((float) $linha['base_comissao'], 2, ',', '.') }}</td>
                    <td class="center">{{ number_format((float) $linha['desconto_medio'], 2, ',', '.') }}%</td>
                    <td class="center">
                        @if($linha['faixa'])
                            {{ number_format((float) $linha['faixa']->desconto_inicial_percentual, 2, ',', '.') }}%
                            a
                            {{ number_format((float) $linha['faixa']->desconto_final_percentual, 2, ',', '.') }}%
                        @else
                            -
                        @endif
                    </td>
                    <td class="center">{{ number_format((float) $linha['comissao_percentual'], 2, ',', '.') }}%</td>
                    <td class="money">R$ {{ number_format((float) $linha['valor_comissao'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center muted">Nenhum pedido encontrado para o filtro selecionado.</td></tr>
            @endforelse
            <tr class="total-row">
                <td>Total</td>
                <td class="center">{{ $totalizador['quantidade'] }}</td>
                <td class="money">R$ {{ number_format((float) $totalizador['valor_pedidos'], 2, ',', '.') }}</td>
                <td class="money">R$ {{ number_format((float) $totalizador['base_comissao'], 2, ',', '.') }}</td>
                <td class="center">{{ number_format((float) $totalizador['desconto_medio'], 2, ',', '.') }}%</td>
                <td class="center">
                    @if($totalizador['faixa'])
                        {{ number_format((float) $totalizador['faixa']->desconto_inicial_percentual, 2, ',', '.') }}%
                        a
                        {{ number_format((float) $totalizador['faixa']->desconto_final_percentual, 2, ',', '.') }}%
                    @else
                        -
                    @endif
                </td>
                <td class="center">{{ number_format((float) $totalizador['comissao_percentual'], 2, ',', '.') }}%</td>
                <td class="money">R$ {{ number_format((float) $totalizador['valor_comissao'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Pedidos considerados</h2>
    <table class="items">
        <thead>
            <tr>
                <th>Data</th>
                <th>Pedido</th>
                <th>Orçamento</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th class="money">Valor pedido</th>
                <th class="money">Base sem impostos</th>
                <th class="center">Desc.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pedidos as $pedido)
                <tr>
                    <td>{{ $pedido->data_pedido->format('d/m/Y') }}</td>
                    <td>{{ $pedido->numero }}</td>
                    <td>{{ $pedido->orcamento?->numero ?? '-' }}</td>
                    <td>{{ $pedido->cliente?->nome_fantasia ?: $pedido->cliente?->nome_razao_social }}</td>
                    <td>{{ $pedido->vendedor?->name ?? 'Usuário removido' }}</td>
                    <td class="money">R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</td>
                    <td class="money">R$ {{ number_format((float) $pedido->total_produtos, 2, ',', '.') }}</td>
                    <td class="center">{{ number_format((float) $pedido->desconto_efetivo_percentual, 2, ',', '.') }}%</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center muted">Nenhum pedido encontrado para o filtro selecionado.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="5">Total</td>
                <td class="money">R$ {{ number_format((float) $totalizador['valor_pedidos'], 2, ',', '.') }}</td>
                <td class="money">R$ {{ number_format((float) $totalizador['base_comissao'], 2, ',', '.') }}</td>
                <td class="center">{{ number_format((float) $totalizador['desconto_medio'], 2, ',', '.') }}%</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">{{ $empresa->razao_social }} · Relatório de comissões · {{ $periodo['mes'] }}</div>
</body>
</html>
