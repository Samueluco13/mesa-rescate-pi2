# Despliegue verificado · 27 de octubre de 2026

Aplicación: https://lightgreen-echidna-927363.hostingersite.com/

Personalización: https://lightgreen-echidna-927363.hostingersite.com/wp-admin/admin.php?page=rescate-personalizacion

Repositorio: https://github.com/morenotechnology/rescate-alimentos-wordpress/tree/develop

Revisión de integración: https://github.com/morenotechnology/rescate-alimentos-wordpress/pull/1

## Instalación inicial 1.0.0

El usuario autorizó instalar y activar el plugin Rescate de Alimentos 1.0.0 y el tema Rescate Mobile 1.0.0. WordPress confirmó ambas instalaciones y activaciones. Entorno mostrado por Hostinger: WordPress 7.1.3 y PHP 8.3.36.

La portada sirve la webapp con logo vectorial original, imágenes y tipografía locales y el pie «Desarrollado por Moreno Technology». Se configuraron el título del sitio y America/Bogota; el idioma existente es Español de Colombia.

La pantalla del plugin permite guardar nombre, lema, logo, color, textos, ciudad y contacto. Se verificó un guardado correcto. El cliente asigna cuentas y roles desde Usuarios de WordPress.

Se cargaron explícitamente seis donaciones ficticias marcadas como Demostración. La donación #4 (Almuerzos preparados) se reservó y se cerró con una receptora ficticia y observación de prueba. El panel administrativo, consultado de nuevo, refleja cinco disponibles y una entregada. No son donaciones ni entregas reales.

Las escrituras de reserva y cierre pasaron el control de almacenamiento transaccional del plugin. Esto comprueba el funcionamiento básico en el hosting; no equivale a una carrera con dos usuarios en InnoDB.

## Pendientes de aceptación del equipo

- Revisión y reproducción del PR por otro integrante antes de integrar main.
- Diez carreras con dos sesiones en el motor del hosting, además de las diez aprobadas por HTTP en Playground/SQLite.
- Prueba en teléfono físico de gama baja, medición del registro y consumo, respaldo/restauración y cierre del tablero.

La automatización de sintaxis y paquete está registrada como workflow activo en GitHub. La API no devolvió ejecuciones de Actions al comprobar el despliegue; no se declara CI remoto aprobado.

## Actualización 1.1.0: usuarios y mapa

WordPress confirmó la actualización del plugin de 1.0.0 a 1.1.0. El tema sigue en 1.0.0. Se crearon tres cuentas funcionales separadas, sin correos de bienvenida: comercio_e2e, voluntario_e2e y coordinacion_e2e. Las contraseñas se entregaron únicamente en un archivo local, fuera del repositorio. Una cuenta inicial descartada permanece sin perfil y sin acceso a la app.

El comercio publicó la donación ficticia #7; el voluntario la reservó y coordinación registró su entrega. Se volvió a consultar desde los tres perfiles y después de actualizar el plugin: estado, receptora e historial con tres actores conservados. Evidencia: `tests/results-hosting-roles.json` y `docs/prueba-hosting-roles.md`. La prueba es secuencial y no acredita concurrencia en InnoDB.

La donación ficticia #8 permite revisar el punto del mapa. Se publicó desde el registro asistido del administrador con una advertencia explícita de que no hay recogida real. Se verificó el pin persistido, la carga de OpenStreetMap, el destino y modo de transporte de los enlaces y la apertura de una ruta a pie en Google Maps. A 360 px, el documento mide 360 px y el diálogo 334 px, sin desbordamiento horizontal. No se solicitó permiso de ubicación desde la app. Evidencia: `tests/results-hosting-map.json`. Los modos disponibles dependen de la cobertura de Maps; el mapa requiere conexión.

## Actualización 2.0.0: interfaz y caché

WordPress confirmó la instalación de Rescate de Alimentos 2.0.0 y Rescate Mobile 2.0.0 en el mismo sitio. Se ejecutó la acción **Limpiar caché** de Hostinger. La portada sirve la nueva interfaz crema, roja y naranja y los assets de la versión instalada. Se observaron estas URLs de recursos:

- `app.css?ver=2.0.0-c6104662b161`
- `map-tools.js?ver=2.0.0-eef94004563a`
- `app.js?ver=2.0.0-43764a6060c2`

Los sufijos son huellas del contenido observado en esa comprobación. Pueden cambiar tras un nuevo cambio de archivo; no son identificadores fijos que deban conservarse manualmente. El plugin carga `map-tools.js` antes de `app.js` para todos los roles y configura la sesión antes de ambos.

La actualización incluye ocho fotografías originales generadas con IA, optimizadas en WebP y alojadas dentro del plugin. El color naranja por defecto es `#d64114`, con contraste blanco 4.54:1; los valores personalizados guardados permanecen intactos. Los prompts y la procedencia constan en los créditos del paquete.

Las medidas de caché son específicas de la app: `Cache-Control: no-store` para HTML y API, `Vary: Cookie`, respuesta a errores con la misma política y hooks oficiales de LiteSpeed para excluir esta respuesta y purgar las URLs de entrada al actualizar o guardar la marca. No se desactiva la seguridad, el inicio de sesión ni la caché global del sitio. Los hashes y la limpieza reducen el riesgo de contenido obsoleto; no se ha probado que la caché causara una falla anterior.

Se comprobaron además las cabeceras HTTP del hosting en la portada `/` y en `/wp-json/rescate/v1/bootstrap`: ambas responden 200, incluyen `no-store`, `private`, `max-age=0`, `Vary: Cookie` y `X-LiteSpeed-Cache-Control: no-cache`. La evidencia sanitizada está en `tests/headers-hosting-v2.json`. Esto verifica la política actual de esos dos recursos en producción y no establece la causa de un comportamiento anterior.

La comprobación local de actualización desde 1.0.0 a 2.0.0 conserva datos, cuentas e historial. Además, se completó desde la interfaz del hosting un recorrido nuevo con las tres cuentas separadas, sin cambiar credenciales:

1. `comercio_e2e` publicó la donación ficticia #9 **PRUEBA REDISEÑO · Canasta solidaria**, identificada como simulación sin recogida real, y confirmó el pin `3.4516, -76.532`.
2. `voluntario_e2e` abrió el mapa de OpenStreetMap con su marcador y comprobó que el enlace ofrece una ruta en Maps al destino correcto, sin solicitar GPS desde la app. No se volvió a abrir Google Maps externo en esta comprobación 2.0; la apertura exterior tiene evidencia anterior en 1.1. Reservó y, tras recargar, encontró la misma reserva en Mis rescates.
3. `coordinacion_e2e` confirmó la entrega a **Organización ficticia de prueba v2**. El detalle muestra los tres eventos de publicación, reserva y entrega con sus actores.
4. Se volvió a ingresar como comercio: #9 aparece Entregada y el rescate anterior #7 también conserva Entregada.

Cadena verificada: **Disponible → Reservada → Entregada**. Evidencia sanitizada: `tests/results-hosting-v2.json`. Capturas entregadas fuera del repositorio: `rediseño-01-publicacion.png`, `rediseño-02-mapa-voluntario.png` y `rediseño-03-entrega.png`. Este recorrido es secuencial y no acredita la carrera pendiente de dos voluntarios en InnoDB. El detalle se registra en `docs/rediseno-v2.md`.

La revisión visual acotada resolvió cuatro ajustes. Ese resultado corresponde a la superficie revisada y no representa aceptación académica completa. El PR #1 sigue pendiente de aprobación humana; la rama de trabajo es `feature/rediseno-mobile-v2` y `main` no se modifica.
