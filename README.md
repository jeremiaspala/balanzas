# Balanzas

Servicio y aplicacion web para leer balanzas por telnet y capturar imagenes de camaras IP cuando el peso se estabiliza. Utiliza MySQL para configuracion y registro de pesadas.

## Requisitos

- Python 3
- Paquetes: `mysql-connector-python`, `flask`, `requests`

## Base de datos

Ejecutar `schema.sql` en MySQL para crear las tablas necesarias. Crear un usuario `balanzas` con la clave `balanzas` y otorgarle permisos sobre la base `balanzas`.

## Servicio

`service/balanza_service.py` lee la configuracion desde la base de datos y crea un hilo por cada balanza. Cada hilo se conecta por telnet, guarda los pesos y captura imagenes de las camaras configuradas cuando el peso permanece igual durante 5 segundos. Las imagenes se guardan en `/var/www/html/balanzas` organizadas por id de balanza y fecha.

Ejemplo de ejecucion:

```bash
python3 service/balanza_service.py
```

## Aplicacion web

La carpeta `webapp` contiene una aplicacion Flask sencilla para administrar balanzas y camaras y consultar las pesadas.

Ejemplo de ejecucion:

```bash
python3 webapp/app.py
```

La interfaz permite:
- Listar camaras y ver sus pesadas e imagenes.
- Cargar balanzas y camaras (ABM basico).

