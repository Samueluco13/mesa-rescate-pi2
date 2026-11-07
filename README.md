# Mesa Rescate PI2

Entrega del MVP **Rescate de Alimentos**, webapp móvil para la Fundación Mesa Común. Versión actual: plugin **2.0.2** y tema **Rescate Mobile 2.0.0**. El corte académico original se preparó el **29 de octubre de 2026**; la corrección de mensajes en español corresponde al **7 de noviembre de 2026**.

[Abrir la aplicación](https://lightgreen-echidna-927363.hostingersite.com/) · [Guía de instalación y uso](GUIA-DE-USO.md)

Esta rama incluye el plugin y el tema integrado mediante el PR 2. Contiene código, ZIP instalables, pruebas y guía. La corrección 2.0.2 presenta los errores en español, identifica cada campo y conserva los datos y las solicitudes pendientes. Consulta [mensajes y validación](docs/mensajes-en-espanol.md).

## Qué permite hacer

**Publicar → Disponible → Reservada → Entregada.** El comercio registra los alimentos; un voluntario reserva la recogida; coordinación confirma la recepción. La reserva es exclusiva, las acciones se validan en el servidor y cada transición conserva historial. El administrador autoriza las cuentas y personaliza la identidad desde WordPress.

La interfaz incluye búsqueda, categorías, tarjetas de alimentos, perfiles y mapa de recogida. Un pin confirmado permite ver la ubicación y abrir la ruta en Google Maps. Sin pin, se conserva la búsqueda por dirección. Las demostraciones muestran una vista ilustrativa de Cali, sin marcador de un comercio ni ruta real. No existe seguimiento en tiempo real.

## Instalar y probar

1. Usa WordPress **6.6+**, PHP **8.1+**, HTTPS y MySQL 8 o MariaDB 10.6+ con InnoDB.
2. Instala y activa [entrega/rescate-alimentos-plugin.zip](entrega/rescate-alimentos-plugin.zip). También puedes comprimir `plugin/rescate-alimentos/` en su propio ZIP. Instala también [el tema móvil](entrega/rescate-mobile-theme.zip) si quieres utilizar la interfaz completa.
3. Configura la zona horaria **America/Bogota**, la página con `[rescate_app]` y **Rescate de Alimentos → Personalización**.
4. Como administrador, crea cuentas separadas con los roles **Comercio donante**, **Voluntario** y **Coordinación**. No hay registro público automático.
5. Ejecuta el recorrido de [GUIA-DE-USO.md](GUIA-DE-USO.md) con un registro claramente ficticio y consulta su estado final.

Los accesos administrativos y de prueba se entregan por separado en una hoja privada. El repositorio no contiene contraseñas ni una base de datos del hosting. Las imágenes de alimentos son ilustrativas; sus créditos y procedencia están en [assets/CREDITS.md](plugin/rescate-alimentos/assets/CREDITS.md).

## Archivos principales

| Ruta | Uso |
|---|---|
| [plugin/rescate-alimentos](plugin/rescate-alimentos/) | Plugin 2.0.2: datos, API, permisos, personalización, interfaz y mapas. |
| [entrega/rescate-alimentos-plugin.zip](entrega/rescate-alimentos-plugin.zip) | Plugin instalable publicado en este corte. |
| [theme/rescate-mobile](theme/rescate-mobile/) y [ZIP del tema](entrega/rescate-mobile-theme.zip) | Tema 2.0.0 aportado por Diego e integrado en develop. |
| [GUIA-DE-USO.md](GUIA-DE-USO.md) | Instalación, perfiles y recorrido completo. |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Estructura técnica y decisiones. |
| [docs/VALIDACION.md](docs/VALIDACION.md) | Evidencia de pruebas, versiones y límites. |
| [docs/mapa-compatibilidad.md](docs/mapa-compatibilidad.md) | Diagnóstico y comprobaciones del mapa 2.0.1. |
| [tests](tests/) | Pruebas y resultados sanitizados. |

## Desarrollo local

El plugin puede utilizarse mediante `[rescate_app]` con un tema de WordPress existente; su mínimo individual es WordPress 6.5 y PHP 8.0. Los mínimos de la instalación conjunta de arriba incluyen los del tema móvil.

Con Node.js **20.18+** y npm, ejecuta desde la raíz:

```sh
sh dev/start.sh
```

Abre la URL que indique WordPress Playground. El acceso automático de ese servidor es exclusivo del entorno local. Usa datos ficticios y conserva separadas las cuentas y la base de datos del hosting.

Pruebas rápidas de formularios en español y mapas:

```sh
node --test tests/forms-es.mjs
node tests/maps-ui.mjs
```

## Corte de entrega y colaboración

Este repositorio recibe el código actual como un corte de entrega, sin importar las versiones ni el historial anterior del MVP. Se conservan la historia y la documentación que ya existían en el repositorio de destino, incluida `docs/spec/constitucion.md`.

La organización usa ramas funcionales:

- `feature/wordpress-plugin`: entrega del plugin completo, pruebas y documentación de este corte.
- `develop`: integración del corte del plugin para revisión.
- `feature/mobile-theme`: aporte posterior del tema, que Diego debe revisar y registrar con sus propios commits.

Cada integrante debe revisar su trabajo, hacer sus propios commits con su identidad y proponer su integración. Preparar una rama o reservar archivos no crea commits ni acredita trabajo del otro integrante. El aporte del tema pendiente no se presenta como ya publicado. La revisión técnica automatizada no sustituye la revisión humana del compañero o del profesor.

## Evidencia y límites

Se conserva evidencia de un recorrido ficticio completo con tres perfiles en el hosting: [tests/results-hosting-v2.json](tests/results-hosting-v2.json). La donación #9 y sus participantes pertenecen a esa demostración; no se crean automáticamente en una instalación nueva.

El módulo de mapas 2.0.1 tiene **16 pruebas JavaScript aprobadas**. En Chromium del hosting se verificaron calles visibles para el mapa de ejemplo y el mapa con pin. La prueba final de Safari 2.0.1 y el iPhone físico siguen pendientes; la captura anterior de Safari 2.0.0 mostró marcador sobre gris, sin calles verificadas. Consulta [el diagnóstico detallado](docs/mapa-compatibilidad.md).

La concurrencia equivalente en InnoDB del hosting, las comprobaciones de campo y la aceptación humana de la entrega permanecen pendientes. La publicación y las pruebas secuenciales no acreditan por sí solas esos criterios.

Desarrollado por **Moreno Technology**.
