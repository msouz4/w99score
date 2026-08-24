#!/bin/bash

echo "🛑 Encerrando o túnel público do Ngrok..."
pkill ngrok 2>/dev/null || killall ngrok 2>/dev/null

echo "🛑 Parando os containers Docker do w99score..."
docker-compose down

echo "✅ Tudo desligado e encerrado com sucesso!"
