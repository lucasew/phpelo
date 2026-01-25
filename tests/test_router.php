<?php

$IS_ROUTED = false;
$ROUTE = '/test';
$INPUT_DATA = [];

require_once __DIR__ . '/../lib/router.php';

// Test exact_route
echo "Testing exact_route...\n";
$MOCK_EXECUTED = false;
exact_route('/test', 'tests/mock_handler.php');

if ($IS_ROUTED === true) {
    echo "PASS: IS_ROUTED is true\n";
} else {
    echo "FAIL: IS_ROUTED is false\n";
    exit(1);
}

if ($MOCK_EXECUTED === true) {
    echo "PASS: Mock handler executed\n";
} else {
    echo "FAIL: Mock handler NOT executed\n";
    exit(1);
}

// Reset for next test
$IS_ROUTED = false;
$ROUTE = '/sub/path';
$MOCK_EXECUTED = false;

echo "Testing use_route...\n";
use_route('/sub', 'tests/mock_handler.php');

if ($ROUTE === '/path') {
     echo "PASS: ROUTE updated correctly\n";
} else {
     echo "FAIL: ROUTE not updated correctly. Got: $ROUTE\n";
     exit(1);
}

if ($MOCK_EXECUTED === true) {
    echo "PASS: Mock handler executed (use_route)\n";
} else {
    echo "FAIL: Mock handler NOT executed (use_route)\n";
    exit(1);
}

echo "All tests passed.\n";
?>
