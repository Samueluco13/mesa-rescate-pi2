# Mensajes y validación en español

Corrección del plugin 2.0.2, 7 de noviembre de 2026. Conserva el tema 2.0.0, los datos, los permisos y las claves de las operaciones pendientes.

Los formularios de publicación y entrega muestran todos los errores junto a sus campos y en un resumen con enlaces. El foco pasa al primer campo inválido. Al corregir un dato se retira su aviso; se mantienen las otras indicaciones y las ayudas. La consulta de donaciones explica si falta el identificador o contiene letras, decimales o un número inválido.

La validación de la app usa mensajes propios en español y no depende del idioma de los avisos del navegador. Se comprueban campos vacíos o con espacios, longitud, cantidad entre 0.001 y 1.000.000 con hasta tres decimales, fechas reales y coherentes, y confirmaciones. En los campos numéricos se utiliza un punto para escribir decimales. El servidor vuelve a validar los datos y devuelve instrucciones por campo; rechaza textos demasiado largos en vez de recortarlos sin avisar.

Los errores de sesión, permisos, solicitudes ilegibles y parámetros rechazados por WordPress se presentan en español. La localización del servidor se limita al espacio REST `rescate/v1`, sin cambiar los códigos HTTP, los identificadores de error ni las respuestas de otros plugins. Los fallos inesperados no muestran excepciones técnicas en inglés. Un resultado incierto conserva el mismo identificador para reintentar sin duplicar.

También se cubren los campos obligatorios del acceso y la recuperación de contraseña, y el correo de Personalización. Los errores conocidos del acceso se traducen sin modificar la autenticación ni distinguir si existe la cuenta. Si una petición directa envía una preferencia inválida, se conserva el valor anterior de ese campo y aparece un aviso que lo identifica. Los controles y los fallos del mapa están en español; Leaflet y OpenStreetMap conservan sus nombres y atribuciones.

## Comprobar el cambio

```sh
node --test tests/forms-es.mjs
node tests/maps-ui.mjs
python3 tests/package_check.py
```

La suite PHP `tests/spanish-validation.php` se ejecuta únicamente en WordPress aislado con el plugin activo y `RA_SPANISH_TEST=true`. Fuerza el idioma de WordPress a `en_US` durante la prueba y restaura sus opciones y datos al finalizar. Las suites existentes `acceptance.php` y `maps-validation.php` comprueban el núcleo y las coordenadas. No deben exponerse como endpoints del hosting.

Prueba manual: intenta publicar sin completar los campos; corrige uno y comprueba que se conserva lo demás. Repite con cantidad cero, fecha pasada y confirmación desmarcada. En coordinación comprueba que cada horario futuro o invertido tenga su propio mensaje. En Personalización introduce un correo incompleto. Los mensajes deben seguir en español aunque WordPress o el navegador utilicen inglés.

## Resultados de esta corrección

El 7 de noviembre se aprobaron 29 comprobaciones PHP de mensajes y campos, 56 de regresión del núcleo, 28 de coordenadas, 18 JavaScript de formularios y 19 JavaScript de mapas. Los JSON `spanish-results-v202.json`, `regression-core-v202.json` y `regression-maps-v202.json` conservan el entorno y los resultados de servidor.

El plugin 2.0.2 quedó actualizado en el hosting. Se comprobaron los assets versionados, tres errores de publicación con datos conservados y foco en la cantidad, y el error REST real de sesión inválida en español con HTTP 403. La revisión local incluyó acceso, correo de Personalización y anchos de 1280 y 390 px con WordPress en inglés. Evidencia: `tests/results-spanish-hosting-v202.json` y `evidencia/validacion-espanol-v202.jpg`. No se afirma una prueba en iPhone físico ni una nueva ejecución del flujo completo en producción.
