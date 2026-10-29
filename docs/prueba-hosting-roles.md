# Prueba del flujo completo con tres perfiles en el hosting

Fecha: 27 de octubre de 2026. Horarios de Colombia (UTC-5).

Sitio: https://lightgreen-echidna-927363.hostingersite.com/

Resultado registrado: aprobado. Donación: **#7**. Prueba secuencial con cuentas separadas; no representa traslado real de alimentos ni una carrera concurrente.

| Usuario | Perfil | Acción observada |
|---|---|---|
| comercio_e2e | Comercio donante | Publicó a las 10:37 y consultó su donación. |
| voluntario_e2e | Voluntario | Reservó a las 10:41; quedó identificado como transportador. |
| coordinacion_e2e | Coordinación | Confirmó la entrega ficticia a las 10:42. |

## Resultados

- La publicación obtuvo ID 7 y estado Disponible.
- El comercio no mostró botones de reserva ni cierre.
- El voluntario no mostró Donar alimentos. Su reserva pasó a Reservada, dejó de aparecer en Explorar y apareció en Mis rescates.
- El voluntario no mostró Registrar entrega; el registro asignó al Voluntario de prueba como transportador.
- Coordinación registró Organización receptora ficticia E2E; recogida 2026-10-27 10:41 y entrega 10:42; responsable Coordinación de prueba E2E.
- El estado Entregada persistió tras recargar y consultar desde nuevas sesiones de los tres perfiles relacionados.
- El historial conserva los actores de publicación, reserva y cierre. El botón de cierre dejó de aparecer.

## Evidencia y límites

Registro sanitizado: `tests/results-hosting-roles.json`. El reporte HTML y la captura de historial se entregan aparte en los outputs de esta tarea. No se incorporan contraseñas, cookies o nonces al repositorio.

Los controles visibles se comprobaron durante el recorrido. Esta ejecución no atribuye nuevas pruebas negativas de permisos por API, simultaneidad con dos voluntarios, ni aprobación humana del PR.
