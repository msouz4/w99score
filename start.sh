#!/bin/bash

echo "🚀 Iniciando os containers Docker do w99score..."
docker-compose up -d

echo "✅ Docker iniciado na porta 8080!"
echo "🌐 Conectando o Ngrok no domínio público..."
echo "📱 Acesse: https://emil-unmanipulative-nonmonarchically.ngrok-free.dev/analise.php"
echo "--------------------------------------------------------"

ngrok http 8080 --url https://emil-unmanipulative-nonmonarchically.ngrok-free.dev
