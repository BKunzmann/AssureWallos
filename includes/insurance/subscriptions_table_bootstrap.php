<?php

require_once __DIR__ . '/subscriptions_list_query.php';
require_once __DIR__ . '/subscriptions_table_columns.php';
require_once __DIR__ . '/subscriptions_table_data.php';
require_once __DIR__ . '/subscriptions_table_grouping.php';
require_once __DIR__ . '/ins_repository.php';

/**
 * Loads and sorts subscription rows for the table view.
 */
class Ins_Subscriptions_Table_Bootstrap
{
    /**
     * @return array{
     *   subscriptions: list<array<string, mixed>>,
     *   displayRows: list<array<string, mixed>>,
     *   renderBlocks: list<array<string, mixed>>,
     *   sort: string,
     *   sortOrder: string,
     *   sortDir: string,
     *   group: string,
     *   group2: string
     * }
     */
    public static function insLoad(
        SQLite3 $db,
        int $userId,
        array $settings,
        array $getParams,
        array $categories,
        array $payment_methods,
        array $members,
        array $cycles,
        array $currencies,
        int $mainCurrencyId,
        ?array $columnIds = null
    ): array {
        $withInsurance = Ins_Repository::insSchemaReady($db);
        $built = Ins_Subscriptions_List_Query::insBuild(
            $userId,
            $settings,
            $getParams,
            'ajax',
            ['with_insurance' => $withInsurance, 'db' => $db]
        );
        $sort = $built['sort'];
        $sortOrder = $built['sortOrder'];
        $sortDir = $built['order'];
        $group = $built['group'];
        $group2 = $built['group2'];

        $subscriptions = Ins_Subscriptions_List_Query::insFetchRows(
            $db,
            $userId,
            $settings,
            $getParams,
            'ajax',
            ['with_insurance' => $withInsurance, 'db' => $db]
        );

        if ($sortOrder === 'category' || $sort === 'category') {
            usort($subscriptions, static function ($a, $b) use ($categories) {
                return ($categories[$a['category_id']]['order'] ?? 0) - ($categories[$b['category_id']]['order'] ?? 0);
            });
        }

        if ($sortOrder === 'payment_method' || $sort === 'payment_method') {
            usort($subscriptions, static function ($a, $b) use ($payment_methods) {
                return ($payment_methods[$a['payment_method_id']]['order'] ?? 0)
                    - ($payment_methods[$b['payment_method_id']]['order'] ?? 0);
            });
        }

        $displayRows = Ins_Subscriptions_Table_Data::insBuildDisplayRows(
            $db,
            $userId,
            $subscriptions,
            $categories,
            $payment_methods,
            $members,
            $cycles,
            $currencies,
            $settings,
            $mainCurrencyId
        );

        if ($sort === 'alphanumeric' || $sortOrder === 'alphanumeric') {
            usort($displayRows, static function ($a, $b) {
                return strnatcmp(strtolower((string) $a['name']), strtolower((string) $b['name']));
            });
            if (strtoupper($sortDir) === 'DESC') {
                $displayRows = array_reverse($displayRows);
            }
        }

        if ($sort === 'cycle') {
            usort($displayRows, static function ($a, $b) use ($sortDir) {
                $cmp = strnatcmp((string) ($a['cells']['cycle'] ?? ''), (string) ($b['cells']['cycle'] ?? ''));

                return strtoupper($sortDir) === 'DESC' ? -$cmp : $cmp;
            });
        }

        $visible = $columnIds ?? Ins_Subscriptions_Table_Columns::insNormalizeVisible(null);
        $renderBlocks = Ins_Subscriptions_Table_Grouping::insBuildBlocks(
            $displayRows,
            $group,
            $group2,
            $visible
        );

        return [
            'subscriptions' => $subscriptions,
            'displayRows' => $displayRows,
            'renderBlocks' => $renderBlocks,
            'sort' => $sort,
            'sortOrder' => $sortOrder,
            'sortDir' => $sortDir,
            'group' => $group,
            'group2' => $group2,
        ];
    }
}
