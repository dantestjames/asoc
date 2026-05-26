<?php
/**
 * Privacy Policy — Australian APP-Compliant Scaffold
 *
 * This is a starter template. ICCWA needs to review this with their
 * legal advisor before publishing. The structure follows the 13 Australian
 * Privacy Principles (APPs) under the Privacy Act 1988.
 *
 * To customise: edit this file directly, OR replace it with a Page Builder
 * page once that's running.
 */

use ASOC\Core\App;

$lang = App::lang();
$settings = App::settings();
$siteName = $settings->get('association_name');
$contactEmail = $settings->get('association_email');
$abn = $settings->get('association_abn');
$address = $settings->get('association_address_physical');

$pageTitle = $lang->get('legal.privacy_title');
require __DIR__ . '/partials/header.php';
$heroTitle = $lang->get('legal.privacy_title');
require __DIR__ . '/partials/page-hero.php';
?>

<section class="legal-page">
    <div class="container container--narrow">
        <p class="meta">Last updated: <?= date('j F Y') ?></p>

        <div class="legal-warning">
            <strong>Note for admin:</strong> This is a starter template aligned with the Australian Privacy Principles (APPs) under the Privacy Act 1988 (Cth). Have it reviewed by a lawyer before going live. Edit at <code>public/views/privacy-policy.php</code> or via the page builder once enabled.
        </div>

        <h2>1. About this policy</h2>
        <p><?= htmlspecialchars($siteName) ?> (ABN <?= htmlspecialchars($abn) ?>) is committed to protecting the privacy of personal information we collect, hold, use and disclose. This Privacy Policy explains how we handle personal information in accordance with the Privacy Act 1988 (Cth) and the Australian Privacy Principles (APPs).</p>

        <h2>2. What information we collect</h2>
        <p>We collect personal information that is reasonably necessary for our functions and activities as a chamber of commerce, including:</p>
        <ul>
            <li>Contact details (name, email, phone, postal address)</li>
            <li>Business information (business name, ABN, trading name, industry)</li>
            <li>Membership history and payment records</li>
            <li>Event registration details</li>
            <li>Communications you have with us (support tickets, enquiries)</li>
            <li>Website usage data (anonymous visitor analytics, where applicable)</li>
        </ul>

        <h2>3. How we collect it</h2>
        <p>We collect personal information directly from you when you:</p>
        <ul>
            <li>Apply for membership</li>
            <li>Register for an event</li>
            <li>Submit a support ticket or enquiry</li>
            <li>Subscribe to communications</li>
            <li>Visit our website (limited technical information)</li>
        </ul>

        <h2>4. Why we collect it</h2>
        <p>We use personal information to:</p>
        <ul>
            <li>Process membership applications and renewals</li>
            <li>Provide member services and benefits</li>
            <li>Communicate about events, news, and updates relevant to members</li>
            <li>Maintain our member directory (only with your consent for public listing)</li>
            <li>Process payments and fulfil financial reporting obligations</li>
            <li>Improve our services and website</li>
            <li>Comply with legal and regulatory obligations</li>
        </ul>

        <h2>5. Disclosure to third parties</h2>
        <p>We may share personal information with:</p>
        <ul>
            <li><strong>Payment processors</strong> (Stripe, Square, PayPal) to process membership fees and event tickets</li>
            <li><strong>Email service providers</strong> for sending newsletters and notifications</li>
            <li><strong>Hosting providers</strong> who store our data (located in Australia or other countries)</li>
            <li><strong>Government or law enforcement</strong> where required by law</li>
        </ul>
        <p>We do not sell personal information to third parties.</p>

        <h2>6. Cross-border disclosure</h2>
        <p>Some of our service providers may be located outside Australia. Where we disclose information overseas, we take reasonable steps to ensure the recipient handles it consistently with the APPs.</p>

        <h2>7. Data security</h2>
        <p>We take reasonable steps to protect personal information from misuse, interference, loss, unauthorised access, modification, or disclosure. This includes:</p>
        <ul>
            <li>Encryption of sensitive data at rest (payment credentials, passwords)</li>
            <li>HTTPS for all website traffic</li>
            <li>Access controls limiting who can view member data</li>
            <li>Regular security reviews</li>
        </ul>

        <h2>8. Accessing and correcting your information</h2>
        <p>You have the right to access the personal information we hold about you and ask us to correct it if it's inaccurate. Members can update most details directly through the member dashboard. For other requests, contact us using the details below.</p>

        <h2>9. Member directory</h2>
        <p>Our public member directory is an opt-in feature. Members may choose whether to display their profile or business listing publicly when they register, and can change this at any time through their dashboard.</p>

        <h2>10. Cookies</h2>
        <p>Our website uses cookies that are strictly necessary for functionality (such as keeping you logged in). We do not currently use third-party tracking cookies. If this changes, we will update this policy.</p>

        <h2>11. Complaints</h2>
        <p>If you believe we have breached the APPs or otherwise mishandled your personal information, please contact us first using the details below. We will acknowledge your complaint within 5 business days and respond within 30 days.</p>
        <p>If you are not satisfied with our response, you may complain to the Office of the Australian Information Commissioner (OAIC) at <a href="https://www.oaic.gov.au" target="_blank">oaic.gov.au</a> or 1300 363 992.</p>

        <h2>12. Changes to this policy</h2>
        <p>We may update this policy from time to time. The current version is always available on this page with the "last updated" date.</p>

        <h2>13. Contact us</h2>
        <p><strong><?= htmlspecialchars($siteName) ?></strong><br>
        ABN: <?= htmlspecialchars($abn) ?><br>
        <?= nl2br(htmlspecialchars($address)) ?><br>
        Email: <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a></p>
    </div>
</section>

<style>
.legal-page { padding: 3rem 0 5rem; }
.legal-page .container--narrow { max-width: 760px; margin: 0 auto; padding: 0 1.5rem; }
.legal-page h1 { font-size: 2.25rem; margin-bottom: 0.5rem; }
.legal-page .meta { color: var(--grey-text); font-size: 0.9rem; margin-bottom: 2rem; }
.legal-page h2 { font-size: 1.25rem; margin-top: 2rem; margin-bottom: 0.75rem; color: var(--dark); }
.legal-page p { margin-bottom: 1rem; }
.legal-page ul { margin-bottom: 1rem; padding-left: 1.5rem; }
.legal-page li { margin-bottom: 0.4rem; }
.legal-warning {
    background: #FEF3C7;
    border-left: 4px solid #D97706;
    padding: 1rem 1.25rem;
    border-radius: 6px;
    margin-bottom: 2rem;
    font-size: 0.9rem;
}
.legal-warning code { background: rgba(0,0,0,0.06); padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.85em; }
</style>

<?php require __DIR__ . '/partials/footer.php'; ?>
