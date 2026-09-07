<?php
const WOMPI_PUBLIC_KEY = '';
const WOMPI_INTEGRITY_SECRET = '';
const WOMPI_ENVIRONMENT = 'sandbox';

function wompiBaseUrl() {
    return WOMPI_ENVIRONMENT === 'production'
        ? 'https://production.wompi.co'
        : 'https://sandbox.wompi.co';
}
