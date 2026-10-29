<?php
define('WOMPI_PUBLIC_KEY', trim((string) (getenv('WOMPI_PUBLIC_KEY') ?: '')));
define('WOMPI_PRIVATE_KEY', trim((string) (getenv('WOMPI_PRIVATE_KEY') ?: '')));
define('WOMPI_INTEGRITY_SECRET', trim((string) (getenv('WOMPI_INTEGRITY_SECRET') ?: '')));
define('WOMPI_EVENTS_SECRET', trim((string) (getenv('WOMPI_EVENTS_SECRET') ?: '')));
const WOMPI_ENVIRONMENT = 'sandbox';

function wompiBaseUrl() {
    return 'https://sandbox.wompi.co';
}
