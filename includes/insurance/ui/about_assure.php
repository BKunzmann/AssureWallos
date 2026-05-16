<?php
/**
 * About: AssureWallos (Version, Releases, Issues).
 * Erwartet: $i18n, optional $demoMode.
 */
$assureDemoSuffix = (!empty($demoMode)) ? ' Demo' : '';
?>
            <div>
                <h3><?= htmlspecialchars(assure_app_name(), ENT_QUOTES, 'UTF-8') ?>
                    <?= assure_version_number() ?><?= $assureDemoSuffix ?></h3>
                <span><?= translate('current_version', $i18n) ?>:
                    <?= htmlspecialchars(assure_version_display(), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div>
                <h3><?= translate('release_notes', $i18n) ?> (AssureWallos)</h3>
                <span>
                    GitHub
                    <a href="<?= htmlspecialchars(assure_release_notes_url(), ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                        title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
            <div>
                <h3><?= translate('assurewallos_issues', $i18n) ?></h3>
                <span>
                    GitHub
                    <a href="<?= htmlspecialchars(assure_issues_url(), ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                        title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
