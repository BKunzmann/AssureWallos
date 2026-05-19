<?php

/**
 * Shared subscription list SQL for cards (subscriptions.php) and table view (AssureWallos).
 */

class Ins_Subscriptions_List_Query
{
    private const ALLOWED_SORT = [
        'name', 'id', 'next_payment', 'price', 'payer_user_id',
        'category_id', 'payment_method_id', 'inactive', 'alphanumeric', 'renewal_type',
    ];

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $getParams $_GET or equivalent
     * @param string $mode 'page' (subscriptions.php) or 'ajax' (get.php / table)
     * @return array{sql: string, params: array<string, int|string>, sort: string, sortOrder: string, order: string}
     */
    public static function insBuild(int $userId, array $settings, array $getParams, string $mode = 'ajax'): array
    {
        $params = [];
        $sql = 'SELECT * FROM subscriptions WHERE user_id = :userId';
        $params[':userId'] = $userId;

        if ($mode === 'page') {
            self::insApplyPageFilters($sql, $params, $getParams, $settings);
        } else {
            self::insApplyAjaxFilters($sql, $params, $getParams);
        }

        $sortInfo = self::insResolveSort($settings, $getParams);
        $sql .= ' ORDER BY ' . implode(', ', $sortInfo['orderByClauses']);

        return [
            'sql' => $sql,
            'params' => $params,
            'sort' => $sortInfo['sort'],
            'sortOrder' => $sortInfo['sortOrder'],
            'order' => $sortInfo['order'],
        ];
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $getParams
     * @return list<array<string, mixed>>
     */
    public static function insFetchRows(SQLite3 $db, int $userId, array $settings, array $getParams, string $mode = 'ajax'): array
    {
        $built = self::insBuild($userId, $settings, $getParams, $mode);
        $stmt = $db->prepare($built['sql']);
        foreach ($built['params'] as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        }

        $rows = [];
        $result = $stmt->execute();
        while ($result && ($row = $result->fetchArray(SQLITE3_ASSOC))) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array<string, int|string> $params
     * @param array<string, mixed> $getParams
     */
    /**
     * @param array<string, mixed> $settings
     */
    private static function insApplyPageFilters(string &$sql, array &$params, array $getParams, array $settings): void
    {
        if (isset($getParams['member']) && $getParams['member'] !== '') {
            $memberIds = explode(',', (string) $getParams['member']);
            $placeholders = [];
            foreach ($memberIds as $key => $memberId) {
                $ph = ":member{$key}";
                $placeholders[] = $ph;
                $params[$ph] = (int) $memberId;
            }
            $sql .= ' AND payer_user_id IN (' . implode(',', $placeholders) . ')';
        }

        if (isset($getParams['category']) && $getParams['category'] !== '') {
            $categoryIds = explode(',', (string) $getParams['category']);
            $placeholders = [];
            foreach ($categoryIds as $key => $categoryId) {
                $ph = ":category{$key}";
                $placeholders[] = $ph;
                $params[$ph] = (int) $categoryId;
            }
            $sql .= ' AND category_id IN (' . implode(',', $placeholders) . ')';
        }

        if (isset($getParams['payment']) && $getParams['payment'] !== '') {
            $paymentIds = explode(',', (string) $getParams['payment']);
            $placeholders = [];
            foreach ($paymentIds as $key => $paymentId) {
                $ph = ":payment{$key}";
                $placeholders[] = $ph;
                $params[$ph] = (int) $paymentId;
            }
            $sql .= ' AND payment_method_id IN (' . implode(',', $placeholders) . ')';
        }

        $hideDisabled = ($settings['hideDisabledSubscriptions'] ?? '') === 'true';
        if (!$hideDisabled && isset($getParams['state']) && $getParams['state'] !== '') {
            $sql .= ' AND inactive = :inactive';
            $params[':inactive'] = (int) $getParams['state'];
        }
    }

    /**
     * @param array<string, int|string> $params
     * @param array<string, mixed> $getParams
     */
    private static function insApplyAjaxFilters(string &$sql, array &$params, array $getParams): void
    {
        if (!empty($getParams['categories'])) {
            $all = explode(',', (string) $getParams['categories']);
            $parts = [];
            foreach ($all as $idx => $category) {
                $ph = ":categories{$idx}";
                $parts[] = "category_id = {$ph}";
                $params[$ph] = (int) $category;
            }
            $sql .= ' AND (' . implode(' OR ', $parts) . ')';
        }

        if (!empty($getParams['payments'])) {
            $all = explode(',', (string) $getParams['payments']);
            $parts = [];
            foreach ($all as $idx => $payment) {
                $ph = ":payments{$idx}";
                $parts[] = "payment_method_id = {$ph}";
                $params[$ph] = (int) $payment;
            }
            $sql .= ' AND (' . implode(' OR ', $parts) . ')';
        }

        if (!empty($getParams['members'])) {
            $all = explode(',', (string) $getParams['members']);
            $parts = [];
            foreach ($all as $idx => $member) {
                $ph = ":members{$idx}";
                $parts[] = "payer_user_id = {$ph}";
                $params[$ph] = (int) $member;
            }
            $sql .= ' AND (' . implode(' OR ', $parts) . ')';
        }

        if (isset($getParams['state']) && $getParams['state'] !== '') {
            $sql .= ' AND inactive = :inactive';
            $params[':inactive'] = (int) $getParams['state'];
        }

        if (isset($getParams['renewalType']) && $getParams['renewalType'] !== '') {
            $sql .= ' AND auto_renew = :auto_renew';
            $params[':auto_renew'] = (int) $getParams['renewalType'];
        }
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $getParams
     * @return array{sort: string, sortOrder: string, order: string, orderByClauses: list<string>}
     */
    private static function insResolveSort(array $settings, array $getParams): array
    {
        $sort = 'next_payment';
        if (!empty($getParams['sortOrder_cookie'])) {
            $sort = (string) $getParams['sortOrder_cookie'];
        } elseif (isset($_COOKIE['sortOrder']) && $_COOKIE['sortOrder'] !== '') {
            $sort = (string) $_COOKIE['sortOrder'];
        }

        $sortOrder = $sort;
        $order = ($sort === 'price' || $sort === 'id') ? 'DESC' : 'ASC';

        if ($sort === 'alphanumeric') {
            $sort = 'name';
        }
        if (!in_array($sortOrder, self::ALLOWED_SORT, true)) {
            $sort = 'next_payment';
            $sortOrder = $sort;
        }
        if ($sort === 'renewal_type') {
            $sort = 'auto_renew';
        }

        $orderByClauses = [];
        $disabledBottom = ($settings['disabledToBottom'] ?? '') === 'true';

        if ($disabledBottom) {
            if (in_array($sort, ['payer_user_id', 'category_id', 'payment_method_id'], true)) {
                $orderByClauses[] = "{$sort} {$order}";
                $orderByClauses[] = 'inactive ASC';
            } else {
                $orderByClauses[] = 'inactive ASC';
                $orderByClauses[] = "{$sort} {$order}";
            }
        } else {
            $orderByClauses[] = "{$sort} {$order}";
            if ($sort !== 'inactive') {
                $orderByClauses[] = 'inactive ASC';
            }
        }

        if ($sort !== 'next_payment') {
            $orderByClauses[] = 'next_payment ASC';
        }

        return [
            'sort' => $sort,
            'sortOrder' => $sortOrder,
            'order' => $order,
            'orderByClauses' => $orderByClauses,
        ];
    }
}
