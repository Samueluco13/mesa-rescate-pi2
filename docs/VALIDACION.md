> Corte de entrega: los resultados conservados de octubre 27 son evidencia histórica sanitizada. Este clon importa solo el código final; no incluye los arneses de migración dependientes de Git 1.0 ni el arnés de caché fijado a 2.0.0. Para comprobar el módulo actual: `node tests/maps-ui.mjs` (16 casos). El tema está reservado para `feature/mobile-theme`.

# Validación ejecutada · 27 de octubre de 2026

## Base 1.0.0

Entorno: WordPress 6.8.11, PHP 8.3.33, WordPress Playground CLI 3.1.61, SQLite, dos procesos PHP. Versión de plugin/tema: 1.0.0.

- 56 comprobaciones de servidor aprobadas, cero fallos: creación/validación, permisos, registro asistido, filtros vigentes, claves repetidas, entrega, privacidad e historial. Evidencia: tests/results-local.json.
- 10 carreras por HTTP con dos cuentas autenticadas distintas, orden alternado: un 200 y un 409 por donación. Reintento del ganador conserva titular; la consulta de persistencia encuentra un único evento de reserva en cada caso. Evidencia: tests/results-race.json.
- Publicación, detalle, reserva y cierre de entrega completados desde la interfaz móvil con datos ficticios.
- Revisión de escritorio y 360px; se corrigieron el indicador de actualización, el contraste del botón de cabecera y 3px de desbordamiento de categorías.
- Sintaxis PHP comprobada por el analizador de PHP 8.3; JavaScript pasa node --check. Revisión de paquete y detector de diseño sin hallazgos.

No se presenta esta ejecución como aprobación del Product Owner ni como revisión humana de PR. Quedan pendientes InnoDB en el hosting, prueba en un teléfono físico de gama baja, medición observada del objetivo de tres minutos, consumo de datos, respaldo/restauración y aceptación del equipo. Los datos de demostración no representan operaciones reales.

La primera aproximación de concurrencia mediante evaluación de código compartido de Playground se descartó porque no aislaba los programas entre procesos. La evidencia conservada corresponde a peticiones HTTP reales con cookies y nonces distintos.

## Plugin 1.1.0: plan de recogida

- 28 comprobaciones de coordenadas opcionales y compatibilidad, más 56 del núcleo, aprobadas en Playground/PHP 8.3.
- 18 comprobaciones de actualización real ejecutadas entonces: se instaló el código 1.0.0 del commit 1cdbc5c, se publicó/reservó/entregó un rescate y se actualizó la misma base a 1.1.0, conservando cierre, datos, eventos y respuesta idempotente. El arnés `tests/maps-migration-v1.mjs` conserva ese origen real y actualmente verifica el destino 2.0.0.
- 10 pruebas JavaScript aprobadas: URLs, modos de transporte, XSS, datos de demostración, ausencia de carga externa/GPS automáticos y recuperación ante fallo de teselas. Ejecutar `node tests/maps-ui.mjs`.
- Revisión visual local del selector: confirmar y quitar un punto conserva el formulario. En hosting se comprobó el mapa guardado de #8, la apertura de Maps y la vista de 360 px. El rescate #7 permanece Entregada con los tres actores después de la migración.

Estas pruebas no sustituyen las pendientes de concurrencia en InnoDB ni la aceptación independiente del equipo.

## Plugin y tema 2.0.0

Pruebas ejecutadas en WordPress aislado con PHP 8.3 y SQLite, sin conexión a las cuentas ni a los registros del hosting:

| Suite | Resultado | Evidencia y alcance |
|---|---|---|
| Caché y HTTP de sesión | 29 aprobadas, 0 fallos | `tests/cache-results-v2.json`: hashes por contenido y dependencia, settings personalizados conservados, contraste, política limitada a la app, HTML y bootstrap por comercio/voluntario/coordinación, rechazo de nonce inválido y perfil/rol frescos en la siguiente petición. |
| Núcleo | 56 aprobadas, 0 fallos | Invocación de `tests/acceptance.php` desde la suite 2.0; el resumen se conserva en `tests/cache-results-v2.json`. |
| Mapas y coordenadas | 28 aprobadas, 0 fallos | Invocación de `tests/maps-validation.php` desde la suite 2.0; validación, permisos y reintentos anteriores compatibles. |
| Actualización real 1.0.0 → 2.0.0 | 18 aprobadas, 0 fallos | `tests/migration-results-v2.json`: versión anterior instalada desde el commit original, mismo almacenamiento y rescate cerrado conservado; hash y resultado idempotente anteriores sin reescritura ni duplicación. |
| JavaScript de mapas | 10 aprobadas, 0 fallos | `tests/maps-ui.mjs`: URLs y transporte, datos ficticios, escape de contenido, pin opcional, ausencia de carga externa/GPS automática y recuperación ante fallo de teselas. |

La suite de 29 comprobaciones incluye dos resultados agregados de regresión que invocan las suites de 56 y 28; no se suman estos recuentos como pruebas independientes. El chequeo de paquete y `git diff --check` también finalizaron sin errores. La carga de PHP durante las pruebas integra los archivos del plugin; el controlador JavaScript de caché pasa el analizador de Node.

En la revisión del color se midió `#d84316` con blanco en 4.4358:1, inferior a 4.5 para texto normal. El default acordado se ajustó a `#d64114`, que alcanza 4.54:1, manteniendo la personalización existente del cliente.

Reproducción: ejecutar `node tests/cache-validation.mjs`, `node tests/maps-migration-v1.mjs` y `node tests/maps-ui.mjs` con `@wp-playground/cli` instalado para las dos primeras. `RA_PLAYGROUND_CLI` permite señalar el módulo de CLI ya instalado. Los scripts de PHP son arneses aislados, nunca endpoints públicos. Los fixtures son ficticios y la suite de caché elimina sus propias cuentas de prueba.

En hosting se confirmaron ambas instalaciones 2.0.0, la acción de limpieza de caché y los assets con huella de contenido. La revisión visual acotada cerró cuatro ajustes.

La verificación HTTP de producción confirmó 200 para la portada y bootstrap, con `Cache-Control` que incluye `no-store` y `private`, `Vary: Cookie` y `X-LiteSpeed-Cache-Control: no-cache`. Evidencia: `tests/headers-hosting-v2.json`. Las pruebas locales cubren adicionalmente errores y cambios de perfil/rol; no se atribuyen esas comprobaciones negativas al hosting.

El recorrido de los tres perfiles se completó desde la interfaz con datos ficticios: comercio publica #9 y su pin; voluntario abre el mapa, comprueba el destino del enlace que ofrece la ruta en Maps, reserva y comprueba la persistencia al recargar; coordinación registra la entrega a una receptora ficticia y consulta los tres eventos auditados. No se volvió a abrir Google Maps externo en 2.0; esa apertura cuenta con evidencia anterior en 1.1. Al ingresar nuevamente como comercio, #9 está Entregada y #7 continúa Entregada. La cadena Disponible → Reservada → Entregada, el mapa accesible al voluntario y la conservación del rescate anterior están verificados en esta instalación. Evidencia sanitizada: `tests/results-hosting-v2.json`; capturas de publicación, mapa y cierre entregadas por separado. No se realizó una recogida real.

La revisión humana del PR #1, la integración autorizada en `main`, las carreras con dos usuarios en InnoDB y las demás comprobaciones de campo continúan pendientes. Publicar en el hosting no satisface por sí solo la definición académica de Terminado.
