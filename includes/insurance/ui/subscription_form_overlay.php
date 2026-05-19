<?php
/** AssureWallos: Subscription edit overlay (shared by subscriptions.php and subscriptions_table.php). */
?>
<section class="subscription-form" id="subscription-form">
  <header>
    <h3 id="form-title"><?= translate('add_subscription', $i18n) ?></h3>
    <span class="fa-solid fa-xmark close-form" onClick="closeAddSubscription()"></span>
  </header>
  <form action="endpoints/subscription/add.php" method="post" id="subs-form">

    <div class="form-group-inline">
      <input type="text" id="name" name="name" autocomplete="off"
        placeholder="<?= translate('subscription_name', $i18n) ?>"
        onchange="setSearchButtonStatus()" onkeypress="this.onchange();" onpaste="this.onchange();"
        oninput="this.onchange();" required>
      <label for="logo" class="logo-preview">
        <img src="" alt="<?= translate('logo_preview', $i18n) ?>" id="form-logo">
      </label>
      <input type="file" id="logo" name="logo" accept="image/jpeg, image/png, image/gif, image/webp, image/svg+xml"
        onchange="handleFileSelect(event)" class="hidden-input">
      <input type="hidden" id="logo-url" name="logo-url">
      <div id="logo-search-button" class="image-button medium disabled" title="<?= translate('search_logo', $i18n) ?>"
        onClick="searchLogo()">
        <?php include "images/siteicons/svg/websearch.php"; ?>
      </div>
      <input type="hidden" id="id" name="id">
      <div id="logo-search-results" class="logo-search">
        <header>
          <?= translate('web_search', $i18n) ?>
          <span class="fa-solid fa-xmark close-logo-search" onClick="closeLogoSearch()"></span>
        </header>
        <div id="logo-search-images"></div>
      </div>
    </div>

    <div class="form-group-inline">
      <input type="number" step="0.01" id="price" name="price" autocomplete="off"
        placeholder="<?= translate('price', $i18n) ?>" required>
      <select id="currency" name="currency_id" placeholder="<?= translate('add_subscription', $i18n) ?>">
        <?php
        foreach ($currencies as $currency) {
          $selected = ($currency['id'] == $main_currency) ? 'selected' : '';
          ?>
          <option value="<?= $currency['id'] ?>" <?= $selected ?>><?= $currency['name'] ?></option>
          <?php
        }
        ?>
      </select>
    </div>

    <div class="form-group">
      <div class="inline">
        <div class="split66">
          <label for="cycle"><?= translate('payment_every', $i18n) ?></label>
          <div class="inline">
            <select id="frequency" name="frequency" placeholder="<?= translate('frequency', $i18n) ?>">
              <?php
              for ($i = 1; $i <= 366; $i++) {
                ?>
                <option value="<?= $i ?>"><?= $i ?></option>
                <?php
              }
              ?>
            </select>
            <select id="cycle" name="cycle" placeholder="Cycle">
              <?php
              foreach ($cycles as $cycle) {
                ?>
                <option value="<?= $cycle['id'] ?>" <?= $cycle['id'] == 3 ? "selected" : "" ?>>
                  <?= translate(strtolower($cycle['name']), $i18n) ?>
                </option>
                <?php
              }
              ?>
            </select>
          </div>
        </div>
        <div class="split33">
          <label><?= translate('auto_renewal', $i18n) ?></label>
          <div class="inline height50">
            <input type="checkbox" id="auto_renew" name="auto_renew" checked>
            <label for="auto_renew"><?= translate('automatically_renews', $i18n) ?></label>
          </div>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="inline">
        <div class="split50">
          <label for="start_date"><?= translate('start_date', $i18n) ?></label>
          <div class="date-wrapper">
            <input type="date" id="start_date" name="start_date" autocomplete="off">
          </div>
        </div>
        <button type="button" id="autofill-next-payment-button"
          class="button secondary-button autofill-next-payment hideOnMobile"
          title="<?= translate('calculate_next_payment_date', $i18n) ?>" onClick="autoFillNextPaymentDate(event)">
          <i class="fa-solid fa-wand-magic-sparkles"></i>
        </button>
        <div class="split50">
          <label for="next_payment" class="split-label">
            <?= translate('next_payment', $i18n) ?>
            <div id="autofill-next-payment-button" class="autofill-next-payment hideOnDesktop"
              title="<?= translate('calculate_next_payment_date', $i18n) ?>" onClick="autoFillNextPaymentDate(event)">
              <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>
          </label>
          <div class="date-wrapper">
            <input type="date" id="next_payment" name="next_payment" autocomplete="off" required>
          </div>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="inline">
        <div class="split50">
          <label for="payment_method"><?= translate('payment_method', $i18n) ?></label>
          <select id="payment_method" name="payment_method_id">
            <?php
            foreach ($payment_methods as $payment) {
              ?>
              <option value="<?= $payment['id'] ?>">
                <?= $payment['name'] ?>
              </option>
              <?php
            }
            ?>
          </select>
        </div>
        <div class="split50">
          <label for="payer_user"><?= translate('paid_by', $i18n) ?></label>
          <select id="payer_user" name="payer_user_id">
            <?php
            foreach ($members as $member) {
              ?>
              <option value="<?= $member['id'] ?>"><?= $member['name'] ?></option>
              <?php
            }
            ?>
          </select>
        </div>
      </div>
    </div>

    <?php
    // START ASSUREWALLOS MOD
    // Kategorie-ID für „Versicherung“ (Name oder Standard-ID 11), damit JS mit #is_insurance synchronisieren kann.
    $assure_insurance_category_id = null;
    foreach ($categories as $cid => $category) {
      $nm = strtolower(trim((string) ($category['name'] ?? '')));
      if ($nm === 'insurance' || $nm === 'versicherung' || $nm === 'versicherungen' || $nm === 'assurance') {
        $assure_insurance_category_id = (int) $cid;
        break;
      }
    }
    if ($assure_insurance_category_id === null && isset($categories[11])) {
      $assure_insurance_category_id = 11;
    }
    // END ASSUREWALLOS MOD
    ?>
    <div class="form-group">
      <label for="category"><?= translate('category', $i18n) ?></label>
      <select id="category" name="category_id"<?php
      // START ASSUREWALLOS MOD
      if ($assure_insurance_category_id !== null) {
        echo ' data-assure-insurance-category-id="' . htmlspecialchars((string) $assure_insurance_category_id, ENT_QUOTES, 'UTF-8') . '"';
      }
      // END ASSUREWALLOS MOD
      ?>>
        <?php
        foreach ($categories as $category) {
          ?>
          <option value="<?= $category['id'] ?>">
            <?= $category['name'] ?>
          </option>
          <?php
        }
        ?>
      </select>
    </div>

    <div class="form-group-inline grow">
      <input type="checkbox" id="notifications" name="notifications" onchange="toggleNotificationDays()">
      <label for="notifications" class="grow"><?= translate('enable_notifications', $i18n) ?></label>
    </div>

    <div class="form-group">
      <div class="inline">
        <div class="split66 mobile-split-50">
          <label for="notify_days_before"><?= translate('notify_me', $i18n) ?></label>
          <select id="notify_days_before" name="notify_days_before" disabled>
            <option value="-1"><?= translate('default_value_from_settings', $i18n) ?></option>
            <option value="0"><?= translate('on_due_date', $i18n) ?></option>
            <option value="1">1 <?= translate('day_before', $i18n) ?></option>
            <?php
            for ($i = 2; $i <= 180; $i++) {
              ?>
              <option value="<?= $i ?>"><?= $i ?>   <?= translate('days_before', $i18n) ?></option>
              <?php
            }
            ?>
          </select>
        </div>
        <div class="split33 mobile-split-50">
          <label for="cancellation_date"><?= translate('cancellation_notification', $i18n) ?></label>
          <div class="date-wrapper">
            <input type="date" id="cancellation_date" name="cancellation_date" autocomplete="off">
          </div>
        </div>
      </div>
    </div>

    <div class="form-group">
      <input type="text" id="url" name="url" autocomplete="off" placeholder="<?= translate('url', $i18n) ?>">
    </div>

    <div class="form-group">
      <input type="text" id="notes" name="notes" autocomplete="off" placeholder="<?= translate('notes', $i18n) ?>">
    </div>

    <div class="form-group">
      <div class="inline grow">
        <input type="checkbox" id="inactive" name="inactive" onchange="toggleReplacementSub()">
        <label for="inactive" class="grow"><?= translate('inactive', $i18n) ?></label>
      </div>
    </div>

    <?php
    $orderedSubscriptions = $subscriptions;
    usort($orderedSubscriptions, function ($a, $b) {
      return strnatcmp(strtolower($a['name']), strtolower($b['name']));
    });
    ?>

    <div class="form-group hide" id="replacement_subscritpion">
      <label for="replacement_subscription_id"><?= translate('replaced_with', $i18n) ?>:</label>
      <select id="replacement_subscription_id" name="replacement_subscription_id">
        <option value="0"><?= translate('none', $i18n) ?></option>
        <?php
        foreach ($orderedSubscriptions as $sub) {
          if ($sub['inactive'] == 0) {
            ?>
            <option value="<?= htmlspecialchars($sub['id']) ?>"><?= htmlspecialchars($sub['name']) ?>
            </option>
            <?php
          }
        }
        ?>
      </select>
    </div>

    <?php
    // START ASSUREWALLOS MOD
    include __DIR__ . '/toggle.php';
    include __DIR__ . '/form_fields.php';
    include __DIR__ . '/documents_section.php';
    // END ASSUREWALLOS MOD
    ?>

    <div class="buttons">
      <input type="button" value="<?= translate('delete', $i18n) ?>" class="warning-button left thin" id="deletesub"
        style="display: none">
      <input type="button" value="<?= translate('cancel', $i18n) ?>" class="secondary-button thin"
        onClick="closeAddSubscription()">
      <input type="submit" value="<?= translate('save', $i18n) ?>" class="thin" id="save-button">
    </div>
  </form>
</section>
