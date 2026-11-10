<?php

namespace App\Enums;

/**
 * Permissões do sistema. O valor é o nome gravado na tabela `permissions`
 * e o que as rotas checam com o middleware `can:`.
 */
enum Permission: string
{
    case RunConsultations = 'consultas.realizar';
    case ViewPanel = 'painel.visualizar';
    case SyncSources = 'fontes.sincronizar';
    case ManageSources = 'fontes.gerenciar';
}
