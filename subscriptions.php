<?php

require_once 'includes/header.php';
require_once 'includes/getdbkeys.php';

include_once 'includes/list_subscriptions.php';

// START ASSUREWALLOS MOD
require_once __DIR__ . '/includes/insurance/subscriptions_list_query.php';
$builtList = Ins_Subscriptions_List_Query::insBuild($userId, $settings, $_GET, 'page');
$sort = $builtList['sort'];
$sortOrder = $builtList['sortOrder'];
$stmt = $db->prepare($builtList['sql']);
foreach ($builtList['params'] as $key => $value) {
  $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
}
$result = $stmt->execute();
$subscriptions = [];
if ($result) {
  while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $subscriptions[] = $row;
  }
}
// END ASSUREWALLOS MOD

foreach ($subscriptions as $subscription) {
  $memberId = $subscription['payer_user_id'];
  // START ASSUREWALLOS MOD — leerer Zahler (z. B. CSV-Import)
  if ($memberId !== null && $memberId !== '' && isset($members[$memberId])) {
    $members[$memberId]['count']++;
  }
  // END ASSUREWALLOS MOD
  $categoryId = $subscription['category_id'];
  $categories[$categoryId]['count']++;
  $paymentMethodId = $subscription['payment_method_id'];
  $payment_methods[$paymentMethodId]['count']++;
}

if ($sortOrder == "category_id") {
  usort($subscriptions, function ($a, $b) use ($categories) {
    return $categories[$a['category_id']]['order'] - $categories[$b['category_id']]['order'];
  });
}

if ($sortOrder == "payment_method_id") {
  usort($subscriptions, function ($a, $b) use ($payment_methods) {
    return $payment_methods[$a['payment_method_id']]['order'] - $payment_methods[$b['payment_method_id']]['order'];
  });
}

$headerClass = count($subscriptions) > 0 ? "main-actions" : "main-actions hidden";
?>
<style>
  .logo-preview:after {
    content: '<?= translate('upload_logo', $i18n) ?>';
  }
</style>

