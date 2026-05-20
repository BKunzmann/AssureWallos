<?php

require_once __DIR__ . '/subscriptions_table_dimensions.php';

/**
 * Builds grouped render blocks with sum rows for table view.
 */
class Ins_Subscriptions_Table_Grouping
{
    /**
     * @param list<array<string, mixed>> $displayRows
     * @param list<string> $visibleColumnIds
     * @return list<array<string, mixed>>
     */
    public static function insBuildBlocks(
        array $displayRows,
        ?string $groupPrimary,
        ?string $groupSecondary,
        array $visibleColumnIds
    ): array {
        $groupPrimary = Ins_Subscriptions_Table_Dimensions::insValidateGroupKey($groupPrimary ?? 'none') ?? 'none';
        $groupSecondary = $groupSecondary !== null && $groupSecondary !== ''
            ? (Ins_Subscriptions_Table_Dimensions::insValidateGroupKey($groupSecondary) ?? 'none')
            : 'none';

        if ($groupPrimary === 'none') {
            return array_map(static fn ($row) => ['type' => 'data', 'row' => $row], $displayRows);
        }

        /** @var array<string, array<string, list<array<string, mixed>>>> $tree */
        $tree = [];
        foreach ($displayRows as $row) {
            $p = self::insRowGroupLabel($row, $groupPrimary);
            $s = $groupSecondary !== 'none' ? self::insRowGroupLabel($row, $groupSecondary) : "\0";
            $tree[$p][$s][] = $row;
        }
        ksort($tree);
        foreach ($tree as &$secondaries) {
            ksort($secondaries);
        }
        unset($secondaries);

        $blocks = [];
        foreach ($tree as $pLabel => $secondaries) {
            $blocks[] = [
                'type' => 'group_header',
                'level' => 1,
                'label' => $pLabel,
                'dimension' => $groupPrimary,
            ];

            $primaryRows = [];
            foreach ($secondaries as $sLabel => $rows) {
                if ($groupSecondary !== 'none') {
                    $blocks[] = [
                        'type' => 'group_header',
                        'level' => 2,
                        'label' => $sLabel,
                        'dimension' => $groupSecondary,
                    ];
                }
                foreach ($rows as $row) {
                    $blocks[] = ['type' => 'data', 'row' => $row];
                    $primaryRows[] = $row;
                }
                $blocks[] = self::insSumBlock($rows, $visibleColumnIds, $groupSecondary !== 'none' ? 2 : 1, $groupSecondary !== 'none' ? $groupSecondary : $groupPrimary, false);
            }

            if ($groupSecondary !== 'none' && count($secondaries) > 1) {
                $blocks[] = self::insSumBlock($primaryRows, $visibleColumnIds, 1, $groupPrimary, false);
            }
        }

        return $blocks;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private static function insSumBlock(
        array $rows,
        array $visibleColumnIds,
        int $level,
        string $dimension,
        bool $isGrand
    ): array {
        $count = count($rows);
        $sums = [];
        foreach (Ins_Subscriptions_Table_Dimensions::insSummableColumnIds() as $colId) {
            if (!in_array($colId, $visibleColumnIds, true)) {
                continue;
            }
            $total = 0.0;
            $currencies = [];
            foreach ($rows as $row) {
                $numericKey = $colId . '_numeric';
                if (isset($row[$numericKey])) {
                    $total += (float) $row[$numericKey];
                }
                $cc = (string) ($row['currency_code'] ?? '');
                if ($cc !== '') {
                    $currencies[$cc] = true;
                }
            }
            $sums[$colId] = [
                'total' => $total,
                'mixed_currency' => count($currencies) > 1,
                'currency' => count($currencies) === 1 ? array_key_first($currencies) : '',
            ];
        }

        return [
            'type' => 'group_sum',
            'level' => $level,
            'dimension' => $dimension,
            'is_grand' => $isGrand,
            'count' => $count,
            'sums' => $sums,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function insRowGroupLabel(array $row, string $dimension): string
    {
        $labels = $row['group_labels'] ?? [];
        if (isset($labels[$dimension]) && (string) $labels[$dimension] !== '') {
            return (string) $labels[$dimension];
        }

        return '(leer)';
    }
}
