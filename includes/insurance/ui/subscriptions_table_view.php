<?php
/**
 * Renders subscriptions table body (AssureWallos).
 *
 * @var list<array<string, mixed>> $assure_table_render_blocks
 * @var list<string> $assure_table_columns
 * @var array<string, array<string, mixed>> $assure_table_column_defs
 * @var string $assure_table_sort
 * @var string $assure_table_sort_dir
 */

if (!isset($assure_table_render_blocks, $assure_table_columns, $assure_table_column_defs)) {
    return;
}

$assure_table_sort = $assure_table_sort ?? '';
$assure_table_sort_dir = $assure_table_sort_dir ?? 'ASC';
$colSpan = count($assure_table_columns) + 1;

$insThClass = static function (string $colId, array $def) use ($assure_table_sort): string {
    $classes = ['assure-col-' . $colId, 'assure-col-group-' . ($def['group'] ?? 'abo')];
    if (!empty($def['sortable'])) {
        $classes[] = 'assure-th-sortable';
    }
    if (($def['sort_key'] ?? '') === $assure_table_sort) {
        $classes[] = 'assure-th-sorted';
    }

    return implode(' ', $classes);
};
?>
<div class="assure-table-scroll" id="assure-subscriptions-table-wrap">
  <table class="assure-subscriptions-table" id="assure-subscriptions-table"
         data-sort="<?= htmlspecialchars($assure_table_sort, ENT_QUOTES, 'UTF-8') ?>"
         data-sort-dir="<?= htmlspecialchars($assure_table_sort_dir, ENT_QUOTES, 'UTF-8') ?>">
    <thead>
      <tr>
        <th class="assure-col-select" scope="col">
          <input type="checkbox" class="assure-table-checkbox" id="assure-table-select-all" title="Alle auswählen" aria-label="Alle auswählen">
        </th>
        <?php foreach ($assure_table_columns as $colId): ?>
          <?php
          $def = $assure_table_column_defs[$colId] ?? ['label' => $colId];
          $sortKey = $def['sort_key'] ?? '';
          $groupKey = $def['group_key'] ?? '';
          $sortable = !empty($def['sortable']) && $sortKey !== '';
          $groupable = !empty($def['groupable']) && $groupKey !== '';
          $isSorted = $sortable && $sortKey === $assure_table_sort;
          $sortIcon = '';
          if ($isSorted) {
              $sortIcon = strtoupper($assure_table_sort_dir) === 'DESC' ? '▼' : '▲';
          }
          $colLabelEsc = htmlspecialchars((string) ($def['label'] ?? $colId), ENT_QUOTES, 'UTF-8');
          $colIdEsc = htmlspecialchars($colId, ENT_QUOTES, 'UTF-8');
          $sortKeyEsc = htmlspecialchars($sortKey, ENT_QUOTES, 'UTF-8');
          $groupKeyEsc = htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8');
          $thClassEsc = htmlspecialchars($insThClass($colId, $def), ENT_QUOTES, 'UTF-8');
          $ariaSortVal = $isSorted
              ? (strtoupper($assure_table_sort_dir) === 'DESC' ? 'descending' : 'ascending')
              : '';
          ?>
          <th scope="col"
              class="<?= $thClassEsc ?>"
              data-column="<?= $colIdEsc ?>"
              <?php if ($sortable): ?>data-sortable="1" data-sort-key="<?= $sortKeyEsc ?>"<?php endif; ?>
              <?php if ($groupable): ?>data-groupable="1" data-group-key="<?= $groupKeyEsc ?>"<?php endif; ?>
              <?php if ($isSorted): ?>aria-sort="<?= $ariaSortVal ?>"<?php endif; ?>>
            <div class="assure-th-inner">
              <span class="assure-th-label"<?php if ($sortable): ?> role="button" tabindex="0"<?php endif; ?>>
                <?= $colLabelEsc ?>
              </span>
              <?php if ($sortIcon !== ''): ?>
                <span class="assure-th-sort-icon" aria-hidden="true"><?= $sortIcon ?></span>
              <?php endif; ?>
              <?php if ($sortable || $groupable): ?>
                <button type="button" class="assure-th-menu" aria-label="Spalte <?= $colLabelEsc ?>" data-column-menu="<?= $colIdEsc ?>">&#8942;</button>
              <?php endif; ?>
            </div>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      $hasData = false;
      foreach ($assure_table_render_blocks as $block):
          $type = (string) ($block['type'] ?? '');
          if ($type === 'group_header'):
              $level = (int) ($block['level'] ?? 1);
              ?>
        <tr class="assure-table-group-header assure-table-group-header--level<?= $level ?>">
          <td colspan="<?= $colSpan ?>">
            <?= htmlspecialchars((string) ($block['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </td>
        </tr>
              <?php
              continue;
          endif;
          if ($type === 'group_sum'):
              $level = (int) ($block['level'] ?? 1);
              $count = (int) ($block['count'] ?? 0);
              $sums = $block['sums'] ?? [];
              ?>
        <tr class="assure-table-group-sum assure-table-group-sum--level<?= $level ?>">
          <td class="assure-col-select"></td>
          <?php foreach ($assure_table_columns as $colId): ?>
            <td class="assure-col-<?= htmlspecialchars($colId, ENT_QUOTES, 'UTF-8') ?>" data-column="<?= htmlspecialchars($colId, ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($colId === 'name'): ?>
                <span class="assure-sum-label">Summe · <?= $count ?> Verträge</span>
              <?php elseif (isset($sums[$colId])): ?>
                <?php
                $sum = $sums[$colId];
                $total = number_format((float) ($sum['total'] ?? 0), 2, ',', '.');
                $hint = !empty($sum['mixed_currency']) ? ' *' : '';
                $cur = (string) ($sum['currency'] ?? '');
                ?>
                <span class="assure-sum-value"><?= htmlspecialchars($total . ($cur !== '' && empty($sum['mixed_currency']) ? ' ' . $cur : '') . $hint, ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
              <?php
              continue;
          endif;
          if ($type !== 'data' || !isset($block['row'])) {
              continue;
          }
          $hasData = true;
          $row = $block['row'];
          ?>
        <tr class="assure-table-data-row"
            data-id="<?= (int) ($row['id'] ?? 0) ?>"
            data-name="<?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            data-policy="<?= htmlspecialchars((string) ($row['policy_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          <td class="assure-col-select">
            <input type="checkbox" class="assure-table-checkbox assure-row-select" value="<?= (int) ($row['id'] ?? 0) ?>" aria-label="Zeile auswählen">
          </td>
          <?php foreach ($assure_table_columns as $colId): ?>
            <td class="assure-col-<?= htmlspecialchars($colId, ENT_QUOTES, 'UTF-8') ?>" data-column="<?= htmlspecialchars($colId, ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($colId === 'logo'): ?>
                <?php
                $logoUrl = (string) ($row['logo_url'] ?? '');
                if ($logoUrl !== '') {
                    ?>
                  <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="assure-table-logo" width="40" height="40" loading="lazy">
                    <?php
                }
                ?>
              <?php elseif ($colId === 'url' && ($row['cells']['url'] ?? '') !== ''): ?>
                <a href="<?= htmlspecialchars((string) $row['cells']['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="assure-table-link" onclick="event.stopPropagation()">
                  <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                </a>
              <?php elseif ($colId === 'portal_url' && ($row['cells']['portal_url'] ?? '') !== ''): ?>
                <a href="<?= htmlspecialchars((string) $row['cells']['portal_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="assure-table-link" onclick="event.stopPropagation()">
                  <i class="fa-solid fa-globe" aria-hidden="true"></i>
                </a>
              <?php else: ?>
                <?= htmlspecialchars((string) ($row['cells'][$colId] ?? ''), ENT_QUOTES, 'UTF-8') ?>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
          <?php
      endforeach;
      if (!$hasData):
          ?>
        <tr class="assure-table-empty-row">
          <td colspan="<?= $colSpan ?>">Keine Verträge gefunden.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<div id="assure-th-dropdown" class="assure-th-dropdown hide" hidden role="menu"></div>
