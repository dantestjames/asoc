<?php
/**
 * Terms of Use — Australian-compliant scaffold
 *
 * Replace or edit before going live. Have a lawyer review.
 */

use ASOC\Core\App;

$lang = App::lang();
$settings = App::settings();
$siteName = $settings->get('association_name');
$contactEmail = $settings->get('association_email');
$abn = $settings->get('association_abn');

$pageTitle = $lang->get('legal.terms_title');
require __DIR__ . '/partials/header.php';
$heroTitle = $lang->get('legal.terms_title');
require __DIR__ . '/partials/page-hero.php';
?>

<section class="legal-page">
    <div class="container container--narrow">
        <p class="meta">Last updated: <?= date('j F Y') ?></p>

        <div class="legal-warning">
            <strong>Note for admin:</strong> Starter template, get this reviewed by a lawyer before going live. Edit at <code>public/views/terms-of-use.php</code> or via the page builder once enabled.
        </div>

        <h2>1. Agreement</h2>
        <p>By accessing or using the website and services of <?= htmlspecialchars($siteName) ?> (ABN <?= htmlspecialchars($abn) ?>) ("we", "us", "our"), you agree to these Terms of Use. If you don't agree, please don't use the site.</p>

        <h2>2. Membership</h2>
        <p>Membership is open to individuals and businesses who meet our eligibility criteria. By applying for membership you agree to:</p>
        <ul>
            <li>Provide accurate and current information</li>
            <li>Pay the applicable membership fee</li>
            <li>Comply with our constitution and code of conduct</li>
        </ul>
        <p>Membership applications are subject to approval by the ICCWA committee. We reserve the right to refuse or revoke membership at our discretion, in accordance with our constitution.</p>

        <h2>3. Fees and refunds</h2>
        <p>Membership fees are payable annually unless otherwise specified. Fees are non-refundable except where required by Australian Consumer Law.</p>
        <p>Auto-renewal: where members opt into auto-renewal, the fee will be charged 30 days before expiry using the payment method on file. You may cancel auto-renewal at any time via your member dashboard.</p>

        <h2>4. Acceptable use</h2>
        <p>You agree not to:</p>
        <ul>
            <li>Use the site for any unlawful purpose</li>
            <li>Attempt to gain unauthorised access to any part of the site</li>
            <li>Post or transmit content that is defamatory, harassing, or infringes intellectual property</li>
            <li>Use member data for any purpose other than legitimate business networking within ICCWA's stated purpose</li>
            <li>Spam or send unsolicited commercial communications to other members</li>
        </ul>

        <h2>5. Member directory</h2>
        <p>Information in the public member directory is provided by members for the purpose of professional networking. Use of directory information for marketing, mass messaging, or other unsolicited contact is prohibited and may result in membership suspension.</p>

        <h2>6. Intellectual property</h2>
        <p>All content on this site, including text, images, logos, and code, is owned by <?= htmlspecialchars($siteName) ?> or used under licence. You may not reproduce, distribute, or create derivative works without our written permission, except for personal, non-commercial use.</p>
        <p>By submitting content to the site (e.g. business profiles, articles, comments), you grant us a non-exclusive, royalty-free licence to use, display, and distribute that content in connection with our services.</p>

        <h2>7. Events</h2>
        <p>Event registrations are subject to availability. Refund policies for individual events will be specified at the time of booking. We reserve the right to cancel or reschedule events; in such cases, registered attendees will be notified and offered refunds where appropriate.</p>

        <h2>8. Third-party services</h2>
        <p>Our site uses third-party services for payment processing (Stripe, Square, PayPal) and may include links to other websites. We are not responsible for the content or practices of third-party sites. Use of payment services is subject to the terms of the respective providers.</p>

        <h2>9. Limitation of liability</h2>
        <p>To the extent permitted by law, our liability for any claim arising from your use of the site is limited to the amount of fees you have paid in the 12 months preceding the claim. Nothing in these terms excludes any consumer guarantees under the Australian Consumer Law that cannot be excluded.</p>

        <h2>10. Termination</h2>
        <p>We may suspend or terminate your account for breach of these terms. You may cancel your account at any time through your member dashboard or by contacting us.</p>

        <h2>11. Changes to these terms</h2>
        <p>We may update these terms from time to time. Material changes will be notified via email to current members. Continued use of the site after changes constitutes acceptance of the updated terms.</p>

        <h2>12. Governing law</h2>
        <p>These terms are governed by the laws of Western Australia. Any disputes will be resolved in the courts of Western Australia.</p>

        <h2>13. Contact</h2>
        <p>Questions about these terms? Email <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a>.</p>
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
