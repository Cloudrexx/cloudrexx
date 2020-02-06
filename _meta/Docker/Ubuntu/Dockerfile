FROM ubuntu:rolling
RUN apt update && apt install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    software-properties-common \
    wget \
    mysql-client \
    vim \
    ftp \
    zip \
 && curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add - \
 && add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) edge" \
 && apt update \
 && apt install -y docker-ce \
 && rm -rf /var/lib/apt/lists/*
RUN curl -L https://github.com/docker/compose/releases/download/1.18.0/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose \
 && curl -L https://raw.githubusercontent.com/docker/compose/1.18.0/contrib/completion/bash/docker-compose -o /etc/bash_completion.d/docker-compose \
 && chmod +x /usr/local/bin/docker-compose
