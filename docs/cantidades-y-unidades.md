# Cantidades por unidades y medidas

Mejora del plugin 2.0.3 solicitada el 7 de noviembre de 2026, posterior al corte académico original.

La publicación empieza preguntando cómo se indica la cantidad. Las nuevas donaciones proponen **Unidades o piezas** para contar manzanas, panes, latas o bolsas. **¿Qué estás contando?** es opcional: escribir «manzanas» junto a la cantidad 12 hace que la donación se presente como **12 manzanas** en tarjetas, detalle, reserva, entrega y administración. El texto se conserva tal como lo escribe el usuario, con un máximo de 60 caracteres. Si se omite, se utiliza «unidades».

Las unidades o piezas deben ser números enteros mayores que cero. Kilogramos, litros y porciones mantienen hasta tres decimales. Al cambiar la forma de contar se actualizan la etiqueta, el ejemplo y los límites del campo; el número no se transforma ni se redondea. La vista previa permite revisar el resultado antes de publicar. No se crea una donación hasta que se completa y confirma el formulario.

## Persistencia y compatibilidad

Los valores técnicos `units`, `kg`, `portions` y `liters` se conservan. El nuevo campo opcional `unit_label` solo acompaña a `units`; enviar un nombre no vacío para otra medida produce un error específico. Los textos demasiado largos o de tipo inválido se rechazan. El valor se sanea en el servidor y se escapa al mostrarse.

La actualización añade una columna de texto vacía a los registros existentes. No modifica cantidades, estados, responsables, eventos ni respuestas guardadas. Si se reintenta una solicitud anterior sin `unit_label`, el dato vacío no se incorpora al cálculo de su clave. La nueva validación de enteros se aplica después de comprobar si esa solicitud ya fue confirmada: una operación antigua puede recuperar su resultado original aunque tuviera unidades fraccionarias o su plazo haya vencido. Los registros históricos se siguen consultando con su cantidad original.

## Verificación

```sh
node --test tests/forms-es.mjs
node tests/maps-ui.mjs
python3 tests/package_check.py
```

`tests/units-validation.php` requiere WordPress aislado, plugin activo y `RA_UNITS_TEST=true`. Comprueba el nombre de las piezas durante creación, consulta, reserva y entrega; las cantidades enteras; los rechazos por campo; las medidas decimales y los reintentos. Las pruebas de migración deben instalar primero el ZIP real 2.0.2, crear sus datos y actualizar después la misma base a 2.0.3. Los arneses no se exponen como endpoints del sitio publicado.

La ejecución aislada del 7 de noviembre de 2026 terminó sin fallos: migración desde el ZIP real 2.0.2 a 2.0.3, **4 comprobaciones previas y 14 posteriores**; unidades, **19**; mensajes en español, **29**; núcleo, **56**; coordenadas y mapas del servidor, **28**; formularios, **26**; y mapas en JavaScript, **19**. Los resultados PHP se conservan en `tests/migration-before-results-v203.json`, `tests/migration-after-results-v203.json`, `tests/units-results-v203.json`, `tests/spanish-results-v203.json`, `tests/regression-core-v203.json` y `tests/regression-maps-v203.json`. Esta evidencia corresponde a pruebas locales; no acredita una actualización del sitio publicado.

## Publicación

El 7 de noviembre de 2026 se actualizó el plugin del hosting a 2.0.3 mediante el instalador de WordPress, que confirmó la actualización. En una pestaña nueva de `/rescates/` se verificó la opción inicial «Unidades o piezas», el campo opcional y la vista previa «Vas a publicar 12 manzanas.». Los tres scripts del plugin cargaron con versión 2.0.3. La evidencia está en `evidencia/cantidades-por-unidades-v203.jpg`. Esta comprobación no creó donaciones en el hosting ni constituye una prueba en un iPhone físico.
