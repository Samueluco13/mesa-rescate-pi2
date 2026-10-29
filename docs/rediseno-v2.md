> Corte de entrega: los resultados conservados de octubre 27 son evidencia histórica sanitizada. Este clon importa solo el código final; no incluye los arneses de migración dependientes de Git 1.0 ni el arnés de caché fijado a 2.0.0. Para comprobar el módulo actual: `node tests/maps-ui.mjs` (16 casos). El tema está reservado para `feature/mobile-theme`.

# Actualización 2.0: interfaz móvil y conservación del MVP

Fecha: 27 de octubre de 2026. Rama de trabajo: `feature/rediseno-mobile-v2`.

El plugin Rescate de Alimentos y el tema Rescate Mobile 2.0.0 están instalados en [el hosting del proyecto](https://lightgreen-echidna-927363.hostingersite.com/). La actualización reemplaza el sistema visual por una interfaz crema, roja y naranja, con navegación pensada para móvil, categorías fotográficas, tarjetas de donaciones y acceso directo al plan de recogida. Las reglas del núcleo siguen siendo registrar, consultar disponibles, reservar exclusivamente, cerrar entrega y consultar estado.

El naranja por defecto es `#d64114`: con texto blanco alcanza 4.54:1. Se corrigió el tono inicialmente propuesto `#d84316`, que medía 4.4358:1. El default se aplica cuando falta configuración; actualizar el plugin conserva las opciones personalizadas guardadas. El cliente mantiene la edición de nombre, lema, logotipo, color, textos, ciudad y contacto desde el plugin.

## Imágenes del rediseño

Se generaron ocho imágenes originales con OpenAI ImageGen, sin marcas de terceros ni texto dentro de la fotografía. Están optimizadas y alojadas en `plugin/rescate-alimentos/assets/images/v2/`:

| Archivos | Uso |
|---|---|
| `hero.webp`, `promo.webp` | Banner principal y promoción secundaria. |
| `vegetables.webp`, `fruit.webp`, `bakery.webp` | Categorías y tarjetas de verduras, frutas y panadería. |
| `prepared.webp`, `dairy.webp`, `pantry.webp` | Categorías y tarjetas de preparados, lácteos y despensa. |

Los ocho WebP suman 1.022.114 bytes, aproximadamente 1.02 MB. Esta cifra corresponde al conjunto de archivos, no a una medición del consumo de una visita. Son imágenes ilustrativas: no documentan alimentos aportados por comercios ni entregas reales. Los prompts completos y la procedencia están en `assets/asset-sources.json`; los créditos y licencias de los recursos conservados figuran en `assets/CREDITS.md`. La tipografía y las imágenes se sirven localmente.

## Caché y sesión

Los estilos y scripts reciben una versión formada por la versión del plugin y una huella SHA256 de su contenido. La versión de `app.js` incluye su dependencia `map-tools.js`; así, cambiar esa dependencia modifica también la URL del script dependiente. Los assets del administrador y los recursos locales de Leaflet también reciben versión por contenido. La configuración publica `appVersion`, `cacheVersion` y las versiones de Leaflet antes de cargar los módulos de la app. `map-tools.js` es dependencia de `app.js` para todos los roles.

El HTML de la app y las respuestas de su namespace REST, incluidos errores de autorización y de nonce, aplican `no-store`, `private`, caducidad pasada y `Vary: Cookie`, conservando otros valores de `Vary` ya existentes. Los nonces inválidos siguen rechazándose; no se reemplazan para eludir la autenticación. Las pruebas HTTP comprueban que el perfil y los permisos reflejan cambios en la siguiente petición.

La integración usa los hooks oficiales `litespeed_control_set_nocache` para la respuesta actual y `litespeed_purge_url` para las entradas de la app al actualizar o guardar personalización. No modifica la política de rutas REST ajenas ni desactiva la caché global, el acceso o la seguridad. [API oficial de LiteSpeed](https://docs.litespeedtech.com/lscache/lscwp/api/).

En hosting se ejecutó **Limpiar caché** de Hostinger y se observaron `app.css?ver=2.0.0-c6104662b161`, `map-tools.js?ver=2.0.0-eef94004563a` y `app.js?ver=2.0.0-43764a6060c2`. Son huellas del despliegue observado y cambian al editar los archivos. El esquema anterior de versión fija podía mantener la misma URL al cambiar un archivo; no hay evidencia suficiente para afirmar que la caché causara una falla previa.

La política HTTP se comprobó también en producción: la portada `/` y `/wp-json/rescate/v1/bootstrap` respondieron 200 con `no-store`, `private`, `max-age=0`, `Vary: Cookie` y `X-LiteSpeed-Cache-Control: no-cache`. Se conserva el registro en `tests/headers-hosting-v2.json`. Las comprobaciones de errores autenticados y de actualización de perfil/rol pertenecen a la suite local, sin extrapolarlas a peticiones de producción que no se ejecutaron.

Los borradores y las solicitudes pendientes mantienen sus claves de idempotencia. La limpieza de recursos del hosting no implica borrar `sessionStorage`, cuentas, donaciones, reservas ni historial.

## Evidencia ejecutada

| Verificación | Resultado |
|---|---|
| Caché/HTTP y sesión | 29 aprobadas, 0 fallos; `tests/cache-results-v2.json`. |
| Núcleo del servidor | 56 aprobadas, 0 fallos, invocadas como regresión desde la suite 2.0. |
| Coordenadas, permisos y compatibilidad | 28 aprobadas, 0 fallos, invocadas como regresión desde la suite 2.0. |
| Actualización real 1.0.0 → 2.0.0 | 18 aprobadas, 0 fallos; `tests/migration-results-v2.json`. |
| Comportamientos JavaScript de mapas | 10 aprobadas, 0 fallos; `tests/maps-ui.mjs`. |

La suite de 29 incluye dos resultados agregados que invocan las suites de 56 y 28; no se suman como casos independientes. La migración parte del código real 1.0.0 del commit `1cdbc5c012f23ec13425be3ef3f02cde4649584f`, conserva un rescate cerrado con sus tres actores, el historial y la respuesta idempotente anterior y no crea una donación adicional. Son pruebas aisladas con datos ficticios, separadas del hosting.

La revisión visual acotada resolvió cuatro ajustes en la superficie revisada. El mapa sigue cargando servicios externos solo después de una acción explícita; la prueba del selector impide confirmar antes de disponer de teselas visibles y permite recuperar un fallo de carga. No hay seguimiento en tiempo real.

## Recorrido verificado en el hosting

La evidencia sanitizada `tests/results-hosting-v2.json` registra un recorrido por interfaz con tres cuentas separadas y la donación #9 **PRUEBA REDISEÑO · Canasta solidaria**, marcada como simulación sin recogida real:

1. El comercio publica y confirma el punto `3.4516, -76.532`. El servidor muestra la donación Disponible.
2. El voluntario abre la acción de mapa: OpenStreetMap carga el marcador y se comprueba que el enlace ofrece la ruta en Google Maps con el destino correcto, sin permiso GPS solicitado desde la app. Después reserva y recarga Mis rescates; la asignación permanece Reservada. No se vuelve a abrir Google Maps externo en esta prueba 2.0; la apertura exterior tiene evidencia anterior en 1.1.
3. Coordinación confirma la entrega a **Organización ficticia de prueba v2** y consulta los tres eventos auditados con sus actores. El estado pasa a Entregada.
4. Tras ingresar nuevamente como comercio, #9 figura Entregada y el rescate antiguo #7 conserva Entregada.

Cadena comprobada: **Disponible → Reservada → Entregada**. Las capturas entregadas por separado muestran publicación, mapa del voluntario y cierre: `rediseño-01-publicacion.png`, `rediseño-02-mapa-voluntario.png` y `rediseño-03-entrega.png`. No se cambiaron credenciales ni se efectuó una recogida real durante esta verificación.

## Estado de aceptación

La prueba del hosting es secuencial. La carrera con dos voluntarios en InnoDB, la prueba de campo en un teléfono físico de gama baja, las mediciones de tiempo/consumo, el ensayo de restauración y la aceptación independiente del equipo siguen pendientes según el plan.

El [PR #1](https://github.com/morenotechnology/rescate-alimentos-wordpress/pull/1) permanece en revisión humana. Instalar la app y aprobar comprobaciones técnicas no equivale a aprobación del Product Owner, integración autorizada en `main` ni tarjeta académica Terminada. El trabajo se conserva en su rama; esta documentación no modifica `main` ni acredita una aprobación humana inexistente.
