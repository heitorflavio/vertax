<?php

namespace App\Demo;

class DemoConfig
{
    public const DISCLAIMER = 'Ambiente 100% demonstrativo. Todos os nomes, CPFs, endereços, contatos e scores são fictícios, sintéticos e inválidos. Nada aqui consulta bases reais ou identifica pessoas.';

    public const SHORT_DISCLAIMER = 'Dados 100% fictícios para demonstração de UI/UX.';

    public const COPY_NOTICE = 'AVISO: resultado gerado pelo protótipo ConsultaDEMO. Todos os dados são fictícios e sem validade legal.';

    /**
     * Campos canônicos usados na busca, mesclagem e apresentação.
     *
     * @var array<string, string>
     */
    public const FIELD_LABELS = [
        'full_name' => 'Nome completo',
        'cpf' => 'CPF',
        'birth_date' => 'Data de nascimento',
        'cnh' => 'CNH',
        'cnpj' => 'CNPJ vinculado',
        'mother_name' => 'Nome da mãe',
        'email' => 'E-mail',
        'phone' => 'Telefone',
        'address' => 'Endereço',
        'city' => 'Cidade',
        'state' => 'UF',
    ];

    /**
     * Campos usados pelo merge multi-fonte.
     *
     * @var list<string>
     */
    public const MERGE_FIELDS = [
        'full_name',
        'cpf',
        'birth_date',
        'cnh',
        'cnpj',
        'mother_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
    ];

    /**
     * Campos mesclados que nem todo registro tem (não contam na completude).
     *
     * @var list<string>
     */
    public const OPTIONAL_FIELDS = [
        'cnh',
        'cnpj',
    ];

    /**
     * Como cada termo de busca foi encontrado (exibido nos resultados).
     *
     * @var array<string, string>
     */
    public const SEARCH_TYPE_LABELS = [
        'id' => 'ID',
        'name' => 'Nome',
        'cpf' => 'CPF',
        'cnpj' => 'CNPJ',
        'cnh' => 'CNH',
        'email' => 'E-mail',
        'phone' => 'Telefone',
    ];

    /**
     * Campos exibidos no bloco principal do resultado.
     *
     * @var list<string>
     */
    public const BASIC_FIELDS = [
        'full_name',
        'cpf',
        'birth_date',
    ];

    /**
     * Peso de cada dimensão no score de confiança (somam 1.0).
     *
     * @var array<string, float>
     */
    public const TRUST_WEIGHTS = [
        'source_quality' => 0.35,
        'completeness' => 0.25,
        'consistency' => 0.25,
        'freshness' => 0.15,
    ];

    /**
     * @var array<string, string>
     */
    public const HIDDEN_LABELS = [
        'registro_interno' => 'Registro interno',
        'observacao_sigilosa' => 'Observação sigilosa',
        'email_alternativo' => 'E-mail alternativo',
        'canal_preferido' => 'Canal preferido',
        'geocodigo' => 'Geocódigo',
        'renda_presumida' => 'Renda presumida',
        'score_credito' => 'Score de crédito',
        'vinculo_societario' => 'Vínculo societário',
        'alerta_fraude' => 'Alerta de fraude',
    ];

    public const DEFAULT_PLAN_SLUG = 'demo-pro';

    /**
     * @return array<string, string>
     */
    public static function fieldLabels(): array
    {
        return self::FIELD_LABELS;
    }

    /**
     * @return array<string, string>
     */
    public static function hiddenLabels(): array
    {
        return self::HIDDEN_LABELS;
    }

    public static function trustLabel(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Alto',
            $score >= 65 => 'Moderado',
            default => 'Baixo',
        };
    }

    public static function trustExplanation(string $label): string
    {
        return match ($label) {
            'Alto' => 'As fontes fictícias concordam na maior parte dos campos e os payloads são recentes.',
            'Moderado' => 'Há divergências ou lacunas nas fontes fictícias. O resultado é apenas ilustrativo.',
            default => 'Poucas fontes, dados incompletos ou divergentes. Score demonstrativo baixo.',
        };
    }
}
