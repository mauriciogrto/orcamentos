<?php

namespace App\Enums;

enum PermissionName: string
{
    case GerenciarEmpresas = 'empresas.gerenciar';
    case GerenciarModulos = 'modulos.gerenciar';
    case GerenciarUsuariosGlobais = 'usuarios.gerenciar_global';
    case VisualizarAuditoriaGlobal = 'auditoria.visualizar_global';
    case GerenciarUsuariosEmpresa = 'usuarios.gerenciar_empresa';
    case GerenciarComissionamento = 'comissionamento.gerenciar';
    case VisualizarTodosClientes = 'clientes.visualizar_todos';
    case VisualizarPropriosClientes = 'clientes.visualizar_proprios';
    case CriarPropriosClientes = 'clientes.criar_proprios';
    case EditarPropriosClientes = 'clientes.editar_proprios';
    case ExcluirPropriosClientes = 'clientes.excluir_proprios';
    case CriarTodosClientes = 'clientes.criar_todos';
    case EditarTodosClientes = 'clientes.editar_todos';
    case ExcluirTodosClientes = 'clientes.excluir_todos';
    case VisualizarItens = 'itens.visualizar';
    case GerenciarItens = 'itens.gerenciar';
    case VisualizarHistoricoItens = 'itens.visualizar_historico';
    case VisualizarTodosOrcamentos = 'orcamentos.visualizar_todos';
    case CriarTodosOrcamentos = 'orcamentos.criar_todos';
    case EditarTodosOrcamentos = 'orcamentos.editar_todos';
    case ExcluirTodosOrcamentos = 'orcamentos.excluir_todos';
    case VisualizarPropriosOrcamentos = 'orcamentos.visualizar_proprios';
    case CriarPropriosOrcamentos = 'orcamentos.criar_proprios';
    case EditarPropriosOrcamentos = 'orcamentos.editar_proprios';
    case ExcluirPropriosOrcamentos = 'orcamentos.excluir_proprios';
}
