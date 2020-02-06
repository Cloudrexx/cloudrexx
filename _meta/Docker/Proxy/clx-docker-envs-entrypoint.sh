#!/bin/bash

/app/minimalHttpServer.sh 8080 /app/envManager.sh &

/app/docker-entrypoint.sh "$@"
