<?php
/**
 * About: Wallos-Upstream (Basis, Updates, Credits).
 * Erwartet: $i18n, $version, optional $demoMode, $wallosIsUpToDate, $latestVersion.
 */
$assureDemoSuffix = (!empty($demoMode)) ? ' Demo' : '';
?>
            <div>
                <h3><?= translate('assurewallos_upstream', $i18n) ?></h3>
                <span>Wallos <?= htmlspecialchars((string) $version, ENT_QUOTES, 'UTF-8') ?><?= $assureDemoSuffix ?></span>
            </div>
            <div>
                <h3><?= translate('release_notes', $i18n) ?> (Wallos)</h3>
                <span>
                    GitHub
                    <a href="<?= htmlspecialchars(assure_wallos_upstream_release_url(), ENT_QUOTES, 'UTF-8') ?>"
                        target="_blank" title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
            <?php if (isset($wallosIsUpToDate) && !$wallosIsUpToDate && !empty($latestVersion)): ?>
                <div class="update-available">
                    <h3>
                        <i class="fa-solid fa-info-circle"></i>
                        <?= translate('assure_upstream_update_available', $i18n) ?>
                        <?= htmlspecialchars((string) $latestVersion, ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <span>
                        <?= translate('release_notes', $i18n) ?>
                        <a href="<?= htmlspecialchars(assure_wallos_upstream_url(), ENT_QUOTES, 'UTF-8') ?>/releases/tag/<?= htmlspecialchars((string) $latestVersion, ENT_QUOTES, 'UTF-8') ?>"
                            target="_blank" title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </span>
                </div>
            <?php endif; ?>
            <div>
                <h3><?= translate('license', $i18n) ?></h3>
                <span>
                    GPLv3
                    <a href="https://www.gnu.org/licenses/gpl-3.0.en.html" target="_blank"
                        title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
            <div>
                <h3><?= translate('assurewallos_wallos_issues', $i18n) ?></h3>
                <span>
                    GitHub (ellite/Wallos)
                    <a href="<?= htmlspecialchars(assure_wallos_upstream_issues_url(), ENT_QUOTES, 'UTF-8') ?>"
                        target="_blank" title="<?= translate('external_url', $i18n) ?>" rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
            <div>
                <h3><?= translate('assurewallos_upstream_author', $i18n) ?></h3>
                <span>
                    https://henrique.pt
                    <a href="https://henrique.pt/" target="_blank" title="<?= translate('external_url', $i18n) ?>"
                        rel="noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </span>
            </div>