<section class="contain">
  <header class="<?= $headerClass ?>" id="main-actions">
    <button class="button" onClick="addSubscription()">
      <i class="fa-solid fa-circle-plus"></i>
      <?= translate('new_subscription', $i18n) ?>
    </button>
    <div class="top-actions">
      <div class="search">
        <input type="text" autocomplete="off" name="search" id="search" placeholder="<?= translate('search', $i18n) ?>"
          onkeyup="searchSubscriptions()" />
        <span class="fa-solid fa-magnifying-glass search-icon"></span>
        <span class="fa-solid fa-xmark clear-search" onClick="clearSearch()"></span>
      </div>

      <div class="filtermenu on-dashboard">
        <button class="button secondary-button" id="filtermenu-button" title="<?= translate("filter", $i18n) ?>">
          <i class="fa-solid fa-filter"></i>
        </button>
        <?php include 'includes/filters_menu.php'; ?>
      </div>

      <div class="sort-container">
        <button class="button secondary-button" value="Sort" onClick="toggleSortOptions()" id="sort-button"
          title="<?= translate('sort', $i18n) ?>">
          <i class="fa-solid fa-arrow-down-wide-short"></i>
        </button>
        <?php include 'includes/sort_options.php'; ?>
      </div>
      <a href="subscriptions_table.php" class="button secondary-button" title="Tabellenansicht">
        <i class="fa-solid fa-table"></i>
        <span class="mobileNavigationHideOnMobile">Tabellenansicht</span>
      </a>
    </div>
  </header>
  <div class="subscriptions" id="subscriptions">
    <?php
    $formatter = new IntlDateFormatter(
      'en', // Force English locale
      IntlDateFormatter::SHORT,
      IntlDateFormatter::NONE,
      null,
      null,
      'MMM d, yyyy'
    );

    foreach ($subscriptions as $subscription) {
      if ($subscription['inactive'] == 1 && isset($settings['hideDisabledSubscriptions']) && $settings['hideDisabledSubscriptions'] === 'true') {
        continue;
      }
      $id = $subscription['id'];
      $print[$id]['id'] = $id;
      $print[$id]['logo'] = $subscription['logo'] != "" ? "images/uploads/logos/" . $subscription['logo'] : "";
      $print[$id]['name'] = $subscription['name'];
      $cycle = $subscription['cycle'];
      $frequency = $subscription['frequency'];
      $print[$id]['billing_cycle'] = getBillingCycle($cycle, $frequency, $i18n);
      $paymentMethodId = $subscription['payment_method_id'];
      $print[$id]['currency_code'] = $currencies[$subscription['currency_id']]['code'];
      $currencyId = $subscription['currency_id'];
      $print[$id]['auto_renew'] = $subscription['auto_renew'];
      $next_payment_timestamp = strtotime($subscription['next_payment']);
      $formatted_date = $formatter->format($next_payment_timestamp);
      $print[$id]['next_payment'] = $formatted_date;
      $paymentIconFolder = (strpos($payment_methods[$paymentMethodId]['icon'], 'images/uploads/icons/') !== false) ? "" : "images/uploads/logos/";
      $print[$id]['payment_method_icon'] = $paymentIconFolder . $payment_methods[$paymentMethodId]['icon'];
      $print[$id]['payment_method_name'] = $payment_methods[$paymentMethodId]['name'];
      $print[$id]['payment_method_id'] = $paymentMethodId;
      $print[$id]['category_id'] = $subscription['category_id'];
      $print[$id]['payer_user_id'] = $subscription['payer_user_id'];
      $print[$id]['price'] = floatval($subscription['price']);
      $print[$id]['progress'] = getSubscriptionProgress($cycle, $frequency, $subscription['next_payment']);
      $print[$id]['inactive'] = $subscription['inactive'];
      $print[$id]['url'] = $subscription['url'];
      $print[$id]['notes'] = $subscription['notes'];
      $print[$id]['replacement_subscription_id'] = $subscription['replacement_subscription_id'];

      if (isset($settings['convertCurrency']) && $settings['convertCurrency'] === 'true' && $currencyId != $mainCurrencyId) {
        $print[$id]['price'] = getPriceConverted($print[$id]['price'], $currencyId, $db);
        $print[$id]['currency_code'] = $currencies[$mainCurrencyId]['code'];
      }
      if (isset($settings['showMonthlyPrice']) && $settings['showMonthlyPrice'] === 'true') {
        $print[$id]['price'] = getPricePerMonth($cycle, $frequency, $print[$id]['price']);
      }
      if (isset($settings['showOriginalPrice']) && $settings['showOriginalPrice'] === 'true') {
        $print[$id]['original_price'] = floatval($subscription['price']);
        $print[$id]['original_currency_code'] = $currencies[$subscription['currency_id']]['code'];
      }
    }

    if ($sortOrder == "alphanumeric") {
      usort($print, function ($a, $b) {
        return strnatcmp(strtolower($a['name']), strtolower($b['name']));
      });
      if ($settings['disabledToBottom'] === 'true') {
        usort($print, function ($a, $b) {
          return $a['inactive'] - $b['inactive'];
        });
      }
    }

    if (isset($print)) {
      printSubscriptions($print, $sort, $categories, $members, $i18n, $colorTheme, "", $settings['disabledToBottom'], $settings['mobileNavigation'], $settings['showSubscriptionProgress'], $currencies, $lang);
    }
    $db->close();

    if (count($subscriptions) == 0) {
      ?>
      <div class="empty-page">
        <img src="images/siteimages/empty.png" alt="<?= translate('empty_page', $i18n) ?>" />
        <p>
          <?= translate('no_subscriptions_yet', $i18n) ?>
        </p>
        <button class="button" onClick="addSubscription()">
          <i class="fa-solid fa-circle-plus"></i>
          <?= translate('add_first_subscription', $i18n) ?>
        </button>
      </div>
      <?php
    }
    ?>
  </div>
</section>
<script src="scripts/subscriptions.js?<?= $version ?>"></script>
<?php // START ASSUREWALLOS MOD
include __DIR__ . '/includes/insurance/ui/subscription_form_overlay.php';
?>
<script src="scripts/insurance/form_toggle.js?<?= $version ?>"></script>
<script src="scripts/insurance/paperless_archive.js?<?= $version ?>"></script>
<!-- END ASSUREWALLOS MOD -->
<?php
if (isset($_GET['add'])) {
  ?>
  <script>
    addSubscription();
  </script>
  <?php
}

// START ASSUREWALLOS MOD
if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
  $assure_edit_id = (int) $_GET['edit'];
  $assure_return_table = isset($_GET['return']) && $_GET['return'] === 'table';
  ?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      openEditSubscription(null, <?= (int) $assure_edit_id ?>);
      <?php if ($assure_return_table): ?>
      const origClose = window.closeAddSubscription;
      if (typeof origClose === 'function') {
        window.closeAddSubscription = function () {
          origClose.apply(this, arguments);
          window.location.href = 'subscriptions_table.php';
        };
      }
      <?php endif; ?>
    });
  </script>
  <?php
}
// END ASSUREWALLOS MOD

require_once 'includes/footer.php';
?>