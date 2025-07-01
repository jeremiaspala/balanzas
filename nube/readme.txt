# Sistema de Lectura de Balanzas IP con Subida Remota

Este sistema permite leer cabezales de balanza por Telnet, detectar pesadas estables, capturar imágenes desde cámaras IP asociadas y subir todos los datos a un servidor remoto mediante una API segura.

## Estructura del Proyecto

```
/opt/balanza/
├── balanza_service_remoto.py
├── sync_config.py
└── config.json (generado por sync_config)

/var/www/html/balanza/
└── (Solo requerido si se cachean imágenes localmente)

/api/ (en el servidor web con PHP)
├── ingresar_datos.php
├── subir_foto.php
└── descargar_config.php
```

---

## Requisitos en Ubuntu Server 24.04

```bash
sudo apt update
sudo apt install python3 python3-pip php php-mysql mariadb-server curl -y
sudo pip3 install requests
```

Permisos del log (si querés habilitar logs locales):
```bash
sudo touch /var/log/balanza.log
sudo chown www-data:www-data /var/log/balanza.log
sudo chmod 664 /var/log/balanza.log
```

---

## Instalación del Cliente (balanza)

1. Descargar configuración desde la nube:
```bash
python3 /opt/balanza/sync_config.py
```

2. Iniciar el servicio (modo prueba):
```bash
python3 /opt/balanza/balanza_service_remoto.py
```

3. Para que arranque automáticamente:
```bash
sudo nano /etc/systemd/system/balanza.service
```
Contenido:
```ini
[Unit]
Description=Servicio Balanza Remota
After=network.target

[Service]
ExecStart=/usr/bin/python3 /opt/balanza/balanza_service_remoto.py
Restart=always

[Install]
WantedBy=multi-user.target
```
```bash
sudo systemctl daemon-reexec
sudo systemctl enable --now balanza
```

---

## API Remota (en servidor con PHP)

- **ingresar_datos.php**: Guarda registros de peso (requiere token)
- **subir_foto.php**: Guarda imágenes capturadas
- **descargar_config.php**: Devuelve escalas y cámaras asociadas como JSON

La base de datos debe tener al menos:
- Tabla `equipos` (con campo `token`, `activo`)
- Tabla `scales`
- Tabla `cameras`
- Tabla `weights`

---

## Seguridad
- El token identifica a cada cliente.
- Solo los tokens activos pueden subir datos o descargar configuración.
- Las imágenes se envían por POST multipart con validación.

---

## Personalización
- Cambiá las URLs de API (`API_PESO`, `API_IMG`) y el `TOKEN` en el archivo `balanza_service_remoto.py` y `sync_config.py`.
- Podés automatizar la sincronización diaria con:
```bash
crontab -e
```
Y agregar:
```cron
@hourly python3 /opt/balanza/sync_config.py
```

---

## Contacto
Este sistema fue diseñado por Jeremias. Para soporte, contactalo por GitHub: [jeremiaspala](https://github.com/jeremiaspala)
