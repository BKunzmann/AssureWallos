<?php
require_once '../../includes/connect_endpoint.php';

require_once '../../includes/currency_formatter.php';
require_once '../../includes/getdbkeys.php';

include_once '../../includes/list_subscriptions.php';

require_once '../../includes/getsettings.php';

$theme = "light";
if (isset($settings['theme'])) {
  $theme = $settings['theme'];
}

$colorTheme = "blue";
if (isset($settings['color_theme'])) {
  $colorTheme = $settings['color_theme'];
}

$formatter = new IntlDateFormatter(
  'en', // Force English locale
  IntlDateFormatter::SHORT,
  IntlDateFormatter::NONE,
  null,
  null,
  'MMM d, yyyy'
);

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {


  // START ASSUREWALLOS MOD
  require_once __DIR__ . '/../../includes/insurance/subscriptions_list_query.php';
  $builtList = Ins_Subscriptions_List_Query::insBuild($userId, $settings, $_GET, 'ajax');
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
    if ($subscription['inactive'] == 1 && isset($settings['hideDisabledSubscriptions']) && $settings['hideDisabledSubscriptions'] === 'true') {
      continue;
    }
    $id = $subscription['id'];
    $print[$id]['id'] = $id;
    $print[$id]['logo'] = $subscription['logo'] != "" ? "images/uploads/logos/" . $subscription['logo'] : "";
    $print[$id]['name'] = $subscription['name'] ?? "";
    $cycle = $subscription['cycle'];
    $frequency = $subscription['frequency'];
    $print[$id]['billing_cycle'] = getBillingCycle($cycle, $frequency, $i18n);
    $paymentMethodId = $subscription['payment_method_id'];
    $print[$id]['currency_code'] = $currencies[$subscription['currency_id']]['code'];
    $currencyId = $subscription['currency_id'];
    $next_payment_timestamp = strtotime($subscription['next_payment']);
    $formatted_date = $formatter->format($next_payment_timestamp);
    $print[$id]['next_payment'] = $formatted_date;
    $print[$id]['auto_renew'] = $subscription['auto_renew'];
    $paymentIconFolder = (strpos($payment_methods[$paymentMethodId]['icon'], 'images/uploads/icons/') !== false) ? "" : "images/uploads/logos/";
    $print[$id]['payment_method_icon'] = $paymentIconFolder . $payment_methods[$paymentMethodId]['icon'];
    $print[$id]['payment_method_name'] = $payment_methods[$paymentMethodId]['name'];
    $print[$id]['payment_method_id'] = $paymentMethodId;
    $print[$id]['category_id'] = $subscription['category_id'];
    $print[$id]['payer_user_id'] = $subscription['payer_user_id'];
    $print[$id]['price'] = floatval($subscription['price']);
    $print[$id]['progress'] = getSubscriptionProgress($cycle, $frequency, $subscription['next_payment']);
    $print[$id]['inactive'] = $subscription['inactive'];
    $print[$id]['url'] = $subscription['url'] ?? "";
    $print[$id]['notes'] = $subscription['notes'] ?? "";
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

  if ($sortOrder == "category_id") {
    usort($print, function ($a, $b) use ($categories) {
      return $categories[$a['category_id']]['order'] - $categories[$b['category_id']]['order'];
    });
  }
  
  if ($sortOrder == "payment_method_id") {
    usort($print, function ($a, $b) use ($payment_methods) {
      return $payment_methods[$a['payment_method_id']]['order'] - $payment_methods[$b['payment_method_id']]['order'];
    });
  }

  if (isset($print)) {
    printSubscriptions($print, $sort, $categories, $members, $i18n, $colorTheme, "../../", $settings['disabledToBottom'], $settings['mobileNavigation'], $settings['showSubscriptionProgress'], $currencies, $lang);
  }

  if (count($subscriptions) == 0) {
    ?>
    <div class="no-matching-subscriptions">
      <p>
        <?= translate('no_matching_subscriptions', $i18n) ?>
      </p>
      <button class="button" onClick="clearFilters()">
        <span clasS="fa-solid fa-minus-circle"></span>
        <?= translate('clear_filters', $i18n) ?>
      </button>
      <img src="images/siteimages/empty.png" alt="<?= translate('empty_page', $i18n) ?>" />
    </div>
    <?php
  }
}

$db->close();
?>