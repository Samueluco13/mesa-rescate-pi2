# Mapa: diagnóstico y alcance del hotfix 2.0.1

Fecha: 27 de octubre de 2026. Versiones instaladas: plugin Rescate de Alimentos 2.0.1 y tema Rescate Mobile 2.0.0. WordPress confirmó la actualización del plugin y Hostinger mostró éxito al limpiar la caché.

## Diagnóstico de la foto

La fotografía recibida desde el iPhone muestra una donación identificada como **Demostración** y el texto que informa que no se genera una ruta de recogida real. En el código anterior, la rama `is_demo` sustituía toda la sección del mapa por ese aviso. Además, las donaciones iniciales de demostración no tenían coordenadas de recogida.

Esa combinación explica por qué ese registro no mostraba una acción para cargar el mapa. La condición dependía de la marca y los datos del registro; no era una comprobación del navegador, sistema operativo ni rol del participante. La foto no demuestra una incompatibilidad del iPhone ni un fallo de Safari. Tampoco demuestra que hubiera fallado una petición de teselas: en ese estado no se ofrecía cargar el mapa.

## Comportamiento corregido

Los registros con `is_demo` permiten abrir una vista ilustrativa pública de Cali, centrada en `3.4516, -76.532`, zoom 13. El aviso especifica que no ubica a un comercio ni representa una recogida real. No se agrega marcador de recogida, copia de dirección ficticia, ruta real ni consulta de ubicación del dispositivo. La ampliación abre la misma vista pública de OpenStreetMap.

Los registros fuera de esa marca conservan su punto confirmado, el marcador de recogida y los enlaces de búsqueda/ruta con el destino correspondiente. Una dirección sin coordenadas no recibe un pin inventado. No se modifican donaciones, reservas, entregas ni historial para producir el mapa ilustrativo.

El mapa continúa cargándose solo por acción explícita. El cargador espera CSS y JavaScript de Leaflet, recupera un fallo de carga con reintento y distingue errores de teselas de falta de conexión. Al reintentar se elimina la instancia anterior. Se recalcula el tamaño tras montar el contenedor, se conserva `100vh` como fallback de `100svh` y se lleva el contenido al área visible. Estas medidas refuerzan la carga y el diseño adaptable; no constituyen una garantía de compatibilidad con todos los dispositivos.

## Evidencia separada por entorno

| Entorno y versión | Evidencia observada | Límite |
|---|---|---|
| JavaScript local, plugin 2.0.1 | 16 comprobaciones aprobadas en `tests/maps-ui.mjs`: regresión del pin y rutas, render seguro, mapa de demostración sin marcador/ruta/GPS, espera de CSS, fallo y reintento, desconexión y recálculo de tamaño. | Pruebas del módulo con un arnés DOM/Leaflet; no equivalen a ejecutarlo en un teléfono físico. |
| Revisión visual acotada del componente actualizado | Revisor independiente aceptó la superficie revisada sin ajustes adicionales. | Corresponde al componente y al alcance revisado; no es aprobación humana académica del PR ni una matriz completa de dispositivos. |
| Safari nativo en macOS, plugin 2.0.0 | En una sesión privada como voluntario aparecieron el contenedor, los controles y el marcador de #8. La captura `safari-mapa-con-pin.png` muestra el fondo gris, sin teselas visibles, aunque el texto de estado afirmaba que el mapa había cargado. | Se confirma la inicialización de la interfaz y el marcador; la captura no acredita la carga del mapa base. No se considera una prueba aprobada de teselas en Safari. |
| Safari nativo en macOS, después de instalar 2.0.1 | El sistema operativo presentó una solicitud de autenticación para acceder a la ventana privada. La verificación se detuvo sin actuar en esa solicitud ni eludirla. Se pidió al usuario el desbloqueo manual y su respuesta permanece pendiente. | No se obtuvo una ejecución verificada de Safari con el hotfix. |
| Chromium en el hosting, plugin 2.0.1, 390 × 844 px, perfil voluntario | Se verificaron teselas visibles en el mapa ilustrativo de Cali, sin marcador de comercio ni ruta real, y en el mapa de #8 con su pin guardado; el enlace conserva el destino de ruta. | La prueba corresponde a Chromium a ese tamaño. No equivale a Safari de iPhone ni a una apertura externa de Maps durante este hotfix. |
| iPhone físico / iOS Simulator | No se ejecutó una prueba física posterior al hotfix. El entorno tiene Command Line Tools y no dispone de iOS Simulator. | No se afirma validación física ni garantía de compatibilidad iPhone. |

La evidencia de Chromium del hotfix se conserva en `tests/results-map-compatibility.json`. La evidencia 2.0 del recorrido completo con tres roles sigue en `tests/results-hosting-v2.json`; esta corrección del mapa no debe presentarse como una nueva ejecución de ese recorrido. La apertura externa de Google Maps tiene evidencia anterior en 1.1; en 2.0 y en el hotfix se comprobó el destino del enlace, sin repetir esa apertura exterior.

La inspección de píxeles prevalece sobre el texto de estado: el contenedor con un marcador sobre gris no demuestra que las teselas se vieran. Esperar CSS y JavaScript y recalcular tamaño en 2.0.1 reduce dos riesgos de inicialización, pero no prueba por sí mismo que alguno causara el fondo gris en Safari 2.0.0 ni que el hotfix lo resuelva allí.

## Estado de validación

El diagnóstico comprobado de la foto del iPhone es una exclusión por datos de demostración en la interfaz anterior. Las 16 pruebas automatizadas cubren la corrección y su regresión. La actualización, la limpieza del hosting y las teselas de las dos vistas en Chromium 2.0.1 están confirmadas. La captura de Safari 2.0.0 plantea una comprobación distinta: la inicialización del marcador no fue suficiente para verificar el mapa base.

La evidencia incompleta de Safari 2.0.0 permanece separada de la comprobación pendiente de Safari con el hotfix. La prueba en un iPhone físico, las carreras simultáneas en InnoDB y la aceptación humana del PR continúan pendientes. No se declara una incompatibilidad telefónica demostrada ni una compatibilidad universal a partir de esta foto y estas pruebas.
