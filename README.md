# Balanzas

Servicio y aplicación web para leer balanzas por telnet y capturar imágenes de cámaras IP cuando el peso se estabiliza. Utiliza MySQL para configuración y registro de pesadas. Ahora todo el panel está implementado en PHP.

## Requisitos

- PHP 7 u 8 con extensiones `mysqli` y `curl`

## Base de datos

Ejecutar `schema.sql` en MySQL para crear las tablas necesarias. Crear un usuario `balanzas` con la clave `balanzas` y otorgarle permisos sobre la base `balanzas`.

## Servicio

`phpapp/service.php` lee la configuración desde la base de datos y procesa las balanzas una a una. Se conecta por telnet, guarda los pesos y captura imágenes de las cámaras configuradas cuando el peso permanece igual durante 5 segundos. Las imágenes se guardan en `/var/www/html/balanzas` organizadas por id de balanza y fecha.

Ejemplo de ejecución:

```bash
php phpapp/service.php
```

## Aplicación web

La carpeta `phpapp` contiene la aplicación web en PHP para administrar balanzas y cámaras y consultar las pesadas.

Para ejecutarla con PHP embebido:

```bash
php -S localhost:8080 -t phpapp
```

La interfaz permite:
- Listar cámaras y ver sus pesadas e imágenes.
- Cargar balanzas y cámaras (ABM básico).

