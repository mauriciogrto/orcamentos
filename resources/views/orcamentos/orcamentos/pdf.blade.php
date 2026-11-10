<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $orcamento->numero }}</title>
    <style>
        @page { margin: 28px 34px 40px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; line-height: 1.4; }
        .header { width: 100%; border-bottom: 2px solid #4338ca; padding-bottom: 14px; margin-bottom: 18px; }
        .header td { vertical-align: middle; }
        .logo-cell { width: 180px; padding-right: 18px; }
        .logo { display: block; max-width: 165px; max-height: 75px; }
        .company { vertical-align: middle; }
        .title { text-align: right; }
        h1 { color: #312e81; font-size: 24px; margin: 0 0 5px; }
        h2 { color: #312e81; font-size: 13px; margin: 0 0 8px; }
        .muted { color: #6b7280; }
        .box { border: 1px solid #d1d5db; padding: 11px; margin-bottom: 14px; }
        .columns { width: 100%; }
        .columns td { width: 50%; vertical-align: top; padding-right: 12px; }
        .columns td:last-child { padding-right: 0; padding-left: 12px; }
        table.items { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 14px; }
        .items th:nth-child(1) { width: 43%; }
        .items th:nth-child(2) { width: 5%; }
        .items th:nth-child(3) { width: 11%; }
        .items th:nth-child(4) { width: 7%; }
        .items th:nth-child(5) { width: 11%; }
        .items th:nth-child(6) { width: 11%; }
        .items th:nth-child(7) { width: 12%; }
        .items th { background: #4338ca; color: white; padding: 8px 6px; text-align: left; font-size: 10px; }
        .items td { border-bottom: 1px solid #e5e7eb; padding: 8px 6px; vertical-align: top; }
        .right { text-align: right !important; }
        .items .right { white-space: nowrap; }
        .center { text-align: center !important; }
        .totals { width: 45%; margin-left: 55%; border-collapse: collapse; }
        .totals td { padding: 5px 7px; }
        .totals .section td { border-top: 1px solid #c7d2fe; padding-top: 8px; color: #312e81; font-weight: bold; }
        .totals .tax-total td { background: #eef2ff; font-weight: bold; }
        .totals .final td { border-top: 2px solid #4338ca; color: #312e81; font-size: 14px; font-weight: bold; }
        .footer { position: fixed; bottom: -24px; left: 0; right: 0; text-align: center; color: #9ca3af; font-size: 9px; }
        .notes { white-space: pre-wrap; }
    </style>
</head>
<body>
    <table class="header"><tr>
        @if($logo)
        <td class="logo-cell"><img src="{!! $logo !!}" class="logo" alt="Logo da {{ $orcamento->empresa->razao_social }}"></td>
        @endif
        <td class="company">
            <strong>{{ $orcamento->empresa->razao_social }}</strong><br>
            @if($orcamento->empresa->cnpj)CNPJ: {{ $orcamento->empresa->cnpj }}<br>@endif
            @php
                $enderecoEmpresa = collect([
                    collect([$orcamento->empresa->logradouro, $orcamento->empresa->numero])->filter()->implode(', '),
                    $orcamento->empresa->bairro,
                    collect([$orcamento->empresa->cidade, $orcamento->empresa->uf])->filter()->implode(' / '),
                    $orcamento->empresa->cep ? 'CEP '.$orcamento->empresa->cep : null,
                ])->filter()->implode(' - ');
            @endphp
            @if($enderecoEmpresa){{ $enderecoEmpresa }}<br>@endif
            {{ $orcamento->empresa->telefone }} @if($orcamento->empresa->telefone && $orcamento->empresa->email) | @endif {{ $orcamento->empresa->email }}
        </td>
        <td class="title"><h1>ORÇAMENTO</h1><strong>{{ $orcamento->numero }}</strong><br><span class="muted">Emissão: {{ $orcamento->data_emissao->format('d/m/Y') }}</span></td>
    </tr></table>

    <table class="columns"><tr>
        <td><div class="box"><h2>Cliente</h2><strong>{{ $orcamento->cliente->nome_razao_social }}</strong><br>@if($orcamento->cliente->cnpj)CNPJ: {{ $orcamento->cliente->cnpj }}<br>@endif
            @php
                $enderecoCliente = collect([
                    collect([$orcamento->cliente->logradouro, $orcamento->cliente->numero])->filter()->implode(', '),
                    $orcamento->cliente->bairro,
                    collect([$orcamento->cliente->cidade, $orcamento->cliente->uf])->filter()->implode(' / '),
                    $orcamento->cliente->cep ? 'CEP '.$orcamento->cliente->cep : null,
                ])->filter()->implode(' - ');
            @endphp
            @if($enderecoCliente){{ $enderecoCliente }}<br>@endif
            {{ $orcamento->cliente->telefone }}<br>{{ $orcamento->cliente->email }}</div></td>
        <td><div class="box"><h2>Proposta</h2>Responsável: {{ $orcamento->vendedor->name }}<br>Finalidade: {{ $orcamento->finalidade?->label() ?? 'Revenda' }}<br>Validade: {{ $orcamento->validade_dias }} dias<br>Frete: {{ $orcamento->frete_tipo->label() }}<br>Pagamento: {{ $orcamento->forma_pagamento->label() }} @if($orcamento->boleto_prazos) - {{ $orcamento->boleto_prazos }} dias @endif</div></td>
    </tr></table>

    <table class="items">
        <thead><tr><th>Item</th><th class="center">Qtd.</th><th class="right">Preço-base</th><th class="right">Desc.</th><th class="right">Produtos</th><th class="right">Impostos</th><th class="right">Total</th></tr></thead>
        <tbody>@foreach($orcamento->itens as $linha)<tr>
            <td><strong>{{ $linha->descricao }}</strong><br><span class="muted">{{ $linha->item?->numero_serie ?: '-' }} | {{ $linha->tipo_venda === 'metro' ? 'Metro' : 'Unitário' }} | Embalagem {{ number_format((float)($linha->embalagem_quantidade ?? 1), 3, ',', '.') }} {{ $linha->tipo_venda === 'metro' ? 'm' : 'un.' }}@if($linha->ncm) | NCM {{ $linha->ncm }}@endif<br>ICMS {{ number_format((float)$linha->aliquota_icms, 2, ',', '.') }}% | IPI {{ number_format((float)$linha->aliquota_ipi, 2, ',', '.') }}% | PIS {{ number_format((float)$linha->aliquota_pis, 2, ',', '.') }}% | COFINS {{ number_format((float)$linha->aliquota_cofins, 2, ',', '.') }}%@if($linha->possui_st) | ST MVA {{ number_format((float)$linha->mva, 2, ',', '.') }}%@endif</span></td><td class="center">{{ $linha->quantidade }}</td><td class="right">R$ {{ number_format((float)$linha->valor_unitario, 2, ',', '.') }}</td><td class="right">{{ number_format((float)$linha->desconto_percentual, 2, ',', '.') }}%</td><td class="right">R$ {{ number_format((float)$linha->valor_produtos, 2, ',', '.') }}</td><td class="right">R$ {{ number_format((float)$linha->valor_impostos, 2, ',', '.') }}</td><td class="right">R$ {{ number_format((float)$linha->valor_total, 2, ',', '.') }}</td>
        </tr>@endforeach</tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal-base</td><td class="right">R$ {{ number_format((float)$orcamento->subtotal_sem_desconto, 2, ',', '.') }}</td></tr>
        <tr><td>Desconto efetivo</td><td class="right">{{ number_format((float)$orcamento->desconto_efetivo_percentual, 2, ',', '.') }}%</td></tr>
        <tr class="section"><td>Valor dos produtos</td><td class="right">R$ {{ number_format((float)$orcamento->total_produtos, 2, ',', '.') }}</td></tr>
        <tr><td>ICMS</td><td class="right">R$ {{ number_format($tributos['icms'], 2, ',', '.') }}</td></tr>
        <tr><td>IPI</td><td class="right">R$ {{ number_format($tributos['ipi'], 2, ',', '.') }}</td></tr>
        <tr><td>PIS</td><td class="right">R$ {{ number_format($tributos['pis'], 2, ',', '.') }}</td></tr>
        <tr><td>COFINS</td><td class="right">R$ {{ number_format($tributos['cofins'], 2, ',', '.') }}</td></tr>
        <tr><td>ICMS-ST</td><td class="right">R$ {{ number_format($tributos['icms_st'], 2, ',', '.') }}</td></tr>
        <tr class="tax-total"><td>Total dos impostos</td><td class="right">R$ {{ number_format((float)$orcamento->total_impostos, 2, ',', '.') }}</td></tr>
        <tr class="final"><td>Valor final do pedido</td><td class="right">R$ {{ number_format((float)$orcamento->valor_total, 2, ',', '.') }}</td></tr>
    </table>

    @if($orcamento->observacoes)<div class="box" style="margin-top: 18px"><h2>Observações</h2><div class="notes">{{ $orcamento->observacoes }}</div></div>@endif
    <div class="footer">{{ $orcamento->empresa->razao_social }} - {{ $orcamento->numero }}</div>
</body>
</html>
