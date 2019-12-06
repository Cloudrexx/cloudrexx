#!/usr/bin/env sh

set -eu

# Install gcc
apt-get update && apt-get install gcc
gcc -v

# Add python pip and bash
apk add --no-cache py-pip bash

# Install docker-compose via pip
pip install --no-cache-dir docker-compose
docker-compose -v
