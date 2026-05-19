<?php
/**
 * Renders subscriptions table body (AssureWallos).
 *
 * @var list<array<string, mixed>> $assure_table_rows
 * @var list<string> $assure_table_columns
 * @var array<string, array{id: string, label: string}> $assure_table_column_defs
 */

if (!isset($assure_table_rows, $assure_table_columns, $assure_table_column_defs)) {
    return;
}
?>
<div class="assure-table-scroll" id="assure-subscriptions-table-wrap">
  <table class="assure-subscriptions-table" id="assure-subscriptions-table">
    <thead>
      <tr>
        <th class="assure-col-select" scope="col">
          <input type="checkbox" class="assure-table-checkbox" id="assure-table-select-all" title="Alle auswählen" aria-label="Alle auswählen">
        </th>
        <?php foreach ($assure_table_columns as $colId): ?>
          <?php
          $def = $assure_table_column_defs[$colId] ?? ['label' => $colId];
          $group = $def['group'] ?? 'abo';
          ?>
          <th scope="col" class="assure-col-<?= htmlspecialchars($colId, ENT_QUOTES, 'UTF-8') ?> assure-col-group-<?= htmlspecialchars($group, ENT_QUOTES, 'UTF-8') ?>" data-column="<?= htmlspecialchars($colId, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($def['label'] ?? $colId, ENT_QUOTES, 'UTF-8') ?>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php if ($assure_table_rows === []): ?>
        <tr class="assure-table-empty-row">
          <td colspan="<?= count($assure_table_columns) + 1 ?>">Keine Verträge gefunden.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($assure_table_rows as $row): ?>
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
                    <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="assure-table-logo" width="32" height="32" loading="lazy">
                      <?php
                  }
                  ?>
                <?php else: ?>
                  <?= htmlspecialchars((string) ($row['cells'][$colId] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
