#!/bin/bash

# Quick test runner script
# Usage: ./RUN_TESTS.sh [test-name]

set -e

echo "🐳 Building test container..."
docker build -f Dockerfile.test -t mailer-test . -q

if [ -z "$1" ]; then
    echo "🧪 Running all tests..."
    docker run --rm mailer-test
else
    echo "🧪 Running $1..."
    docker run --rm mailer-test ./vendor/bin/phpunit "tests/$1.php" --verbose
fi
