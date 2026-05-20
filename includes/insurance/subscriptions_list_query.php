<?php

require_once __DIR__ . '/ins_repository.php';
require_once __DIR__ . '/subscriptions_table_dimensions.php';

/**
 * Shared subscription list SQL for cards (subscriptions.php) and table view (AssureWallos).
 */
class Ins_Subscriptions_List_Query
{
    /**
     * Maps Wallos page URL params (subscriptions.php) to AJAX params (get.php / table).
     *
     * @param array<string, mixed> $getParams
     * @return array<string, mixed>
     */
    public static function insNormalizeGetParams(array $getParams): array
    {
        $normalized = $getParams;

        if (!empty($getParams['member']) && empty($getParams['members'])) {
            $normalized['members'] = $getParams['member'];
        }
        if (!empty($getParams['category']) && empty($getParams['categories'])) {
            $normalized['categories'] = $getParams['category'];
        }
        if (!empty($getParams['payment']) && empty($getParams['payments'])) {
            $normalized['payments'] = $getParams['payment'];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $getParams
     * @param array{with_insurance?: bool, db?: SQLite3} $options
     * @return array{sql: string, params: array<string, int|string>, sort: string, sortOrder: string, order: string, group: string, group2: string}
     */
    public static function insBuild(int $userId, array $settings, array $getParams, string $mode = 'ajax', array $options = []): array
    {
        if ($mode === 'ajax') {
            $getParams = self::insNormalizeGetParams($getParams);
        }

        $group = Ins_Subscriptions_Table_Dimensions::insValidateGroupKey((string) ($getParams['group'] ?? 'none')) ?? 'none';
        $group2Raw = (string) ($getParams['group2'] ?? '');
        $group2 = $group2Raw !== ''
            ? (Ins_Subscriptions_Table_Dimensions::insValidateGroupKey($group2Raw) ?? 'none')
            : 'none';

        $withInsurance = ($options['with_insurance'] ?? false)
            && isset($options['db'])
            && Ins_Repository::insSchemaReady($options['db']);

        $sortInfo = self::insResolveSort($settings, $getParams, $withInsurance);

        $params = [];
        if ($withInsurance) {
            $sql = 'SELECT s.* FROM subscriptions s
                LEFT JOIN assure_insurance_details d ON d.subscription_id = s.id
                WHERE s.user_id = :userId';
        } else {
            $sql = 'SELECT * FROM subscriptions WHERE user_id = :userId';
        }
        $params[':userId'] = $userId;

        if ($mode === 'page') {
            self::insApplyPageFilters($sql, $params, $getParams, $settings, $withInsurance);
        } else {
            self::insApplyAjaxFilters($sql, $params, $getParams, $withInsurance, $options['db'] ?? null, $userId);
        }

        $sql .= ' ORDER BY ' . implode(', ', $sortInfo['orderByClauses']);

        return [
            'sql' => $sql,
            'params' => $params,
            'sort' => $sortInfo['sort'],
            'sortOrder' => $sortInfo['sortOrder'],
            'order' => $sortInfo['order'],
            'group' => $group,
            'group2' => $group2,
        ];
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $getParams
     * @param array{with_insurance?: bool, db?: SQLite3} $options
     * @return list<array<string, mixed>>
     */
    public static function insFetchRows(SQLite3 $db, int $userId, array $settings, array $getParams, string $mode = 'ajax', array $options = []): array
    {
        $options['db'] = $db;
        $built = self::insBuild($userId, $settings, $getParams, $mode, $options);
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
     * @param array<string, mixed> $settings
     */
    private static function insApplyPageFilters(string &$sql, array &$params, array $getParams, array $settings, bool $withInsurance): void
    {
        if (isset($getParams['member']) && $getParams['member'] !== '') {
            $memberIds = explode(',', (string) $getParams['member']);
            $placeholders = [];
            foreach ($memberIds as $key => $memberId) {
                $ph = ":member{$key}";
                $placeholders[] = $ph;
                $params[$ph] = (int) $memberId;
            }
            $prefix = $withInsurance ? 's.' : '';
            $sql .= ' AND ' . $prefix . 'payer_user_id IN (' . implode(',', $placeholders) . ')';
        }

        if (isset($getParams['category']) && $getParams['category'] !== '') {
            $categoryIds = explode(',', (string) $getParams['category']);
            $placeholders = [];
            foreach ($categoryIds as $key => $categoryId) {
                $ph = ":category{$key}";
                $placeholders[] = $ph;
                $params[$ph] = (int) $categoryId;
            }
            $prefix = $withInsurance ? 's.' : '';
            $sql .= ' AND ' . $prefix . 'category_id IN (' . implode(',', $placeholders) . ')';
        }

        if (isset($getParams['payment']) && $getParams['payment'] !== '') {
            $paymentIds = explode(',', (string) $getParams['payment']);
            $placeholders = [];
            foreach ($paymentIds as $key => $paymentId) {
                $ph = ":payment{$key}";
                $placeholders[] = $ph;
                $params[$ph] = (int) $paymentId;
            }
            $prefix = $withInsurance ? 's.' : '';
            $sql .= ' AND ' . $prefix . 'payment_method_id IN (' . implode(',', $placeholders) . ')';
        }

        $hideDisabled = ($settings['hideDisabledSubscriptions'] ?? '') === 'true';
        if (!$hideDisabled && isset($getParams['state']) && $getParams['state'] !== '') {
            $prefix = $withInsurance ? 's.' : '';
            $sql .= ' AND ' . $prefix . 'inactive = :inactive';
            $params[':inactive'] = (int) $getParams['state'];
        }
    }

    /**
     * @param array<string, int|string> $params
     * @param array<string, mixed> $getParams
     */
    private static function insApplyAjaxFilters(
        string &$sql,
        array &$params,
        array $getParams,
        bool $withInsurance,
        ?SQLite3 $db,
        int $userId
    ): void {
        $col = static function (string $field) use ($withInsurance): string {
            return $withInsurance ? 's.' . $field : $field;
        };

        if (isset($getParams['categories']) && $getParams['categories'] !== '') {
            $all = explode(',', (string) $getParams['categories']);
            $parts = [];
            foreach ($all as $idx => $category) {
                $ph = ":categories{$idx}";
                $parts[] = $col('category_id') . " = {$ph}";
                $params[$ph] = (int) $category;
            }
            $sql .= ' AND (' . implode(' OR ', $parts) . ')';
        }

        if (isset($getParams['payments']) && $getParams['payments'] !== '') {
            $all = explode(',', (string) $getParams['payments']);
            $parts = [];
            foreach ($all as $idx => $payment) {
                $ph = ":payments{$idx}";
                $parts[] = $col('payment_method_id') . " = {$ph}";
                $params[$ph] = (int) $payment;
            }
            $sql .= ' AND (' . implode(' OR ', $parts) . ')';
        }

        if (isset($getParams['members']) && $getParams['members'] !== '') {
            $all = explode(',', (string) $getParams['members']);
            $parts = [];
            foreach ($all as $idx => $member) {
                $ph = ":members{$idx}";
                $parts[] = $col('payer_user_id') . " = {$ph}";
                $params[$ph] = (int) $member;
            }
            $sql .= ' AND (' . implode(' OR ', $parts) . ')';
        }

        if (isset($getParams['state']) && $getParams['state'] !== '') {
            $sql .= ' AND ' . $col('inactive') . ' = :inactive';
            $params[':inactive'] = (int) $getParams['state'];
        }

        if (isset($getParams['renewalType']) && $getParams['renewalType'] != '') {
            $sql .= ' AND ' . $col('auto_renew') . ' = :auto_renew';
            $params[':auto_renew'] = (int) $getParams['renewalType'];
        }

        if ($withInsurance) {
            if (isset($getParams['is_insurance']) && $getParams['is_insurance'] !== '') {
                $sql .= ' AND s.is_insurance = :is_insurance';
                $params[':is_insurance'] = (int) $getParams['is_insurance'];
            }

            if (!empty($getParams['insurance_types'])) {
                $ids = array_filter(array_map('intval', explode(',', (string) $getParams['insurance_types'])));
                if ($ids !== []) {
                    $parts = [];
                    foreach ($ids as $idx => $typeId) {
                        $ph = ":insType{$idx}";
                        $parts[] = $ph;
                        $params[$ph] = $typeId;
                    }
                    $sql .= ' AND d.insurance_type_id IN (' . implode(',', $parts) . ')';
                }
            }

            if (!empty($getParams['insurance_groups']) && $db !== null && Ins_Repository::insTaxonomyReady($db)) {
                $groupIds = array_filter(array_map('intval', explode(',', (string) $getParams['insurance_groups'])));
                if ($groupIds !== []) {
                    $typeIds = [];
                    $res = $db->query(
                        'SELECT id FROM assure_insurance_types WHERE group_id IN (' . implode(',', $groupIds) . ')'
                    );
                    while ($res && ($row = $res->fetchArray(SQLITE3_ASSOC))) {
                        $typeIds[] = (int) $row['id'];
                    }
                    if ($typeIds === []) {
                        $sql .= ' AND 1=0';
                    } else {
                        $parts = [];
                        foreach ($typeIds as $idx => $typeId) {
                            $ph = ":insGrpType{$idx}";
                            $parts[] = $ph;
                            $params[$ph] = $typeId;
                        }
                        $sql .= ' AND d.insurance_type_id IN (' . implode(',', $parts) . ')';
                    }
                }
            }

            if (!empty($getParams['contract_status'])) {
                $statuses = array_filter(array_map('trim', explode(',', (string) $getParams['contract_status'])));
                if ($statuses !== []) {
                    $parts = [];
                    foreach ($statuses as $idx => $status) {
                        $ph = ":contractStatus{$idx}";
                        $parts[] = "d.contract_status = {$ph}";
                        $params[$ph] = $status;
                    }
                    $sql .= ' AND (' . implode(' OR ', $parts) . ')';
                }
            }

            if (!empty($getParams['cancellation_status'])) {
                $statuses = array_filter(array_map('trim', explode(',', (string) $getParams['cancellation_status'])));
                if ($statuses !== []) {
                    $parts = [];
                    foreach ($statuses as $idx => $status) {
                        $ph = ":cancelStatus{$idx}";
                        $parts[] = "d.cancellation_status = {$ph}";
                        $params[$ph] = $status;
                    }
                    $sql .= ' AND (' . implode(' OR ', $parts) . ')';
                }
            }

            if (!empty($getParams['next_payment_month'])) {
                $months = array_filter(explode(',', (string) $getParams['next_payment_month']));
                if ($months !== []) {
                    $parts = [];
                    foreach ($months as $idx => $month) {
                        $ph = ":payMonth{$idx}";
                        $parts[] = "strftime('%Y-%m', s.next_payment) = {$ph}";
                        $params[$ph] = $month;
                    }
                    $sql .= ' AND (' . implode(' OR ', $parts) . ')';
                }
            }

            if (!empty($getParams['q'])) {
                $q = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $getParams['q']) . '%';
                $params[':searchQ'] = $q;
                $sql .= ' AND (
                    s.name LIKE :searchQ ESCAPE \'\\\'
                    OR d.policy_number LIKE :searchQ ESCAPE \'\\\'
                    OR d.insurer_name LIKE :searchQ ESCAPE \'\\\'
                    OR d.broker_name LIKE :searchQ ESCAPE \'\\\'
                    OR d.tariff_name LIKE :searchQ ESCAPE \'\\\'
                    OR s.notes LIKE :searchQ ESCAPE \'\\\'
                )';
            }
        }
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $getParams
     * @return array{sort: string, sortOrder: string, order: string, orderByClauses: list<string>}
     */
    private static function insResolveSort(array $settings, array $getParams, bool $withInsurance = false): array
    {
        $sort = 'next_payment';
        if (!empty($getParams['sort'])) {
            $validated = Ins_Subscriptions_Table_Dimensions::insValidateSortKey((string) $getParams['sort']);
            if ($validated !== null) {
                $sort = $validated;
            }
        } elseif (!empty($getParams['sortOrder_cookie'])) {
            $validated = Ins_Subscriptions_Table_Dimensions::insValidateSortKey((string) $getParams['sortOrder_cookie']);
            $sort = $validated ?? 'next_payment';
        } elseif (isset($_COOKIE['sortOrder']) && $_COOKIE['sortOrder'] !== '') {
            $validated = Ins_Subscriptions_Table_Dimensions::insValidateSortKey((string) $_COOKIE['sortOrder']);
            $sort = $validated ?? 'next_payment';
        }

        $sortOrder = $sort;
        $order = 'ASC';
        if (!empty($getParams['sortOrder_dir'])) {
            $dir = strtoupper((string) $getParams['sortOrder_dir']);
            $order = $dir === 'DESC' ? 'DESC' : 'ASC';
        } elseif ($sort === 'price' || $sort === 'id') {
            $order = 'DESC';
        }

        if ($sort === 'alphanumeric') {
            $sort = 'name';
        }

        $sortSql = Ins_Subscriptions_Table_Dimensions::insSortSqlForKey($sort);
        if ($sortSql === null) {
            $sort = 'next_payment';
            $sortOrder = $sort;
            $sortSql = 'next_payment';
            $order = 'ASC';
        }

        if (!$withInsurance) {
            $sortSql = preg_replace('/^s\./', '', $sortSql) ?? $sortSql;
            $sortSql = preg_replace('/^d\./', 'id', $sortSql) ?? $sortSql;
            if (str_starts_with($sortSql, 'id') && $sort !== 'id') {
                $sortSql = 'next_payment';
            }
        }

        $inactiveCol = $withInsurance ? 's.inactive' : 'inactive';
        $nextCol = $withInsurance ? 's.next_payment' : 'next_payment';
        $orderByClauses = [];
        $disabledBottom = ($settings['disabledToBottom'] ?? '') === 'true';
        $sortField = $sortSql;

        if ($disabledBottom) {
            if (in_array($sort, ['payer', 'category', 'payment_method'], true)) {
                $orderByClauses[] = "{$sortField} {$order}";
                $orderByClauses[] = "{$inactiveCol} ASC";
            } else {
                $orderByClauses[] = "{$inactiveCol} ASC";
                $orderByClauses[] = "{$sortField} {$order}";
            }
        } else {
            $orderByClauses[] = "{$sortField} {$order}";
            if ($sort !== 'inactive') {
                $orderByClauses[] = "{$inactiveCol} ASC";
            }
        }

        if ($sort !== 'next_payment' && $sort !== 'next_payment_month') {
            $orderByClauses[] = "{$nextCol} ASC";
        }

        return [
            'sort' => $sort,
            'sortOrder' => $sortOrder,
            'order' => $order,
            'orderByClauses' => $orderByClauses,
        ];
    }
}
