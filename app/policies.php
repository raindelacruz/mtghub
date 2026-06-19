<?php

return [
    'privacy' => ['title' => 'Privacy Policy', 'sections' => [
        'Data we collect' => ['We collect account identity, contact and delivery details, listings, orders, payment-proof metadata and images, messages, reports, reviews, security logs, and wallet records needed to operate MTGHub PH.'],
        'How data is used' => ['Data is used for authentication, marketplace fulfillment, fraud prevention, moderation, notifications, dispute handling, and legal or accounting obligations. Payment proofs and order messages are private to transaction participants and authorized administrators handling disputes.'],
        'Retention and deletion' => ['Deletion requests have a 30-day cancellation period. Active obligations and store credit must be resolved first. On completion, direct identifiers are anonymized and active listings are cancelled. Completed transaction, wallet, moderation, dispute, and audit records may be retained for up to seven years; security logs are retained for up to 90 days and payment-proof files for up to 180 days after order closure unless a dispute or legal hold requires longer retention.'],
        'Security and contact' => ['MTGHub uses password hashing, CSRF protection, access controls, private upload storage, and operational logging. No system is risk-free. Contact the platform administrator to exercise access, correction, or deletion rights.'],
    ]],
    'terms' => ['title' => 'Terms of Use', 'sections' => [
        'Platform role' => ['MTGHub PH is an unofficial marketplace and collection platform. Users, not MTGHub, are responsible for listing accuracy, lawful ownership, payment, delivery, taxes, and compliance with applicable law.'],
        'Accounts' => ['Users must provide accurate information, secure their credentials, verify email before trading, and maintain only accounts they are authorized to use. Suspended, fraudulent, or abusive activity may be restricted.'],
        'Transactions' => ['An order is binding when placed. Cash or external-payment portions are exchanged outside MTGHub; the platform records references and proof but does not itself process those funds. Store credit is platform value, is not cash, and may be used or refunded only through supported workflows.'],
        'Liability' => ['Card prices, availability, and user content may change or be inaccurate. To the extent permitted by law, the platform is provided as available and does not guarantee a successful transaction.'],
    ]],
    'marketplace' => ['title' => 'Marketplace Rules', 'sections' => [
        'Listings' => ['List only authentic cards you own or are authorized to sell. Photos and descriptions must accurately represent edition, language, foil treatment, quantity, and condition. Counterfeit, stolen, misleading, or duplicate unavailable inventory is prohibited.'],
        'Conduct' => ['Keep order communication on-platform, communicate respectfully, protect personal information, and never use messages for harassment, phishing, or unrelated solicitation.'],
        'Fulfillment and evidence' => ['Buyers must pay by the displayed deadline. Sellers must verify payment evidence before fulfillment and provide tracking for shipped orders. Preserve proof, packaging, and messages until settlement.'],
        'Enforcement' => ['Listings, reviews, and accounts may be restricted after review. Reports and disputes are assessed using order history, payment proof, tracking, and messages.'],
    ]],
    'condition-guide' => ['title' => 'Card Condition Guide', 'sections' => [
        'Near Mint' => ['A clean card with only minimal handling wear, no bends, creases, clouding, or meaningful edge damage.'],
        'Lightly Played' => ['Minor edge, corner, or surface wear visible on close inspection, without structural damage.'],
        'Moderately Played' => ['Noticeable wear, whitening, scratches, or minor shuffle wear while remaining tournament sleeve playable.'],
        'Heavily Played or Damaged' => ['Heavy wear, bends, creases, water damage, ink, peeling, or structural damage must be clearly disclosed. When uncertain, choose the lower grade and describe defects.'],
    ]],
    'refunds' => ['title' => 'Cancellation and Refund Policy', 'sections' => [
        'Cancellation' => ['Pending-payment orders may be cancelled and inventory is restored. Unpaid orders expire after the payment deadline. Later cancellation requires a dispute or administrator intervention because fulfillment may already have begun.'],
        'Disputes' => ['Buyer or seller may open a dispute from payment verification through completion. Completed orders may be disputed for seven days. Opening a dispute freezes automatic settlement while administrators review available evidence.'],
        'Refunds' => ['Approved full or partial refunds restore the store-credit portion automatically. Any external cash portion is recorded by MTGHub but must be returned through the original external channel by the responsible party. Administrators record the external amount and resolution notes; MTGHub cannot guarantee or execute an external transfer.'],
        'Settlement' => ['Delivered orders settle after the confirmation window unless disputed. Buyer confirmation can accelerate settlement. Refund decisions consider listing accuracy, condition, delivery evidence, messages, and payment proof.'],
    ]],
];
