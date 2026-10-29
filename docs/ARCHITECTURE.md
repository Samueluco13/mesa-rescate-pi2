# Arquitectura del MVP

El tema presenta la aplicación. El plugin contiene reglas, REST, permisos, persistencia y administración; puede usarse mediante `[rescate_app]` con otro tema. La aplicación consume únicamente REST autenticado de WordPress. No depende de servicios externos para imágenes, tipografía o datos.

## Persistencia y concurrencia

`{prefix}ra_donations` contiene una fila por donación, identificada por clave primaria. Esa misma fila representa su única reserva y entrega: hay una sola columna de titular y una sola sección de cierre. La operación `UPDATE ... WHERE status='available' AND volunteer_id IS NULL AND deadline > ahora` arbitra la reserva. Un cambio confirmado debe afectar exactamente una fila. No existe una lectura separada seguida de una asignación sin condición.

`{prefix}ra_requests` tiene una restricción única por actor/clave. Guarda la huella de la operación y su respuesta. Un reintento recibe el resultado original; reutilizar la clave para otra operación o datos distintos produce conflicto. La reclamación de clave, la actualización de donación y el evento se confirman juntos en una transacción.

`{prefix}ra_events` registra actor, momento, estado anterior/nuevo y referencia de solicitud. La entrega solo transiciona Reservada → Entregada, una sola vez. No hay cancelación o reapertura implícita.

MySQL/MariaDB requieren InnoDB en las tres tablas. El plugin comprueba el motor antes de escribir; ante un motor incompatible rechaza la operación. SQLite se permite para el entorno oficial de desarrollo Playground.

## Autorización

Comercio: crear y consultar sus donaciones. Voluntario: ofertas vigentes y sus reservas. Coordinación: operación completa, solicitudes asistidas y cierre. Administrador: configuración y acceso operativo completo. No se habilita registro público automático ni se asignan roles por parámetros del navegador.

WordPress valida sesión y nonce REST. Cada ruta aplica además permisos y relación con el registro. Ausencia e ID ajeno producen la misma respuesta de consulta. El acceso REST y el HTML de la app evitan caché de información de sesión.

## Conectividad

Los formularios conservan borrador y clave estable en sessionStorage. Una respuesta perdida mantiene la operación pendiente; no se presenta como éxito. El usuario reintenta la solicitud original. El servidor sigue siendo la autoridad para fechas, estados y asignación. No hay cola automática de operaciones sin conexión.

## Registro asistido

La coordinación conserva nombre de comercio, autor y canal (llamada o SMS). Un comercio sin cuenta puede tener donor_id=0 y nombre de origen persistido; asociarlo más adelante a una cuenta requerirá un procedimiento adicional. No se recibe SMS automáticamente.

## Operación y respaldo

Antes de instalar o actualizar, exportar base de datos y respaldar wp-content con las herramientas del hosting. El respaldo debe incluir tablas ra_*, usuarios y opciones ra_*. Conservarlo fuera del repositorio. Ensayar restauración en un entorno aislado, verificar recuentos e historial y repetir una prueba de exclusividad antes de operar. Desactivar el plugin conserva datos. No se incluye borrado automático de donaciones.
