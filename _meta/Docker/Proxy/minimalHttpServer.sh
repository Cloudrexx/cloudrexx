#!/bin/bash

# Minimal HTTP server designed to not use more than pure bash, GNU core-utils,
# util-linux and nc
# Usage: ./minimalHttpServer <port> [<handlerCommand>]
# handlerCommand gets called for each received request. It gets called with
# a single argument: the complete HTTP request including the headers.

# Sample handler command/function
function helloWorldResponse {
echo "<html>
    <body>
        <h1>Hello, World!</h1>
        <p>This server is working!</p>
    </body>
</html>"
}

# Handles TCP requests
# Expects first argument to be a command to handle requests and return response
function minimalHttpServer {
    SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
    while true; do
        read -r msg
        # TODO: Split header and body in two parts
        # TODO: Split header further into method, path, query, anchor, other headers
        # TODO: Return error if request could not be parsed
        # TODO: Allow handler command to return other statuses than 200
        resp="$($1 "$msg")"
        echo "HTTP/1.1 200 OK
Date: $(date)
Server: Bash with Netcat
Last-Modified: $(date -r "$SCRIPTPATH")
Content-Length: ${#resp}
Content-Type: text/html
Connection: Closed

$resp"
    done
}

# Help
if [[ $# -lt 1 ]]; then
    echo "Usage: minimalHttpServer.sh <port> [<handlerCommand>]"
    exit 1
fi

# Set handler
handler="helloWorldResponse"
if [[ $# -gt 1 ]]; then
    handler="$2"
fi

# Start server
coproc SERVERIO { minimalHttpServer "$handler"; }
while true; do nc -l -p "$1" -k <&"${SERVERIO[0]}" >&"${SERVERIO[1]}"; done
