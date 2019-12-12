#!/usr/bin/env sh

set -eu

# Add python pip and bash
apk add --no-cache py-pip bash

# Install gcc via pip
pip install gcc7
gcc -v

# Install docker-compose via pip
pip install --no-cache-dir docker-compose
docker-compose -v
