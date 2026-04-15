<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CustomFieldDefinition;

/**
 * Auto-mapping de colunas da planilha do user → campos do Lead.
 *
 * Usado pelo wizard de import (endpoint /contatos/importar/preview).
 * Reduz o esforço do user: se a planilha tem headers razoáveis
 * ("Cliente", "Celular", "Orçamento"), já pré-seleciona o mapping certo.
 */
class LeadsImportMapper
{
    /** Campos nativos do Lead + aliases prováveis (lowercase, sem acento). */
    private const NATIVE_ALIASES = [
        'name'    => ['nome', 'name', 'cliente', 'contato', 'lead', 'pessoa', 'nome completo', 'full name'],
        'phone'   => ['telefone', 'phone', 'celular', 'whatsapp', 'wpp', 'fone', 'telemovel', 'mobile', 'numero'],
        'email'   => ['email', 'e-mail', 'mail', 'endereco de email', 'email address'],
        'company' => ['empresa', 'company', 'organizacao', 'cia', 'razao social', 'business'],
        'value'   => ['valor', 'value', 'orcamento', 'budget', 'preco', 'price', 'total', 'montante'],
        'source'  => ['origem', 'source', 'canal', 'origin', 'fonte', 'captacao'],
        'notes'   => ['observacoes', 'observacao', 'notes', 'anotacoes', 'comentario', 'obs'],
        'tags'    => ['tags', 'etiquetas', 'rotulos', 'labels'],
    ];

    /** Threshold mínimo de similaridade (0-100) pra considerar match confiável. */
    private const THRESHOLD = 70;

    /**
     * Retorna a lista completa de fields disponíveis pro dropdown do wizard,
     * incluindo custom fields dinâmicos do tenant.
     *
     * @return list<array{key: string, label: string, required?: bool}>
     */
    public static function availableFields(?int $tenantId = null): array
    {
        $tenantId ??= activeTenantId();

        $fields = [
            ['key' => 'name',    'label' => 'Nome',                   'required' => true],
            ['key' => 'phone',   'label' => 'Telefone'],
            ['key' => 'email',   'label' => 'E-mail'],
            ['key' => 'company', 'label' => 'Empresa'],
            ['key' => 'value',   'label' => 'Valor'],
            ['key' => 'source',  'label' => 'Origem'],
            ['key' => 'notes',   'label' => 'Observações'],
            ['key' => 'tags',    'label' => 'Tags (separadas por vírgula)'],
        ];

        $customs = CustomFieldDefinition::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'label']);

        foreach ($customs as $cf) {
            $fields[] = [
                'key'   => 'custom:' . $cf->id,
                'label' => 'Custom: ' . $cf->label,
            ];
        }

        $fields[] = ['key' => '__skip', 'label' => '— Ignorar coluna —'];

        return $fields;
    }

    /**
     * Detecta automaticamente o mapping dos headers da planilha.
     *
     * @param list<string> $fileHeaders Headers como aparecem no arquivo
     * @return array<string, string> header => fieldKey (ou '__skip' se não reconhecido)
     */
    public static function autoDetect(array $fileHeaders, ?int $tenantId = null): array
    {
        $tenantId ??= activeTenantId();

        // Monta pool de aliases: nativo + custom fields
        $pool = self::NATIVE_ALIASES;

        $customs = CustomFieldDefinition::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get(['id', 'name', 'label']);

        foreach ($customs as $cf) {
            $pool['custom:' . $cf->id] = [
                self::normalize($cf->name),
                self::normalize($cf->label),
            ];
        }

        $mapping = [];
        $usedFields = []; // previne mapear o mesmo field pra 2 colunas diferentes

        foreach ($fileHeaders as $header) {
            $normalized = self::normalize((string) $header);
            if ($normalized === '') {
                $mapping[$header] = '__skip';
                continue;
            }

            $bestField = null;
            $bestScore = 0;

            foreach ($pool as $fieldKey => $aliases) {
                if (in_array($fieldKey, $usedFields, true)) {
                    continue; // já mapeado
                }

                foreach ($aliases as $alias) {
                    // Match exato primeiro
                    if ($normalized === $alias) {
                        $bestField = $fieldKey;
                        $bestScore = 100;
                        break 2;
                    }

                    // Similar_text pra fuzzy
                    similar_text($normalized, $alias, $pct);
                    if ($pct > $bestScore) {
                        $bestScore = (int) $pct;
                        $bestField = $fieldKey;
                    }
                }
            }

            if ($bestField !== null && $bestScore >= self::THRESHOLD) {
                $mapping[$header] = $bestField;
                $usedFields[] = $bestField;
            } else {
                $mapping[$header] = '__skip';
            }
        }

        return $mapping;
    }

    /**
     * Normaliza string pra comparação: lowercase + remove acentos + trim.
     */
    private static function normalize(string $str): string
    {
        $str = mb_strtolower(trim($str));
        $map = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];
        return strtr($str, $map);
    }
}
