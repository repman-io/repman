#!/bin/ash

COMMON_NAME="*.${APP_HOST}"
SUBJECT="/C=PL/ST=None/L=None/O=Repman/CN=$COMMON_NAME"

adduser -D -H -u 1000 -s /bin/bash -G www-data www-data

sed s/%%PHP_URL%%/${PHP_URL}/g < /nginx/nginx.conf > /etc/nginx/nginx.conf

function generate_certificate {
    echo "Generating self-signed certificate for domain: ${APP_HOST}..."

    apk update && \
        apk add --no-cache -q \
        openssl && \
        rm -rf /var/cache/apk/*

    openssl req \
        -batch -sha256 -new -x509 \
        -days 365 \
        -nodes \
        -subj "/C=PL/ST=Silesian/L=Bielsko Biala/O=Repman/CN=Buddy/" \
        -keyout /etc/ssl/private/rootCA.key \
        -out /etc/ssl/certs/rootCA.crt

    openssl req -new -newkey rsa:2048 -sha256 -nodes \
        -keyout /etc/ssl/private/server.key -subj ${SUBJECT} -out /etc/ssl/certs/server.csr

    sed s/%%DOMAIN%%/${APP_HOST}/g < /nginx/v3.ext > /tmp/__v3.ext

    openssl x509 -req -in /etc/ssl/certs/server.csr -CA /etc/ssl/certs/rootCA.crt \
        -CAkey /etc/ssl/private/rootCA.key -CAcreateserial -out /etc/ssl/certs/server.crt \
        -days 365 -sha256 -extfile /tmp/__v3.ext

    openssl dhparam -out /etc/ssl/certs/dhparam.pem 2048

    echo "Self-signed certificate generated"
}

if [ -f "/etc/ssl/private/server.key" ] && [ -f "/etc/ssl/certs/server.crt" ]
then
    echo "Certificate found"
else
    generate_certificate
fi

echo "Starting nginx"
nginx
