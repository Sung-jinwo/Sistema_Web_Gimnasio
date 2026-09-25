# Despliegue en Railway

Usa un servicio MySQL de Railway. El proyecto contiene migraciones orientadas a MySQL/MariaDB, por lo que no debe desplegarse sobre PostgreSQL sin una migración independiente de compatibilidad.

## Servicios

1. **web**: usa el `Dockerfile`, expone el dominio público y recibe las variables de `.env.railway.example`. El archivo `railway.json` configura automáticamente `sh railway/predeploy.sh` como predespliegue.
2. **worker**: clona el mismo repositorio y usa como **Start Command** `sh railway/run-worker.sh`. No expone dominio público.
3. **scheduler**: clona el mismo repositorio y usa como **Start Command** `sh railway/run-scheduler.sh`. No expone dominio público.

No habilites `RUN_MIGRATIONS=true` si ya usas el pre-deploy; las migraciones deben ejecutarse una sola vez desde el servicio web.

## Primera carga de datos de prueba

Cuando el primer despliegue termine correctamente, abre la **Shell** del servicio web en Railway y ejecuta una sola vez:

```sh
php artisan db:seed --force
```

Esto crea los usuarios, sedes, productos y demás datos de demostración. No ejecutes ese comando si ya ingresaste datos reales: las migraciones son automáticas en cada despliegue, pero el seeder es solo para inicializar una base de pruebas.

Genera una única `APP_KEY` con `php artisan key:generate --show` y guárdala como secreto de Railway. Configura SMTP real antes de habilitar recuperación de contraseña; `MAIL_MAILER=log` no envía correos.

Los servicios usan cache, sesiones y cola en MySQL. Actualmente no hay cargas de archivos persistentes; si se agregan, cambia `FILESYSTEM_DISK` a S3/R2 antes de usarlas.
