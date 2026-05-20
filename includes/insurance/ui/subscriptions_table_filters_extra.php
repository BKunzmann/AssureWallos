<?php
/**
 * Extra filter items inside filtermenu-content (table view only).
 *
 * @var list<array<string, mixed>> $assure_insurance_types
 * @var list<array<string, mixed>> $assure_insurance_groups
 */
if (!isset($assure_insurance_types)) {
    $assure_insurance_types = [];
}
if (!isset($assure_insurance_groups)) {
    $assure_insurance_groups = [];
}
?>
<div class="filtermenu-submenu assure-filter-extra">
  <div class="filter-title" onClick="toggleSubMenu('insurance_type_filter')">Versicherung</div>
  <div class="filtermenu-submenu-content" id="filter-insurance_type_filter">
    <div class="filter-item" data-insurance-only="1">Nur Versicherungen</div>
    <div class="filter-item" data-insurance-only="0">Nur Abos</div>
  </div>
</div>
<?php if ($assure_insurance_groups !== []): ?>
<div class="filtermenu-submenu assure-filter-extra">
  <div class="filter-title" onClick="toggleSubMenu('insurance_group')">Versicherungsgruppe</div>
  <div class="filtermenu-submenu-content" id="filter-insurance_group">
    <?php foreach ($assure_insurance_groups as $group): ?>
      <div class="filter-item" data-insurance-group="<?= (int) ($group['id'] ?? 0) ?>">
        <?= htmlspecialchars((string) ($group['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<?php if ($assure_insurance_types !== []): ?>
<div class="filtermenu-submenu assure-filter-extra">
  <div class="filter-title" onClick="toggleSubMenu('insurance_art')">Versicherungsart</div>
  <div class="filtermenu-submenu-content" id="filter-insurance_art">
    <?php foreach ($assure_insurance_types as $type): ?>
      <div class="filter-item" data-insurance-type="<?= (int) ($type['id'] ?? 0) ?>">
        <?= htmlspecialchars((string) ($type['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<div class="filtermenu-submenu assure-filter-extra">
  <div class="filter-title" onClick="toggleSubMenu('contract_status')">Vertragsstatus</div>
  <div class="filtermenu-submenu-content" id="filter-contract_status">
    <?php
    $statuses = ['aktiv', 'ruhend', 'gekündigt', 'beendet', 'in Bearbeitung'];
    foreach ($statuses as $status):
        ?>
      <div class="filter-item" data-contract-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
