FROM jwilder/nginx-proxy

RUN apt update && apt install -y netcat

COPY minimalHttpServer.sh /app/
COPY envManager.sh /app/
COPY clx-docker-envs-entrypoint.sh /app/
COPY my_proxy.conf /etc/nginx/conf.d/my_proxy.conf

ENTRYPOINT ["/app/clx-docker-envs-entrypoint.sh"]
CMD ["forego", "start", "-r"]
