#!/bin/bash

echo "<h1>Existing Hosts</h1>"
servers=$(grep "server_name " /etc/nginx/conf.d/default.conf | grep -v "server_name _" | grep -v "server_name mail." | grep -v "server_name phpma.")
if [[ "$servers" == "" ]]; then
    echo "No hosts up..."
    exit
fi
echo "<ul>"
while read -r line; do
    server="${line//"server_name "/}"
    server="${server//";"/}"
    env="${server//".lvh.me"/}"
    echo "<li>$env<ul><li><a href=\"http://$server\">$server</a></li><li><a href=\"http://phpma.$server\">phpMyAdmin</a></li><li><a href=\"http://mail.$server\">MailHog</a></li></ul></li>"
done <<< "$servers"
echo "</ul>"
