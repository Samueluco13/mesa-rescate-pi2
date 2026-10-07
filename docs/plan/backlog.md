# Backlog del producto

Estado: actividades creadas, pendientes de ejecución y validación de DoR.

[Tablero](https://uao-team-plsjfvw8.atlassian.net/jira/software/projects/SCRUM/boards/1) · [Backlog](https://uao-team-plsjfvw8.atlassian.net/jira/software/projects/SCRUM/boards/1/backlog)

Dos épicas organizan el trabajo: [base del producto](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-5) y [núcleo del rescate](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-6). Las cinco historias están en SCRUM-7 a SCRUM-11.

## Priorización

P0: decisiones y habilitadores. P1: registro y disponibilidad. P2: reserva, entrega, consulta y validación. El orden de dependencia prevalece sobre el número de una actividad. El PO valida prioridad y capacidad antes de comprometer un sprint. Las prioridades P0/P1/P2 están documentadas en las descripciones.

## Base del producto

### [SCRUM-12](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-12) P01 | Validar alcance y trazabilidad de requisitos

Responsable: Jhonathan (PO). Prioridad P0. Estimación: una sesión de 2 h.
Objetivo: establecer el alcance funcional y su trazabilidad.
Criterio de aceptación: los cinco comportamientos de docs/spec/comportamientos.md identifican HU, RF y criterios verificables; se concilian los identificadores CMP/CA con SCRUM-7 a SCRUM-11 y se registran las funciones fuera del núcleo.
Dependencia: especificación funcional disponible para revisión. DoD: aprobación del PO y revisión de trazabilidad por QA, con registro de decisiones y cambios.

### [SCRUM-13](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-13) P02 | Definir arquitectura, integraciones y riesgos técnicos

Responsable: Diego (Developer). Prioridad P0. Estimación: una sesión de 2 h.
Objetivo: versionar docs/plan/plan-tecnico.md con decisiones implementables.
Criterio de aceptación: documentar alternativa seleccionada y justificación, componentes y diagrama, entidades/atributos/estados, integraciones y manejo de fallos, entorno y despliegue. Precisar herramientas y mecanismo de reserva exclusiva. Vincular los riesgos de costo, conectividad, adopción y dependencia de proveedor con mitigaciones y actividades del backlog. Distinguir WhatsApp de canales compatibles con teléfonos básicos y definir registro asistido HU-13.
Dependencia: evaluación de alternativas y especificación funcional. DoD: revisión de PO y DevOps y PR aprobado.

### [SCRUM-14](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-14) P03 | Validar entorno reproducible y accesos del equipo

Responsable: Roiman (DevOps). Prioridad P0. Estimación: una sesión de 2 h.
Objetivo: disponer de un entorno reproducible y accesible para los roles autorizados.
Criterio de aceptación: otro integrante abre el entorno y ejecuta el ejemplo mínimo; README contiene URL e instrucciones verificadas; repositorio y Jira permiten el acceso previsto para desarrollo y revisión sin exponer secretos. Adjuntar evidencia y registrar bloqueos.
Dependencia: plataforma definida en el plan técnico. DoD: reproducción independiente y controles de acceso comprobados.

### [SCRUM-15](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-15) P04 | Auditar especificación y criterios de calidad

Responsable: Samuel Sepúlveda (QA). Prioridad P0. Estimación: una sesión de 2 h.
Objetivo: detectar inconsistencias antes de comprometer implementación.
Criterio de aceptación: auditar trazabilidad HU/RF/CA, escenarios negativos y concurrencia, atomicidad del backlog, dependencias, DoR/DoD y enlaces. Verificar que las decisiones de arquitectura incluyen criterios ponderados, justificación y tratamiento de riesgos. Registrar hallazgos con severidad, responsable y condición de cierre.
Dependencias: especificación, plan técnico y backlog disponibles. DoD: reporte revisado con PO y defectos de documentación resueltos o priorizados.

### [SCRUM-16](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-16) P05 | Revisar identidad y legibilidad de la documentación

Responsable: Sergio (UX). Prioridad P0. Estimación: una sesión de 1 h.
Objetivo: asegurar identidad consistente y documentación legible.
Criterio de aceptación: portada con identidad aprobada, nombre del producto y equipo, integrantes y roles; diagramas legibles, tablas sin cortes y enlaces funcionales. Comprobar vocabulario consistente de estados y claridad del registro asistido.
Dependencias: identidad del producto y documentación consolidada. DoD: revisión visual registrada y ajustes verificados.

### [SCRUM-17](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-17) P06 | Consolidar documentación y enlaces del proyecto

Responsable: Samuel Moreno (Developer). Prioridad P0. Estimación: una sesión de 2 h.
Objetivo: mantener una entrada única a la documentación del proyecto.
Criterio de aceptación: README enlaza Jira, acuerdos de trabajo, especificación, plan técnico y entorno verificado. Consolidar una versión legible y trazable de la documentación con enlaces funcionales y control de versión.
Dependencias: P01 a P05. DoD: revisión de PO y QA y publicación de la versión aprobada. El estado debe reflejar evidencia verificable y pendientes reales.

## Implementación del núcleo

### [SCRUM-18](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-18) MVP-01 | Diseñar el formulario mínimo de donación

Responsable: sergio.talero. Prioridad P1: construir núcleo en orden de dependencias. Estimación: una sesión de 1,5 h.
Comportamiento: CMP-01. Criterio de aceptación: CA-01.1. Trazabilidad: HU-01 / RF-01. Historia en Jira: SCRUM-7.
Resultado verificable: Prototipo móvil con tipo, cantidad, recogida y hora límite; recorrido entendible validado por PO.
Dependencias: P01; criterios aprobados. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-19](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-19) MVP-02 | Guardar una donación válida con identificador único

