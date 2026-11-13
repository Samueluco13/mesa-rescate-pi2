# Asignación a receptoras y escenarios de mapa

En **Operación** y **Mis rescates**, **Disponibles** solo muestra ofertas cuyo plazo aún no vence. Una oferta que vence con la lista abierta se actualiza sin recargar. **Vencidas** y **Todos** permiten consultarla; el historial no se borra. Una reserva conserva su estado aunque pase la hora de recogida.

## Asignación a organizaciones (CMP-05 a CMP-07)

1. En WordPress, asigna a la cuenta de la organización el rol **Organización receptora**. Esa cuenta entra en **Recepción**, marca si está disponible e indica sus capacidades diarias en kilos, litros, porciones y piezas (0 si no usa una medida), refrigeración y, si las conoce, sus coordenadas. Guarda el perfil. No hay conversiones entre medidas.
2. El comercio indica si la donación requiere refrigeración. El voluntario confirma la reserva. Coordinación abre la donación reservada en **Operación**: se evalúan receptoras activas, cupo restante en la misma unidad y refrigeración. Si hay coordenadas en ambos extremos, la sugerencia prioriza el menor traslado estimado. La estimación es aproximada, a partir de distancia, sin tráfico ni navegación. Si no se pueden comparar los traslados, se permite seleccionar una receptora compatible y fijar la hora manualmente.
3. Si ninguna cumple, coordinación ve el aviso **No hay una asignación automática viable** al abrir la reserva. No se asigna una receptora incompatible.
4. Coordinación revisa destino y hora de llegada y pulsa **Confirmar destino y avisar**. El servidor vuelve a comprobar las condiciones y descuenta el cupo del día de llegada (hora colombiana). La receptora encuentra el alimento, cantidad y hora estimada en **Recepción → Avisos de llegada**. Es un aviso dentro de la app; no usa correo, SMS ni notificaciones push. El destino queda visible en el detalle del rescate y se utiliza al confirmar la entrega.

## Comercio: poner el punto de recogida

| Escenario | Resultado esperado |
| --- | --- |
| Abrir **Marcar punto en mapa**, mover, acercar y confirmar | El centro confirmado se guarda en el borrador y se publica al confirmar la donación. |
| Cambiar, quitar o cancelar | Cambiar sustituye el punto al confirmar; quitar deja solo la dirección; cancelar conserva el punto anterior. |
| Centrar en mi ubicación | Con permiso, centra el mapa una vez. Si se deniega o falla, permite moverlo o introducir coordenadas conocidas. |
| Teselas incompletas o sin conexión | Informa del problema y permite reintentar. También se puede volver y publicar solo la dirección. |

## Voluntario: abrir la ruta y desplegar el mapa

| Escenario | Resultado esperado |
| --- | --- |
| Donación con pin confirmado | **Mapa y ruta → Mostrar mapa** abre el mapa en el punto. **Abrir ruta en Maps** envía esas coordenadas. |
| Solo dirección | **Buscar dirección en Maps** y **Abrir ruta en Maps** usan la dirección escrita. Verifica la coincidencia antes de salir. No se inventa un pin. |
| Elegir transporte | **Elegir en Maps**, **A pie**, **En bicicleta** o **En vehículo**. Maps decide el origen y calcula la ruta. |
| Donación de demostración | **Ver mapa de ejemplo** muestra Cali sin pin de comercio ni ruta real. |
| Sin conexión o mapa que no carga | Muestra el error y ofrece reintentar; los datos escritos siguen visibles. Si falta dirección y pin, pide confirmar el punto con coordinación. |

Las pruebas de mapa cubren generación de enlaces, selección de transporte, vista ilustrativa, carga y reintentos. Son pruebas automatizadas; no equivalen a verificar navegación externa en todos los teléfonos.

Verificación de 2.1.0: 15 casos de recepción/asignación, 56 del núcleo y 28 del mapa en WordPress aislado, además de 46 pruebas de formularios y mapas en JavaScript. En el sitio publicado se verificó que las 10 ofertas vencidas aparecen en **Vencidas** y ninguna en **Disponibles**. Para repetir los casos de asignación, carga `tests/receivers-validation.php` dentro de un WordPress de prueba recién instalado, con el plugin activo y `RA_RECEIVERS_TEST=true`. El archivo crea datos ficticios en esa instancia.
