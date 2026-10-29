# Rescate mobile webapp

Mode: Operate.

Scope: acceso invitado, exploración, rescates propios/operación, publicación, cuenta, detalle, reserva, entrega y planificación de recogida. Implementación principal: `plugin/rescate-alimentos/assets/app.css` y `app.js`; el tema `theme/rescate-mobile/` extiende la identidad al acceso y al marco WordPress.

Audience and jobs: comercios publican; voluntarios descubren y reservan una recogida; coordinación registra solicitudes asistidas y confirma la entrega. El primer vistazo debe hacer reconocibles los alimentos y el siguiente paso permitido por el rol.

## Direction contract

**THESIS:** Una app de comida hace deseable y comprensible compartir alimentos. La referencia elegida por el usuario fija la estética; la tarea sigue siendo rescatar y entregar.

**OWN-WORLD:** Crema melocotón, blanco cálido, hero fotográfico rojo profundo, naranja de acción, Manrope firme, categorías circulares, tarjetas suaves y navegación activa circular.

**STORY:** Identificar alimento, cantidad, lugar y hora; abrir detalle y mapa; reservar una sola recogida; seguir su estado hasta la entrega confirmada.

**FIRST VIEWPORT:** En móvil: marca, saludo y cuenta compactos; búsqueda redondeada; hero rojo con copia izquierda y alimentos derecha. Debajo: categorías horizontales, ofertas en dos columnas y franja fotográfica. La barra inferior conserva el destino activo y la acción principal está dentro del hero.

**FORM:** Réplica estética de la referencia móvil suministrada, fijada por el usuario por encima del roll. Sin posición alternativa en una lista. Seed reconocido: `b4cd99b4`. Interacción distintiva: categoría circular seleccionada → lista filtrada → «Mapa y ruta» o «Mapa de ejemplo», según el registro, desde cada tarjeta y desde el inicio del detalle.

**FINISH:** unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance

## Product constraints on this surface

- Mostrar cantidades y hechos de recogida; no precios, ratings, carrito, emojis, métricas ni notificaciones ficticias.
- Conservar la marca y copia configurables y el crédito «Desarrollado por Moreno Technology».
- Mantener avisos visibles de demostración, carga, fallo, información anterior y resultado incierto. La confirmación del servidor determina el éxito mostrado.
- La dirección visual conserva datos, configuración, borradores y claves de solicitud existentes. La documentación no autoriza reinicios de datos ni cambios de permisos.
- Mantener la versión por hash de contenido de CSS/JS y recursos dependientes para que la nueva identidad se actualice con los archivos publicados; no resolver caché borrando configuración o información de usuarios.

## Pickup planning

«Mapa y ruta» abre el detalle de una donación real y lleva al plan. Un pin guardado por el publicador permite abrir OpenStreetMap mediante acción explícita. Los registros reales sin pin ofrecen búsqueda por dirección y ruta externa; nunca se infiere una coordenada. Si falla el código del mapa, queda una alternativa visible. La ruta abre Google Maps, donde se elige el origen; no hay seguimiento en tiempo real.

En la extensión del plugin 2.0.1, los registros de demostración muestran «Mapa de ejemplo» en tarjeta y detalle. El panel «Mapa de ejemplo · Cali» abre una vista de ciudad centrada en `3.4516, -76.532`, zoom `13`, con aviso de demostración y sin marcador de comercio, ruta de recogida real, copia de dirección ni solicitud de GPS. Se conecta con OpenStreetMap al abrirlo. «Ampliar mapa de ejemplo» conserva la misma vista pública, también como alternativa si falla el módulo. No se convierte una dirección ficticia en un punto de recogida.

La cabecera modal y su cierre quedan fuera del cuerpo desplazable. Aplicación, shell y límites de altura del diálogo usan `100vh` antes de `100svh` como fallback de compatibilidad, sin cambiar el resultado en navegadores que admiten `svh`. La extensión conserva los tokens y componentes del sistema vigente; el tema mantiene versión 2.0.0.

## Implementation evidence

Documentado desde la fuente final: tokens y breakpoints de `app.css`, estructura/estados/configuración de `app.js`, estilos del tema y acceso, hash de recursos en PHP, y ocho imágenes v2 con procedencia en `asset-sources.json`. `DESIGN.md` contiene las primitivas normativas y `.impeccable/design.json` sus extensiones y ejemplos.

Los cuatro ajustes finales se contrastan con código: cabecera modal persistente y X de 44px; zonas tonales estables bajo texto de hero/promo; categorías de ancho mínimo 76px con separación 14px y fuente explícita; ubicación, horario y confirmación con casilla a 12px. Las capturas previas no se usan como evidencia de estos ajustes.

Evidencia de la identidad base 2.0 abierta por el documentador: `.impeccable/review/final-mobile-first.png`, `final-mobile.png`, `final-user-484.png`, `final-desktop.png`, `final-map.png`, `final-form.png` y `final-guest.png`. Estas capturas no se usan para validar la extensión del mapa de ejemplo 2.0.1.

La extensión 2.0.1 se contrastó con `map-tools.js`, las etiquetas/fallback de `app.js` y las declaraciones de altura de `app.css`. Se abrieron la Foto 1 de iPhone aportada por el usuario y las capturas nuevas `.impeccable/review/map-demo-mobile-390.png`, `map-demo-user-484.png` y `map-demo-desktop.png`. El agente raíz informó el veredicto **SHIP** de `impeccable_map_reviewer`, limitado al componente y sin fixes. En Safari Mac 2.0.0 se iniciaron el marcador y los controles, pero la captura muestra teselas grises; no acredita un mapa base cargado. La ejecución final de Safari 2.0.1 está pendiente de desbloqueo manual de la ventana privada. No se ha probado esta corrección en un iPhone físico. La documentación conserva las primitivas de DESIGN.md y no extiende ese veredicto a toda la aplicación.