Responsable: samuel.moreno_man. Prioridad P1: construir núcleo en orden de dependencias. Estimación: una sesión de 3 h.
Comportamiento: CMP-01. Criterio de aceptación: CA-01.1. Trazabilidad: HU-01 / RF-01. Historia en Jira: SCRUM-7.
Resultado verificable: Desde el formulario se persiste una donación Disponible con ID único y datos completos; otro integrante recupera el mismo registro.
Dependencias: MVP-01; plan y entorno aprobados. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-20](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-20) MVP-03 | Validar campos y conservar datos ante errores

Responsable: diego_a.hernandez. Prioridad P1: construir núcleo en orden de dependencias. Estimación: una sesión de 2 h.
Comportamiento: CMP-01. Criterio de aceptación: CA-01.2. Trazabilidad: HU-01 / RF-01. Historia en Jira: SCRUM-7.
Resultado verificable: Cantidad no positiva, límite vencido o campos requeridos vacíos muestran error específico, no guardan y conservan entradas válidas.
Dependencias: MVP-02. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-21](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-21) MVP-04 | Habilitar registro asistido con autor y canal

Responsable: samuel.moreno_man. Prioridad P1: construir núcleo en orden de dependencias. Estimación: una sesión de 2 h.
Comportamiento: CMP-01. Criterio de aceptación: CA-01.3. Trazabilidad: HU-13 / RF-02. Historia en Jira: SCRUM-7.
Resultado verificable: Coordinador registra a nombre de comercio dejando autor, canal y marca asistida; voluntario sin permiso no puede usar la función.
Dependencias: MVP-02; permisos de coordinador acordados. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-22](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-22) MVP-05 | Listar donaciones disponibles y vigentes

Responsable: diego_a.hernandez. Prioridad P1: construir núcleo en orden de dependencias. Estimación: una sesión de 3 h.
Comportamiento: CMP-02. Criterio de aceptación: CA-02.1. Trazabilidad: HU-03 / RF-03, RF-05. Historia en Jira: SCRUM-8.
Resultado verificable: Lista muestra tipo, cantidad, recogida y límite; excluye vencidas o reservadas según reglas aprobadas.
Dependencias: MVP-02. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-23](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-23) MVP-06 | Diseñar estados de lista vacía y error de conexión

Responsable: sergio.talero. Prioridad P2: construir núcleo en orden de dependencias. Estimación: una sesión de 1,5 h.
Comportamiento: CMP-02. Criterio de aceptación: CA-02.2. Trazabilidad: HU-03 / RNF-01, RNF-02. Historia en Jira: SCRUM-8.
Resultado verificable: Vista sin donaciones se distingue de error de conexión y ofrece reintentar; no presenta datos antiguos como confirmación actual.
Dependencias: MVP-05. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-24](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-24) MVP-07 | Implementar reserva exclusiva frente a concurrencia

Responsable: samuel.moreno_man. Prioridad P2: construir núcleo en orden de dependencias. Estimación: una sesión de 3 h.
Comportamiento: CMP-03. Criterio de aceptación: CA-03.1. Trazabilidad: HU-04 / RF-04. Historia en Jira: SCRUM-9.
Resultado verificable: Dos voluntarios reservan simultáneamente; solo uno obtiene confirmación y el otro recibe mensaje no disponible. No basta ocultar un botón.
Dependencias: MVP-05; exclusión atómica demostrable elegida en plan. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-25](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-25) MVP-08 | Probar reserva simultánea con dos usuarios

Responsable: SAMUEL SEPULVEDA CASTANO. Prioridad P2: construir núcleo en orden de dependencias. Estimación: una sesión de 2 h.
Comportamiento: CMP-03. Criterio de aceptación: CA-03.1. Trazabilidad: HU-04 / RF-04. Historia en Jira: SCRUM-9.
Resultado verificable: Caso reproducible con dos sesiones, registro único de ganador y captura del aviso al perdedor; adjuntar evidencia y defecto si falla.
Dependencias: MVP-07. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-26](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-26) MVP-09 | Evitar duplicar reservas al reintentar

