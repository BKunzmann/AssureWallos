<?php

require_once __DIR__ . '/subscriptions_list_query.php';
require_once __DIR__ . '/subscriptions_table_columns.php';
require_once __DIR__ . '/subscriptions_table_data.php';

/**
 * Loads and sorts subscription rows for the table view.
 */
class Ins_Subscriptions_Table_Bootstrap
{
    /**
     * @return array{
     *   subscriptions: list<array<string, mixed>>,
     *   displayRows: list<array<string, mixed>>,
     *   sort: string,
     *   sortOrder: string
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
        int $mainCurrencyId
    ): array {
        $built = Ins_Subscriptions_List_Query::insBuild($userId, $settings, $getParams, 'ajax');
        $sort = $built['sort'];
        $sortOrder = $built['sortOrder'];

        $subscriptions = Ins_Subscriptions_List_Query::insFetchRows($db, $userId, $settings, $getParams, 'ajax');

        if ($sortOrder === 'category_id') {
            usort($subscriptions, static function ($a, $b) use ($categories) {
                return ($categories[$a['category_id']]['order'] ?? 0) - ($categories[$b['category_id']]['order'] ?? 0);
            });
        }

        if ($sortOrder === 'payment_method_id') {
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

        if ($sortOrder === 'alphanumeric') {
            usort($displayRows, static function ($a, $b) {
                return strnatcmp(strtolower((string) $a['name']), strtolower((string) $b['name']));
            });
        }

        return [
            'subscriptions' => $subscriptions,
            'displayRows' => $displayRows,
            'sort' => $sort,
            'sortOrder' => $sortOrder,
        ];
    }
}
