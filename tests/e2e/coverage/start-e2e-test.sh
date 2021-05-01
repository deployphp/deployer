#!/bin/sh

ROOTDIR=$(readlink -f "$(dirname "$0")/../../..")

# Run E2E tests and grab exit code of the process
php "$ROOTDIR/vendor/bin/pest" --config "$ROOTDIR/tests/e2e/phpunit-e2e.xml"
E2E_EXIT_CODE=$?

# Generate coverage report file
php "$ROOTDIR/tests/e2e/coverage/coverage-report.php"

return $E2E_EXIT_CODE