Responsable: roiman.urrego. Prioridad P2: construir núcleo en orden de dependencias. Estimación: una sesión de 2 h.
Comportamiento: CMP-03. Criterio de aceptación: CA-03.2. Trazabilidad: HU-04 / RNF-01, RNF-09. Historia en Jira: SCRUM-9.
Resultado verificable: Repetir la misma solicitud tras respuesta interrumpida mantiene una única reserva y el mismo titular; dejar prueba reproducible y configuración documentada.
Dependencias: MVP-07; apoyo del desarrollador para revisión. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-27](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-27) MVP-10 | Guardar entrega con receptor, hora y responsable

Responsable: diego_a.hernandez. Prioridad P2: construir núcleo en orden de dependencias. Estimación: una sesión de 3 h.
Comportamiento: CMP-04. Criterio de aceptación: CA-04.1. Trazabilidad: HU-08 / RF-09. Historia en Jira: SCRUM-10.
Resultado verificable: Cierre autorizado de reserva guarda organización receptora, fecha/hora y responsable, conserva origen y cantidad y cambia a Entregada.
Dependencias: MVP-07; modelo de trazabilidad aprobado. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-28](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-28) MVP-11 | Probar entrega inválida y repetida

Responsable: SAMUEL SEPULVEDA CASTANO. Prioridad P2: construir núcleo en orden de dependencias. Estimación: una sesión de 2 h.
Comportamiento: CMP-04. Criterio de aceptación: CA-04.2. Trazabilidad: HU-08 / RF-09, RNF-09. Historia en Jira: SCRUM-10.
Resultado verificable: Verificar rechazo de datos incompletos, actor sin permiso y reenvío sin crear segunda entrega; conservar evidencia.
Dependencias: MVP-10. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-29](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-29) MVP-12 | Mostrar estado vigente por identificador

Responsable: samuel.moreno_man. Prioridad P2: construir núcleo en orden de dependencias. Estimación: una sesión de 2 h.
Comportamiento: CMP-05. Criterio de aceptación: CA-05.1. Trazabilidad: HU-10 / RF-12. Historia en Jira: SCRUM-11.
Resultado verificable: Consulta refleja Disponible, Reservada y Entregada conforme al registro persistido; presenta responsable solo a rol permitido.
Dependencias: MVP-02, MVP-07, MVP-10. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-30](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-30) MVP-13 | Verificar privacidad y donación inexistente

Responsable: roiman.urrego. Prioridad P2: construir núcleo en orden de dependencias. Estimación: una sesión de 2 h.
Comportamiento: CMP-05. Criterio de aceptación: CA-05.2. Trazabilidad: HU-10 / RNF-06. Historia en Jira: SCRUM-11.
Resultado verificable: Ejecutar comprobaciones de acceso con dos roles y un ID inexistente; no filtrar datos privados y documentar respuestas claras.
Dependencias: MVP-12; roles aprobados. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

### [SCRUM-31](https://uao-team-plsjfvw8.atlassian.net/browse/SCRUM-31) MVP-14 | Validar consulta de estados con el PO

Responsable: jhonathan.chicaiza. Prioridad P2: construir núcleo en orden de dependencias. Estimación: una sesión de 1 h.
Comportamiento: CMP-05. Criterio de aceptación: CA-05.1. Trazabilidad: HU-10 / RF-12. Historia en Jira: SCRUM-11.
Resultado verificable: Recorrer la consulta de una donación de prueba en los tres estados y registrar aceptación o cambios del PO con evidencia; no cerrar si diverge del estado real.
Dependencias: MVP-12; revisión QA disponible. DoR: criterio y solución revisados por PO/QA; dependencias terminadas; mantener fuera del sprint comprometido hasta cumplirlo. DoD: revisión por otra persona, evidencia de prueba, PR cuando corresponda y actualización de documentación; cerrar únicamente con evidencia de cumplimiento.

## Flujo de trabajo

El tablero cuenta con Por hacer, En curso, En revisión y Finalizado. Las nuevas actividades están en el backlog, fuera de un sprint comprometido hasta verificar DoR y capacidad. La creación de una actividad no acredita implementación ni aprobación. La relación con la historia, el comportamiento, el criterio, el esfuerzo y las dependencias figuran en la descripción; los responsables están asignados en Jira.

## Pendientes de planificación

Validar el reparto con cada responsable, conciliar los criterios con la especificación aprobada, convertir dependencias relevantes en vínculos de bloqueo y comprometer solo actividades listas. Los registros genéricos preexistentes deben ser revisados por el PO antes de reutilizarlos o retirarlos.
