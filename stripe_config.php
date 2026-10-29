<?php
define('STRIPE_SECRET_KEY', trim((string) (getenv('STRIPE_SECRET_KEY') ?: '')));
define('STRIPE_WEBHOOK_SECRET', trim((string) (getenv('STRIPE_WEBHOOK_SECRET') ?: '')));

function stripeModoPruebaDisponible() {
    return str_starts_with(STRIPE_SECRET_KEY, 'sk_test_')
        || str_starts_with(STRIPE_SECRET_KEY, 'rk_test_');
}