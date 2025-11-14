#!/bin/bash

# Verifica se o servidor já está rodando
if lsof -i :8000 >/dev/null; then
    echo "Servidor já está rodando na porta 8000"
    echo "Parando servidor atual..."
    kill $(lsof -t -i:8000) 2>/dev/null
    sleep 2
fi

echo "Iniciando servidor PHP na porta 8000..."
echo "Acesse: http://localhost:8000"
echo "Pressione Ctrl+C para parar o servidor."

php -S localhost:8000 -t .