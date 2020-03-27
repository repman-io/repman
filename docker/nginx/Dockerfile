FROM nginx:alpine

ARG PHP_URL
ARG DOMAIN
ARG COMMON_NAME="*.$DOMAIN"
ARG SUBJECT="/C=PL/ST=None/L=None/O=Repman/CN=$COMMON_NAME"

SHELL ["sh", "-eo", "pipefail", "-c"]

RUN apk update && \
    apk add --no-cache -q \
    openssl && \
    rm -rf /var/cache/apk/*

COPY nginx.conf /etc/nginx/nginx.conf
RUN echo "upstream php-upstream { server $PHP_URL; }" > /etc/nginx/conf.d/upstream.conf

RUN adduser -D -H -u 1000 -s /bin/bash -G www-data www-data

COPY cert cert
COPY ssl/dhparam.pem /etc/ssl/dhparam.pem
COPY ssl/v3.ext .

# generate self-sign certificate if it's not provided by the user
RUN if [ -f "cert/server.key" ] && [ -f "cert/server.crt" ]; \
    then echo "User certificate found"; \
    else \
    openssl req \
    -batch -sha256 -new -x509 \
    -days 365 \
    -nodes \
    -subj "/C=PL/ST=Silesian/L=Bielsko Biala/O=Repman/CN=Buddy/" \
    -keyout cert/rootCA.key \
    -out cert/rootCA.crt && \
    openssl req -new -newkey rsa:2048 -sha256 -nodes \
    -keyout cert/server.key -subj ${SUBJECT} -out cert/server.csr && \
    sed s/%%DOMAIN%%/${DOMAIN}/g < v3.ext > /tmp/__v3.ext && \
    openssl x509 -req -in cert/server.csr -CA cert/rootCA.crt \
    -CAkey cert/rootCA.key -CAcreateserial -out cert/server.crt \
    -days 365 -sha256 -extfile /tmp/__v3.ext \
    ; fi

RUN cp cert/server.key /etc/ssl/private/nginx.key && \
    cp cert/server.crt /etc/ssl/certs/nginx.crt

CMD ["nginx"]
